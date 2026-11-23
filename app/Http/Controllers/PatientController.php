<?php

namespace App\Http\Controllers;

use App\Models\Queue;
use App\Services\QueueingTheoryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

class PatientController extends Controller
{
    protected QueueingTheoryService $queueService;

    public function __construct(QueueingTheoryService $queueService)
    {
        $this->queueService = $queueService;
    }

    public function switchLang($locale)
    {
        if (in_array($locale, ['en', 'zh'])) {
            session(['locale' => $locale]);
        }
        return redirect()->back();
    }

    public function index()
    {
        app(\App\Http\Controllers\StaffController::class)->runAutoCallIfDue();
        App::setLocale(session('locale', 'en'));
        
        $now = Carbon::now();
        $startTime = Carbon::createFromTime(8, 0, 0);
        $endTime = Carbon::createFromTime(20, 0, 0);
        
        // Allow operating between 08:00 - 20:00, or when staff enables off-hours test override
        $isOperatingHours = $now->between($startTime, $endTime) || Cache::get('test_override_open', false);

        $currentlyServing = Queue::where('status', 'serving')->first();
        $waitingCount = Queue::where('status', 'waiting')->count();
        $mu = $this->queueService->getServiceRate();
        $generalWaitTime = (int) round($waitingCount / $mu);

        return view('patient.index', compact('currentlyServing', 'waitingCount', 'generalWaitTime', 'isOperatingHours', 'now'));
    }

    public function generateToken(Request $request)
    {
        $now = Carbon::now();
        $startTime = Carbon::createFromTime(8, 0, 0);
        $endTime = Carbon::createFromTime(20, 0, 0);
        
        $isOperatingHours = $now->between($startTime, $endTime) || Cache::get('test_override_open', false);
        
        if (!$isOperatingHours) {
            return redirect()->route('patient.index')->with('error', 'Clinic is currently closed. Queue token generation is only available between 08:00 and 20:00.');
        }

        // Validate optional consultation note
        $request->validate([
            'consultation_note' => 'nullable|string|max:500',
        ]);

        // Clean up old records from previous days
        Queue::whereDate('created_at', '<', Carbon::today())->delete();

        $todayCount = Queue::whereDate('created_at', Carbon::today())->count();
        $tokenNumber = 'A-' . str_pad($todayCount + 1, 3, '0', STR_PAD_LEFT);

        $queue = Queue::create([
            'token_number' => $tokenNumber,
            'status' => 'waiting',
            'priority' => 0,
            'consultation_note' => $request->input('consultation_note'),
            'arrived_at' => $now,
        ]);

        return redirect()->route('patient.status', $queue->id);
    }

    public function checkStatus($id)
    {
        app(\App\Http\Controllers\StaffController::class)->runAutoCallIfDue();
        App::setLocale(session('locale', 'en'));
        $queue = Queue::findOrFail($id);
        $currentlyServing = Queue::where('status', 'serving')->first();

        $patientsAhead = Queue::where('status', 'waiting')
            ->where(function ($query) use ($queue) {
                $query->where('priority', '>', $queue->priority)
                      ->orWhere(function ($q) use ($queue) {
                          $q->where('priority', '=', $queue->priority)
                            ->where('id', '<', $queue->id);
                      });
            })
            ->count();

        $estimatedWaitTime = $this->queueService->calculateEstimatedWaitTime($queue);

        return view('patient.status', compact('queue', 'currentlyServing', 'patientsAhead', 'estimatedWaitTime'));
    }
}
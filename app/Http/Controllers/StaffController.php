<?php

namespace App\Http\Controllers;

use App\Models\Queue;
use App\Services\QueueingTheoryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class StaffController extends Controller
{
    protected QueueingTheoryService $queueService;

    public function __construct(QueueingTheoryService $queueService)
    {
        $this->queueService = $queueService;
    }

    public function dashboard(Request $request)
    {
        if (!session('staff_logged_in')) {
            return redirect()->route('staff.login');
        }
        App::setLocale(session('locale', 'en'));

        $currentTime = Carbon::now();
        $startTime = Carbon::createFromTime(8, 0, 0);
        $endTime = Carbon::createFromTime(20, 0, 0);
        
        $isRealOperatingHours = $currentTime->between($startTime, $endTime);
        $testOverrideOpen = session('test_override_open', false);
        $isOperatingHours = $isRealOperatingHours || $testOverrideOpen;

        $waitingQueues = Queue::where('status', 'waiting')
            ->orderBy('priority', 'desc')
            ->orderBy('id', 'asc')
            ->get();

        $currentlyServing = Queue::where('status', 'serving')->first();

        $completedCount = Queue::where('status', 'completed')
            ->whereDate('completed_at', Carbon::today())
            ->count();

        $lambda = $this->queueService->getArrivalRate();
        $mu = $this->queueService->getServiceRate();
        $utilization = $mu > 0 ? min(($lambda / $mu) * 100, 100) : 0;

        return view('staff.dashboard', compact(
            'waitingQueues',
            'currentlyServing',
            'completedCount',
            'lambda',
            'mu',
            'utilization',
            'currentTime',
            'isOperatingHours',
            'isRealOperatingHours',
            'testOverrideOpen'
        ));
    }

    // Toggle off-hours test override (only active outside 08:00 - 20:00)
    public function toggleTestOverride()
    {
        if (!session('staff_logged_in')) {
            return redirect()->route('staff.login');
        }
        $currentTime = Carbon::now();
        $startTime = Carbon::createFromTime(8, 0, 0);
        $endTime = Carbon::createFromTime(20, 0, 0);

        if (!$currentTime->between($startTime, $endTime)) {
            $currentState = session('test_override_open', false);
            session(['test_override_open' => !$currentState]);
        }

        return redirect()->route('staff.dashboard')->with('success', 'Off-hours test mode updated.');
    }

    public function callNext()
    {
        if (!session('staff_logged_in')) {
            return redirect()->route('staff.login');
        }
        $now = Carbon::now();

        // 1. Mark currently serving patient(s) as completed and record completed_at timestamp
        $servingPatients = Queue::where('status', 'serving')->get();
        foreach ($servingPatients as $serving) {
            $serving->status = 'completed';
            $serving->completed_at = $now;
            $serving->save();
        }

        // 2. Fetch the next patient using queuing theory algorithm
        $nextPatient = $this->queueService->getNextPatient();

        // 3. Mark next patient as serving and record served_at timestamp
        if ($nextPatient) {
            $nextPatient->status = 'serving';
            $nextPatient->served_at = $now;
            $nextPatient->save();
        }

        return redirect()->route('staff.dashboard');
    }

    public function upgradeToPriority($id)
    {
        if (!session('staff_logged_in')) {
            return redirect()->route('staff.login');
        }
        $queue = Queue::findOrFail($id);

        if ($queue->priority == 0) {
            // Keep the original numeric suffix and only change prefix from A- to P- (e.g., A-004 -> P-004)
            $queue->token_number = preg_replace('/^A-/', 'P-', $queue->token_number);
            $queue->priority = 1;
            $queue->save();
        }

        return redirect()->route('staff.dashboard');
    }

    public function showLogin()
    {
        if (session('staff_logged_in')) {
            return redirect()->route('staff.dashboard');
        }
        return view('staff.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($request->input('username') === 'admin' && $request->input('password') === 'asdf') {
            session(['staff_logged_in' => true]);
            return redirect()->route('staff.dashboard');
        }

        return back()->with('error', 'Invalid username or password.');
    }

    public function logout()
    {
        session()->forget('staff_logged_in');
        return redirect()->route('staff.login');
    }
}
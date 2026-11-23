<?php

namespace App\Services;

use App\Models\Queue;
use Carbon\Carbon;

class QueueingTheoryService
{
    /**
     * Calculate dynamic moving-average Service Rate (mu) in patients per minute
     * using served_at and completed_at timestamps.
     */
    public function getServiceRate(): float
    {
        $completedQueues = Queue::where('status', 'completed')
            ->whereNotNull('served_at')
            ->whereNotNull('completed_at')
            ->orderBy('completed_at', 'desc')
            ->take(10)
            ->get();

        if ($completedQueues->isEmpty()) {
            return 0.1; // Default baseline: 10 mins per consultation
        }

        $totalServiceMinutes = 0;
        foreach ($completedQueues as $q) {
            $duration = Carbon::parse($q->served_at)->diffInMinutes(Carbon::parse($q->completed_at));
            $totalServiceMinutes += max($duration, 1);
        }

        $averageServiceTime = $totalServiceMinutes / $completedQueues->count();
        return round(1 / $averageServiceTime, 4);
    }

    /**
     * Calculate dynamic moving-average Arrival Rate (lambda) in patients per minute.
     */
    public function getArrivalRate(): float
    {
        $recentArrivals = Queue::whereDate('arrived_at', Carbon::today())
            ->orderBy('arrived_at', 'asc')
            ->get();

        if ($recentArrivals->count() < 2) {
            return 0.08;
        }

        $firstArrival = Carbon::parse($recentArrivals->first()->arrived_at);
        $lastArrival = Carbon::parse($recentArrivals->last()->arrived_at);
        $totalMinutes = max($firstArrival->diffInMinutes($lastArrival), 1);

        return round($recentArrivals->count() / $totalMinutes, 4);
    }

    /**
     * Advanced Scheduling Algorithm:
     * - Aging Mechanism (> 60 mins wait for Standard A)
     * - Adaptive Priority Interleaving: Priority-first when queue <= 6, Strict 3:1 when queue > 6
     */
    public function getNextPatient(): ?Queue
    {
        $now = Carbon::now();

        // 1. Aging Mechanism: Standard (A) patient waiting > 60 minutes gets immediate priority
        $agedPatient = Queue::where('status', 'waiting')
            ->where('priority', 0)
            ->where('arrived_at', '<=', $now->copy()->subMinutes(60))
            ->orderBy('id', 'asc')
            ->first();

        if ($agedPatient) {
            return $agedPatient;
        }

        $priorityWaiting = Queue::where('status', 'waiting')
            ->where('priority', 1)
            ->orderBy('id', 'asc')
            ->first();

        $nextStandard = Queue::where('status', 'waiting')
            ->where('priority', 0)
            ->orderBy('id', 'asc')
            ->first();

        $totalWaiting = Queue::where('status', 'waiting')->count();

        // 2. Small Queue Mode (<= 6 waiting): P tokens get immediate priority so P-005 & P-006 aren't blocked by 3 A tokens
        if ($totalWaiting <= 6 && $priorityWaiting) {
            return $priorityWaiting;
        }

        // 3. Busy Queue Mode (> 6 waiting): Strict 3:1 Interleaving Enforcement
        $recentCompleted = Queue::where('status', 'completed')
            ->whereDate('completed_at', Carbon::today())
            ->orderBy('completed_at', 'desc')
            ->take(3)
            ->get();

        $standardCountInRecent = 0;
        foreach ($recentCompleted as $q) {
            if ($q->priority === 0) {
                $standardCountInRecent++;
            } else {
                break;
            }
        }

        // If fewer than 3 standard patients were served since the last priority patient, serve standard
        if ($standardCountInRecent < 3 && $nextStandard) {
            return $nextStandard;
        }

        if ($priorityWaiting) {
            return $priorityWaiting;
        }

        // 4. Fallback to FIFO
        return Queue::where('status', 'waiting')
            ->orderBy('id', 'asc')
            ->first();
    }

    /**
     * Calculate Estimated Waiting Time (Wq) in minutes for a specific queue position.
     */
    public function calculateEstimatedWaitTime(Queue $targetQueue): int
    {
        if ($targetQueue->status !== 'waiting') {
            return 0;
        }

        $patientsAhead = Queue::where('status', 'waiting')
            ->where(function ($query) use ($targetQueue) {
                $query->where('priority', '>', $targetQueue->priority)
                      ->orWhere(function ($q) use ($targetQueue) {
                          $q->where('priority', '=', $targetQueue->priority)
                            ->where('id', '<', $targetQueue->id);
                      });
            })
            ->count();

        $currentlyServing = Queue::where('status', 'serving')->exists() ? 0.5 : 0;
        $mu = $this->getServiceRate();

        $estimatedMinutes = ($patientsAhead + $currentlyServing) / $mu;
        return (int) round($estimatedMinutes);
    }
}
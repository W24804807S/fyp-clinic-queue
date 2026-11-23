<?php

namespace Database\Seeders;

use App\Models\Queue;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class QueueSeeder extends Seeder
{
    /**
     * Run the database seeds to populate realistic patient arrival flows 
     * for testing and TAM/SUS scenario-based simulations.
     */
    public function run(): void
    {
        $now = Carbon::now();

        // 1. Seed 5 completed consultations to establish historical Service Rate (μ)
        for ($i = 1; $i <= 5; $i++) {
            $arrived = $now->copy()->subMinutes(90 - ($i * 10));
            $served = $arrived->copy()->addMinutes(5);
            $completed = $served->copy()->addMinutes(8); // ~8 mins per consultation

            Queue::create([
                'token_number' => 'A-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'status' => 'completed',
                'priority' => 0,
                'arrived_at' => $arrived,
                'served_at' => $served,
                'completed_at' => $completed,
            ]);
        }

        // 2. Seed 1 patient currently in consultation (Serving status)
        Queue::create([
            'token_number' => 'A-006',
            'status' => 'serving',
            'priority' => 0,
            'arrived_at' => $now->copy()->subMinutes(15),
            'served_at' => $now->copy()->subMinutes(4),
        ]);

        // 3. Seed 4 patients currently waiting in the queue (including priority cases)
        $waitingTokens = [
            ['token' => 'P-007', 'priority' => 1, 'mins_ago' => 12], // Priority patient (Elderly/Urgent)
            ['token' => 'A-008', 'priority' => 0, 'mins_ago' => 10], // Standard patient
            ['token' => 'A-009', 'priority' => 0, 'mins_ago' => 6],  // Standard patient
            ['token' => 'A-010', 'priority' => 0, 'mins_ago' => 2],  // Standard patient
        ];

        foreach ($waitingTokens as $wt) {
            Queue::create([
                'token_number' => $wt['token'],
                'status' => 'waiting',
                'priority' => $wt['priority'],
                'arrived_at' => $now->copy()->subMinutes($wt['mins_ago']),
            ]);
        }
    }
}
<!DOCTYPE html>
<html lang="{{ session('locale', 'en') }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Auto-refresh page every 30 seconds to detect new arrivals and update wait times -->
    <meta http-equiv="refresh" content="30">
    <title>Staff Dashboard - Clinic Queue</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen p-6">

    <div class="max-w-6xl mx-auto space-y-6">

        <!-- Top Header & Navigation -->
        <div class="flex items-center justify-between bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
            <div>
                <div class="flex items-center gap-2">
                    <span class="bg-teal-100 text-teal-800 text-xs font-semibold px-3 py-1 rounded-full">Staff Portal</span>
                    <span class="text-xs font-semibold px-3 py-1 rounded-full {{ $isOperatingHours ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                        {{ $isOperatingHours ? '● Clinic Open' : '● Clinic Closed' }} ({{ $currentTime->format('H:i') }})
                    </span>
                    <span class="text-[11px] bg-slate-100 text-slate-500 font-medium px-2.5 py-1 rounded-full flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-teal-500 animate-pulse"></span>
                        Auto-sync: <span id="countdown">30</span>s
                    </span>
                </div>
                <h1 class="text-2xl font-bold mt-2 text-slate-900">Queue Management Dashboard</h1>
                <p class="text-xs text-slate-500 mt-1">Real-time queue monitoring, patient call actions, and queuing theory parameters (v1.2)</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('patient.index') }}" target="_blank" class="text-xs bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold px-4 py-2 rounded-xl border border-slate-300 transition">
                    Go to Patient Portal ↗
                </a>
                <form action="{{ route('staff.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-xs bg-rose-50 hover:bg-rose-100 text-rose-700 font-semibold px-4 py-2 rounded-xl border border-rose-200 transition">
                        Logout
                    </button>
                </form>
            </div>
        </div>

        <!-- Off-Hours Testing Control Panel (Active outside 08:00 - 20:00) -->
        @if(!$isRealOperatingHours)
            <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                <div>
                    <h3 class="text-xs font-bold text-amber-900 uppercase tracking-wider">🧪 Off-Hours Testing Control</h3>
                    <p class="text-xs text-amber-700 mt-1">
                        System time ({{ $currentTime->format('H:i:s') }}) is outside standard operating hours (08:00 - 20:00). Toggle override to test token generation.
                    </p>
                </div>
                <form action="{{ route('staff.override.toggle') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-xs font-semibold px-4 py-2.5 rounded-xl shadow transition {{ $testOverrideOpen ? 'bg-rose-600 hover:bg-rose-700 text-white' : 'bg-emerald-600 hover:bg-emerald-700 text-white' }}">
                        {{ $testOverrideOpen ? '🔒 Disable Test Override (Close Clinic)' : '🔓 Force Open Clinic (For Testing)' }}
                    </button>
                </form>
            </div>
        @endif

        <!-- Success Flash Message -->
        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-xl text-xs font-medium">
                {{ session('success') }}
            </div>
        @endif

        <!-- Queuing Theory Metrics Grid -->
        @php
            if ($utilization >= 85) {
                $utilColor = 'text-rose-600';
                $utilBadge = 'bg-rose-100 text-rose-700';
                $utilStatus = 'Overloaded';
            } elseif ($utilization >= 70) {
                $utilColor = 'text-amber-600';
                $utilBadge = 'bg-amber-100 text-amber-700';
                $utilStatus = 'Busy';
            } else {
                $utilColor = 'text-teal-600';
                $utilBadge = 'bg-teal-50 text-teal-700';
                $utilStatus = 'Optimal';
            }
        @endphp
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
                <p class="text-xs font-semibold text-slate-400 uppercase">Arrival Rate (&lambda;)</p>
                <p class="text-xl font-bold text-slate-800 mt-1">{{ number_format($lambda, 2) }} <span class="text-xs font-normal text-slate-500">p/min</span></p>
            </div>
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
                <p class="text-xs font-semibold text-slate-400 uppercase">Service Rate (&mu;)</p>
                <p class="text-xl font-bold text-slate-800 mt-1">{{ number_format($mu, 2) }} <span class="text-xs font-normal text-slate-500">p/min</span></p>
            </div>
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold text-slate-400 uppercase">System Utilization (&rho;)</p>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $utilBadge }}">{{ $utilStatus }}</span>
                </div>
                <p class="text-xl font-bold {{ $utilColor }} mt-1">{{ number_format($utilization, 1) }}%</p>
            </div>
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
                <p class="text-xs font-semibold text-slate-400 uppercase">Completed Today</p>
                <p class="text-xl font-bold text-slate-800 mt-1">{{ $completedCount }} <span class="text-xs font-normal text-slate-500">patients</span></p>
            </div>
        </div>

        <!-- Main Action & Serving Area -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <!-- Currently Serving Box -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex flex-col justify-between">
                <div>
                    <span class="bg-teal-50 text-teal-700 text-xs font-semibold px-2.5 py-1 rounded-md">Active Counter</span>
                    <h2 class="text-lg font-bold text-slate-900 mt-3">Currently Serving</h2>
                    <div class="my-6 text-center bg-slate-50 py-8 rounded-xl border border-slate-100">
                        <span class="text-4xl font-extrabold text-teal-600">
                            {{ $currentlyServing ? $currentlyServing->token_number : '---' }}
                        </span>
                        @if($currentlyServing)
                            <p class="text-xs text-slate-400 mt-2">Token ID: #{{ $currentlyServing->id }} | Type: {{ $currentlyServing->priority > 0 ? 'Priority (P)' : 'Standard (A)' }}</p>
                        @endif
                        @if($currentlyServing && $currentlyServing->consultation_note)
                            <div class="mt-3 mx-3 p-3 bg-teal-50/80 border border-teal-200 rounded-lg text-left">
                                <p class="text-[10px] font-bold text-teal-800 uppercase">Consultation Note:</p>
                                <p class="text-xs text-slate-700 mt-0.5 break-words">{{ $currentlyServing->consultation_note }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Call Next Button -->
                <form action="{{ route('staff.callNext') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full bg-teal-600 hover:bg-teal-700 text-white font-bold py-3.5 px-4 rounded-xl shadow transition duration-150">
                        Call Next Patient ➔
                    </button>
                </form>
            </div>

            <!-- Waiting Queue Table -->
            <div class="md:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-slate-900">Waiting Queue List</h2>
                    <span class="text-xs bg-slate-100 text-slate-600 px-3 py-1 rounded-full font-semibold">Total Waiting: {{ $waitingQueues->count() }}</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 text-xs font-semibold text-slate-400 uppercase">
                                <th class="py-3 px-3">Token</th>
                                <th class="py-3 px-3">Type</th>
                                <th class="py-3 px-3">Consultation Note</th>
                                <th class="py-3 px-3">Arrived Time</th>
                                <th class="py-3 px-3">Wait Time</th>
                                <th class="py-3 px-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 text-sm">
                            @forelse($waitingQueues as $queue)
                                @php
                                    $arrivedAt = $queue->arrived_at ? \Carbon\Carbon::parse($queue->arrived_at) : $queue->created_at;
                                    $waitMinutes = (int) $arrivedAt->diffInMinutes($currentTime);
                                    $isAged = $waitMinutes >= 60;
                                @endphp
                                <tr class="hover:bg-slate-50/50 {{ $isAged ? 'bg-rose-50/30' : '' }}">
                                    <td class="py-3.5 px-3 font-bold text-slate-800">{{ $queue->token_number }}</td>
                                    <td class="py-3.5 px-3">
                                        @if($queue->priority > 0)
                                            <span class="bg-rose-100 text-rose-700 text-[11px] font-semibold px-2.5 py-0.5 rounded-full">Priority (P)</span>
                                        @else
                                            <span class="bg-slate-100 text-slate-600 text-[11px] font-semibold px-2.5 py-0.5 rounded-full">Standard (A)</span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-3 text-xs text-slate-600 max-w-[180px] truncate" title="{{ $queue->consultation_note }}">
                                        {{ $queue->consultation_note ?: '—' }}
                                    </td>
                                    <td class="py-3.5 px-3 text-slate-500 text-xs">
                                        {{ $arrivedAt->format('H:i:s') }}
                                    </td>
                                    <td class="py-3.5 px-3 text-xs">
                                        @if($isAged)
                                            <span class="inline-flex items-center gap-1 bg-rose-100 text-rose-700 font-bold px-2.5 py-0.5 rounded-full">
                                                ⚠️ {{ $waitMinutes }} mins
                                            </span>
                                        @else
                                            <span class="text-slate-600 font-medium">{{ $waitMinutes }} mins</span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-3 text-right">
                                        @if($queue->priority == 0)
                                            <form action="{{ route('staff.upgrade', $queue->id) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="text-xs bg-amber-50 hover:bg-amber-100 text-amber-700 font-semibold px-3 py-1 rounded-lg border border-amber-200 transition">
                                                    ↑ Upgrade to P
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-xs text-slate-400 font-medium">Upgraded</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-8 text-slate-400 text-xs">No patients currently waiting in the queue.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>

    <script>
        // Visual countdown timer for 30-second auto-refresh
        let secondsLeft = 30;
        const countdownEl = document.getElementById('countdown');
        setInterval(() => {
            secondsLeft--;
            if (secondsLeft >= 0 && countdownEl) {
                countdownEl.textContent = secondsLeft;
            }
        }, 1000);
    </script>

</body>
</html>
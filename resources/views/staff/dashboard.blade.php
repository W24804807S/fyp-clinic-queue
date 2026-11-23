<!DOCTYPE html>
<html lang="{{ session('locale', 'en') }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="30">
    <title>Staff Dashboard - Clinic Queue</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen p-6">
    <div class="max-w-6xl mx-auto space-y-6">

        <!-- Header -->
        <div class="bg-white rounded-2xl shadow-sm p-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2 mb-2">
                    <span class="bg-teal-50 text-teal-700 text-xs font-semibold px-3 py-1 rounded-full">Staff Portal</span>
                    @if($isOperatingHours)
                        <span class="bg-emerald-50 text-emerald-700 text-xs font-semibold px-3 py-1 rounded-full">
                            • Clinic Open ({{ $currentTime->format('H:i') }})
                        </span>
                    @else
                        <span class="bg-rose-50 text-rose-700 text-xs font-semibold px-3 py-1 rounded-full">
                            • Clinic Closed ({{ $currentTime->format('H:i') }})
                        </span>
                    @endif
                    <span class="bg-slate-100 text-slate-500 text-xs font-medium px-3 py-1 rounded-full">
                        • Auto-sync: <span id="countdown">30</span> s
                    </span>
                </div>
                <h1 class="text-2xl font-bold text-slate-900">Queue Management Dashboard</h1>
                <p class="text-sm text-slate-500 mt-1">Real-time queue monitoring, patient call actions, and queueing theory parameters (v1.3)</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ url('/') }}" target="_blank"
                   class="px-4 py-2 rounded-xl border border-slate-200 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
                    Go to Patient Portal ↗
                </a>
                <form action="{{ route('staff.logout') }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="px-4 py-2 rounded-xl bg-rose-50 text-rose-600 text-sm font-semibold hover:bg-rose-100 transition">
                        Logout
                    </button>
                </form>
            </div>
        </div>

        <!-- Off-Hours Testing Control -->
        @if(!$isRealOperatingHours)
            <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <p class="text-xs font-bold text-amber-800 uppercase tracking-wider">🧪 OFF-HOURS TESTING CONTROL</p>
                    <p class="text-sm text-amber-700 mt-1">
                        System time ({{ $currentTime->format('H:i:s') }}) is outside standard operating hours (08:00 - 20:00). Toggle override to test token generation.
                    </p>
                </div>
                <form action="{{ route('staff.toggleOverride') }}" method="POST">
                    @csrf
                    @if($testOverrideOpen)
                        <button type="submit"
                                class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-sm font-bold shadow-sm transition whitespace-nowrap">
                            🔒 Disable Test Override (Close Clinic)
                        </button>
                    @else
                        <button type="submit"
                                class="px-5 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-sm font-bold shadow-sm transition whitespace-nowrap">
                            🔓 Force Open Clinic (For Testing)
                        </button>
                    @endif
                </form>
            </div>
        @endif

        <!-- Queueing Theory Metrics -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl shadow-sm p-5">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">ARRIVAL RATE (Λ)</p>
                <p class="text-2xl font-bold text-slate-900 mt-2">
                    {{ number_format($lambda, 2) }} <span class="text-sm font-normal text-slate-500">p/min</span>
                </p>
            </div>

            <div class="bg-white rounded-2xl shadow-sm p-5">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">SERVICE RATE (Μ)</p>
                <p class="text-2xl font-bold text-slate-900 mt-2">
                    {{ number_format($mu, 2) }} <span class="text-sm font-normal text-slate-500">p/min</span>
                </p>
            </div>

            <div class="bg-white rounded-2xl shadow-sm p-5">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">SYSTEM UTILIZATION (Ρ)</p>
                    @if($utilization >= 90)
                        <span class="bg-rose-50 text-rose-600 text-[11px] font-bold px-2 py-0.5 rounded-full">Overloaded</span>
                    @elseif($utilization >= 70)
                        <span class="bg-amber-50 text-amber-600 text-[11px] font-bold px-2 py-0.5 rounded-full">Busy</span>
                    @else
                        <span class="bg-emerald-50 text-emerald-600 text-[11px] font-bold px-2 py-0.5 rounded-full">Normal</span>
                    @endif
                </div>
                <p class="text-2xl font-bold mt-2 {{ $utilization >= 90 ? 'text-rose-600' : 'text-slate-900' }}">
                    {{ number_format($utilization, 1) }}%
                </p>
            </div>

            <div class="bg-white rounded-2xl shadow-sm p-5">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">COMPLETED TODAY</p>
                <p class="text-2xl font-bold text-slate-900 mt-2">
                    {{ $completedCount }} <span class="text-sm font-normal text-slate-500">patients</span>
                </p>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Active Counter -->
            <div class="bg-white rounded-2xl shadow-sm p-6 flex flex-col justify-between">
                <div>
                    <span class="bg-teal-50 text-teal-700 text-xs font-semibold px-3 py-1 rounded-full">Active Counter</span>
                    <h2 class="text-lg font-bold text-slate-900 mt-3">Currently Serving</h2>

                    <div class="mt-4 bg-slate-50 border border-slate-100 rounded-2xl p-6 text-center">
                        @if($currentlyServing)
                            <p class="text-4xl font-extrabold text-teal-600 tracking-tight">
                                {{ $currentlyServing->token_number }}
                            </p>
                            <p class="text-xs text-slate-400 mt-2">
                                Token ID: #{{ $currentlyServing->id }} | Type: {{ $currentlyServing->priority ? 'Priority (P)' : 'Standard (A)' }}
                            </p>

                            @if(!empty($currentlyServing->consultation_note))
                                <div class="mt-4 bg-white border border-teal-100 rounded-xl p-3 text-left">
                                    <p class="text-[10px] font-bold text-teal-600 uppercase tracking-wider">CONSULTATION NOTE:</p>
                                    <p class="text-sm text-slate-700 mt-1 break-words">{{ $currentlyServing->consultation_note }}</p>
                                </div>
                            @endif
                        @else
                            <p class="text-4xl font-extrabold text-teal-600 tracking-widest py-4">---</p>
                        @endif
                    </div>
                </div>

                <div>
                    <form id="callNextForm" action="{{ route('staff.callNext') }}" method="POST" class="mt-6">
                        @csrf
                        <button type="submit"
                                class="w-full bg-teal-600 hover:bg-teal-700 text-white font-bold py-3 px-4 rounded-xl shadow transition">
                            Call Next Patient →
                        </button>
                    </form>

                    <!-- 24/7 Server Auto-Call Control -->
                    @php
                        $autoCallOn = \Illuminate\Support\Facades\Cache::get('auto_call_enabled', false);
                        $nextAt = \Illuminate\Support\Facades\Cache::get('auto_call_next_at');
                        $remain = $nextAt ? max(0, $nextAt - now()->timestamp) : null;
                    @endphp
                    <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between">
                        <div class="text-left">
                            <p class="text-xs font-bold text-slate-700">🤖 24/7 Auto-Call Simulation</p>
                            <p class="text-[11px] text-slate-400">
                                @if(!$autoCallOn)
                                    Status: OFF
                                @elseif($remain !== null)
                                    Next call in ~{{ $remain }}s (Random 180-600s)
                                @else
                                    Standby (Waiting for patients...)
                                @endif
                            </p>
                        </div>
                        <form action="{{ route('staff.toggleAutoCall') }}" method="POST">
                            @csrf
                            <button type="submit"
                                    class="text-xs font-bold px-3.5 py-1.5 rounded-lg border transition {{ $autoCallOn ? 'bg-emerald-50 text-emerald-700 border-emerald-300 hover:bg-emerald-100' : 'bg-slate-100 text-slate-600 border-slate-300 hover:bg-slate-200' }}">
                                {{ $autoCallOn ? 'Turn OFF' : 'Turn ON' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Waiting Queue List -->
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-slate-900">Waiting Queue List</h2>
                    <span class="bg-slate-100 text-slate-600 text-xs font-semibold px-3 py-1 rounded-full">
                        Total Waiting: {{ $waitingQueues->count() }}
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
                                <th class="py-3 px-2">TOKEN</th>
                                <th class="py-3 px-2">TYPE</th>
                                <th class="py-3 px-2">CONSULTATION NOTE</th>
                                <th class="py-3 px-2">ARRIVED TIME</th>
                                <th class="py-3 px-2">WAIT TIME</th>
                                <th class="py-3 px-2 text-right">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            @forelse($waitingQueues as $queue)
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="py-3.5 px-2 font-bold text-slate-900">{{ $queue->token_number }}</td>
                                    <td class="py-3.5 px-2">
                                        @if($queue->priority)
                                            <span class="bg-amber-100 text-amber-800 text-xs font-bold px-2.5 py-1 rounded-full">Priority (P)</span>
                                        @else
                                            <span class="bg-slate-100 text-slate-600 text-xs font-medium px-2.5 py-1 rounded-full">Standard (A)</span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-2 text-slate-600 max-w-xs truncate">
                                        {{ $queue->consultation_note ?: '—' }}
                                    </td>
                                    <td class="py-3.5 px-2 text-slate-500">
                                        {{ $queue->created_at->format('H:i:s') }}
                                    </td>
                                    <td class="py-3.5 px-2 text-slate-500">
                                        {{ (int) $queue->created_at->diffInMinutes(now()) }} mins
                                    </td>
                                    <td class="py-3.5 px-2 text-right">
                                        @if(!$queue->priority)
                                            <form action="{{ route('staff.upgrade', $queue->id) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit"
                                                        class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100 transition">
                                                    ↑ Upgrade to P
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-xs text-slate-400">Prioritized</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-10 text-center text-slate-400 text-sm">
                                        No patients currently waiting in the queue.
                                    </td>
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
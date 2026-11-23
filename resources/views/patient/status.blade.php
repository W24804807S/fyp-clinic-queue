<!DOCTYPE html>
<html lang="{{ session('locale', 'en') }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="10">
    <title>Token {{ $queue->token_number }} - Status</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Short bounce animation that runs 3 times (~3 seconds) and stops */
        @keyframes shortBounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-6px); }
        }
        .animate-bounce-short {
            animation: shortBounce 0.8s ease-in-out 3;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex items-center justify-center p-4">

    @php
        $lang = include resource_path('lang/' . session('locale', 'en') . '.php');
        $suffix = preg_replace('/^[AP]-/', '', $queue->token_number);
        $originalAToken = 'A-' . $suffix;
        $upgradeText = str_replace(
            [':old', ':new'],
            [$originalAToken, $queue->token_number],
            $lang['upgraded_desc']
        );
    @endphp

    <div class="max-w-md w-full bg-white rounded-2xl shadow-lg p-8 border border-slate-100 text-center relative">
        
        <!-- Language Switcher -->
        <div class="absolute top-4 right-4 flex space-x-1 text-xs font-semibold">
            <a href="{{ route('lang.switch', 'en') }}" class="px-2 py-1 rounded {{ session('locale', 'en') === 'en' ? 'bg-teal-600 text-white' : 'bg-slate-100 text-slate-600' }}">EN</a>
            <a href="{{ route('lang.switch', 'zh') }}" class="px-2 py-1 rounded {{ session('locale', 'zh') === 'zh' ? 'bg-teal-600 text-white' : 'bg-slate-100 text-slate-600' }}">繁中</a>
        </div>

        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mt-2">{{ $lang['your_token'] }}</p>
        <h1 class="text-5xl font-black {{ $queue->priority > 0 ? 'text-rose-600' : 'text-teal-600' }} my-2">
            {{ $queue->token_number }}
        </h1>

        <!-- Status Badge -->
        @if($queue->status === 'waiting')
            <span class="inline-block bg-amber-100 text-amber-800 text-xs font-bold px-3 py-1 rounded-full">
                {{ $lang['waiting_in_queue'] }}
            </span>
        @elseif($queue->status === 'serving')
            <span class="inline-block bg-emerald-100 text-emerald-800 text-sm font-bold px-4 py-1.5 rounded-full animate-pulse">
                {{ $lang['turn_ready'] }}
            </span>
        @elseif($queue->status === 'completed')
            <span class="inline-block bg-slate-200 text-slate-700 text-xs font-bold px-3 py-1 rounded-full">
                {{ $lang['completed'] }}
            </span>
        @else
            <span class="inline-block bg-rose-100 text-rose-700 text-xs font-bold px-3 py-1 rounded-full">
                {{ $lang['cancelled'] }}
            </span>
        @endif

        <!-- Priority Upgrade Notice (Shown when upgraded from A-0xx to P-0xx) -->
        @if($queue->priority > 0)
            <div class="mt-4 bg-rose-50 border border-rose-200 text-rose-900 p-3.5 rounded-xl text-xs text-left">
                <p class="font-bold text-rose-700 mb-0.5">{{ $lang['upgraded_title'] }}</p>
                <p class="text-rose-800 leading-relaxed">{{ $upgradeText }}</p>
            </div>
        @endif

        <!-- Visualization Metrics & Warning if Patients Ahead < 3 -->
        @if($queue->status === 'waiting')
            @if($patientsAhead < 3)
                <div class="mt-4 bg-amber-50 border border-amber-300 text-amber-900 p-3 rounded-xl text-xs font-medium animate-bounce-short">
                    {{ $lang['patients_ahead_warning'] }}
                </div>
            @endif

            <div class="mt-5 bg-slate-50 rounded-xl p-5 border border-slate-200 space-y-4">
                <div>
                    <p class="text-xs text-slate-500 uppercase">{{ $lang['est_wait'] }}</p>
                    <p class="text-4xl font-extrabold text-slate-900 mt-1">{{ $estimatedWaitTime }} <span class="text-base font-medium text-slate-500">{{ $lang['mins'] }}</span></p>
                </div>

                @php $progress = max(10, 100 - ($patientsAhead * 15)); @endphp
                <div class="w-full bg-slate-200 rounded-full h-3 overflow-hidden">
                    <div class="bg-teal-500 h-3 rounded-full transition-all duration-500" style="width: {{ $progress }}%"></div>
                </div>

                <div class="flex justify-between text-xs text-slate-600 pt-2 border-t border-slate-200">
                    <span>{{ $lang['patients_ahead'] }}: <strong>{{ $patientsAhead }}</strong></span>
                    <span>{{ $lang['now_serving'] }}: <strong>{{ $currentlyServing ? $currentlyServing->token_number : 'None' }}</strong></span>
                </div>
            </div>
        @endif

        <p class="text-xs text-slate-400 mt-6">
            {{ $lang['checked_in_at'] }} {{ $queue->arrived_at->format('H:i') }} • {{ $lang['auto_refresh'] }}
        </p>

        <div class="mt-4">
            <a href="{{ route('patient.index') }}" class="text-xs text-teal-600 hover:underline">{{ $lang['back_to_checkin'] }}</a>
        </div>
    </div>

</body>
</html>
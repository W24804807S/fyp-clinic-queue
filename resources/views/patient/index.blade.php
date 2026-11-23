<!DOCTYPE html>
<html lang="{{ session('locale', 'en') }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinic Queue Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full bg-white rounded-2xl shadow-lg p-8 border border-slate-100 relative">
        <!-- Language Switcher -->
        <div class="absolute top-4 right-4 flex space-x-1 text-xs font-semibold">
            <a href="{{ route('lang.switch', 'en') }}" class="px-2 py-1 rounded {{ session('locale', 'en') === 'en' ? 'bg-teal-600 text-white' : 'bg-slate-100 text-slate-600' }}">EN</a>
            <a href="{{ route('lang.switch', 'zh') }}" class="px-2 py-1 rounded {{ session('locale', 'zh') === 'zh' ? 'bg-teal-600 text-white' : 'bg-slate-100 text-slate-600' }}">繁中</a>
        </div>

        @php $lang = include resource_path('lang/' . session('locale', 'en') . '.php'); @endphp

        <!-- Header -->
        <div class="text-center mb-6 mt-4">
            <span class="bg-teal-100 text-teal-800 text-xs font-semibold px-3 py-1 rounded-full">Smart Primary Care</span>
            <h1 class="text-2xl font-bold mt-3 text-slate-900">{{ $lang['title'] }}</h1>
            <p class="text-sm text-slate-500 mt-1">{{ $lang['subtitle'] }}</p>
            <p class="text-xs font-medium text-teal-700 mt-2">🕒 {{ $lang['operating_hours'] }}</p>
        </div>

        <!-- Flash Error Message if out of hours -->
        @if(session('error'))
            <div class="bg-rose-50 border border-rose-200 text-rose-700 p-3 rounded-lg mb-4 text-xs">
                {{ session('error') }}
            </div>
        @endif

        <!-- Live Clinic Status Summary -->
        <div class="grid grid-cols-2 gap-4 bg-slate-50 p-4 rounded-xl mb-6 border border-slate-200">
            <div class="text-center">
                <p class="text-xs text-slate-500 uppercase">{{ $lang['now_serving'] }}</p>
                <p class="text-2xl font-extrabold text-teal-600 mt-1">
                    {{ $currentlyServing ? $currentlyServing->token_number : '---' }}
                </p>
            </div>
            <div class="text-center border-l border-slate-200">
                <p class="text-xs text-slate-500 uppercase">{{ $lang['est_wait'] }}</p>
                <p class="text-2xl font-extrabold text-slate-800 mt-1">
                    ~{{ $generalWaitTime }} <span class="text-sm font-normal">{{ $lang['mins'] }}</span>
                </p>
            </div>
        </div>

        <!-- Operating Hours Check & Token Form -->
        @if($isOperatingHours)
            <div class="bg-blue-50 p-3.5 rounded-lg border border-blue-200 mb-5 space-y-1">
                <p class="text-xs font-semibold text-blue-900 flex items-center gap-1.5">
                    <span>ℹ️</span> {{ $lang['notice_title'] }}
                </p>
                <p class="text-[11px] text-blue-800 leading-relaxed">
                    {{ $lang['notice_desc'] }}
                </p>
            </div>

            <form action="{{ route('token.generate') }}" method="POST">
                @csrf
                <!-- Optional Pre-Consultation Note Input -->
                <div class="mb-4 text-left">
                    <label for="consultation_note" class="block text-xs font-bold text-slate-700 mb-1.5">
                        {{ $lang['note_label'] }}
                    </label>
                    <textarea id="consultation_note" name="consultation_note" rows="2" maxlength="500"
                        placeholder="{{ $lang['note_placeholder'] }}"
                        class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-xs text-slate-800 focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none transition"></textarea>
                    <p class="text-[11px] text-slate-400 mt-1">🔒 {{ $lang['note_privacy'] }}</p>
                </div>
                <button type="submit" class="w-full bg-teal-600 hover:bg-teal-700 text-white font-bold py-3.5 px-4 rounded-xl shadow transition duration-150">
                    {{ $lang['get_token'] }}
                </button>
            </form>
        @else
            <div class="bg-amber-50 border border-amber-300 text-amber-900 p-4 rounded-xl text-center space-y-2 mb-5">
                <p class="text-sm font-bold">🌙 {{ $lang['closed_title'] }}</p>
                <p class="text-xs text-amber-800 leading-relaxed">{{ $lang['closed_desc'] }}</p>
            </div>
            <button disabled class="w-full bg-slate-200 text-slate-400 font-bold py-3.5 px-4 rounded-xl cursor-not-allowed">
                {{ $lang['get_token'] }} (Closed)
            </button>
        @endif

        <!-- Staff Portal Link -->
        <div class="mt-6 text-center border-t pt-4">
            <a href="{{ route('staff.dashboard') }}" class="text-xs text-slate-400 hover:text-teal-600 underline">
                {{ $lang['staff_login'] }}
            </a>
        </div>
    </div>

</body>
</html>
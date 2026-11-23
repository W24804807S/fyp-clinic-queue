<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login - Clinic Queue</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-sm bg-white rounded-2xl shadow-lg border border-slate-200 p-8">
        <div class="text-center mb-6">
            <span class="bg-teal-100 text-teal-800 text-xs font-semibold px-3 py-1 rounded-full">Staff Portal</span>
            <h1 class="text-2xl font-bold text-slate-900 mt-3">Clinic Staff Login</h1>
            <p class="text-xs text-slate-500 mt-1">Sign in to access the queue management dashboard</p>
        </div>

        @if(session('error'))
            <div class="mb-4 bg-rose-50 border border-rose-200 text-rose-700 text-xs font-medium p-3 rounded-xl text-center">
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('staff.login.submit') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="username" class="block text-xs font-bold text-slate-700 mb-1.5">Username</label>
                <input type="text" id="username" name="username" required autofocus
                    placeholder="Enter username"
                    class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none transition">
            </div>

            <div>
                <label for="password" class="block text-xs font-bold text-slate-700 mb-1.5">Password</label>
                <input type="password" id="password" name="password" required
                    placeholder="Enter password"
                    class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none transition">
            </div>

            <button type="submit" class="w-full bg-teal-600 hover:bg-teal-700 text-white font-bold py-3 px-4 rounded-xl shadow transition duration-150 text-sm">
                Sign In →
            </button>
        </form>

        <div class="mt-6 pt-4 border-t border-slate-100 text-center">
            <a href="{{ route('patient.index') }}" class="text-xs text-teal-600 hover:underline font-medium">
                ← Back to Patient Portal
            </a>
        </div>
    </div>

</body>
</html>
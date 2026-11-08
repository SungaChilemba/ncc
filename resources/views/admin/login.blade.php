<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title>Admin Portal — {{ config('app.name') }}</title>
    <script>
        // Set the theme before first paint to avoid a flash of the wrong mode.
        (function () {
            try {
                var stored = window.localStorage.getItem('ncc-theme');
                var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (stored === 'dark' || (stored !== 'light' && prefersDark)) {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) { /* storage unavailable — fall back to default (light) */ }
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 dark:bg-slate-950 text-slate-900 dark:text-slate-100 flex flex-col antialiased transition-colors duration-200">

    <!-- Top bar: identity left, dark mode toggle right -->
    <header class="flex items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-2 text-brand-800 dark:text-brand-300">
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
            </svg>
            <span class="text-lg font-bold tracking-tight">NCC Portal</span>
        </div>

        <button type="button" id="theme-toggle" aria-label="Toggle dark mode" aria-pressed="false"
                class="inline-flex items-center justify-center rounded-full border border-slate-300 bg-white p-2 text-slate-600 shadow-sm transition hover:bg-slate-50 hover:text-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-brand-300 dark:focus:ring-offset-slate-950">
            <!-- Moon: shown in light mode (switch to dark) -->
            <svg class="h-5 w-5 dark:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/>
            </svg>
            <!-- Sun: shown in dark mode (switch to light) -->
            <svg class="hidden h-5 w-5 dark:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/>
            </svg>
        </button>
    </header>

    <!-- Centered login card -->
    <main class="flex flex-1 items-center justify-center px-4 pb-16">
        <div class="max-w-md w-full">
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 shadow-md dark:shadow-black/40 rounded-lg p-8">
                <p class="text-center text-[11px] font-semibold uppercase tracking-[0.2em] text-brand-700 dark:text-brand-400">National Children&rsquo;s Commission</p>
                <h1 class="text-2xl font-bold text-brand-700 dark:text-brand-400 mt-2 mb-6 text-center">Admin Portal</h1>

                @if ($errors->any())
                    <div class="bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-900/60 text-red-700 dark:text-red-300 px-4 py-3 rounded mb-4">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.login') }}">
                    @csrf

                    <div class="mb-4">
                        <label for="email" class="block text-sm font-semibold text-gray-700 dark:text-slate-300 mb-1">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                               class="w-full border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-brand-500 dark:focus:ring-brand-400">
                    </div>

                    <div class="mb-6">
                        <label for="password" class="block text-sm font-semibold text-gray-700 dark:text-slate-300 mb-1">Password</label>
                        <input id="password" type="password" name="password" required
                               class="w-full border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-brand-500 dark:focus:ring-brand-400">
                    </div>

                    <button type="submit" class="w-full bg-brand-600 hover:bg-brand-700 dark:bg-brand-600 dark:hover:bg-brand-500 text-white px-4 py-2 rounded-md font-semibold transition focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900">
                        Login
                    </button>
                </form>
            </div>
        </div>
    </main>

    <script>
        (function () {
            var toggle = document.getElementById('theme-toggle');
            if (!toggle) { return; }

            var sync = function () {
                toggle.setAttribute('aria-pressed', document.documentElement.classList.contains('dark') ? 'true' : 'false');
            };

            toggle.addEventListener('click', function () {
                var isDark = document.documentElement.classList.toggle('dark');
                try { window.localStorage.setItem('ncc-theme', isDark ? 'dark' : 'light'); } catch (e) { /* storage unavailable */ }
                sync();
            });

            sync();
        })();
    </script>
</body>
</html>

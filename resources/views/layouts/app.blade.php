<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') · srv.mgr</title>
    <script>
        (function () {
            var fallback = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            var theme = fallback;
            try {
                var stored = localStorage.getItem('theme');
                theme = stored === 'dark' || stored === 'light' ? stored : fallback;
            } catch (e) {
                /* storage unavailable (private mode) — keep fallback */
            }
            document.documentElement.dataset.theme = theme;
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">
    <header class="border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
        <div class="mx-auto flex h-14 max-w-7xl items-center gap-6 px-4">
            <a href="{{ route('dashboard') }}" class="text-lg font-extrabold tracking-tight">srv.mgr</a>

            @auth
                <nav class="flex items-center gap-1 text-sm">
                    <a href="{{ route('dashboard') }}" class="rounded-md px-3 py-1.5 hover:bg-slate-100 dark:hover:bg-slate-800">Dashboard</a>
                    <a href="{{ route('servers.create') }}" class="ml-2 rounded-md bg-blue-600 px-3 py-1.5 font-medium text-white hover:bg-blue-500">+ New server</a>
                </nav>
            @endauth

            <div class="ml-auto flex items-center gap-3 text-sm">
                <button type="button" id="theme-toggle" aria-label="Toggle color theme"
                    class="rounded-md px-2 py-1.5 hover:bg-slate-100 dark:hover:bg-slate-800">
                    <span class="hidden dark:inline">☀</span><span class="dark:hidden">☾</span>
                </button>
                @auth
                    <span class="text-slate-500 dark:text-slate-400">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="rounded-md px-3 py-1.5 hover:bg-slate-100 dark:hover:bg-slate-800">Log out</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="rounded-md px-3 py-1.5 hover:bg-slate-100 dark:hover:bg-slate-800">Log in</a>
                    <a href="{{ route('register') }}" class="rounded-md bg-blue-600 px-3 py-1.5 text-white hover:bg-blue-500">Register</a>
                @endauth
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-8">
        @yield('content')
    </main>

    <script>
        document.getElementById('theme-toggle').addEventListener('click', function () {
            var next = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
            document.documentElement.dataset.theme = next;
            localStorage.setItem('theme', next);
        });
    </script>
    @stack('scripts')
</body>
</html>

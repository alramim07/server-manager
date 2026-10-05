@extends('layouts.app')

@section('title', 'Log in')

@section('content')
    <div class="mx-auto max-w-md">
        <h1 class="mb-6 text-xl font-bold">Log in</h1>

        @if (config('app.demo_enabled'))
            <div class="mb-4 rounded-lg border border-dashed border-blue-300 bg-blue-50 p-4 text-sm text-blue-900 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-200">
                <p class="font-semibold">Demo access</p>
                <p class="mt-1">Email <span class="font-mono">{{ config('app.demo_email') }}</span> / Password <span class="font-mono">{{ config('app.demo_password') }}</span></p>
                <p class="mt-1 text-xs opacity-80">Pre-filled below — just press Log in. Demo data resets after inactivity.</p>
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="space-y-4 rounded-lg border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
            @csrf

            <div>
                <label for="email" class="mb-1 block text-sm font-medium">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', config('app.demo_enabled') ? config('app.demo_email') : '') }}" required autofocus
                    class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800">
                @error('email')<p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm font-medium">Password</label>
                <input id="password" name="password" type="password" value="{{ config('app.demo_enabled') ? config('app.demo_password') : '' }}" required
                    class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800">
                @error('password')<p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
            </div>

            <button class="w-full rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500">Log in</button>

            @if (config('app.registration_enabled'))
                <p class="text-sm text-slate-500 dark:text-slate-400">No account yet? <a href="{{ route('register') }}" class="text-blue-600 hover:underline dark:text-blue-400">Register</a></p>
            @endif
        </form>
    </div>
@endsection

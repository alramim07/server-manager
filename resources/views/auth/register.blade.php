@extends('layouts.app')

@section('title', 'Register')

@section('content')
    <div class="mx-auto max-w-md">
        <h1 class="mb-6 text-xl font-bold">Create your account</h1>

        <form method="POST" action="{{ route('register.store') }}" class="space-y-4 rounded-lg border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
            @csrf

            <div>
                <label for="name" class="mb-1 block text-sm font-medium">Name</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus
                    class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800">
                @error('name')<p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="email" class="mb-1 block text-sm font-medium">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required
                    class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800">
                @error('email')<p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm font-medium">Password</label>
                <input id="password" name="password" type="password" required
                    class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800">
                @error('password')<p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password_confirmation" class="mb-1 block text-sm font-medium">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required
                    class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800">
            </div>

            <button class="w-full rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500">Register</button>

            <p class="text-sm text-slate-500 dark:text-slate-400">Already have an account? <a href="{{ route('login') }}" class="text-blue-600 hover:underline dark:text-blue-400">Log in</a></p>
        </form>
    </div>
@endsection

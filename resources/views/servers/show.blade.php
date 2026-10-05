@extends('layouts.app')

@section('title', $server->name)

@section('content')
    @if (session('status'))
        <div class="mb-4 rounded-md bg-emerald-100 px-4 py-2 text-sm text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    <div class="mx-auto max-w-3xl">
        <div class="mb-4 flex items-center justify-between gap-4">
            <a href="{{ route('dashboard') }}" class="text-sm text-blue-600 hover:underline dark:text-blue-400">&larr; Back to servers</a>

            <div class="flex gap-2">
                @can('update', $server)
                    <a href="{{ route('servers.edit', $server) }}"
                        class="rounded-md border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">Edit</a>
                @endcan
                @can('delete', $server)
                    <form method="POST" action="{{ route('servers.destroy', $server) }}"
                        data-server-name="{{ $server->name }}" data-server-ip="{{ $server->ip_address }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="rounded-md border border-rose-300 px-4 py-2 text-sm text-rose-600 hover:bg-rose-50 dark:border-rose-800 dark:text-rose-400 dark:hover:bg-rose-950">Delete</button>
                    </form>
                @endcan
            </div>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-start justify-between gap-4">
                <h1 class="text-xl font-bold">{{ $server->name }}</h1>
                <span class="whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-medium {{ $server->status->badgeClass() }}">
                    &#9679; {{ $server->status->label() }}
                </span>
            </div>

            <dl class="mt-6 grid gap-x-6 gap-y-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">IP address</dt>
                    <dd class="mt-1 font-mono text-sm">{{ $server->ip_address }}</dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">Operating system</dt>
                    <dd class="mt-1 text-sm">{{ $server->operating_system }}</dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">Provider</dt>
                    <dd class="mt-1 text-sm">{{ $server->provider }}</dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">Deployed apps</dt>
                    <dd class="mt-1 text-sm">{{ $server->deployed_apps_count }}</dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">Renewal date</dt>
                    <dd class="mt-1 text-sm {{ $server->renewalUrgency()->textClass() }}">
                        {{ $server->renewal_date?->format('Y-m-d') ?? '—' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">Owner</dt>
                    <dd class="mt-1 text-sm">{{ $server->owner?->name ?? '—' }}</dd>
                </div>

                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">Notes</dt>
                    <dd class="mt-1 text-sm">{{ $server->notes ?: '—' }}</dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">Created</dt>
                    <dd class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $server->created_at->format('Y-m-d H:i') }}</dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">Updated</dt>
                    <dd class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $server->updated_at->format('Y-m-d H:i') }}</dd>
                </div>
            </dl>
        </div>
    </div>

    @include('servers._delete-dialog')
@endsection

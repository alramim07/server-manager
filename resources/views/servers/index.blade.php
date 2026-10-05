@extends('layouts.app')

@section('title', 'Servers')

@section('content')
    @if (session('status'))
        <div class="mb-4 rounded-md bg-emerald-100 px-4 py-2 text-sm text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    @if ($servers->isEmpty())
        <div class="rounded-lg border border-slate-200 bg-white p-12 text-center dark:border-slate-800 dark:bg-slate-900">
            <p class="text-slate-500 dark:text-slate-400">No servers yet — add your first VPS</p>
            <a href="{{ route('servers.create') }}"
                class="mt-4 inline-block rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500">Add server</a>
        </div>
    @else
        <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-200 text-xs uppercase text-slate-500 dark:border-slate-800 dark:text-slate-400">
                    <tr>
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">IP</th>
                        <th class="px-4 py-3 font-medium">OS</th>
                        <th class="px-4 py-3 font-medium">Provider</th>
                        <th class="px-4 py-3 font-medium">Apps</th>
                        <th class="px-4 py-3 font-medium">Renewal</th>
                        <th class="px-4 py-3 font-medium">Owner</th>
                        <th class="px-4 py-3 font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @foreach ($servers as $server)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <td class="px-4 py-3">
                                <a href="{{ route('servers.show', $server) }}" class="font-medium text-blue-600 hover:underline">{{ $server->name }}</a>
                            </td>
                            <td class="px-4 py-3">
                                <span class="whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-medium {{ $server->status->badgeClass() }}">
                                    &#9679; {{ $server->status->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 font-mono">{{ $server->ip_address }}</td>
                            <td class="px-4 py-3">{{ $server->operating_system }}</td>
                            <td class="px-4 py-3">{{ $server->provider }}</td>
                            <td class="px-4 py-3">{{ $server->deployed_apps_count }}</td>
                            <td class="px-4 py-3 {{ $server->renewalUrgency()->textClass() }}">
                                {{ $server->renewal_date?->format('Y-m-d') ?? '—' }}
                            </td>
                            <td class="px-4 py-3">{{ $server->owner?->name ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2">
                                    @can('update', $server)
                                        <a href="{{ route('servers.edit', $server) }}" class="text-blue-600 hover:underline">Edit</a>
                                    @endcan
                                    @can('delete', $server)
                                        <button type="button"
                                            data-delete-url="{{ route('servers.destroy', $server) }}"
                                            data-server-name="{{ $server->name }}"
                                            class="text-rose-600 hover:underline">Delete</button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @include('servers._delete-dialog')
@endsection

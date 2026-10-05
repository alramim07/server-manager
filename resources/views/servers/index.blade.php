@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @if (session('status'))
        <div class="mb-4 rounded-md bg-emerald-100 px-4 py-2 text-sm text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    @php
        $cards = [
            ['label' => 'Total servers', 'value' => $stats['total'], 'class' => 'text-slate-900 dark:text-slate-100'],
            ['label' => 'Active', 'value' => $stats['active'], 'class' => 'text-emerald-600 dark:text-emerald-400'],
            ['label' => 'Payment required', 'value' => $stats['payment'], 'class' => 'text-rose-600 dark:text-rose-400'],
            ['label' => 'Inactive', 'value' => $stats['inactive'], 'class' => 'text-slate-500 dark:text-slate-400'],
            ['label' => 'Apps deployed', 'value' => $stats['apps'], 'class' => 'text-blue-600 dark:text-blue-400'],
            ['label' => 'Renewals due ≤30d', 'value' => $stats['dueSoon'], 'class' => 'text-amber-600 dark:text-amber-400'],
        ];
        $sortUrl = fn (string $column) => request()->fullUrlWithQuery([
            'sort' => $column,
            'dir' => $filters['sort'] === $column && $filters['dir'] === 'asc' ? 'desc' : 'asc',
        ]);
        $sortMark = fn (string $column) => $filters['sort'] === $column ? ($filters['dir'] === 'asc' ? ' ↑' : ' ↓') : '';
        $hasFilters = $filters['status'] !== ''
            || $filters['provider'] !== ''
            || $filters['q'] !== ''
            || $filters['owner'] === 'mine';
    @endphp

    {{-- Stat cards --}}
    <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
        @foreach ($cards as $card)
            <div class="rounded-lg border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $card['label'] }}</p>
                <p class="mt-1 text-2xl font-bold {{ $card['class'] }}">{{ $card['value'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Filter validation errors --}}
    @if ($errors->any())
        <div class="mb-4 rounded-md bg-amber-100 px-4 py-2 text-sm text-amber-800 dark:bg-amber-900 dark:text-amber-200">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Filter bar --}}
    <form method="GET" action="{{ route('dashboard') }}" class="mb-4 flex flex-wrap items-center gap-2">
        <select name="status"
            class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
            <option value="">All statuses</option>
            @foreach (\App\Enums\ServerStatus::cases() as $statusOption)
                <option value="{{ $statusOption->value }}" @selected($filters['status'] === $statusOption->value)>{{ $statusOption->label() }}</option>
            @endforeach
        </select>

        <select name="provider"
            class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
            <option value="">All providers</option>
            @foreach ($providers as $providerOption)
                <option value="{{ $providerOption }}" @selected($filters['provider'] === $providerOption)>{{ $providerOption }}</option>
            @endforeach
        </select>

        <select name="owner"
            class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
            <option value="all" @selected($filters['owner'] === 'all')">Everyone's servers</option>
            <option value="mine" @selected($filters['owner'] === 'mine')">Mine only</option>
        </select>

        <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Search name, IP, OS…"
            class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">

        @if ($filters['sort'] !== '')
            <input type="hidden" name="sort" value="{{ $filters['sort'] }}">
        @endif
        @if ($filters['dir'] !== '')
            <input type="hidden" name="dir" value="{{ $filters['dir'] }}">
        @endif

        <button type="submit"
            class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200">
            Filter
        </button>
        <a href="{{ route('dashboard') }}"
            class="rounded-md border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">
            Clear
        </a>
    </form>

    @if ($servers->isEmpty())
        <div class="rounded-lg border-2 border-dashed border-slate-300 bg-white p-12 text-center dark:border-slate-700 dark:bg-slate-900">
            @if ($hasFilters)
                <p class="text-slate-500 dark:text-slate-400">No matches for these filters</p>
                <a href="{{ route('dashboard') }}"
                    class="mt-4 inline-block rounded-md border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">
                    Clear filters
                </a>
            @else
                <p class="text-slate-500 dark:text-slate-400">No servers yet — add your first VPS</p>
                <a href="{{ route('servers.create') }}"
                    class="mt-4 inline-block rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500">
                    Add server
                </a>
            @endif
        </div>
    @else
        <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-200 text-xs uppercase text-slate-500 dark:border-slate-800 dark:text-slate-400">
                    <tr>
                        <th class="px-4 py-3 font-medium">
                            <a href="{{ $sortUrl('name') }}" class="hover:underline">Name{{ $sortMark('name') }}</a>
                        </th>
                        <th class="px-4 py-3 font-medium">
                            <a href="{{ $sortUrl('status') }}" class="hover:underline">Status{{ $sortMark('status') }}</a>
                        </th>
                        <th class="px-4 py-3 font-medium">IP</th>
                        <th class="px-4 py-3 font-medium">OS</th>
                        <th class="px-4 py-3 font-medium">Provider</th>
                        <th class="px-4 py-3 font-medium">
                            <a href="{{ $sortUrl('deployed_apps_count') }}" class="hover:underline">Apps{{ $sortMark('deployed_apps_count') }}</a>
                        </th>
                        <th class="px-4 py-3 font-medium">
                            <a href="{{ $sortUrl('renewal_date') }}" class="hover:underline">Renewal{{ $sortMark('renewal_date') }}</a>
                        </th>
                        <th class="px-4 py-3 font-medium">Owner</th>
                        <th class="px-4 py-3 font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @foreach ($servers as $server)
                        @php($warning = $server->billingWarning())
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50{{ $warning ? ' bg-rose-50 dark:bg-rose-950/40' : '' }}"
                            @if ($warning) data-billing-warning="1" @endif>
                            <td class="px-4 py-3">
                                <a href="{{ route('servers.show', $server) }}" class="font-medium text-blue-600 hover:underline">{{ $server->name }}</a>
                            </td>
                            <td class="px-4 py-3">
                                <span class="whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-medium {{ $server->status->badgeClass() }}">
                                    &#9679; {{ $server->status->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs">{{ $server->ip_address }}</td>
                            <td class="px-4 py-3">{{ $server->operating_system }}</td>
                            <td class="px-4 py-3">{{ $server->provider }}</td>
                            <td class="px-4 py-3">{{ $server->deployed_apps_count }}</td>
                            <td class="px-4 py-3 {{ $server->renewalUrgency()->textClass() }}">
                                {{ $server->renewal_date?->format('Y-m-d') ?? '—' }}
                            </td>
                            <td class="px-4 py-3">{{ $server->owner?->name ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2 text-xs">
                                    @can('update', $server)
                                        <form method="POST" action="{{ route('servers.status', $server) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status"
                                                value="{{ $server->status === \App\Enums\ServerStatus::Active ? 'inactive' : 'active' }}">
                                            <button type="submit" class="text-slate-600 hover:underline dark:text-slate-300">
                                                {{ $server->status === \App\Enums\ServerStatus::Active ? 'Set inactive' : 'Set active' }}
                                            </button>
                                        </form>
                                    @endcan
                                    @can('delete', $server)
                                        <form method="POST" action="{{ route('servers.destroy', $server) }}"
                                            data-server-name="{{ $server->name }}" data-server-ip="{{ $server->ip_address }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-rose-600 hover:underline">Delete</button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $servers->links() }}
        </div>
    @endif

    @include('servers._delete-dialog')
@endsection

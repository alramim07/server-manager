<?php

namespace App\Http\Controllers;

use App\Enums\ServerStatus;
use App\Models\Server;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ServerController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $validated = $request->validate([
            'status' => ['nullable', Rule::enum(ServerStatus::class)],
            'provider' => ['nullable', 'string', 'max:80'],
            'owner' => ['nullable', 'string', 'in:mine,all'],
            'q' => ['nullable', 'string', 'max:80'],
            'sort' => ['nullable', Rule::in(['name', 'status', 'renewal_date', 'deployed_apps_count', 'created_at'])],
            'dir' => ['nullable', 'string', 'in:asc,desc'],
        ]);

        $search = $validated['q'] ?? null;

        $servers = Server::query()->with('owner')
            ->when($validated['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($validated['provider'] ?? null, fn ($q, $provider) => $q->where('provider', $provider))
            ->when(($validated['owner'] ?? null) === 'mine', fn ($q) => $q->where('owner_id', $user->id))
            ->when($search, function ($q, $search) {
                $like = '%'.$search.'%';
                $q->where(fn ($w) => $w->where('name', 'like', $like)
                    ->orWhere('ip_address', 'like', $like)
                    ->orWhere('provider', 'like', $like)
                    ->orWhere('operating_system', 'like', $like));
            })
            ->orderBy($validated['sort'] ?? 'created_at', $validated['dir'] ?? 'desc')
            ->paginate(15)
            ->withQueryString();

        $statusCounts = Server::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('servers.index', [
            'servers' => $servers,
            'providers' => $this->providers(),
            'stats' => [
                'total' => (int) $statusCounts->sum(),
                'active' => (int) ($statusCounts[ServerStatus::Active->value] ?? 0),
                'payment' => (int) ($statusCounts[ServerStatus::PaymentRequired->value] ?? 0),
                'inactive' => (int) ($statusCounts[ServerStatus::Inactive->value] ?? 0),
                'apps' => (int) Server::sum('deployed_apps_count'),
                'dueSoon' => Server::whereNotNull('renewal_date')
                    ->whereDate('renewal_date', '<=', now()->addDays(30)->toDateString())->count(),
            ],
            'filters' => [
                'status' => $validated['status'] ?? '',
                'provider' => $validated['provider'] ?? '',
                'owner' => $validated['owner'] ?? 'all',
                'q' => $validated['q'] ?? '',
                'sort' => $validated['sort'] ?? '',
                'dir' => $validated['dir'] ?? '',
            ],
        ]);
    }

    public function updateStatus(Request $request, Server $server): RedirectResponse
    {
        Gate::authorize('update', $server);

        $request->validate(['status' => ['required', Rule::enum(ServerStatus::class)]]);

        $server->update(['status' => ServerStatus::from($request->string('status')->toString())]);

        return redirect()->route('dashboard')->with('status', 'Status updated.');
    }

    public function create(): View
    {
        return view('servers.create', ['providers' => $this->providers()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $server = Server::create([
            ...$this->validateServer($request),
            'owner_id' => $request->user()->id,
        ]);

        return redirect()->route('servers.show', $server)->with('status', 'Server added.');
    }

    public function show(Server $server): View
    {
        Gate::authorize('view', $server);

        return view('servers.show', [
            'server' => $server->load('owner'),
        ]);
    }

    public function edit(Server $server): View
    {
        Gate::authorize('update', $server);

        return view('servers.edit', [
            'server' => $server,
            'providers' => $this->providers(),
        ]);
    }

    public function update(Request $request, Server $server): RedirectResponse
    {
        Gate::authorize('update', $server);

        $server->update($this->validateServer($request, $server));

        return redirect()->route('servers.show', $server)->with('status', 'Server updated.');
    }

    public function destroy(Server $server): RedirectResponse
    {
        Gate::authorize('delete', $server);

        $server->delete();

        return redirect()->route('dashboard')->with('status', 'Server deleted.');
    }

    private function validateServer(Request $request, ?Server $server = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'ip_address' => [
                'required',
                'max:45',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! filter_var($value, FILTER_VALIDATE_IP)) {
                        $fail('The IP address must be a valid IPv4 or IPv6 address.');
                    }
                },
                Rule::unique('servers', 'ip_address')->ignore($server?->id),
            ],
            'operating_system' => ['required', 'string', 'max:80'],
            'provider' => ['required', 'string', 'max:80'],
            'status' => ['required', Rule::enum(ServerStatus::class)],
            'deployed_apps_count' => ['required', 'integer', 'min:0', 'max:100000'],
            'renewal_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function providers(): Collection
    {
        return Server::query()->distinct()->orderBy('provider')->pluck('provider');
    }
}

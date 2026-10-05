<?php

namespace App\Http\Controllers;

use App\Enums\ServerStatus;
use App\Models\DeployedApp;
use App\Models\Server;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ServerController extends Controller
{
    public function index(Request $request): View
    {
        Server::flipExpiredToPaymentRequired();

        $validated = $request->validate([
            'status' => ['nullable', Rule::enum(ServerStatus::class)],
            'provider' => ['nullable', 'string', 'max:80'],
            'q' => ['nullable', 'string', 'max:80'],
            'sort' => ['nullable', Rule::in(['name', 'status', 'renewal_date', 'deployed_apps_count', 'created_at'])],
            'dir' => ['nullable', 'string', 'in:asc,desc'],
        ]);

        $search = $validated['q'] ?? null;

        $servers = Server::query()->with('owner', 'deployedApps')->withCount('deployedApps')
            ->when($validated['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($validated['provider'] ?? null, fn ($q, $provider) => $q->where('provider', $provider))
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
                'apps' => DeployedApp::count(),
                'dueSoon' => Server::whereNotNull('renewal_date')
                    ->whereDate('renewal_date', '<=', now()->addDays(30)->toDateString())->count(),
            ],
            'filters' => [
                'status' => $validated['status'] ?? '',
                'provider' => $validated['provider'] ?? '',
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
        return view('servers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        [$fields, $apps] = $this->validateServer($request);

        $server = Server::create([
            ...$fields,
            'owner_id' => $request->user()->id,
        ]);

        $this->syncDeployedApps($server, $apps);

        return redirect()->route('servers.show', $server)->with('status', 'Server added.');
    }

    public function show(Server $server): View
    {
        Gate::authorize('view', $server);

        Server::flipExpiredToPaymentRequired();
        $server->refresh();

        return view('servers.show', [
            'server' => $server->load('owner', 'deployedApps'),
        ]);
    }

    public function edit(Server $server): View
    {
        Gate::authorize('update', $server);

        return view('servers.edit', [
            'server' => $server,
            'appNames' => $server->deployedApps()->pluck('name')->all(),
        ]);
    }

    public function update(Request $request, Server $server): RedirectResponse
    {
        Gate::authorize('update', $server);

        [$fields, $apps] = $this->validateServer($request, $server);

        $server->update($fields);

        $this->syncDeployedApps($server, $apps);

        return redirect()->route('servers.show', $server)->with('status', 'Server updated.');
    }

    public function destroy(Server $server): RedirectResponse
    {
        Gate::authorize('delete', $server);

        $server->delete();

        return redirect()->route('dashboard')->with('status', 'Server deleted.');
    }

    /**
     * Validate server fields and normalize the deployed-app names.
     *
     * @return array{0: array<string, mixed>, 1: array<int, string>}
     */
    private function validateServer(Request $request, ?Server $server = null): array
    {
        $validated = $request->validate([
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
            'deployed_apps' => ['nullable', 'array'],
            'deployed_apps.*' => ['nullable', 'string', 'max:80'],
            'deployed_apps_text' => ['nullable', 'string', 'max:6000'],
            'renewal_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $apps = $this->normalizeDeployedApps(
            $validated['deployed_apps'] ?? [],
            $validated['deployed_apps_text'] ?? null,
        );

        unset($validated['deployed_apps'], $validated['deployed_apps_text']);

        return [$validated, $apps];
    }

    /**
     * @param  array<int, string|null>  $fields
     * @return array<int, string>
     */
    private function normalizeDeployedApps(array $fields, ?string $text): array
    {
        $names = array_map(
            fn ($name) => trim((string) $name),
            [...$fields, ...($text !== null ? preg_split('/\R/u', $text) : [])],
        );
        $names = array_values(array_unique(array_filter($names, fn (string $name) => $name !== '')));

        if (count($names) > 50) {
            throw ValidationException::withMessages([
                'deployed_apps' => 'You can list at most 50 deployed apps.',
            ]);
        }

        return $names;
    }

    /**
     * @param  array<int, string>  $names
     */
    private function syncDeployedApps(Server $server, array $names): void
    {
        $server->deployedApps()->delete();

        foreach ($names as $name) {
            $server->deployedApps()->create(['name' => $name]);
        }
    }

    private function providers(): Collection
    {
        return Server::query()->distinct()->orderBy('provider')->pluck('provider');
    }
}

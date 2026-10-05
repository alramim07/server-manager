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
    public function index(): View
    {
        return view('servers.index', [
            'servers' => Server::with('owner')->latest()->get(),
        ]);
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

        return redirect()->route('servers.index')->with('status', 'Server deleted.');
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

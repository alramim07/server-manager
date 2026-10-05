<div class="space-y-4 rounded-lg border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="name" class="mb-1 block text-sm font-medium">Name</label>
            <input id="name" name="name" type="text" maxlength="80" required placeholder="web-prod-01"
                value="{{ old('name', $server->name ?? '') }}"
                class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800">
            @error('name')<p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="ip_address" class="mb-1 block text-sm font-medium">IP address</label>
            <input id="ip_address" name="ip_address" type="text" maxlength="45" required placeholder="203.0.113.10"
                value="{{ old('ip_address', $server->ip_address ?? '') }}"
                class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800">
            @error('ip_address')<p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="operating_system" class="mb-1 block text-sm font-medium">Operating system</label>
            <input id="operating_system" name="operating_system" type="text" maxlength="80" required list="os-options"
                value="{{ old('operating_system', $server->operating_system ?? '') }}"
                class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800">
            <datalist id="os-options">
                <option value="Ubuntu 24.04"></option>
                <option value="Ubuntu 22.04"></option>
                <option value="Debian 12"></option>
                <option value="AlmaLinux 9"></option>
                <option value="Rocky Linux 9"></option>
                <option value="Windows Server 2022"></option>
            </datalist>
            @error('operating_system')<p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="provider" class="mb-1 block text-sm font-medium">Provider</label>
            <input id="provider" name="provider" type="text" maxlength="80" required list="provider-options"
                value="{{ old('provider', $server->provider ?? '') }}"
                class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800">
            <datalist id="provider-options">
                @foreach ($providers as $providerOption)
                    <option value="{{ $providerOption }}"></option>
                @endforeach
            </datalist>
            @error('provider')<p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="status" class="mb-1 block text-sm font-medium">Status</label>
            <select id="status" name="status" required
                class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800">
                @foreach (\App\Enums\ServerStatus::cases() as $statusOption)
                    <option value="{{ $statusOption->value }}"
                        @selected(old('status', $server?->status?->value ?? 'active') === $statusOption->value)>{{ $statusOption->label() }}</option>
                @endforeach
            </select>
            @error('status')<p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="deployed_apps_count" class="mb-1 block text-sm font-medium">Deployed apps</label>
            <input id="deployed_apps_count" name="deployed_apps_count" type="number" min="0" max="100000" required
                value="{{ old('deployed_apps_count', $server->deployed_apps_count ?? '') }}"
                class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800">
            @error('deployed_apps_count')<p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="renewal_date" class="mb-1 block text-sm font-medium">Renewal date
                <span class="font-normal text-slate-500 dark:text-slate-400">(optional)</span></label>
            <input id="renewal_date" name="renewal_date" type="date"
                value="{{ old('renewal_date', $server?->renewal_date?->format('Y-m-d')) }}"
                class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800">
            @error('renewal_date')<p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
        </div>
    </div>

    <div>
        <label for="notes" class="mb-1 block text-sm font-medium">Notes
            <span class="font-normal text-slate-500 dark:text-slate-400">(optional)</span></label>
        <textarea id="notes" name="notes" rows="3" maxlength="2000"
            class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800">{{ old('notes', $server->notes ?? '') }}</textarea>
        @error('notes')<p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
    </div>

    <div class="flex gap-3 pt-2">
        <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500">Save</button>
        <a href="{{ $cancelUrl }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">Cancel</a>
    </div>
</div>

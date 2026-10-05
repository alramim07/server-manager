<div class="flex gap-2" data-app-row>
    <input type="text" name="deployed_apps[]" maxlength="80" value="{{ $appName ?? '' }}" placeholder="nginx-proxy"
        aria-label="App name"
        class="flex-1 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800">
    <button type="button" data-remove-app aria-label="Remove app"
        class="rounded-md border border-slate-300 px-3 text-sm text-rose-600 hover:bg-rose-50 dark:border-slate-700 dark:text-rose-400 dark:hover:bg-rose-950">Remove</button>
</div>

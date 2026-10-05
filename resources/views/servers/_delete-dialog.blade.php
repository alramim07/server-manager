<dialog id="delete-dialog" class="m-auto rounded-lg border border-slate-200 bg-white p-0 backdrop:bg-black/50 dark:border-slate-700 dark:bg-slate-900">
    <form method="POST" id="delete-form" class="w-[24rem] p-6">
        @csrf
        @method('DELETE')
        <h2 class="text-lg font-semibold">Delete server?</h2>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
            You're about to permanently delete <strong id="delete-server-name"></strong>
            (<span id="delete-server-ip" class="font-mono"></span>).
            This can't be undone.
        </p>
        <div class="mt-6 flex justify-end gap-3">
            <button type="button" id="delete-cancel"
                class="rounded-md border border-slate-300 px-4 py-2 text-sm dark:border-slate-700">Cancel</button>
            <button class="rounded-md bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-500">Delete</button>
        </div>
    </form>
</dialog>

@push('scripts')
<script>
    (function () {
        var dialog = document.getElementById('delete-dialog');
        if (!dialog) return;

        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (!form.matches('form[data-server-name]')) return;
            e.preventDefault();
            document.getElementById('delete-form').action = form.action;
            document.getElementById('delete-server-name').textContent = form.dataset.serverName;
            document.getElementById('delete-server-ip').textContent = form.dataset.serverIp || '';
            dialog.showModal();
        });

        document.getElementById('delete-cancel').addEventListener('click', function () { dialog.close(); });
        dialog.addEventListener('click', function (e) { if (e.target === dialog) dialog.close(); });
    })();
</script>
@endpush

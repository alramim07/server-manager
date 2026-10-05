<dialog id="server-details-dialog" class="m-auto rounded-lg border border-slate-200 bg-white p-0 backdrop:bg-black/50 dark:border-slate-700 dark:bg-slate-900">
    <div class="w-[32rem] max-w-[calc(100vw-2rem)] p-6">
        <div id="server-details-body"></div>
        <div class="mt-6 flex justify-end">
            <button type="button" id="server-details-close"
                class="rounded-md border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">Close</button>
        </div>
    </div>
</dialog>

@push('scripts')
<script>
    (function () {
        var dialog = document.getElementById('server-details-dialog');
        var body = document.getElementById('server-details-body');
        if (!dialog || !body) return;

        document.addEventListener('click', function (event) {
            var trigger = event.target.closest('[data-details-for]');
            if (!trigger) return;

            var template = document.getElementById('server-details-' + trigger.dataset.detailsFor);
            if (!template) return;

            body.replaceChildren(template.content.cloneNode(true));
            dialog.showModal();
        });

        document.getElementById('server-details-close').addEventListener('click', function () { dialog.close(); });
        dialog.addEventListener('click', function (e) { if (e.target === dialog) dialog.close(); });
    })();
</script>
@endpush

<dialog id="server-details-dialog" class="m-auto rounded-lg border border-slate-200 bg-white p-0 text-slate-900 backdrop:bg-black/60 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-50">
    <div class="w-[32rem] max-w-[calc(100vw-2rem)] p-6">
        <div id="server-details-body"></div>
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
            if (trigger) {
                var template = document.getElementById('server-details-' + trigger.dataset.detailsFor);
                if (!template) return;

                body.replaceChildren(template.content.cloneNode(true));
                dialog.showModal();
                return;
            }

            if (event.target.closest('[data-details-close]') || event.target === dialog) {
                dialog.close();
            }
        });
    })();
</script>
@endpush

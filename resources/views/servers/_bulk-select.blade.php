<div id="bulk-actions" data-bulk-action="{{ route('servers.bulkDestroy') }}" class="hidden flex items-center gap-2">
    <button type="button" id="bulk-delete-btn"
        class="rounded-md bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-500">Delete</button>
    <button type="button" id="bulk-unselect-btn"
        class="rounded-md border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50 dark:border-slate-600 dark:hover:bg-slate-800">Deselect</button>
</div>

@push('scripts')
<script>
    (function () {
        var bulkBar = document.getElementById('bulk-actions');
        if (!bulkBar) return;

        var selectAll = document.getElementById('select-all-servers');
        var checkboxes = Array.prototype.slice.call(document.querySelectorAll('[data-select-row]'));
        var dialog = document.getElementById('delete-dialog');

        function refresh() {
            var count = checkboxes.filter(function (box) { return box.checked; }).length;
            var bulk = count >= 2;

            bulkBar.classList.toggle('hidden', !bulk);
            document.querySelectorAll('form[data-row-delete]').forEach(function (form) {
                form.classList.toggle('hidden', bulk);
            });

            if (selectAll) {
                selectAll.checked = checkboxes.length > 0 && count === checkboxes.length;
                selectAll.indeterminate = count > 0 && count < checkboxes.length;
            }
        }

        function unselect() {
            checkboxes.forEach(function (box) { box.checked = false; });
            if (selectAll) {
                selectAll.checked = false;
                selectAll.indeterminate = false;
            }
            refresh();
        }

        checkboxes.forEach(function (box) { box.addEventListener('change', refresh); });

        if (selectAll) {
            selectAll.addEventListener('change', function () {
                checkboxes.forEach(function (box) { box.checked = selectAll.checked; });
                refresh();
            });
        }

        document.getElementById('bulk-unselect-btn').addEventListener('click', unselect);
        document.addEventListener('bulk:clear', unselect);

        document.getElementById('bulk-delete-btn').addEventListener('click', function () {
            var checked = checkboxes.filter(function (box) { return box.checked; });
            if (checked.length < 2 || !dialog) return;

            var form = document.getElementById('delete-form');
            form.querySelectorAll('input[name="ids[]"]').forEach(function (input) { input.remove(); });
            checked.forEach(function (box) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = box.value;
                form.appendChild(input);
            });
            form.action = bulkBar.dataset.bulkAction;

            document.getElementById('delete-dialog-heading').textContent = 'Delete servers?';
            document.getElementById('delete-single-text').classList.add('hidden');
            document.getElementById('delete-bulk-text').classList.remove('hidden');
            document.getElementById('delete-bulk-summary').textContent = checked.length + ' selected servers';

            dialog.dataset.bulk = '1';
            dialog.showModal();
        });

        refresh();
    })();
</script>
@endpush

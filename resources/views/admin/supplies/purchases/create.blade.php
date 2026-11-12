<x-app-layout>
    <x-slot name="header">{{ __('New Purchase') }}</x-slot>
    <div class="card shadow-sm border-0">
        <form method="POST" action="{{ route('admin.purchases.store') }}" class="card-body">
            @csrf
            <div class="row g-3 mb-3">
                <div class="col-md-6"><label class="form-label">Supplier</label><select name="supplier_id" class="form-select"><option value="">—</option>@foreach ($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
                <div class="col-md-6"><label class="form-label">Notes</label><input name="notes" class="form-control"></div>
            </div>
            <div class="fw-semibold mb-2">Items</div>
            <table class="table table-sm align-middle" id="itemsTable">
                <thead class="table-light"><tr><th style="width:45%">Product</th><th>Qty</th><th>Unit cost</th><th></th></tr></thead>
                <tbody>
                    <tr>
                        <td><select name="items[0][product_id]" class="form-select" required>@foreach ($products as $p)<option value="{{ $p->id }}">{{ $p->name }} ({{ $p->sku }}) — {{ $p->stock_quantity }} in stock</option>@endforeach</select></td>
                        <td><input type="number" min="1" name="items[0][quantity]" class="form-control" value="1" required></td>
                        <td><input type="number" step="0.01" min="0" name="items[0][unit_cost]" class="form-control" value="0" required></td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger removeRow">×</button></td>
                    </tr>
                </tbody>
            </table>
            <button type="button" class="btn btn-sm btn-outline-secondary mb-3" id="addRow"><i class="hgi-stroke hgi-plus-sign"></i> Add item</button>
            <div><button class="btn btn-primary">Create Purchase</button> <a href="{{ route('admin.purchases.index') }}" class="btn btn-outline-secondary">Cancel</a></div>
        </form>
    </div>
    <script>
        let i = 1;
        document.getElementById('addRow').addEventListener('click', () => {
            const row = document.querySelector('#itemsTable tbody tr').cloneNode(true);
            row.querySelectorAll('select,input').forEach(el => { el.name = el.name.replace(/\[\d+\]/, '[' + i + ']'); if (el.tagName === 'INPUT') el.value = el.type === 'number' ? (el.name.includes('quantity') ? 1 : 0) : ''; });
            document.querySelector('#itemsTable tbody').appendChild(row); i++;
        });
        document.addEventListener('click', e => { const b = e.target.closest('.removeRow'); if (b && document.querySelectorAll('#itemsTable tbody tr').length > 1) b.closest('tr').remove(); });
    </script>
</x-app-layout>

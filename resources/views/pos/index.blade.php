<x-app-layout>
    <x-slot name="header">{{ __('Point of Sale') }}</x-slot>
    <div class="row g-3" style="min-height:70vh">
        {{-- Products --}}
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body border-bottom py-2">
                    <input type="text" id="posSearch" class="form-control" placeholder="Search product name, SKU or barcode...">
                </div>
                <div class="card-body overflow-auto" style="max-height:65vh">
                    <div class="row g-2" id="productGrid">
                        @foreach ($products as $p)
                            <div class="col-md-4 col-6 pos-product" data-name="{{ strtolower($p->name.' '.$p->sku.' '.$p->barcode) }}">
                                <button type="button" class="btn btn-outline-secondary w-100 h-100 text-start p-2 pos-add" data-id="{{ $p->id }}" @disabled($p->stock_quantity <= 0)>
                                    <div class="fw-semibold text-truncate">{{ $p->name }}</div>
                                    <div class="small text-muted">{{ $p->sku }} · {{ $p->stock_quantity }} {{ $p->unit }} left</div>
                                    <div class="fw-bold text-success">{{ number_format((float) $p->selling_price, 2) }}</div>
                                </button>
                            </div>
                        @endforeach
                    </div>
                    @if ($products->isEmpty())
                        <p class="text-muted text-center py-5">No active products. Add products first.</p>
                    @endif
                </div>
            </div>
        </div>
        {{-- Cart --}}
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 h-100 d-flex flex-column">
                <div class="card-body border-bottom py-2 fw-semibold"><i class="hgi-stroke hgi-shopping-cart-02 me-1"></i>Cart</div>
                <div class="card-body overflow-auto flex-grow-1 p-2" id="cartBody">
                    <p class="text-muted text-center py-4" id="cartEmpty">Cart is empty — tap products to add.</p>
                    <table class="table table-sm align-middle mb-0 d-none" id="cartTable">
                        <tbody id="cartRows"></tbody>
                    </table>
                </div>
                <div class="card-body border-top">
                    <div class="d-flex justify-content-between mb-1"><span class="text-muted">Subtotal</span><span id="posSubtotal">0.00</span></div>
                    @if ($taxEnabled)
                        <div class="d-flex justify-content-between mb-1"><span class="text-muted">Tax ({{ $taxPercent }}%)</span><span id="posTax">0.00</span></div>
                    @endif
                    <div class="d-flex justify-content-between fw-bold fs-5 mb-3"><span>Total</span><span id="posTotal">0.00</span></div>
                    <button type="button" class="btn btn-primary w-100" id="checkoutBtn" disabled data-bs-toggle="modal" data-bs-target="#checkoutModal"><i class="hgi-stroke hgi-checkmark-circle-02 me-1"></i>Checkout</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Checkout modal --}}
    <div class="modal fade" id="checkoutModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('pos.checkout') }}" class="modal-content">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Confirm Sale</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label">Customer</label>
                        <div class="input-group">
                            <select name="customer_id" class="form-select">
                                <option value="">Walk-in customer</option>
                                @foreach ($customers as $c)<option value="{{ $c->id }}">{{ $c->name }}{{ $c->phone ? ' · '.$c->phone : '' }}</option>@endforeach
                            </select>
                            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#newCust">New</button>
                        </div>
                        <div class="collapse mt-2" id="newCust">
                            <input type="text" name="customer_name" class="form-control" placeholder="Walk-in customer name (optional)">
                        </div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6"><label class="form-label">Discount</label><input type="number" step="0.01" min="0" name="discount_amount" id="discountInput" class="form-control" value="0"></div>
                        <div class="col-6"><label class="form-label">Discount reason</label><input type="text" name="discount_reason" class="form-control" placeholder="Required if discount"></div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6"><label class="form-label">Payment method</label>
                            <select name="payment_method" class="form-select">@foreach ($paymentMethods as $m)<option>{{ $m }}</option>@endforeach</select>
                        </div>
                        <div class="col-6"><label class="form-label">Amount paid</label><input type="number" step="0.01" min="0" name="amount_paid" id="amountPaid" class="form-control"></div>
                    </div>
                    <div class="form-check mb-2"><input type="checkbox" name="pay_now" value="1" class="form-check-input" id="payNow" checked><label class="form-check-label" for="payNow">Record payment now (uncheck for credit sale)</label></div>
                    <div class="fw-bold">Total due: <span id="modalTotal">0.00</span></div>
                    <input type="hidden" name="items_json" id="itemsJson">
                    <div id="hiddenItems"></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Back</button><button class="btn btn-primary">Complete Sale</button></div>
            </form>
        </div>
    </div>

    @php $productsJson = $products->mapWithKeys(fn ($p) => [$p->id => ['name' => $p->name, 'price' => (float) $p->selling_price, 'stock' => $p->stock_quantity, 'unit' => $p->unit]]); @endphp
    <script>
        const PRODUCTS = @json($productsJson);
        const TAX = {{ $taxEnabled ? (float) $taxPercent : 0 }};
        const cart = new Map();
        const money = n => n.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});

        function render() {
            const rows = document.getElementById('cartRows');
            rows.innerHTML = '';
            let subtotal = 0;
            cart.forEach((qty, id) => {
                const p = PRODUCTS[id]; const line = p.price * qty; subtotal += line;
                rows.insertAdjacentHTML('beforeend', `<tr>
                    <td>${p.name}<div class="small text-muted">${money(p.price)} each</div></td>
                    <td class="text-nowrap" style="width:110px">
                        <button type="button" class="btn btn-sm btn-outline-secondary pos-dec" data-id="${id}">−</button>
                        <span class="mx-1">${qty}</span>
                        <button type="button" class="btn btn-sm btn-outline-secondary pos-inc" data-id="${id}">+</button>
                    </td>
                    <td class="text-end">${money(line)}</td></tr>`);
            });
            const tax = subtotal * TAX / 100;
            const total = subtotal + tax;
            document.getElementById('posSubtotal').textContent = money(subtotal);
            const t = document.getElementById('posTax'); if (t) t.textContent = money(tax);
            document.getElementById('posTotal').textContent = money(total);
            document.getElementById('modalTotal').textContent = money(total);
            const ap = document.getElementById('amountPaid'); if (ap && !ap.dataset.touched) ap.value = total.toFixed(2);
            document.getElementById('cartEmpty').classList.toggle('d-none', cart.size > 0);
            document.getElementById('cartTable').classList.toggle('d-none', cart.size === 0);
            document.getElementById('checkoutBtn').disabled = cart.size === 0;
            const hidden = document.getElementById('hiddenItems'); hidden.innerHTML = '';
            let i = 0;
            cart.forEach((qty, id) => {
                hidden.insertAdjacentHTML('beforeend', `<input type="hidden" name="items[${i}][product_id]" value="${id}"><input type="hidden" name="items[${i}][quantity]" value="${qty}">`);
                i++;
            });
        }
        document.addEventListener('click', e => {
            const add = e.target.closest('.pos-add');
            if (add) { const id = +add.dataset.id; cart.set(id, Math.min((cart.get(id) || 0) + 1, PRODUCTS[id].stock)); render(); return; }
            const inc = e.target.closest('.pos-inc');
            if (inc) { const id = +inc.dataset.id; cart.set(id, Math.min(cart.get(id) + 1, PRODUCTS[id].stock)); render(); return; }
            const dec = e.target.closest('.pos-dec');
            if (dec) { const id = +dec.dataset.id; const q = cart.get(id) - 1; q <= 0 ? cart.delete(id) : cart.set(id, q); render(); }
        });
        document.getElementById('amountPaid').addEventListener('input', e => e.target.dataset.touched = 1);
        document.getElementById('posSearch').addEventListener('input', e => {
            const q = e.target.value.toLowerCase();
            document.querySelectorAll('.pos-product').forEach(el => el.classList.toggle('d-none', q && !el.dataset.name.includes(q)));
        });
    </script>
</x-app-layout>

@php $product = $product ?? null; @endphp
<div class="row g-2">
    <div class="col-6"><label class="form-label">SKU *</label><input name="sku" class="form-control" required value="{{ old('sku', $product?->sku) }}"></div>
    <div class="col-6"><label class="form-label">Barcode</label><input name="barcode" class="form-control" value="{{ old('barcode', $product?->barcode) }}"></div>
    <div class="col-12"><label class="form-label">Name *</label><input name="name" class="form-control" required value="{{ old('name', $product?->name) }}"></div>
    <div class="col-6"><label class="form-label">Category</label><select name="product_category_id" class="form-select"><option value="">—</option>@foreach (\App\Models\ProductCategory::active()->get() as $c)<option value="{{ $c->id }}" @selected(old('product_category_id', $product?->product_category_id) == $c->id)>{{ $c->name }}</option>@endforeach</select></div>
    <div class="col-6"><label class="form-label">Supplier</label><select name="supplier_id" class="form-select"><option value="">—</option>@foreach (\App\Models\Supplier::where('is_active', true)->orderBy('name')->get() as $s)<option value="{{ $s->id }}" @selected(old('supplier_id', $product?->supplier_id) == $s->id)>{{ $s->name }}</option>@endforeach</select></div>
    <div class="col-6"><label class="form-label">Purchase price *</label><input type="number" step="0.01" min="0" name="purchase_price" class="form-control" required value="{{ old('purchase_price', $product?->purchase_price) }}"></div>
    <div class="col-6"><label class="form-label">Selling price *</label><input type="number" step="0.01" min="0" name="selling_price" class="form-control" required value="{{ old('selling_price', $product?->selling_price) }}"></div>
    @unless ($product)
        <div class="col-6"><label class="form-label">Opening stock</label><input type="number" min="0" name="stock_quantity" class="form-control" value="{{ old('stock_quantity', 0) }}"></div>
    @endunless
    <div class="col-6"><label class="form-label">Min. stock level</label><input type="number" min="0" name="min_stock_level" class="form-control" value="{{ old('min_stock_level', $product?->min_stock_level ?? 0) }}"></div>
    <div class="col-6"><label class="form-label">Unit</label><input name="unit" class="form-control" value="{{ old('unit', $product?->unit ?? 'piece') }}" placeholder="piece, box, pair..."></div>
    <div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2">{{ old('description', $product?->description) }}</textarea></div>
    <div class="col-12"><label class="form-label">Image</label><input type="file" name="image" class="form-control" accept="image/*"></div>
</div>

@php $c = $customer ?? null; @endphp
<div class="row g-2">
    <div class="col-12"><label class="form-label">Name *</label><input name="name" class="form-control" required value="{{ old('name', $c?->name) }}"></div>
    <div class="col-6"><label class="form-label">Phone</label><input name="phone" class="form-control" value="{{ old('phone', $c?->phone) }}"></div>
    <div class="col-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="{{ old('email', $c?->email) }}"></div>
    <div class="col-12"><label class="form-label">Address</label><input name="address" class="form-control" value="{{ old('address', $c?->address) }}"></div>
    <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2">{{ old('notes', $c?->notes) }}</textarea></div>
</div>

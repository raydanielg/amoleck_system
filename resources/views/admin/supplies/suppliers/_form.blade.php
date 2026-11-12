@php $s = $supplier ?? null; @endphp
<div class="row g-2">
    <div class="col-12"><label class="form-label">Name *</label><input name="name" class="form-control" required value="{{ old('name', $s?->name) }}"></div>
    <div class="col-6"><label class="form-label">Contact person</label><input name="contact_person" class="form-control" value="{{ old('contact_person', $s?->contact_person) }}"></div>
    <div class="col-6"><label class="form-label">Phone</label><input name="phone" class="form-control" value="{{ old('phone', $s?->phone) }}"></div>
    <div class="col-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="{{ old('email', $s?->email) }}"></div>
    <div class="col-6"><label class="form-label">Company</label><input name="company" class="form-control" value="{{ old('company', $s?->company) }}"></div>
    <div class="col-12"><label class="form-label">Address</label><input name="address" class="form-control" value="{{ old('address', $s?->address) }}"></div>
    <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2">{{ old('notes', $s?->notes) }}</textarea></div>
</div>

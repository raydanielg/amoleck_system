<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Service Name <span class="text-danger">*</span></label>
        <input type="text" name="name" value="{{ old('name', $service->name ?? '') }}" class="form-control @error('name') is-invalid @enderror" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Code</label>
        <input type="text" name="service_code" value="{{ old('service_code', $service->service_code ?? '') }}" class="form-control @error('service_code') is-invalid @enderror">
        @error('service_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Category</label>
        <select name="service_category_id" class="form-select @error('service_category_id') is-invalid @enderror">
            <option value="">None</option>
            @foreach ($categories as $cat)
                <option value="{{ $cat->id }}" @selected(old('service_category_id', $service->service_category_id ?? '') == $cat->id)>{{ $cat->name }}</option>
            @endforeach
        </select>
        @error('service_category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Standard Price ({{ \App\Support\Settings::get('currency', 'TZS') }})</label>
        <input type="number" name="standard_price" value="{{ old('standard_price', $service->standard_price ?? '') }}" step="0.01" min="0" class="form-control @error('standard_price') is-invalid @enderror" placeholder="Leave empty if N/A">
        @error('standard_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Duration (minutes)</label>
        <input type="number" name="duration_minutes" value="{{ old('duration_minutes', $service->duration_minutes ?? '') }}" min="0" class="form-control @error('duration_minutes') is-invalid @enderror">
        @error('duration_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Display Order</label>
        <input type="number" name="sort_order" value="{{ old('sort_order', $service->sort_order ?? 0) }}" min="0" class="form-control">
    </div>
    <div class="col-12">
        <label class="form-label">Description</label>
        <textarea name="description" rows="2" class="form-control @error('description') is-invalid @enderror">{{ old('description', $service->description ?? '') }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <div class="form-check">
            <input type="checkbox" name="requires_notes" value="1" id="requires_notes" class="form-check-input" @checked(old('requires_notes', $service->requires_notes ?? false))>
            <label for="requires_notes" class="form-check-label">Require treatment notes when selected</label>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-check">
            <input type="checkbox" name="is_active" value="1" id="is_active" class="form-check-input" @checked(old('is_active', $service->is_active ?? true))>
            <label for="is_active" class="form-check-label">Active (available for new visits)</label>
        </div>
    </div>
</div>

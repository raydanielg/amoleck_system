<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Title <span class="text-danger">*</span></label>
        <input type="text" name="title" value="{{ old('title', $expense->title ?? '') }}" class="form-control @error('title') is-invalid @enderror" required>
        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Amount ({{ $currency }}) <span class="text-danger">*</span></label>
        <input type="number" name="amount" value="{{ old('amount', $expense->amount ?? '') }}" step="0.01" min="0.01" class="form-control @error('amount') is-invalid @enderror" required>
        @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Expense Date <span class="text-danger">*</span></label>
        <input type="date" name="expense_date" value="{{ old('expense_date', isset($expense) ? $expense->expense_date->format('Y-m-d') : now()->toDateString()) }}" class="form-control @error('expense_date') is-invalid @enderror" required>
        @error('expense_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Category <span class="text-danger">*</span></label>
        <select name="expense_category_id" class="form-select @error('expense_category_id') is-invalid @enderror" required>
            <option value="">Select...</option>
            @foreach ($categories as $cat)
                <option value="{{ $cat->id }}" @selected(old('expense_category_id', $expense->expense_category_id ?? '') == $cat->id)>{{ $cat->name }}</option>
            @endforeach
        </select>
        @error('expense_category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Payee / Supplier</label>
        <input type="text" name="payee" value="{{ old('payee', $expense->payee ?? '') }}" class="form-control @error('payee') is-invalid @enderror">
        @error('payee')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Payment Method</label>
        <select name="payment_method" class="form-select @error('payment_method') is-invalid @enderror">
            <option value="">Select...</option>
            @foreach ($paymentMethods as $method)
                <option value="{{ $method }}" @selected(old('payment_method', $expense->payment_method ?? '') === $method)>{{ $method }}</option>
            @endforeach
        </select>
        @error('payment_method')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <label class="form-label">Description / Notes</label>
        <textarea name="description" rows="2" class="form-control @error('description') is-invalid @enderror">{{ old('description', $expense->description ?? '') }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    @if ($receiptsEnabled)
        <div class="col-md-6">
            <label class="form-label">Receipt (JPG, PNG or PDF, max 5MB)</label>
            <input type="file" name="receipt" accept=".jpg,.jpeg,.png,.pdf" class="form-control @error('receipt') is-invalid @enderror">
            @error('receipt')<div class="invalid-feedback">{{ $message }}</div>@enderror
            @if (isset($expense) && $expense->receipt_path)
                <div class="small mt-1"><a href="{{ route('expenses.receipt', $expense) }}">Download current receipt</a></div>
            @endif
        </div>
    @endif
</div>

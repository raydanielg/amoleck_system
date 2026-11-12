<x-app-layout>
    <x-slot name="header">{{ __('Add Expense Category') }}</x-slot>
    <div class="card shadow-sm border-0" style="max-width: 700px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.expense-categories.store') }}">
                @csrf
                @include('admin.expense-categories._form', ['category' => null])
                <div class="mt-4 d-flex gap-2">
                    <button class="btn btn-primary">Save</button>
                    <a href="{{ route('admin.expense-categories.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

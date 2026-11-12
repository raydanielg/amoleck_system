<x-app-layout>
    <x-slot name="header">{{ __('Record Expense') }}</x-slot>

    <div class="card shadow-sm border-0" style="max-width: 850px;">
        <div class="card-body">
            <form method="POST" action="{{ route('expenses.store') }}" enctype="multipart/form-data">
                @csrf
                @include('expenses._form', ['expense' => null])
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Save Expense</button>
                    <a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">Edit Expense {{ $expense->expense_reference }}</x-slot>

    <div class="card shadow-sm border-0" style="max-width: 850px;">
        <div class="card-body">
            <form method="POST" action="{{ route('expenses.update', $expense) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('expenses._form')
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="{{ route('expenses.show', $expense) }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

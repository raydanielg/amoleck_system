@php $cur = \App\Support\Settings::get('currency', 'TZS'); @endphp
<x-app-layout>
    <x-slot name="header">Review {{ $expense->expense_reference }}</x-slot>
    <x-slot name="headerActions">
        <a href="{{ route('admin.expense-approvals.index') }}" class="btn btn-outline-secondary btn-sm"><i class="hgi-stroke hgi-arrow-left-01 me-1"></i> Inbox</a>
    </x-slot>

    @if ($expense->recorded_by === Auth::id())
        <div class="alert alert-info small">You submitted this expense — another administrator must review it (separation of duties).</div>
    @endif

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Expense Details</span>
                    <span class="badge-status status-{{ $expense->status }}">{{ str_replace('_', ' ', $expense->status) }}</span>
                </div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-4 text-muted">Title</dt><dd class="col-8">{{ $expense->title }}</dd>
                        <dt class="col-4 text-muted">Category</dt><dd class="col-8">{{ $expense->category_name_snapshot ?? '—' }}</dd>
                        <dt class="col-4 text-muted">Amount</dt><dd class="col-8 fw-bold fs-6">{{ $cur }} {{ number_format((float) $expense->amount, 2) }}</dd>
                        <dt class="col-4 text-muted">Date</dt><dd class="col-8">{{ $expense->expense_date->format('d/m/Y') }}</dd>
                        <dt class="col-4 text-muted">Payee</dt><dd class="col-8">{{ $expense->payee ?? '—' }}</dd>
                        <dt class="col-4 text-muted">Method</dt><dd class="col-8">{{ $expense->payment_method ?? '—' }}</dd>
                        <dt class="col-4 text-muted">Description</dt><dd class="col-8" style="white-space: pre-wrap;">{{ $expense->description ?? '—' }}</dd>
                        <dt class="col-4 text-muted">Receipt</dt>
                        <dd class="col-8">@if ($expense->receipt_path)<a href="{{ route('expenses.receipt', $expense) }}">Download receipt</a>@else — @endif</dd>
                        <dt class="col-4 text-muted">Submitted By</dt><dd class="col-8">{{ $expense->recorder?->name }} · {{ $expense->created_at->format('d/m/Y H:i') }}</dd>
                        @if ($expense->returned_reason)
                            <dt class="col-4 text-muted">Returned Reason</dt><dd class="col-8 text-warning">{{ $expense->returned_reason }}</dd>
                        @endif
                        @if ($expense->rejection_reason)
                            <dt class="col-4 text-muted">Rejection Reason</dt><dd class="col-8 text-danger">{{ $expense->rejection_reason }}</dd>
                        @endif
                        @if ($expense->paid_at)
                            <dt class="col-4 text-muted">Paid</dt><dd class="col-8">{{ $expense->paid_at->format('d/m/Y H:i') }} by {{ $expense->payer?->name }}</dd>
                        @endif
                    </dl>
                </div>

                @if ($canDecide && $expense->status === 'submitted')
                    <div class="card-footer bg-white">
                        <div class="d-flex flex-wrap gap-2">
                            <form method="POST" action="{{ route('admin.expense-approvals.decide', $expense) }}">
                                @csrf
                                <input type="hidden" name="decision" value="approve">
                                <button class="btn btn-success btn-sm"><i class="hgi-stroke hgi-checkmark-circle-02 me-1"></i> Approve</button>
                            </form>
                            <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal">Reject</button>
                            <button class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#returnModal">Return for Correction</button>
                        </div>
                    </div>
                @endif
                @if ($canDecide && $expense->status === 'approved' && $paymentStep)
                    <div class="card-footer bg-white">
                        <form method="POST" action="{{ route('admin.expense-approvals.decide', $expense) }}" class="d-flex gap-2 align-items-center">
                            @csrf
                            <input type="hidden" name="decision" value="mark_paid">
                            <button class="btn btn-primary btn-sm"><i class="hgi-stroke hgi-money-02 me-1"></i> Mark as Paid</button>
                            <span class="small text-muted">Payment step is enabled in approval rules.</span>
                        </form>
                    </div>
                @endif
            </div>

            {{-- Comments --}}
            <div class="card shadow-sm border-0 mt-3">
                <div class="card-header bg-white fw-semibold">Internal Comments</div>
                <ul class="list-group list-group-flush">
                    @forelse ($expense->comments as $comment)
                        <li class="list-group-item small">
                            <strong>{{ $comment->user?->name }}</strong> <span class="text-muted">· {{ $comment->created_at->format('d/m/Y H:i') }}</span>
                            <div>{{ $comment->comment }}</div>
                        </li>
                    @empty
                        <li class="list-group-item text-muted small">No comments.</li>
                    @endforelse
                </ul>
                <div class="card-footer bg-white">
                    <form method="POST" action="{{ route('admin.expense-approvals.comment', $expense) }}" class="d-flex gap-2">
                        @csrf
                        <input type="text" name="comment" class="form-control form-control-sm" placeholder="Add internal comment..." required>
                        <button class="btn btn-outline-primary btn-sm">Post</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-semibold">Decision History</div>
                <ul class="list-group list-group-flush">
                    @forelse ($expense->approvals as $approval)
                        <li class="list-group-item small">
                            <div class="d-flex justify-content-between">
                                <span class="fw-semibold text-capitalize">{{ str_replace('_', ' ', $approval->decision) }}</span>
                                <span class="text-muted">{{ $approval->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                            <div class="text-muted">{{ $approval->reviewer?->name }} · {{ $approval->previous_status }} → {{ $approval->new_status }}</div>
                            @if ($approval->reason)<div class="mt-1">"{{ $approval->reason }}"</div>@endif
                        </li>
                    @empty
                        <li class="list-group-item text-muted small">No decisions yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    {{-- Reject Modal --}}
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('admin.expense-approvals.decide', $expense) }}" class="modal-content">
                @csrf
                <input type="hidden" name="decision" value="reject">
                <div class="modal-header"><h6 class="modal-title">Reject {{ $expense->expense_reference }}</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <label class="form-label">Rejection reason <span class="text-danger">*</span></label>
                    <textarea name="reason" rows="2" class="form-control" required></textarea>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-danger">Reject Expense</button></div>
            </form>
        </div>
    </div>

    {{-- Return Modal --}}
    <div class="modal fade" id="returnModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('admin.expense-approvals.decide', $expense) }}" class="modal-content">
                @csrf
                <input type="hidden" name="decision" value="return">
                <div class="modal-header"><h6 class="modal-title">Return for Correction</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <p class="small text-muted">The submitter will be able to correct and resubmit this expense.</p>
                    <label class="form-label">What needs correction? <span class="text-danger">*</span></label>
                    <textarea name="reason" rows="2" class="form-control" required></textarea>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-warning">Return Expense</button></div>
            </form>
        </div>
    </div>
</x-app-layout>

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\BillingService;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $query = Payment::with('invoice', 'patient', 'receiver')->latest('payment_date');

        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('payment_reference', 'like', "%{$search}%")
                    ->orWhereHas('invoice', fn ($i) => $i->where('invoice_reference', 'like', "%{$search}%"))
                    ->orWhereHas('patient', fn ($p) => $p->where('full_name', 'like', "%{$search}%"));
            });
        }
        if ($method = $request->query('method')) {
            $query->where('payment_method', $method);
        }
        if ($from = $request->query('from')) {
            $query->where('payment_date', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $query->where('payment_date', '<=', $to);
        }

        return view('admin.payments.index', [
            'payments' => $query->paginate(15)->withQueryString(),
            'paymentMethods' => Settings::paymentMethods(),
        ]);
    }

    public function store(Request $request, Invoice $invoice): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999999'],
            'payment_method' => ['required', Rule::in(Settings::paymentMethods())],
            'payment_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
            'transaction_reference' => ['nullable', 'string', 'max:120'],
            'proof_note' => ['nullable', 'string', 'max:500'],
        ]);

        $payment = BillingService::recordPayment(
            $invoice,
            (string) $validated['amount'],
            $validated['payment_method'],
            $validated['payment_date'],
            $validated['notes'] ?? null,
            $request->user()->id,
            $validated['transaction_reference'] ?? null,
            $validated['proof_note'] ?? null,
        );

        return redirect()->route('admin.invoices.show', $invoice)
            ->with('success', $payment->isVerified() ? "Payment {$payment->payment_reference} recorded." : "Payment {$payment->payment_reference} submitted — pending verification.");
    }

    public function verify(Payment $payment): RedirectResponse
    {
        BillingService::verifyPayment($payment, request()->user()->id);

        return back()->with('success', "Payment {$payment->payment_reference} verified.");
    }

    public function reject(Payment $payment): RedirectResponse
    {
        BillingService::rejectPayment($payment, request()->user()->id);

        return back()->with('success', "Payment {$payment->payment_reference} rejected.");
    }

    public function show(Payment $payment): View
    {
        $payment->load(['invoice.items', 'patient', 'receiver', 'refunds.refunder']);

        return view('admin.payments.show', ['payment' => $payment]);
    }

    public function print(Payment $payment): View
    {
        $payment->load(['invoice', 'patient', 'receiver']);

        return view('admin.payments.print', ['payment' => $payment]);
    }
}

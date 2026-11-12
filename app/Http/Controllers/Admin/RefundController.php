<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Refund;
use App\Services\BillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RefundController extends Controller
{
    public function index(Request $request): View
    {
        $refunds = Refund::with('payment.invoice', 'payment.patient', 'refunder')->latest()->paginate(15);

        return view('admin.refunds.index', ['refunds' => $refunds]);
    }

    public function store(Request $request, Payment $payment): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999999'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $refund = BillingService::refundPayment(
            $payment, (string) $validated['amount'], $validated['reason'], $request->user()->id,
        );

        return back()->with('success', "Refund {$refund->refund_reference} recorded.");
    }
}

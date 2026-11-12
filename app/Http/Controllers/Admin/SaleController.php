<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Services\AuditLogger;
use App\Services\SaleService;
use App\Support\Settings;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function __construct(private SaleService $sales) {}

    public function index(Request $request)
    {
        $query = Sale::with(['customer', 'seller'])->latest();
        if ($s = $request->input('q')) {
            $query->where(fn ($q) => $q->where('sale_reference', 'like', "%{$s}%")->orWhere('customer_name', 'like', "%{$s}%"));
        }
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($from = $request->input('from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->input('to')) {
            $query->whereDate('created_at', '<=', $to);
        }
        $sales = $query->paginate(25)->withQueryString();

        return view('admin.supplies.sales.index', compact('sales'));
    }

    public function show(Sale $sale)
    {
        $sale->load(['items.product', 'payments.receiver', 'refunds.refunder', 'returns.items.product', 'customer', 'seller']);

        return view('admin.supplies.sales.show', compact('sale'));
    }

    public function pay(Request $request, Sale $sale)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|max:60',
        ]);
        try {
            $this->sales->addPayment($sale, (string) $data['amount'], $data['payment_method'], $request->user()->id);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
        AuditLogger::log('sale.payment', 'sale', $sale->id, null, ['amount' => $data['amount']]);

        return back()->with('success', 'Payment recorded.');
    }

    public function refund(Request $request, Sale $sale)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:500',
        ]);
        try {
            $refund = $this->sales->refund($sale, (string) $data['amount'], $data['reason'], $request->user()->id);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
        AuditLogger::log('sale.refunded', 'sale', $sale->id, null, ['refund' => $refund->refund_reference, 'amount' => $data['amount']]);

        return back()->with('success', 'Refund '.$refund->refund_reference.' recorded.');
    }

    public function returnItems(Request $request, Sale $sale)
    {
        $data = $request->validate([
            'reason' => 'required|string|max:500',
            'restock' => 'nullable|boolean',
            'items' => 'required|array|min:1',
            'items.*.sale_item_id' => 'required|exists:sale_items,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);
        try {
            $return = $this->sales->processReturn($sale, $data['items'], $data['reason'], $request->boolean('restock', true), $request->user()->id);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
        AuditLogger::log('sale.returned', 'sale', $sale->id, null, ['return' => $return->return_reference]);

        return back()->with('success', 'Return '.$return->return_reference.' recorded.');
    }

    public function cancel(Request $request, Sale $sale)
    {
        try {
            $this->sales->cancel($sale, $request->user()->id);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
        AuditLogger::log('sale.cancelled', 'sale', $sale->id);

        return back()->with('success', 'Sale cancelled. Stock restored.');
    }

    public function receiptPdf(Sale $sale)
    {
        $sale->load(['items', 'customer', 'seller', 'payments']);
        $clinic = $this->clinicBranding();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.sale-receipt', compact('sale', 'clinic'))->setPaper('a4');

        return $pdf->download($sale->sale_reference.'.pdf');
    }

    private function clinicBranding(): array
    {
        $keys = ['clinic_name', 'clinic_address', 'clinic_phone', 'clinic_email', 'clinic_website', 'clinic_logo', 'clinic_reg_number', 'clinic_tax_id', 'receipt_footer', 'currency', 'signature_label'];

        return collect($keys)->mapWithKeys(fn ($k) => [$k => Settings::get($k)])->all();
    }
}

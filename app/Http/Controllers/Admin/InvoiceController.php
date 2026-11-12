<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Service;
use App\Services\AuditLogger;
use App\Services\BillingService;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $query = Invoice::with('patient', 'issuer')->latest('issue_date');

        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_reference', 'like', "%{$search}%")
                    ->orWhereHas('patient', fn ($p) => $p->where('full_name', 'like', "%{$search}%")
                        ->orWhere('patient_number', 'like', "%{$search}%"));
            });
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($from = $request->query('from')) {
            $query->where('issue_date', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $query->where('issue_date', '<=', $to);
        }

        return view('admin.invoices.index', [
            'invoices' => $query->paginate(15)->withQueryString(),
            'patients' => Patient::where('status', Patient::STATUS_ACTIVE)->orderBy('full_name')->get(['id', 'full_name', 'patient_number']),
            'services' => Service::active()->get(['id', 'name', 'code', 'standard_price']),
            'billingEnabled' => Settings::bool('billing_enabled', true),
            'products' => \App\Models\Product::where('status', 'active')->orderBy('name')->get(['id', 'name', 'selling_price', 'stock_quantity']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(Settings::bool('billing_enabled', true), 403, 'Billing is disabled in settings.');

        $validated = $request->validate([
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'visit_id' => ['nullable', 'integer', 'exists:visits,id'],
            'issue_date' => ['required', 'date'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'services' => ['nullable', 'array'],
            'services.*' => ['integer', 'exists:services,id'],
            'quantities' => ['nullable', 'array'],
            'quantities.*' => ['integer', 'min:1', 'max:100'],
            'products' => ['nullable', 'array'],
            'products.*' => ['integer', 'exists:products,id'],
            'product_quantities' => ['nullable', 'array'],
            'product_quantities.*' => ['integer', 'min:1', 'max:1000'],
            'due_date' => ['nullable', 'date'],
        ]);

        $serviceQuantities = [];
        foreach ($validated['services'] ?? [] as $serviceId) {
            $serviceQuantities[$serviceId] = $validated['quantities'][$serviceId] ?? 1;
        }

        $productItems = [];
        foreach ($validated['products'] ?? [] as $pid) {
            $productItems[] = ['product_id' => $pid, 'quantity' => $validated['product_quantities'][$pid] ?? 1];
        }

        $items = BillingService::itemsFromServices($serviceQuantities);
        if (empty($items) && empty($productItems)) {
            throw ValidationException::withMessages(['services' => 'Select at least one valid service.']);
        }

        $invoice = BillingService::createInvoice(
            Patient::findOrFail($validated['patient_id']),
            $items,
            $validated['visit_id'] ?? null,
            $validated['issue_date'],
            (string) ($validated['discount_amount'] ?? '0'),
            $validated['discount_reason'] ?? null,
            $validated['notes'] ?? null,
            $request->user()->id,
            $productItems,
            $validated['due_date'] ?? null,
        );

        return redirect()->route('admin.invoices.show', $invoice)
            ->with('success', "Invoice {$invoice->invoice_reference} created.");
    }

    /** Create an invoice prefilled from a visit's services. */
    public function storeFromVisit(Request $request, \App\Models\Visit $visit): RedirectResponse
    {
        abort_unless(Settings::bool('billing_enabled', true), 403);
        abort_if($visit->isVoided(), 422, 'Cannot invoice a voided visit.');

        $items = $visit->services->map(fn ($vs) => [
            'service_id' => $vs->service_id,
            'name' => $vs->service_name_snapshot ?? $vs->service?->name ?? 'Service',
            'code' => $vs->service_code_snapshot ?? $vs->service?->service_code,
            'unit_price' => (string) $vs->price_snapshot,
            'quantity' => 1,
        ])->all();

        if (empty($items)) {
            return back()->with('error', 'This visit has no services to invoice.');
        }

        $invoice = BillingService::createInvoice(
            $visit->patient, $items, $visit->id, today()->toDateString(),
            '0', null, "Invoice for visit {$visit->visit_reference}", $request->user()->id,
        );

        return redirect()->route('admin.invoices.show', $invoice)
            ->with('success', "Invoice {$invoice->invoice_reference} created from visit.");
    }

    public function preview(Invoice $invoice): View
    {
        $invoice->load(['items.product', 'items.service', 'patient', 'payments' => fn ($q) => $q->latest()]);

        return view('admin.invoices.preview', [
            'invoice' => $invoice,
            'clinic' => $this->clinicBranding(),
        ]);
    }

    public function pdf(Invoice $invoice)
    {
        $invoice->load(['items.product', 'items.service', 'patient', 'payments' => fn ($q) => $q->latest()]);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'clinic' => $this->clinicBranding(),
        ])->setPaper('a4');

        return $pdf->download($invoice->invoice_reference.'.pdf');
    }

    private function clinicBranding(): array
    {
        $keys = ['clinic_name', 'clinic_address', 'clinic_phone', 'clinic_email', 'clinic_website', 'clinic_logo', 'clinic_reg_number', 'clinic_tax_id', 'receipt_footer', 'currency', 'signature_label', 'office_payment_number', 'office_payment_name', 'office_payment_instructions', 'bank_name', 'bank_account_name', 'bank_account_number', 'bank_branch', 'bank_instructions', 'invoice_terms'];

        return collect($keys)->mapWithKeys(fn ($k) => [$k => Settings::get($k)])->all();
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load(['patient', 'visit', 'issuer', 'items.service', 'payments.receiver', 'payments.refunds']);

        $invoice->load(['items.product', 'serviceFulfilments.assignee', 'serviceFulfilments.item', 'serviceFulfilments.events.user', 'productFulfilments.events.user', 'payments.receiver', 'payments.verifier']);

        return view('admin.invoices.show', [
            'invoice' => $invoice,
            'paymentMethods' => Settings::paymentMethods(),
        ]);
    }

    public function print(Invoice $invoice): View
    {
        $invoice->load(['patient', 'visit', 'issuer', 'items', 'payments']);

        return view('admin.invoices.print', ['invoice' => $invoice]);
    }

    public function void(Request $request, Invoice $invoice): RedirectResponse
    {
        $validated = $request->validate(['void_reason' => ['required', 'string', 'max:500']]);

        BillingService::voidInvoice($invoice, $validated['void_reason'], $request->user()->id);

        return back()->with('success', 'Invoice voided.');
    }
}

<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductFulfilment;
use App\Models\Service;
use App\Models\ServiceFulfilment;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicingFulfilmentTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN, 'status' => User::STATUS_ACTIVE, 'must_change_password' => false]);
    }

    private function patient(): Patient
    {
        return Patient::create(['patient_number' => 'PT-00001', 'full_name' => 'Test Patient', 'phone' => '0712000000', 'status' => 'active']);
    }

    public function test_combined_invoice_creates_both_fulfilments(): void
    {
        $admin = $this->admin();
        $patient = $this->patient();
        $service = Service::create(['name' => 'Physio Session', 'service_code' => 'PS-1', 'standard_price' => 50000, 'is_active' => true]);
        $product = Product::create(['sku' => 'WS-1', 'name' => 'Walking Stick', 'purchase_price' => 10000, 'selling_price' => 20000, 'stock_quantity' => 5]);

        $this->actingAs($admin)->post(route('admin.invoices.store'), [
            'patient_id' => $patient->id, 'issue_date' => today()->toDateString(),
            'services' => [$service->id], 'products' => [$product->id],
        ])->assertRedirect();

        $invoice = Invoice::latest()->first();
        $this->assertSame('combined', $invoice->type);
        $this->assertSame(70000.0, (float) $invoice->total);
        $this->assertSame(1, $invoice->serviceFulfilments()->count());
        $this->assertSame(1, $invoice->productFulfilments()->count());
        $this->assertSame(4, $product->fresh()->stock_quantity); // stock reserved
        $this->assertNotNull($invoice->due_date);
    }

    public function test_unverified_payment_does_not_reduce_balance(): void
    {
        $admin = $this->admin();
        $patient = $this->patient();
        $service = Service::create(['name' => 'Assess', 'service_code' => 'A-1', 'standard_price' => 100000, 'is_active' => true]);

        $this->actingAs($admin)->post(route('admin.invoices.store'), [
            'patient_id' => $patient->id, 'issue_date' => today()->toDateString(), 'services' => [$service->id],
        ]);
        $invoice = Invoice::latest()->first();

        Settings::set('payment_verification_methods', 'Bank Transfer');
        $this->actingAs($admin)->post(route('admin.invoices.payments.store', $invoice), [
            'amount' => 40000, 'payment_method' => 'Bank Transfer',
            'payment_date' => today()->toDateString(), 'transaction_reference' => 'TXN123',
        ])->assertRedirect();

        $payment = Payment::latest()->first();
        $this->assertSame('pending', $payment->verification_status);
        $this->assertSame(100000.0, (float) $invoice->fresh()->balance); // not counted yet

        $this->actingAs($admin)->post(route('admin.payments.verify', $payment))->assertRedirect();
        $invoice->refresh();
        $this->assertSame(60000.0, (float) $invoice->balance);
        $this->assertSame('partially_paid', $invoice->status);
    }

    public function test_cash_payment_auto_verified(): void
    {
        $admin = $this->admin();
        $patient = $this->patient();
        $service = Service::create(['name' => 'S', 'service_code' => 'S-1', 'standard_price' => 30000, 'is_active' => true]);

        $this->actingAs($admin)->post(route('admin.invoices.store'), [
            'patient_id' => $patient->id, 'issue_date' => today()->toDateString(), 'services' => [$service->id],
        ]);
        $invoice = Invoice::latest()->first();

        $this->actingAs($admin)->post(route('admin.invoices.payments.store', $invoice), [
            'amount' => 30000, 'payment_method' => 'Cash', 'payment_date' => today()->toDateString(),
        ]);

        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame('verified', Payment::latest()->first()->verification_status);
    }

    public function test_service_fulfilment_steps_progress(): void
    {
        $admin = $this->admin();
        $patient = $this->patient();
        $service = Service::create(['name' => 'S', 'service_code' => 'S-2', 'standard_price' => 30000, 'is_active' => true]);

        $this->actingAs($admin)->post(route('admin.invoices.store'), [
            'patient_id' => $patient->id, 'issue_date' => today()->toDateString(), 'services' => [$service->id],
        ]);
        $sf = ServiceFulfilment::latest()->first();
        $this->assertSame('requested', $sf->status);

        $this->actingAs($admin)->post(route('admin.fulfilments.service-step', $sf), ['step' => 'scheduled', 'scheduled_date' => today()->toDateString()])->assertRedirect();
        $this->assertSame('scheduled', $sf->fresh()->status);

        // backwards step rejected
        $this->actingAs($admin)->post(route('admin.fulfilments.service-step', $sf), ['step' => 'requested'])->assertSessionHas('error');

        // payment does NOT auto-complete service
        $this->assertSame('scheduled', $sf->fresh()->status);
    }

    public function test_delivery_requires_proof_and_delivery_method(): void
    {
        $admin = $this->admin();
        $patient = $this->patient();
        $product = Product::create(['sku' => 'WC-9', 'name' => 'Wheelchair', 'purchase_price' => 50000, 'selling_price' => 80000, 'stock_quantity' => 3]);

        $this->actingAs($admin)->post(route('admin.invoices.store'), [
            'patient_id' => $patient->id, 'issue_date' => today()->toDateString(), 'products' => [$product->id],
        ]);
        $pf = ProductFulfilment::latest()->first();

        // walk through steps quickly via service
        foreach (['payment_verified', 'stock_confirmed', 'picking', 'quality_check', 'packaging', 'ready_dispatch'] as $step) {
            \App\Services\FulfilmentService::advanceProduct($pf->fresh(), $step, null, [], $admin->id);
        }

        // dispatch without method fails
        $this->actingAs($admin)->post(route('admin.fulfilments.product-step', $pf), ['step' => 'dispatched'])->assertSessionHas('error');
        $this->actingAs($admin)->post(route('admin.fulfilments.product-step', $pf), ['step' => 'dispatched', 'delivery_method' => 'courier', 'recipient_name' => 'Jane', 'courier' => 'DHL'])->assertRedirect();
        $this->assertSame('dispatched', $pf->fresh()->status);

        // delivered without proof fails
        $this->actingAs($admin)->post(route('admin.fulfilments.product-step', $pf), ['step' => 'delivered'])->assertSessionHas('error');
        $this->actingAs($admin)->post(route('admin.fulfilments.product-step', $pf), ['step' => 'delivered', 'proof_note' => 'Signed by Jane'])->assertRedirect();
        $this->assertSame('delivered', $pf->fresh()->status);
    }

    public function test_invoice_preview_and_pdf_accessible(): void
    {
        $admin = $this->admin();
        $patient = $this->patient();
        $service = Service::create(['name' => 'S', 'service_code' => 'S-3', 'standard_price' => 30000, 'is_active' => true]);

        $this->actingAs($admin)->post(route('admin.invoices.store'), [
            'patient_id' => $patient->id, 'issue_date' => today()->toDateString(), 'services' => [$service->id],
        ]);
        $invoice = Invoice::latest()->first();

        $this->actingAs($admin)->get(route('admin.invoices.preview', $invoice))->assertOk();
        $this->actingAs($admin)->get(route('admin.invoices.pdf', $invoice))->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_product_invoice_blocked_when_out_of_stock(): void
    {
        $admin = $this->admin();
        $patient = $this->patient();
        $product = Product::create(['sku' => 'O-1', 'name' => 'Out', 'purchase_price' => 10, 'selling_price' => 20, 'stock_quantity' => 0]);

        $this->actingAs($admin)->post(route('admin.invoices.store'), [
            'patient_id' => $patient->id, 'issue_date' => today()->toDateString(), 'products' => [$product->id],
        ])->assertSessionHasErrors('products');
    }
}

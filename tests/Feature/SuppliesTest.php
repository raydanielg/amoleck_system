<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuppliesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN, 'status' => User::STATUS_ACTIVE, 'must_change_password' => false]);
    }

    private function product(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'sku' => 'SKU-'.uniqid(), 'name' => 'Test Product',
            'purchase_price' => 100, 'selling_price' => 200,
            'stock_quantity' => 10, 'min_stock_level' => 2, 'unit' => 'piece',
        ], $overrides));
    }

    private function makeSale(User $admin, Product $product, int $qty, array $extra = []): Sale
    {
        $this->actingAs($admin)->post(route('pos.checkout'), array_merge([
            'items' => [['product_id' => $product->id, 'quantity' => $qty]],
            'payment_method' => 'Cash', 'pay_now' => '1',
        ], $extra));

        return Sale::latest()->first();
    }

    public function test_product_creation_and_stock_receiving(): void
    {
        $admin = $this->admin();
        $cat = ProductCategory::create(['name' => 'Mobility']);

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'sku' => 'WC-001', 'name' => 'Wheelchair', 'product_category_id' => $cat->id,
            'purchase_price' => 100000, 'selling_price' => 150000, 'stock_quantity' => 5,
            'min_stock_level' => 2, 'unit' => 'piece',
        ])->assertRedirect();

        $product = Product::where('sku', 'WC-001')->firstOrFail();
        $this->actingAs($admin)->post(route('admin.inventory.receive', $product), [
            'quantity' => 10, 'reason' => 'New delivery',
        ])->assertRedirect();

        $this->assertSame(15, $product->fresh()->stock_quantity);
        $this->assertSame(1, StockMovement::where('product_id', $product->id)->where('type', 'received')->count());
    }

    public function test_adjustment_cannot_make_stock_negative(): void
    {
        $admin = $this->admin();
        $product = $this->product(['stock_quantity' => 3]);

        $this->actingAs($admin)->post(route('admin.inventory.adjust', $product), [
            'quantity' => -10, 'reason' => 'Correction',
        ])->assertSessionHas('error');

        $this->assertSame(3, $product->fresh()->stock_quantity);
    }

    public function test_pos_sale_deducts_stock_and_records_payment(): void
    {
        $admin = $this->admin();
        $product = $this->product(['selling_price' => 35000]);

        $sale = $this->makeSale($admin, $product, 2);

        $this->assertNotNull($sale);
        $this->assertSame(70000.0, (float) $sale->total);
        $this->assertSame(70000.0, (float) $sale->amount_paid);
        $this->assertSame('paid', $sale->status);
        $this->assertSame(8, $product->fresh()->stock_quantity);
        $this->assertTrue(StockMovement::where('type', 'sold')->where('reference', $sale->sale_reference)->exists());
    }

    public function test_pos_rejects_insufficient_stock(): void
    {
        $admin = $this->admin();
        $product = $this->product(['stock_quantity' => 1]);

        $this->actingAs($admin)->post(route('pos.checkout'), [
            'items' => [['product_id' => $product->id, 'quantity' => 5]],
            'payment_method' => 'Cash',
        ])->assertSessionHas('error');

        $this->assertSame(0, Sale::count());
        $this->assertSame(1, $product->fresh()->stock_quantity);
    }

    public function test_partial_payment_then_completion(): void
    {
        $admin = $this->admin();
        $product = $this->product(['selling_price' => 1000]);

        $sale = $this->makeSale($admin, $product, 1, ['amount_paid' => 400]);
        $this->assertSame('partially_paid', $sale->status);
        $this->assertSame(600.0, (float) $sale->balance);

        $this->actingAs($admin)->post(route('admin.sales.pay', $sale), [
            'amount' => 600, 'payment_method' => 'Mobile Money',
        ])->assertRedirect();

        $this->assertSame('paid', $sale->fresh()->status);
    }

    public function test_overpayment_rejected(): void
    {
        $admin = $this->admin();
        $product = $this->product();
        $sale = $this->makeSale($admin, $product, 1, ['amount_paid' => 50]);

        $this->actingAs($admin)->post(route('admin.sales.pay', $sale), [
            'amount' => 99999, 'payment_method' => 'Cash',
        ])->assertSessionHas('error');
    }

    public function test_refund_reopens_balance(): void
    {
        $admin = $this->admin();
        $product = $this->product();
        $sale = $this->makeSale($admin, $product, 1, ['amount_paid' => 200]);

        $this->assertSame('paid', $sale->status);

        $this->actingAs($admin)->post(route('admin.sales.refund', $sale), [
            'amount' => 80, 'reason' => 'Complaint',
        ])->assertRedirect();

        $sale->refresh();
        $this->assertSame('partially_refunded', $sale->status);
        $this->assertSame(120.0, (float) $sale->amount_paid);
    }

    public function test_refund_cannot_exceed_paid(): void
    {
        $admin = $this->admin();
        $product = $this->product();
        $sale = $this->makeSale($admin, $product, 1, ['amount_paid' => 100]);

        $this->actingAs($admin)->post(route('admin.sales.refund', $sale), [
            'amount' => 500, 'reason' => 'Too much',
        ])->assertSessionHas('error');
    }

    public function test_return_restocks_items(): void
    {
        $admin = $this->admin();
        $product = $this->product(['stock_quantity' => 10]);
        $sale = $this->makeSale($admin, $product, 3);
        $item = $sale->items->first();

        $this->actingAs($admin)->post(route('admin.sales.return', $sale), [
            'items' => [['sale_item_id' => $item->id, 'quantity' => 2]],
            'reason' => 'Defective', 'restock' => '1',
        ])->assertRedirect();

        $this->assertSame(9, $product->fresh()->stock_quantity); // 10 - 3 + 2
    }

    public function test_purchase_receive_updates_stock(): void
    {
        $admin = $this->admin();
        $supplier = Supplier::create(['name' => 'MedSupply Ltd']);
        $product = $this->product(['stock_quantity' => 0]);

        $this->actingAs($admin)->post(route('admin.purchases.store'), [
            'supplier_id' => $supplier->id,
            'items' => [['product_id' => $product->id, 'quantity' => 20, 'unit_cost' => 45]],
        ])->assertRedirect();

        $purchase = Purchase::latest()->first();
        $this->assertSame(900.0, (float) $purchase->total);

        $this->actingAs($admin)->post(route('admin.purchases.receive', $purchase))->assertRedirect();

        $p = $product->fresh();
        $this->assertSame(20, $p->stock_quantity);
        $this->assertSame(45.0, (float) $p->purchase_price);
        $this->assertSame('received', $purchase->fresh()->status);
    }

    public function test_staff_pos_permission_is_configurable(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_CLINIC_STAFF, 'status' => User::STATUS_ACTIVE, 'must_change_password' => false]);

        $this->actingAs($staff)->get(route('admin.products.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('pos.index'))->assertForbidden();

        Settings::set('staff_can_pos', '1');
        $this->actingAs($staff)->get(route('pos.index'))->assertOk();
    }

    public function test_discount_requires_reason_within_subtotal(): void
    {
        $admin = $this->admin();
        $product = $this->product(['selling_price' => 100]);

        $this->actingAs($admin)->post(route('pos.checkout'), [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'payment_method' => 'Cash', 'discount_amount' => 50,
        ])->assertSessionHas('error');

        $this->actingAs($admin)->post(route('pos.checkout'), [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'payment_method' => 'Cash', 'discount_amount' => 150, 'discount_reason' => 'Promo',
        ])->assertSessionHas('error');
    }
}

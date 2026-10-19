<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockAndBarcodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_customers_cannot_access_backoffice(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_lookup_finds_product_by_barcode(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->withStock(4)->create(['barcode' => '7801234567890']);

        $this->actingAs($admin)
            ->getJson(route('admin.products.lookup', ['code' => '7801234567890']))
            ->assertOk()
            ->assertJsonPath('product.id', $product->id)
            ->assertJsonPath('product.stock', 4);

        $this->actingAs($admin)
            ->getJson(route('admin.products.lookup', ['code' => '0000000000000']))
            ->assertOk()
            ->assertJsonPath('product', null);
    }

    public function test_scanner_stock_in_and_out_is_logged(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->withStock(1)->create();

        $this->actingAs($admin)->postJson(route('admin.products.stock', $product), ['type' => 'in', 'quantity' => 5])
            ->assertOk()->assertJsonPath('product.stock', 6);

        $this->actingAs($admin)->postJson(route('admin.products.stock', $product), ['type' => 'out', 'quantity' => 10])
            ->assertUnprocessable();

        $this->actingAs($admin)->postJson(route('admin.products.stock', $product), ['type' => 'count', 'quantity' => 2])
            ->assertOk()->assertJsonPath('product.stock', 2);

        $this->assertDatabaseCount('stock_movements', 2);
    }

    public function test_barcode_must_be_unique_among_active_products(): void
    {
        $admin = User::factory()->admin()->create();
        Product::factory()->create(['barcode' => '111111']);
        $other = Product::factory()->create(['barcode' => null]);

        $this->actingAs($admin)->postJson(route('admin.products.barcode', $other), ['barcode' => '111111'])
            ->assertUnprocessable();
    }

    public function test_deleted_products_free_their_barcode(): void
    {
        $admin = User::factory()->admin()->create();
        $old = Product::factory()->create(['barcode' => '222222']);
        $this->actingAs($admin)->delete(route('admin.products.destroy', $old))->assertRedirect();

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'name' => 'Nuevo', 'sku' => 'NEW-1', 'barcode' => '222222', 'price' => 1000, 'min_stock' => 1, 'is_active' => true, 'initial_stock' => 7,
        ])->assertRedirect();

        $this->assertSame(7, Product::where('barcode', '222222')->firstOrFail()->stock);
    }
}

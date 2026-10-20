<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSkuTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return ['name' => 'Cable HDMI 2m', 'price' => 5990, 'min_stock' => 2, 'is_active' => true, ...$overrides];
    }

    public function test_sku_is_generated_when_left_empty(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.products.store'), $this->payload(['sku' => '']))
            ->assertSessionHasNoErrors()->assertRedirect();

        $product = Product::firstOrFail();
        $this->assertSame(sprintf('PRD-%04d', $product->id), $product->sku);
    }

    public function test_manual_sku_is_kept(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.products.store'), $this->payload(['sku' => 'hdmi-2m']))
            ->assertSessionHasNoErrors();

        $this->assertSame('HDMI-2M', Product::firstOrFail()->sku);
    }

    public function test_generated_sku_avoids_collisions(): void
    {
        $admin = User::factory()->admin()->create();
        // Un producto con SKU manual igual al que se generaría para el siguiente id.
        $existing = Product::factory()->create();
        $existing->update(['sku' => sprintf('PRD-%04d', $existing->id + 1)]);

        $this->actingAs($admin)->post(route('admin.products.store'), $this->payload())->assertSessionHasNoErrors();

        $new = Product::latest('id')->firstOrFail();
        $this->assertSame(sprintf('PRD-%04d-2', $new->id), $new->sku);
    }

    public function test_clearing_sku_on_edit_regenerates_it(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['sku' => 'VIEJO-1']);

        $this->actingAs($admin)->put(route('admin.products.update', $product), $this->payload(['sku' => null]))
            ->assertSessionHasNoErrors();

        $this->assertSame(sprintf('PRD-%04d', $product->id), $product->fresh()->sku);
    }
}

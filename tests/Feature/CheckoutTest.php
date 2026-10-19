<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderPaymentService;
use App\Services\Payments\FakeGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function shipping(): array
    {
        return ['shipping_name' => 'Ana', 'shipping_phone' => '+56911111111', 'shipping_address' => 'Av. Siempre Viva 123'];
    }

    public function test_guest_cannot_add_to_cart(): void
    {
        $product = Product::factory()->withStock(5)->create();

        $this->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 1])
            ->assertRedirect(route('login'));
    }

    public function test_cannot_add_more_than_stock(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->withStock(2)->create();

        $this->actingAs($user)
            ->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 3])
            ->assertSessionHasErrors('quantity');
    }

    public function test_checkout_reserves_stock_and_clears_cart(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->withStock(5)->create(['price' => 10000]);
        $user->cartItems()->create(['product_id' => $product->id, 'quantity' => 2]);

        $this->actingAs($user)->post(route('checkout.store'), $this->shipping())->assertRedirect(); // hacia la pasarela

        $order = Order::firstOrFail();
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(20000, $order->total);
        $this->assertSame(3, $product->fresh()->stock);
        $this->assertSame(0, $user->cartItems()->count());
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'type' => 'sale', 'quantity' => -2, 'stock_after' => 3]);
    }

    public function test_approved_payment_marks_order_paid_once(): void
    {
        [$user, $order] = $this->pendingOrder();
        $paymentId = FakeGateway::paymentIdFor($order, true);

        $this->actingAs($user)->get(route('orders.show', ['order' => $order, 'payment_id' => $paymentId]))->assertOk();
        // Segunda notificación del mismo pago: idempotente.
        app(OrderPaymentService::class)->syncPayment($paymentId);

        $order->refresh();
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertSame($paymentId, $order->payment_id);
        $this->assertSame(1, $order->items()->first()->product->stock); // 3 - 2, sin doble descuento
    }

    public function test_rejected_payment_releases_stock(): void
    {
        [$user, $order] = $this->pendingOrder();

        $this->actingAs($user)->get(route('orders.show', ['order' => $order, 'payment_id' => FakeGateway::paymentIdFor($order, false)]));

        $order->refresh();
        $this->assertSame(OrderStatus::Failed, $order->status);
        $this->assertSame(3, $order->items()->first()->product->stock);
    }

    public function test_expired_orders_release_stock(): void
    {
        [, $order] = $this->pendingOrder();
        $order->update(['reserved_until' => now()->subMinute()]);

        $this->artisan('orders:expire')->assertSuccessful();

        $this->assertSame(OrderStatus::Expired, $order->fresh()->status);
        $this->assertSame(3, $order->items()->first()->product->stock);
    }

    public function test_customer_cannot_see_others_orders(): void
    {
        [, $order] = $this->pendingOrder();

        $this->actingAs(User::factory()->create())->get(route('orders.show', $order))->assertNotFound();
    }

    /** @return array{0: User, 1: Order} */
    private function pendingOrder(): array
    {
        $user = User::factory()->create();
        $product = Product::factory()->withStock(3)->create(['price' => 5000]);
        $user->cartItems()->create(['product_id' => $product->id, 'quantity' => 2]);

        $this->actingAs($user)->post(route('checkout.store'), $this->shipping());

        return [$user, Order::firstOrFail()];
    }
}

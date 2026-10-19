<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HomeAndContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_shows_featured_products_first(): void
    {
        $featured = Product::factory()->withStock(3)->create(['is_featured' => true]);
        Product::factory()->withStock(3)->create();
        Product::factory()->withStock(3)->create(['is_featured' => true, 'is_active' => false]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->where('hero.id', $featured->id)
                ->has('featured', 0)
                ->has('services'));
    }

    public function test_home_falls_back_to_latest_products_without_featured(): void
    {
        Product::factory()->withStock(2)->count(3)->create();

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->whereNot('hero', null)->has('featured', 2));
    }

    public function test_shop_moved_to_tienda(): void
    {
        $this->get('/tienda')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Shop/Catalog'));
    }

    public function test_contact_form_stores_message(): void
    {
        $this->post(route('contact.store'), [
            'name' => 'Ana', 'email' => 'ana@example.com', 'message' => 'Necesito cotizar una red WiFi.',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('contact_messages', ['email' => 'ana@example.com', 'read_at' => null]);
    }

    public function test_contact_honeypot_discards_bots(): void
    {
        $this->post(route('contact.store'), [
            'name' => 'Bot', 'email' => 'bot@example.com', 'message' => 'Compra seguidores baratos', 'website' => 'http://spam.test',
        ])->assertSessionHas('success');

        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_admin_can_toggle_featured_and_read_messages(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        $message = ContactMessage::create(['name' => 'Ana', 'email' => 'a@b.cl', 'message' => 'Hola, consulta.']);

        $this->actingAs($admin)->post(route('admin.products.featured', $product))->assertRedirect();
        $this->assertTrue($product->fresh()->is_featured);

        $this->actingAs($admin)->get(route('admin.messages.index'))->assertOk();
        $this->actingAs($admin)->post(route('admin.messages.read', $message))->assertRedirect();
        $this->assertNotNull($message->fresh()->read_at);
    }

    public function test_customers_cannot_see_messages(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.messages.index'))->assertForbidden();
    }
}

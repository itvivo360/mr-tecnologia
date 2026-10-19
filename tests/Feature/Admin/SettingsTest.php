<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\Settings;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_customers_cannot_open_settings(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.settings.index'))->assertForbidden();
    }

    public function test_defaults_come_from_config_until_saved(): void
    {
        config(['site.name' => 'Desde env']);

        $this->get(route('home'))->assertInertia(fn (Assert $page) => $page->where('site.name', 'Desde env'));
    }

    public function test_admin_updates_contact_and_social_without_deploy(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put(route('admin.settings.update', 'contact'), [
            'whatsapp' => '+56 9 1234 5678',
            'whatsapp_message' => 'Hola',
            'phone' => null,
            'email' => 'ventas@mrtecnologia.cl',
            'address' => null,
            'maps_url' => null,
            'hours' => 'Lunes a sábado, 10:00 a 19:00',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->actingAs($admin)->put(route('admin.settings.update', 'social'), [
            'instagram' => 'https://instagram.com/mrtecnologia',
        ])->assertSessionHasNoErrors();

        $this->get(route('home'))->assertInertia(fn (Assert $page) => $page
            ->where('site.contact.whatsapp', '56912345678')
            ->where('site.contact.hours', 'Lunes a sábado, 10:00 a 19:00')
            ->where('site.social.0.name', 'Instagram')
            ->has('site.social', 1));
    }

    public function test_support_schedule_changes_available_slots(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-12 06:00', 'America/Santiago')); // lunes
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put(route('admin.settings.update', 'support'), [
            'open_hour' => 10,
            'close_hour' => 13,
            'slot_minutes' => 30,
            'slot_capacity' => 2,
            'working_days' => [1, 2, 3, 4, 5, 6],
            'booking_horizon_days' => 15,
            'min_notice_hours' => 1,
        ])->assertSessionHasNoErrors();

        $slots = $this->actingAs(User::factory()->create())
            ->getJson(route('support.slots', ['date' => '2026-10-17'])) // sábado, ahora habilitado
            ->assertOk()
            ->json('slots');

        $this->assertCount(6, $slots);
        $this->assertSame(2, $slots[0]['remaining']);
    }

    public function test_invalid_schedule_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put(route('admin.settings.update', 'support'), [
            'open_hour' => 18, 'close_hour' => 9, 'slot_minutes' => 60, 'slot_capacity' => 1,
            'working_days' => [], 'booking_horizon_days' => 30, 'min_notice_hours' => 2,
        ])->assertSessionHasErrors(['close_hour', 'working_days']);

        $this->assertSame(config('shop.support.open_hour'), app(Settings::class)->support()['open_hour']);
    }

    public function test_services_can_be_edited(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put(route('admin.settings.update', 'services'), [
            'items' => [['title' => 'Cámaras de seguridad', 'text' => 'Instalación y configuración de cámaras IP.', 'icon' => 'VideoCamera', 'action' => 'whatsapp']],
        ])->assertSessionHasNoErrors();

        $this->get(route('home'))->assertInertia(fn (Assert $page) => $page
            ->has('services', 1)
            ->where('services.0.title', 'Cámaras de seguridad'));
    }
}

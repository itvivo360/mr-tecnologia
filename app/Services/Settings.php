<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

/**
 * Configuración del sitio administrable desde el backoffice.
 *
 * Cada sección se guarda como una fila JSON en `settings`. Los valores de
 * config/site.php y config/shop.php (y por lo tanto del .env) son solo los
 * valores iniciales: en cuanto una sección se guarda desde el backoffice,
 * manda la base de datos. Las lecturas se cachean y la caché se limpia al guardar.
 */
class Settings
{
    public const SECTIONS = ['site', 'contact', 'social', 'services', 'support'];

    private const CACHE_KEY = 'settings:v1';

    /** Redes sociales disponibles en el formulario (clave => nombre visible). */
    public const NETWORKS = [
        'instagram' => 'Instagram',
        'facebook' => 'Facebook',
        'tiktok' => 'TikTok',
        'youtube' => 'YouTube',
        'linkedin' => 'LinkedIn',
        'x' => 'X',
    ];

    private ?array $stored = null;

    public function site(): array
    {
        return $this->section('site');
    }

    public function contact(): array
    {
        return $this->section('contact');
    }

    /** @return array<string, ?string> clave de red => URL (vacía si no se usa) */
    public function social(): array
    {
        return $this->section('social');
    }

    /** Redes con URL, listas para mostrar: [{name, url}] */
    public function socialLinks(): array
    {
        return collect($this->social())
            ->filter()
            ->map(fn ($url, $key) => ['name' => self::NETWORKS[$key] ?? $key, 'url' => $url])
            ->values()
            ->all();
    }

    public function services(): array
    {
        return $this->section('services');
    }

    /** Agenda de soporte. Los motivos (topics) siguen en config/shop.php porque las citas guardan su clave. */
    public function support(): array
    {
        return [...$this->section('support'), 'topics' => config('shop.support.topics')];
    }

    public function section(string $key): array
    {
        $this->assertSection($key);

        $defaults = $this->defaults($key);
        $stored = $this->stored()[$key] ?? null;

        if (! is_array($stored)) {
            return $defaults;
        }

        // Listas (servicios) se reemplazan completas; objetos se combinan campo a campo
        // para que un campo nuevo en el código tome su valor por defecto.
        return array_is_list($defaults) ? $stored : array_replace($defaults, $stored);
    }

    public function put(string $key, array $value, ?User $user = null): void
    {
        $this->assertSection($key);

        Setting::updateOrCreate(['key' => $key], ['value' => $value, 'updated_by' => $user?->id]);

        Cache::forget(self::CACHE_KEY);
        $this->stored = null;
    }

    /** Valores iniciales tomados de los archivos de configuración (.env). */
    public function defaults(string $key): array
    {
        $support = config('shop.support');
        $social = config('site.social', []);

        return match ($key) {
            'site' => [
                'name' => config('site.name'),
                'tagline' => config('site.tagline'),
                'description' => config('site.description'),
            ],
            'contact' => config('site.contact'),
            'social' => collect(self::NETWORKS)->mapWithKeys(fn ($label, $k) => [$k => $social[$label] ?? null])->all(),
            'services' => config('site.services'),
            'support' => [
                'open_hour' => $support['open_hour'],
                'close_hour' => $support['close_hour'],
                'slot_minutes' => $support['slot_minutes'],
                'slot_capacity' => $support['slot_capacity'],
                'working_days' => $support['working_days'],
                'booking_horizon_days' => $support['booking_horizon_days'],
                'min_notice_hours' => $support['min_notice_hours'],
            ],
        };
    }

    private function stored(): array
    {
        if ($this->stored !== null) {
            return $this->stored;
        }

        try {
            return $this->stored = Cache::rememberForever(
                self::CACHE_KEY,
                fn () => Setting::query()->get()->mapWithKeys(fn (Setting $s) => [$s->key => $s->value])->all(),
            );
        } catch (QueryException) {
            // Tabla aún no migrada (instalación nueva): se usan los valores por defecto.
            return $this->stored = [];
        }
    }

    private function assertSection(string $key): void
    {
        if (! in_array($key, self::SECTIONS, true)) {
            throw new InvalidArgumentException("Sección de configuración desconocida: {$key}");
        }
    }
}

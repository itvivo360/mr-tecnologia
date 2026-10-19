<?php

namespace App\Providers;

use App\Services\Payments\FakeGateway;
use App\Services\Settings;
use App\Services\Payments\MercadoPagoGateway;
use App\Services\Payments\PaymentGateway;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Settings::class);

        $this->app->singleton(PaymentGateway::class, function () {
            return match (config('shop.payment_gateway')) {
                'mercadopago' => new MercadoPagoGateway(
                    accessToken: (string) config('services.mercadopago.access_token'),
                    webhookSecret: config('services.mercadopago.webhook_secret'),
                    baseUrl: config('services.mercadopago.base_url'),
                ),
                'fake' => $this->app->isProduction()
                    ? throw new InvalidArgumentException('La pasarela "fake" no puede usarse en producción.')
                    : new FakeGateway(),
                default => throw new InvalidArgumentException('PAYMENT_GATEWAY desconocido: '.config('shop.payment_gateway')),
            };
        });
    }

    public function boot(): void
    {
        // Falla en desarrollo si se intenta asignar un atributo no fillable.
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
        Vite::prefetch(concurrency: 3);
    }
}

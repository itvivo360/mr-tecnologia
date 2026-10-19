<?php

use App\Services\OrderPaymentService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('orders:expire', function (OrderPaymentService $payments) {
    $count = $payments->expireStale();
    $this->info("Órdenes expiradas: {$count}");
})->purpose('Expira órdenes pendientes cuya reserva de stock venció y libera el stock');

Schedule::command('orders:expire')->everyMinute()->withoutOverlapping();

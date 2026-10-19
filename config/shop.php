<?php

return [

    /*
    | Moneda de la tienda. Los montos se guardan como enteros (CLP no usa decimales).
    */
    'currency' => env('SHOP_CURRENCY', 'CLP'),

    /*
    | Pasarela de pago activa: "mercadopago" o "fake".
    | "fake" aprueba los pagos inmediatamente y solo debe usarse en desarrollo.
    */
    'payment_gateway' => env('PAYMENT_GATEWAY', 'fake'),

    /*
    | Minutos que una orden pendiente de pago mantiene el stock reservado.
    | Pasado ese tiempo, el comando orders:expire libera el stock.
    */
    'order_reservation_minutes' => (int) env('ORDER_RESERVATION_MINUTES', 30),

    /*
    | Stock mínimo por defecto para alertas de reposición.
    */
    'default_min_stock' => (int) env('SHOP_DEFAULT_MIN_STOCK', 5),

    /*
    | Agenda de soporte técnico.
    */
    'support' => [
        'open_hour' => (int) env('SUPPORT_OPEN_HOUR', 9),
        'close_hour' => (int) env('SUPPORT_CLOSE_HOUR', 18),
        'slot_minutes' => (int) env('SUPPORT_SLOT_MINUTES', 60),
        'slot_capacity' => (int) env('SUPPORT_SLOT_CAPACITY', 1),
        // ISO-8601: 1 = lunes ... 7 = domingo
        'working_days' => array_map('intval', explode(',', env('SUPPORT_WORKING_DAYS', '1,2,3,4,5'))),
        'booking_horizon_days' => (int) env('SUPPORT_BOOKING_HORIZON_DAYS', 30),
        // Antelación mínima para reservar o cancelar, en horas
        'min_notice_hours' => (int) env('SUPPORT_MIN_NOTICE_HOURS', 2),
        'topics' => [
            'installation' => 'Instalación / configuración',
            'repair' => 'Falla o reparación',
            'warranty' => 'Garantía',
            'other' => 'Otra consulta',
        ],
    ],

];

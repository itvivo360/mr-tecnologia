<?php

/*
|--------------------------------------------------------------------------
| Contenido del sitio público: VALORES INICIALES
|--------------------------------------------------------------------------
| Todo esto se administra desde el backoffice (Configuración). Estos valores
| (y los del .env) solo se usan mientras una sección no se haya guardado
| desde ahí. Una red o canal vacío no se muestra.
*/

return [

    'name' => env('SITE_NAME', 'Mr Tecnología'),

    'tagline' => env('SITE_TAGLINE', 'Equipos, redes y soporte técnico para tu casa y tu negocio.'),

    'description' => env(
        'SITE_DESCRIPTION',
        'Vendemos computadores, redes y accesorios, y te acompañamos después de la compra con soporte técnico agendado.'
    ),

    'contact' => [
        // Solo dígitos con código de país, ej. 56912345678
        'whatsapp' => env('SITE_WHATSAPP'),
        'whatsapp_message' => env('SITE_WHATSAPP_MESSAGE', 'Hola, quiero hacer una consulta.'),
        'phone' => env('SITE_PHONE'),
        'email' => env('SITE_EMAIL'),
        'address' => env('SITE_ADDRESS'),
        'maps_url' => env('SITE_MAPS_URL'),
        'hours' => env('SITE_HOURS', 'Lunes a viernes, 9:00 a 18:00'),
    ],

    'social' => array_filter([
        'Instagram' => env('SOCIAL_INSTAGRAM'),
        'Facebook' => env('SOCIAL_FACEBOOK'),
        'TikTok' => env('SOCIAL_TIKTOK'),
        'YouTube' => env('SOCIAL_YOUTUBE'),
        'LinkedIn' => env('SOCIAL_LINKEDIN'),
        'X' => env('SOCIAL_X'),
    ]),

    /*
    | Servicios de la landing. "action" define el botón de cada uno:
    | shop (tienda), support (agendar turno) o whatsapp (cotizar por WhatsApp).
    | icon: nombre de un ícono de @element-plus/icons-vue.
    */
    'services' => [
        [
            'title' => 'Venta de equipos y accesorios',
            'text' => 'Notebooks, monitores, almacenamiento y periféricos con garantía. Compra online y paga con Mercado Pago.',
            'icon' => 'Monitor',
            'action' => 'shop',
        ],
        [
            'title' => 'Soporte técnico agendado',
            'text' => 'Elige día y hora, cuéntanos el problema y un técnico te atiende en el horario reservado.',
            'icon' => 'Calendar',
            'action' => 'support',
        ],
        [
            'title' => 'Instalación de redes',
            'text' => 'WiFi para la casa o la oficina: diagnóstico de cobertura, cableado, routers, switches y sistemas mesh.',
            'icon' => 'Connection',
            'action' => 'whatsapp',
        ],
        [
            'title' => 'Mantención y reparación',
            'text' => 'Limpieza, cambio de piezas, actualización a SSD y recuperación de equipos lentos.',
            'icon' => 'Tools',
            'action' => 'support',
        ],
    ],

];

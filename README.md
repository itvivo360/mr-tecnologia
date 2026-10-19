# Tienda — ecommerce con soporte técnico y backoffice

Laravel 13 · Inertia 2 · Vue 3 · Element Plus · PostgreSQL · Mercado Pago

## Qué incluye

**Tienda (clientes)**
- Registro, inicio y cierre de sesión (con límite de intentos).
- Catálogo con búsqueda, categorías y orden; ficha de producto.
- Carrito persistido por usuario, validado contra el stock.
- Checkout con Mercado Pago Checkout Pro. Al crear la orden se **reserva el stock** por `ORDER_RESERVATION_MINUTES`; si el pago se rechaza o expira, se libera.
- Mis compras: estado de cada orden, reintento de pago mientras la reserva esté vigente.
- Soporte técnico: agenda de turnos por día/horario con cupos, motivo, compra/producto asociado y cancelación con anticipación mínima.

**Backoffice (`/admin`, rol `admin`)**
- Resumen: ventas del mes, stock bajo, próximos turnos, órdenes a revisar.
- Productos: alta, edición, imagen, activación, eliminación (soft delete: el historial se conserva).
- Stock: cada cambio queda en `stock_movements` con el saldo resultante (ingreso, salida, conteo de inventario, venta, liberación).
- **Escáner** (`/admin/escaner`): consultar, ingresar o descontar stock por escaneo, y asociar códigos a productos. Si un código no existe, permite crear el producto con el código precargado o asociarlo a uno existente.
- Órdenes y turnos de soporte (estado, técnico asignado, notas internas).

## Lector de código de barras

Funcionan los dos modos, sin drivers ni configuración:

| Modo | Cómo funciona |
|---|---|
| **USB / Bluetooth** | Los lectores se comportan como teclado (HID). `useBarcodeScanner` distingue un escaneo de la escritura humana por la velocidad entre teclas (< 50 ms) y el Enter final. En la pantalla del escáner no hace falta enfocar ningún campo. El lector debe estar configurado con sufijo **Enter** (es lo habitual de fábrica). |
| **Cámara** | `CameraScanner.vue` usa `@zxing/browser` (EAN-13/8, UPC, Code 128/39, ITF, QR). El navegador exige **HTTPS o localhost** para usar la cámara; para probar desde el celular usa un túnel HTTPS. |

Para lectores Bluetooth en celular: emparéjalo como teclado y abre `/admin/escaner` en el navegador del teléfono.

## Instalación

Requisitos: PHP 8.3+ (`pdo_pgsql`, `intl`, `fileinfo`), Composer, Node 20+, PostgreSQL 14+.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
# Configura DB_* en .env y crea la base de datos "tienda"
php artisan migrate --seed
php artisan storage:link
composer run dev     # servidor + cola + logs + Vite
```

Usuarios de prueba (seed):
- Admin: `admin@tienda.test` / `password`
- Cliente: `cliente@tienda.test` / `password`

El seed carga 13 productos con códigos EAN-13 válidos (prefijo 780) para probar el escáner.

### Tareas programadas

La expiración de órdenes impagas corre cada minuto con el scheduler:

```bash
php artisan schedule:work            # desarrollo
# producción (cron):
* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

## Pagos

`PAYMENT_GATEWAY` en `.env`:

- `fake` (por defecto, solo desarrollo): una pantalla local permite aprobar o rechazar el pago. Está bloqueado en `APP_ENV=production`.
- `mercadopago`: Checkout Pro vía API REST (sin SDK).

Configuración de Mercado Pago:

1. Crea una aplicación en el panel de desarrolladores y copia el **Access Token** (usa el de prueba primero) en `MERCADOPAGO_ACCESS_TOKEN`.
2. En *Webhooks*, configura la URL `https://TU_DOMINIO/webhooks/mercadopago` con el evento **Pagos**, y copia la clave secreta en `MERCADOPAGO_WEBHOOK_SECRET`.
3. En local, expone la app con un túnel HTTPS (ngrok, Cloudflare Tunnel) y pon esa URL en `APP_URL`; Mercado Pago no acepta `localhost` en `back_urls` ni en notificaciones.

Cómo se confirma un pago:

- El webhook valida la firma `x-signature` (HMAC-SHA256) y **siempre consulta el pago a la API**; nunca confía en el cuerpo de la notificación.
- Al volver del checkout, la página de la orden también sincroniza el pago con la API (por si el webhook aún no llegó).
- Se verifica que `external_reference`, monto y moneda coincidan con la orden. Procesar el mismo pago dos veces no tiene efecto.
- La preferencia expira junto con la reserva de stock. Si igualmente llega un pago aprobado tardío, se intenta volver a reservar; si no hay stock, la orden queda marcada **"requiere revisión"** en el backoffice.

## Estructura

```
app/
  Enums/                      OrderStatus, StockMovementType, AppointmentStatus
  Services/
    StockService.php          único punto que modifica stock (bloqueo de fila + movimiento)
    CartService.php
    CheckoutService.php       carrito → orden + reserva de stock + sesión de pago
    OrderPaymentService.php   aplica resultados de pago, cancela y expira (idempotente)
    AppointmentScheduler.php  bloques horarios, cupos y validaciones de la agenda
    Payments/                 PaymentGateway + MercadoPagoGateway + FakeGateway
  Http/Controllers/{Shop,Admin,Auth}
config/shop.php               moneda, pasarela, reserva, horarios de soporte
resources/js/
  Composables/useBarcodeScanner.js
  Components/CameraScanner.vue, BarcodeInput.vue, StockMoveDialog.vue
  Pages/Shop/*, Pages/Admin/*
```

Horarios de soporte (`.env`): `SUPPORT_OPEN_HOUR`, `SUPPORT_CLOSE_HOUR`, `SUPPORT_SLOT_MINUTES`, `SUPPORT_SLOT_CAPACITY` (técnicos simultáneos), `SUPPORT_WORKING_DAYS` (1 = lunes … 7 = domingo), `SUPPORT_BOOKING_HORIZON_DAYS`. Los motivos de consulta están en `config/shop.php`.

## Tests

```bash
php artisan test
```

Cubren checkout y reserva de stock, pagos aprobados/rechazados e idempotencia, expiración, escáner (búsqueda, ingreso/salida/conteo, unicidad de códigos) y agenda de soporte (cupos, horarios, cancelación).

## Para producción

- `PAYMENT_GATEWAY=mercadopago`, `APP_DEBUG=false`, HTTPS obligatorio (la cámara lo requiere).
- Cola y cache en Redis si hay varias instancias (los bloqueos de la agenda usan `Cache::lock`).
- Imágenes en S3: cambia `FILESYSTEM_DISK` y el disco `public` por uno S3.
- Pendiente según negocio: costos de envío, cupones, boleta/factura electrónica (SII), notificaciones por correo de órdenes y turnos.

<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\Shop\CartController;
use App\Http\Controllers\Shop\CatalogController;
use App\Http\Controllers\Shop\CheckoutController;
use App\Http\Controllers\Shop\FakePaymentController;
use App\Http\Controllers\Shop\OrderController;
use App\Http\Controllers\Shop\SupportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Sitio público
|--------------------------------------------------------------------------
*/
Route::get('/', HomeController::class)->name('home');
Route::post('/contacto', [ContactController::class, 'store'])->middleware('throttle:5,1')->name('contact.store');

/*
|--------------------------------------------------------------------------
| Tienda (público)
|--------------------------------------------------------------------------
*/
Route::get('/tienda', [CatalogController::class, 'index'])->name('shop.index');
Route::get('/productos/{product:slug}', [CatalogController::class, 'show'])->name('shop.product');

/*
|--------------------------------------------------------------------------
| Autenticación
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/registro', [RegisterController::class, 'create'])->name('register');
    Route::post('/registro', [RegisterController::class, 'store'])->middleware('throttle:5,1');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Cliente autenticado
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/carrito', [CartController::class, 'index'])->name('cart.index');
    Route::post('/carrito', [CartController::class, 'store'])->name('cart.store');
    Route::patch('/carrito/{item}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/carrito/{item}', [CartController::class, 'destroy'])->name('cart.destroy');

    Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
    Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('checkout.store');

    Route::get('/mis-compras', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/mis-compras/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/mis-compras/{order}/pagar', [OrderController::class, 'pay'])->name('orders.pay');

    Route::get('/soporte', [SupportController::class, 'index'])->name('support.index');
    Route::get('/soporte/horarios', [SupportController::class, 'slots'])->name('support.slots');
    Route::post('/soporte', [SupportController::class, 'store'])->middleware('throttle:10,1')->name('support.store');
    Route::post('/soporte/{appointment}/cancelar', [SupportController::class, 'cancel'])->name('support.cancel');

    // Pago simulado (PAYMENT_GATEWAY=fake). El controlador responde 404 con otra pasarela.
    Route::get('/pago-simulado/{order}', [FakePaymentController::class, 'show'])->name('payments.fake.show');
    Route::post('/pago-simulado/{order}', [FakePaymentController::class, 'complete'])->name('payments.fake.complete');
});

/*
|--------------------------------------------------------------------------
| Webhooks de pasarelas (sin sesión ni CSRF)
|--------------------------------------------------------------------------
*/
Route::post('/webhooks/mercadopago', [PaymentWebhookController::class, 'mercadopago'])
    ->withoutMiddleware(['web'])
    ->middleware('throttle:120,1')
    ->name('webhooks.mercadopago');

/*
|--------------------------------------------------------------------------
| Backoffice
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');

    // Escáner y stock (declaradas antes del resource para no chocar con {product})
    Route::get('/escaner', [Admin\StockController::class, 'scanner'])->name('scanner');
    Route::get('/productos/buscar-codigo', [Admin\StockController::class, 'lookup'])->name('products.lookup');
    Route::get('/productos/buscar', [Admin\StockController::class, 'search'])->name('products.search');
    Route::post('/productos/{product}/stock', [Admin\StockController::class, 'move'])->name('products.stock');
    Route::post('/productos/{product}/codigo', [Admin\StockController::class, 'assignBarcode'])->name('products.barcode');
    Route::post('/productos/{product}/destacar', [Admin\ProductController::class, 'toggleFeatured'])->name('products.featured');

    Route::resource('productos', Admin\ProductController::class)
        ->parameters(['productos' => 'product'])
        ->names('products');

    Route::get('/categorias', [Admin\CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categorias', [Admin\CategoryController::class, 'store'])->name('categories.store');
    Route::put('/categorias/{category}', [Admin\CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categorias/{category}', [Admin\CategoryController::class, 'destroy'])->name('categories.destroy');

    Route::get('/ordenes', [Admin\OrderController::class, 'index'])->name('orders.index');
    Route::get('/ordenes/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
    Route::post('/ordenes/{order}/cancelar', [Admin\OrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('/ordenes/{order}/revisada', [Admin\OrderController::class, 'resolveReview'])->name('orders.reviewed');

    Route::get('/mensajes', [Admin\ContactMessageController::class, 'index'])->name('messages.index');
    Route::post('/mensajes/{message}/leido', [Admin\ContactMessageController::class, 'toggleRead'])->name('messages.read');
    Route::delete('/mensajes/{message}', [Admin\ContactMessageController::class, 'destroy'])->name('messages.destroy');

    Route::get('/configuracion', [Admin\SettingsController::class, 'index'])->name('settings.index');
    Route::put('/configuracion/{section}', [Admin\SettingsController::class, 'update'])->name('settings.update');

    Route::get('/turnos', [Admin\AppointmentController::class, 'index'])->name('appointments.index');
    Route::put('/turnos/{appointment}', [Admin\AppointmentController::class, 'update'])->name('appointments.update');
});

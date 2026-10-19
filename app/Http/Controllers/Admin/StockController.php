<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StockController extends Controller
{
    public function __construct(private readonly StockService $stock)
    {
    }

    /** Pantalla del escáner (lector USB/Bluetooth o cámara). */
    public function scanner(): Response
    {
        return Inertia::render('Admin/Scanner');
    }

    /** Busca un producto por código de barras (o SKU como respaldo). */
    public function lookup(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:64']]);
        $code = trim($data['code']);

        $product = Product::with('category:id,name')
            ->where('barcode', $code)
            ->first()
            ?? Product::with('category:id,name')->where('sku', strtoupper($code))->first();

        return response()->json([
            'code' => $code,
            'product' => $product ? $this->present($product) : null,
        ]);
    }

    /** Registra un movimiento de stock: ingreso, salida o conteo. */
    public function move(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['in', 'out', 'count'])],
            'quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        if ($data['type'] !== 'count' && $data['quantity'] < 1) {
            throw ValidationException::withMessages(['quantity' => 'La cantidad debe ser al menos 1.']);
        }

        try {
            match ($data['type']) {
                'in' => $this->stock->receive($product, $data['quantity'], $request->user(), $data['reason'] ?? null),
                'out' => $this->stock->withdraw($product, $data['quantity'], $request->user(), $data['reason'] ?? null),
                'count' => $this->stock->setCounted($product, $data['quantity'], $request->user(), $data['reason'] ?? null),
            };
        } catch (InsufficientStockException $e) {
            throw ValidationException::withMessages(['quantity' => $e->getMessage()]);
        }

        $product->refresh();

        if ($request->wantsJson()) {
            return response()->json(['product' => $this->present($product)]);
        }

        return back()->with('success', "Stock actualizado: {$product->stock} unidades.");
    }

    /** Asocia un código de barras escaneado a un producto existente. */
    public function assignBarcode(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'barcode' => [
                'required', 'string', 'max:64', 'regex:/^[\x21-\x7E]+$/',
                Rule::unique('products', 'barcode')->ignore($product)->whereNull('deleted_at'),
            ],
        ], ['barcode.unique' => 'Ese código ya está asociado a otro producto.']);

        $product->update(['barcode' => trim($data['barcode'])]);

        return response()->json(['product' => $this->present($product->fresh('category'))]);
    }

    /** Búsqueda rápida de productos para asociar un código (autocompletado). */
    public function search(Request $request): JsonResponse
    {
        $data = $request->validate(['q' => ['required', 'string', 'max:100']]);

        return response()->json([
            'products' => Product::with('category:id,name')->search($data['q'])->orderBy('name')->limit(10)->get()
                ->map(fn (Product $p) => $this->present($p)),
        ]);
    }

    private function present(Product $product): array
    {
        return [
            ...$product->only(['id', 'name', 'sku', 'barcode', 'price', 'stock', 'min_stock', 'is_active', 'image_url']),
            'category' => $product->category?->name,
            'low_stock' => $product->isLowStock(),
        ];
    }
}

<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'barcode' => filled($this->barcode) ? trim((string) $this->barcode) : null,
            'sku' => filled($this->sku) ? strtoupper(trim((string) $this->sku)) : null,
        ]);
    }

    public function rules(): array
    {
        /** @var Product|null $product */
        $product = $this->route('product');
        $creating = $product === null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:64', 'regex:/^[A-Z0-9._-]+$/', Rule::unique('products', 'sku')->ignore($product)],
            'barcode' => [
                'nullable', 'string', 'max:64', 'regex:/^[\x21-\x7E]+$/',
                Rule::unique('products', 'barcode')->ignore($product)->whereNull('deleted_at'),
            ],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'integer', 'min:0', 'max:999999999'],
            'min_stock' => ['required', 'integer', 'min:0', 'max:1000000'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
            'remove_image' => ['boolean'],
            // El stock inicial solo se define al crear; luego se mueve con movimientos.
            'initial_stock' => [$creating ? 'nullable' : 'prohibited', 'integer', 'min:0', 'max:1000000'],
        ];
    }

    public function messages(): array
    {
        return [
            'barcode.unique' => 'Ese código de barras ya está asociado a otro producto.',
            'sku.unique' => 'Ese SKU ya existe.',
            'sku.regex' => 'El SKU solo admite letras, números, punto, guion y guion bajo.',
            'barcode.regex' => 'El código de barras contiene caracteres no válidos.',
        ];
    }
}

<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(StockService $stock): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Administrador',
            'email' => 'admin@tienda.test',
            'password' => 'password',
        ]);

        User::factory()->create([
            'name' => 'Cliente Demo',
            'email' => 'cliente@tienda.test',
            'password' => 'password',
        ]);

        $catalog = [
            'Computación' => [
                ['Notebook 14" Ryzen 5 16GB', 549990, 8],
                ['Monitor 27" IPS QHD', 229990, 12],
                ['Teclado mecánico TKL', 49990, 25],
                ['Mouse inalámbrico ergonómico', 24990, 40],
            ],
            'Redes' => [
                ['Router WiFi 6 AX3000', 79990, 15],
                ['Switch 8 puertos Gigabit', 29990, 3],
                ['Repetidor WiFi Mesh (pack 2)', 119990, 6],
            ],
            'Almacenamiento' => [
                ['SSD NVMe 1TB', 69990, 30],
                ['Disco externo 2TB USB-C', 64990, 4],
                ['Pendrive 128GB USB 3.2', 12990, 60],
            ],
            'Accesorios' => [
                ['Hub USB-C 7 en 1', 34990, 20],
                ['Audífonos Bluetooth ANC', 89990, 2],
                ['Webcam Full HD 1080p', 39990, 0],
            ],
        ];

        // Aparecen en la portada (se cambian desde el backoffice).
        $featured = ['Notebook 14" Ryzen 5 16GB', 'Router WiFi 6 AX3000', 'Monitor 27" IPS QHD', 'SSD NVMe 1TB', 'Repetidor WiFi Mesh (pack 2)', 'Audífonos Bluetooth ANC'];

        $n = 1;
        foreach ($catalog as $categoryName => $products) {
            $category = Category::create(['name' => $categoryName, 'slug' => Str::slug($categoryName)]);

            foreach ($products as [$name, $price, $qty]) {
                $product = Product::create([
                    'category_id' => $category->id,
                    'sku' => sprintf('PRD-%04d', $n),
                    'barcode' => $this->ean13(sprintf('780%09d', $n)), // 780 = prefijo GS1 Chile
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'description' => "{$name}. Producto de demostración con garantía de 6 meses y soporte técnico incluido.",
                    'price' => $price,
                    'min_stock' => 5,
                    'is_active' => true,
                    'is_featured' => in_array($name, $featured, true),
                ]);

                if ($qty > 0) {
                    $stock->receive($product, $qty, $admin, 'Stock inicial (seed)');
                }

                $n++;
            }
        }
    }

    /** Calcula el dígito verificador EAN-13 a partir de 12 dígitos. */
    private function ean13(string $digits12): string
    {
        $sum = 0;
        foreach (str_split($digits12) as $i => $d) {
            $sum += (int) $d * ($i % 2 === 0 ? 1 : 3);
        }

        return $digits12.((10 - $sum % 10) % 10);
    }
}

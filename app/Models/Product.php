<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'sku',
        'barcode',
        'name',
        'slug',
        'description',
        'price',
        'min_stock',
        'image_path',
        'is_active',
        'is_featured',
    ];

    // El stock NO es asignable masivamente: solo cambia a través de StockService,
    // que deja el movimiento registrado.

    protected $appends = ['image_url'];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'stock' => 'integer',
            'min_stock' => 'integer',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class)->latest('id');
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    public function isLowStock(): bool
    {
        return $this->stock <= $this->min_stock;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * SKU automático a partir del id (PRD-0014), igual que la numeración inicial.
     * Si ya existe (por ejemplo, alguien lo escribió a mano), agrega un sufijo.
     */
    public function assignGeneratedSku(): void
    {
        $base = sprintf('PRD-%04d', $this->getKey());
        $sku = $base;
        $n = 2;

        while (static::withTrashed()->where('sku', $sku)->whereKeyNot($this->getKey())->exists()) {
            $sku = "{$base}-{$n}";
            $n++;
        }

        $this->forceFill(['sku' => $sku])->save();
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('stock', '<=', 'min_stock');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $like = $query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        return $query->where(function (Builder $q) use ($term, $like) {
            $q->where('name', $like, "%{$term}%")
                ->orWhere('sku', $like, "%{$term}%")
                ->orWhere('barcode', $term);
        });
    }
}

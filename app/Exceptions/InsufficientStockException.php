<?php

namespace App\Exceptions;

use App\Models\Product;
use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(public readonly Product $product, public readonly int $requested)
    {
        parent::__construct(sprintf(
            'Stock insuficiente para "%s": disponible %d, solicitado %d.',
            $product->name,
            $product->stock,
            $requested,
        ));
    }
}

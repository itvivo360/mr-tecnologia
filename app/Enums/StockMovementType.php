<?php

namespace App\Enums;

enum StockMovementType: string
{
    case In = 'in';               // ingreso de mercadería
    case Out = 'out';             // salida manual (merma, devolución a proveedor...)
    case Adjustment = 'adjustment'; // ajuste por inventario
    case Sale = 'sale';           // reserva por orden
    case Release = 'release';     // liberación de reserva (pago fallido/expirado)

    public function label(): string
    {
        return match ($this) {
            self::In => 'Ingreso',
            self::Out => 'Salida',
            self::Adjustment => 'Ajuste',
            self::Sale => 'Venta',
            self::Release => 'Liberación',
        };
    }
}

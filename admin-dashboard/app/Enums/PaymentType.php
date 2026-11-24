<?php

namespace App\Enums;

enum PaymentType: string
{
    case Payment = 'payment';
    case Refund = 'refund';

    public function label(): string
    {
        return $this === self::Payment ? 'Pembayaran' : 'Refund';
    }
}

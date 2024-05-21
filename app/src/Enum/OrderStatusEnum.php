<?php

namespace App\Enum;

enum OrderStatusEnum: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case REFUNDED = 'refunded';
    case PARTIAL_REFUNDED = 'partial_refunded';

    public static function toArray(): array
    {
        return [
            self::PENDING,
            self::COMPLETED,
            self::REFUNDED,
            self::PARTIAL_REFUNDED,
        ];
    }

    public function toLabel(): string
    {
        return match ($this) {
            OrderStatusEnum::COMPLETED => 'Completed',
            OrderStatusEnum::REFUNDED => 'Refunded',
            OrderStatusEnum::PENDING => 'Pending',
            OrderStatusEnum::PARTIAL_REFUNDED => 'Partial Refunded',
        };
    }
}

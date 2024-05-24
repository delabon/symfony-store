<?php

namespace App\Event;

use App\Entity\OrderItem;
use Symfony\Contracts\EventDispatcher\Event;

class OrderItemRefundedEvent extends Event
{
    public const NAME = 'order_item.refunded';

    public function __construct(protected OrderItem $orderItem)
    {
    }

    public function getOrderItem(): OrderItem
    {
        return $this->orderItem;
    }
}
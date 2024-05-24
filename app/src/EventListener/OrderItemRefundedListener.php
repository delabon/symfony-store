<?php

namespace App\EventListener;

use App\Event\OrderItemRefundedEvent;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class OrderItemRefundedListener
{
    public function __construct(
        private readonly MailerInterface $mailer,
        #[Autowire('%app_support_email%')]
        private string $fromEmail
    )
    {
    }

    public function onOrderItemRefunded(OrderItemRefundedEvent $event): void
    {
        $orderItem = $event->getOrderItem();
        $order = $orderItem->getOrder();

        $email = (new TemplatedEmail())
            ->from($this->fromEmail)
            ->to($order->getEmail())
            ->subject('Your order item has been refunded')
            ->htmlTemplate('emails/partial_refund.html.twig')
            ->context([
                'order' => $order,
                'orderItem' => $orderItem,
            ]);

        $this->mailer->send($email);
    }
}
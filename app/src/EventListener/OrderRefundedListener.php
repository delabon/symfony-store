<?php

namespace App\EventListener;

use App\Event\OrderRefundedEvent;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class OrderRefundedListener
{
    public function __construct(
        private readonly MailerInterface $mailer,
        #[Autowire('%app_support_email%')]
        private string $fromEmail
    )
    {
    }

    public function onOrderRefunded(OrderRefundedEvent $event): void
    {
        $order = $event->getOrder();

        $email = (new TemplatedEmail())
            ->from($this->fromEmail)
            ->to($order->getEmail())
            ->subject('Your order has been refunded')
            ->htmlTemplate('emails/full_refund.html.twig')
            ->context([
                'order' => $order,
            ]);

        $this->mailer->send($email);
    }
}
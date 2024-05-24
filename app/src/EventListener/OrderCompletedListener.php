<?php

namespace App\EventListener;

use App\Event\OrderCompletedEvent;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class OrderCompletedListener
{
    public function __construct(
        private readonly MailerInterface $mailer,
        #[Autowire('%app_support_email%')]
        private string $fromEmail
    )
    {
    }

    public function onOrderCompleted(OrderCompletedEvent $event): void
    {
        $order = $event->getOrder();

        $email = (new TemplatedEmail())
            ->from($this->fromEmail)
            ->to($order->getEmail())
            ->subject('Your order receipt')
            ->htmlTemplate('emails/receipt.html.twig')
            ->context([
                'order' => $order,
            ]);

        $this->mailer->send($email);
    }
}
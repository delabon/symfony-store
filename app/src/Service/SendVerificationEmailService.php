<?php

namespace App\Service;

use App\Entity\User;
use App\Security\EmailVerifier;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mime\Address;

readonly class SendVerificationEmailService
{
    public function __construct(
        private EmailVerifier $emailVerifier,
        private Security $security,
        #[Autowire("%app_support_email%")]
        private string $supportEmail
    )
    {
    }

    public function send(): void
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return;
        }

        // generate a signed url and email it to the user
        $this->emailVerifier->sendEmailConfirmation(
            'app_verify_email',
            $user,
            (new TemplatedEmail())
                ->from(new Address($this->supportEmail, 'Support'))
                ->to($user->getEmail())
                ->subject('Please Confirm your Email')
                ->htmlTemplate('registration/confirmation_email.html.twig')
        );
    }
}
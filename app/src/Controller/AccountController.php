<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\AccountType;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class AccountController extends AbstractController
{
    #[Route('/account', name: 'app_account')]
    public function index(Request $request, EntityManagerInterface $entityManager, EmailVerifier $emailVerifier, Security $security): Response
    {
        $oldEmail = $this->getUser()->getEmail();
        $form = $this->createForm(AccountType::class, $this->getUser());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $form->getData();

            if ($user->getEmail() !== $oldEmail) {
                $this->handleEmailChange($user, $emailVerifier, $entityManager);
                $this->logoutAndInvalidateSession($request, $security);
                $this->addFlash('success', 'Account updated successfully.');
                $this->addFlash('info', 'You need to verify your email address. Please check your email for the verification link.');

                return $this->redirectToRoute('app_login');
            }

            $entityManager->flush();
            $this->addFlash('success', 'Account updated successfully.');
        }

        return $this->render('account/index.html.twig', [
            'form' => $form,
        ]);
    }

    private function handleEmailChange(User $user, EmailVerifier $emailVerifier, EntityManagerInterface $entityManager): void
    {
        $user->setVerified(false);
        $entityManager->flush();

        // generate a signed url and email it to the user
        $emailVerifier->sendEmailConfirmation('app_verify_email', $user,
            (new TemplatedEmail())
                ->from(new Address('support@crypto-store.test', 'Support'))
                ->to($user->getEmail())
                ->subject('Please Confirm your Email')
                ->htmlTemplate('registration/confirmation_email.html.twig')
        );
    }

    /**
     * @param Request $request
     * @param Security $security
     * @return void
     */
    protected function logoutAndInvalidateSession(Request $request, Security $security): void
    {
        $request->getSession()->invalidate();
        $security->logout(false);
    }
}

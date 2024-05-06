<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\AccountType;
use App\Service\LogoutUserService;
use App\Service\SendVerificationEmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class AccountController extends AbstractController
{
    public function __construct(
        private readonly LogoutUserService $logoutAndInvalidateSessionService,
        private readonly SendVerificationEmailService $sendVerificationEmailService
    )
    {
    }

    #[Route('/account', name: 'app_account')]
    public function index(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $oldEmail = $this->getUser()->getEmail();
        $form = $this->createForm(AccountType::class, $this->getUser());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $form->getData();
            $newPassword = $form->get('passwordMatch')->getData();

            if (!empty($newPassword)) {
                $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            }

            if ($user->getEmail() !== $oldEmail) {
                $user->setVerified(false);
                $entityManager->flush();
                $this->sendVerificationEmailService->send();
                $this->logoutAndInvalidateSessionService->handle();
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
}

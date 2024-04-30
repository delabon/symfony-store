<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users', name: 'admin_user_')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('admin/user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/ban/{id<\d+>}', name: 'ban')]
    public function ban(User $user, EntityManagerInterface $entityManager): Response
    {
        $user->setBanned(true);
        $entityManager->flush();
        $this->addFlash('success', 'User has been banned.');

        // Destroy the user's session if they are banned.


        return $this->redirectToRoute('admin_user_index');
    }

    #[Route('/unban/{id<\d+>}', name: 'unban')]
    public function unban(User $user, EntityManagerInterface $entityManager): Response
    {
        $user->setBanned(false);
        $entityManager->flush();
        $this->addFlash('success', 'User has been unbanned.');

        return $this->redirectToRoute('admin_user_index');
    }
}

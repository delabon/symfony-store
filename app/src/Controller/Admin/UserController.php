<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users', name: 'admin_user_')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(Request $request, UserRepository $userRepository): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = (int)$this->getParameter('app_admin_per_page');
        $paginator = $userRepository->paginate($page, $limit);

        return $this->render('admin/user/index.html.twig', [
            'users' => $paginator,
            'maxPages' => ceil($paginator->count() / $limit),
            'page' => $page,
        ]);
    }

    #[Route('/ban/{id<\d+>}', name: 'ban')]
    public function ban(User $user, EntityManagerInterface $entityManager): Response
    {
        // Don't ban admin
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            $this->addFlash('danger', 'You cannot ban an admin.');

            return $this->redirectToRoute('admin_user_index');
        }

        $user->setBanned(true);
        $entityManager->flush();
        $this->addFlash('success', 'User has been banned.');

        return $this->redirectToRoute('admin_user_index');
    }

    #[Route('/unban/{id<\d+>}', name: 'unban')]
    public function unban(User $user, EntityManagerInterface $entityManager): Response
    {
        // Don't unban admin
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            $this->addFlash('danger', 'You cannot unban an admin.');

            return $this->redirectToRoute('admin_user_index');
        }

        $user->setBanned(false);
        $entityManager->flush();
        $this->addFlash('success', 'User has been unbanned.');

        return $this->redirectToRoute('admin_user_index');
    }
}

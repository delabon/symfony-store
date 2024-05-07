<?php

namespace App\Controller\Admin;

use App\Entity\Page;
use App\Form\PageType;
use App\Repository\PageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/pages', name: 'admin_page_')]
#[isGranted('ROLE_ADMIN')]
class PageController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(Request $request, PageRepository $pageRepository): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = $this->getParameter('app_admin_per_page');
        $paginator = $pageRepository->paginate($page, $limit);

        return $this->render('admin/page/index.html.twig', [
            'pages' => $paginator,
            'maxPages' => ceil($paginator->count() / $limit),
            'page' => $page,
        ]);
    }

    #[Route('/create', name: 'create')]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PageType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $page = $form->getData();
            $entityManager->persist($page);
            $entityManager->flush();
            $this->addFlash('success', 'You page has been created.');

            return $this->redirectToRoute('admin_page_index');
        }


        return $this->render('admin/page/create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/edit/{id<\d+>}', name: 'edit')]
    public function edit(Page $page, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PageType::class, $page);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'You page has been updated.');

            return $this->redirectToRoute('admin_page_index');
        }


        return $this->render('admin/page/edit.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/delete/{id<\d+>}', name: 'delete', methods: ['DELETE'])]
    public function delete(Page $page, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($page);
        $entityManager->flush();

        $this->addFlash('success', 'You page has been deleted.');

        return $this->redirectToRoute('admin_page_index');
    }
}

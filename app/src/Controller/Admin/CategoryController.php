<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/categories', name: 'admin_category_')]
#[IsGranted('ROLE_ADMIN')]
class CategoryController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(Request $request, CategoryRepository $categoryRepository): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = (int)$this->getParameter('app_admin_per_page');
        $paginator = $categoryRepository->paginate($page, $limit);

        return $this->render('admin/category/index.html.twig', [
            'categories' => $paginator,
            'maxPages' => ceil($paginator->count() / $limit),
            'page' => $page,
        ]);
    }

    #[Route('/create', name: 'create')]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($form->getData());
            $entityManager->flush();
            $this->addFlash('success', 'Your category has been added.');

            return $this->redirectToRoute('admin_category_index');
        }

        return $this->render('admin/category/create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/edit/{id<\d+>}', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Category $category, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Your category has been updated.');

            return $this->redirectToRoute('admin_category_index');
        }

        return $this->render('admin/category/edit.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/delete/{id<\d+>}', name: 'delete', methods: ['DELETE'])]
    public function delete(Category $category, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($category);
        $entityManager->flush();
        $this->addFlash('success', 'Your category has been deleted.');

        return $this->redirectToRoute('admin_category_index');
    }
}

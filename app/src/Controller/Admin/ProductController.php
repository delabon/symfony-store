<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/products', name: 'admin_product_')]
#[IsGranted('ROLE_ADMIN')]
class ProductController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(Request $request, ProductRepository $productRepository): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = (int)$this->getParameter('app_admin_per_page');
        $paginator = $productRepository->paginate($page, $limit);

        return $this->render('admin/product/index.html.twig', [
            'products' => $paginator,
            'maxPages' => ceil($paginator->count() / $limit),
            'page' => $page,
        ]);
    }

    #[Route('/create', name: 'create')]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($form->getData());
            $entityManager->flush();
            $this->addFlash('success', 'Your product has been added.');

            return $this->redirectToRoute('admin_product_index');
        }

        return $this->render('admin/product/create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/edit/{id<\d+>}', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Product $product, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Your product has been updated.');

            return $this->redirectToRoute('admin_product_index');
        }

        return $this->render('admin/product/edit.html.twig', [
            'form' => $form,
            'product' => $product,
        ]);
    }

    #[Route('/delete/{id<\d+>}', name: 'delete', methods: ['DELETE'])]
    public function delete(Product $product, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($product);
        $entityManager->flush();
        $this->addFlash('success', 'Your product has been deleted.');

        return $this->redirectToRoute('admin_product_index');
    }
}

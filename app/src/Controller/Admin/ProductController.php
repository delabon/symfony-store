<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Event\ProductThumbnailUploadedEvent;
use App\Form\ProductType;
use App\Repository\FileRepository;
use App\Repository\ProductRepository;
use App\Service\ThumbnailService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/products', name: 'admin_product_')]
#[IsGranted('ROLE_ADMIN')]
class ProductController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    )
    {
    }

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
    public function create(Request $request, ThumbnailService $thumbnailService): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($form->getData());
            $this->entityManager->flush();
            $this->uploadThumbnail($form, $thumbnailService, $product);
            $this->addFlash('success', 'Your product has been added.');

            return $this->redirectToRoute('admin_product_index');
        }

        return $this->render('admin/product/create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/edit/{id<\d+>}', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Product $product, Request $request, ThumbnailService $thumbnailService, FileRepository $fileRepository): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush(); // needs to save the product's data first
            $thumbnailService->delete($fileRepository->find($product->getThumbnailId()));
            $this->uploadThumbnail($form, $thumbnailService, $product);
            $this->addFlash('success', 'Your product has been updated.');

            return $this->redirectToRoute('admin_product_index');
        }

        return $this->render('admin/product/edit.html.twig', [
            'form' => $form,
            'product' => $product,
            'thumbnail' => $thumbnailService->getUrl($product->getThumbnailId()),
        ]);
    }

    #[Route('/delete/{id<\d+>}', name: 'delete', methods: ['DELETE'])]
    public function delete(Product $product, ThumbnailService $thumbnailService, FileRepository $fileRepository): Response
    {
        $thumbnailService->delete($fileRepository->find($product->getThumbnailId()));
        $this->entityManager->remove($product);
        $this->entityManager->flush();
        $this->addFlash('success', 'Your product has been deleted.');

        return $this->redirectToRoute('admin_product_index');
    }

    /**
     * @param FormInterface $form
     * @param ThumbnailService $thumbnailService
     * @param Product $product
     * @return void
     */
    protected function uploadThumbnail(
        FormInterface $form,
        ThumbnailService $thumbnailService,
        Product $product
    ): void {
        if ($form->has('thumbnailFile') && $form->get('thumbnailFile')->getData()) {
            try {
                $thumbnailId = $thumbnailService->upload($form->get('thumbnailFile')->getData());
                $product->setThumbnailId($thumbnailId);
                $this->entityManager->flush();
            } catch (Exception $e) {
                $this->addFlash('error', 'Could not upload the thumbnail.');
            }
        }
    }
}

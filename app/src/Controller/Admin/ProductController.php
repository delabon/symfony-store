<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\FileRepository;
use App\Repository\ProductRepository;
use App\Service\BaseUrlService;
use App\Service\FileUploaderService;
use App\Service\ThumbnailService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
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
        private readonly FileUploaderService $fileUploaderService,
        private readonly ThumbnailService $thumbnailService
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
    public function create(
        Request $request
    ): Response
    {
        $form = $this->createForm(ProductType::class, new Product());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Product $product */
            $product = $form->getData();

            try {
                $fileIds = $this->uploadFiles($form);

                if ($fileIds === []) {
                    throw new InvalidArgumentException('Please upload at least one file.');
                }

                $product->setFiles($fileIds);
                $this->entityManager->persist($product);
                $this->entityManager->flush();
                $this->uploadThumbnail($form, $product);
                $this->addFlash('success', 'Your product has been added.');

                return $this->redirectToRoute('admin_product_index');
            } catch (FileException $e) {
                $this->addFlash('error', 'Could not upload the files: ' . $e->getMessage());

                return $this->redirectToRoute('admin_product_create');
            } catch (InvalidArgumentException $e) {
                $this->addFlash('error', 'Please upload at least one file.');

                return $this->redirectToRoute('admin_product_create');
            }
        }

        return $this->render('admin/product/create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/edit/{id<\d+>}', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        Product $product,
        Request $request,
        FileRepository $fileRepository,
        BaseUrlService $baseUrlService,
    ): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);
        $currentFiles = $product->getFiles();

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->entityManager->flush(); // needs to save the product's data first

                if ($form->has('thumbnailFile') && $form->get('thumbnailFile')->getData()) {
                    $this->thumbnailService->delete($fileRepository->find($product->getThumbnailId() ?: 0));
                    $this->uploadThumbnail($form, $product);
                }

                $fileIds = $this->uploadFiles($form);

                if ($fileIds === [] && !count($product->getFiles())) {
                    throw new InvalidArgumentException('Please upload at least one file.');
                }

                $product->setFiles([...$product->getFiles(), ...$fileIds]);
                $this->entityManager->flush();

                $this->addFlash('success', 'Your product has been updated.');

                return $this->redirectToRoute('admin_product_index');
            } catch (FileException $e) {
                $this->addFlash('error', 'Could not upload the files: ' . $e->getMessage());

                return $this->redirectToRoute('admin_product_edit', ['id' => $product->getId()]);
            } catch (InvalidArgumentException $e) {
                $this->addFlash('error', 'Please upload at least one file.');

                return $this->redirectToRoute('admin_product_edit', ['id' => $product->getId()]);
            }
        }

        return $this->render('admin/product/edit.html.twig', [
            'form' => $form,
            'product' => $product,
            'thumbnail' => $this->thumbnailService->getUrl($product->getThumbnailId() ?: 0),
            'currentFiles' => $fileRepository->findBy(['id' => $currentFiles]),
            'uploadDirUrl' => $baseUrlService->getBaseUrl() . '/' . explode('/public/', $this->getParameter('app_uploads_dir'))[1]
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

    #[Route('/delete/product/{id<\d+>}/file/{fileId<\d+>}', name: 'delete_file', methods: ['DELETE'])]
    public function deleteProductFile(Product $product, int $fileId): JsonResponse
    {
        $files = array_filter($product->getFiles(), fn ($itemId) => $fileId !== $itemId);
        $product->setFiles($files);
        $this->entityManager->flush();

        return $this->json(true);
    }

    /**
     * @param FormInterface $form
     * @param ThumbnailService $thumbnailService
     * @param Product $product
     * @return void
     */
    protected function uploadThumbnail(
        FormInterface $form,
        Product $product
    ): void {
        if ($form->has('thumbnailFile') && $form->get('thumbnailFile')->getData()) {
            try {
                $thumbnailId = $this->thumbnailService->upload($form->get('thumbnailFile')->getData());
                $product->setThumbnailId($thumbnailId);
                $this->entityManager->flush();
            } catch (Exception $e) {
                $this->addFlash('error', 'Could not upload the thumbnail.');
            }
        }
    }

    private function uploadFiles(FormInterface $form): array
    {
        if ($form->has('productFiles') && $form->get('productFiles')->getData()) {
            try {
                $fileIds = [];

                foreach ($form->get('productFiles')->getData() as $file) {
                    $fileIds[] = $this->fileUploaderService->upload($file);
                }

                return $fileIds;
            } catch (Exception $e) {
                $this->addFlash('error', 'Could not upload the files: ' . $e->getMessage());
            }
        }

        return [];
    }
}

<?php

namespace App\Controller;

use App\Entity\OrderItem;
use App\Repository\FileRepository;
use App\Service\FileDownloaderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FileDownloadController extends AbstractController
{
    public function __construct(
        private readonly FileRepository $fileRepository,
        private readonly FileDownloaderService $fileDownloaderService
    )
    {
    }

    #[Route('/download/{id<\d+>}/file/{fileId<\d+>}', name: 'app_download_order_item_file')]
    public function downloadOrderItemFile(
        OrderItem $orderItem,
        int $fileId
    ): Response
    {
        if ($orderItem->getOrder()->getCustomer() != $this->getUser()) {
            return new Response('You cannot access this order\'s files.', Response::HTTP_FORBIDDEN);
        }

        if ($orderItem->isRefunded()) {
            return new Response('You cannot download the file because the item is refunded.', Response::HTTP_FORBIDDEN);
        }

        $file = $this->fileRepository->find($fileId);

        if (!$file) {
            return new Response('File does not exist', Response::HTTP_NOT_FOUND);
        }

        return $this->fileDownloaderService->download($file);
    }
}

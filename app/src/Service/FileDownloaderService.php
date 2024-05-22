<?php

namespace App\Service;

use App\Entity\File;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class FileDownloaderService
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
        #[Autowire('%app_uploads_dir%')]
        private string $uploadsDir
    )
    {
    }

    public function download(File $file): Response
    {
        // Assuming the File entity has a method getPath() that returns the full path to the file
        $filePath = $this->projectDir . $this->uploadsDir . '/' . $file->getName();

        if (!file_exists($filePath)) {
            return new Response('File not found.', Response::HTTP_NOT_FOUND);
        }

        // Create a BinaryFileResponse instance with the file path
        $response = new BinaryFileResponse($filePath);

        $fileInfo = pathinfo($filePath);
        $ext = $fileInfo['extension'];

        // Set the Content-Disposition header to attachment to trigger a file download
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            uniqid() . '.' . $ext
        );

        return $response;
    }
}
<?php

namespace App\Service;

use App\Entity\File;
use App\Repository\FileRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ThumbnailService
{
    public function __construct(
        private readonly Security $security,
        private readonly FileRepository $fileRepository,
        private readonly ParameterBagInterface $parameterBag,
        private readonly BaseUrlService $baseUrlService,
        private string $uploadsDir = ''
    )
    {
        $this->uploadsDir = $this->parameterBag->get('kernel.project_dir') . '/' . trim($this->parameterBag->get('app_uploads_dir'), '/') . '/';
    }

    public function getUrl(int $id): ?string
    {
        $file = $this->fileRepository->find($id);

        if (!$file) {
            return null;
        }

        return $this->baseUrlService->getBaseUrl() . '/' . explode('/public/', $this->uploadsDir)[1] . $file->getName();
    }

    /**
     * @throws FileException
     */
    public function upload(UploadedFile $file): int
    {
        $filename = hash('md5', $file->getClientOriginalName() . '-' . uniqid()) . '.' . $file->getClientOriginalExtension();

        try {
            $file->move(
                $this->uploadsDir,
                $filename
            );
        } catch (FileException $e) {
            throw new FileException('An error occurred while uploading the file: ' . $e->getMessage());
        }

        $sizes = $this->createSizes($filename);

        return $this->fileRepository->saveFile($filename, $this->security->getUser(), $sizes);
    }

    private function createSizes(string $filename): array
    {
        return [];
    }

    public function delete(?File $file): void
    {
        if (!($file instanceof File)) {
            return;
        }

        $path = $this->uploadsDir . $file->getName();

        if (file_exists($path)) {
            unlink($path);
        }

        $this->fileRepository->removeFile($file);
    }
}
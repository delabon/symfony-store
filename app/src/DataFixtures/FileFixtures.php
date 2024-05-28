<?php

namespace App\DataFixtures;

use App\Abstract\AbstractFixture;
use App\Repository\FileRepository;
use App\Service\ThumbnailService;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Smknstd\FakerPicsumImages\FakerPicsumImagesProvider;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileFixtures extends AbstractFixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly ThumbnailService $thumbnailService,
        private readonly FileRepository $fileRepository,
        private string $uploadsDir = ''
    )
    {
        parent::__construct();
        $this->faker->addProvider(new FakerPicsumImagesProvider($this->faker));
        $this->uploadsDir = $this->parameterBag->get('kernel.project_dir') . '/' . trim($this->parameterBag->get('app_uploads_dir'), '/') . '/';
    }

    public function load(ObjectManager $manager): void
    {
        $this->deleteOldFiles();

        for ($i = 0; $i < 30; $i++) {
            $image = $this->faker->image(dir: '/tmp', width: 1000);

            if (!$image) {
                continue;
            }

            $mime = mime_content_type($image);
            $uploadedFile = new UploadedFile(path: $image, originalName: basename($image), mimeType: $mime, test: true);
            $fileId = $this->thumbnailService->upload($uploadedFile, $this->getReference('admin'));
            $file = $this->fileRepository->find($fileId);
            $this->setReference('image_' . $i, $file);
        }
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class
        ];
    }

    private function deleteOldFiles(): void
    {
        $files = scandir($this->uploadsDir);

        foreach ($files as $filename) {
            $filepath = $this->uploadsDir . $filename;

            if (!is_file($filepath)) {
                continue;
            }

            unlink($filepath);
        }
    }
}

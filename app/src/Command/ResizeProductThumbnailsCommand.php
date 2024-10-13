<?php

namespace App\Command;

use App\Entity\File;
use App\Repository\FileRepository;
use App\Repository\ProductRepository;
use App\Service\ImageCroppingService;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[AsCommand(
    name: 'app:resize-product-thumbnails',
    description: 'Add a short description for your command',
)]
class ResizeProductThumbnailsCommand extends Command
{
    private string $uploadsDir;

    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly FileRepository $fileRepository,
        private readonly ImageCroppingService $imageCroppingService,
        private readonly ParameterBagInterface $parameterBag,
        private readonly Filesystem $filesystem
    )
    {
        $this->uploadsDir = $this->parameterBag->get('kernel.project_dir') . '/' . trim($this->parameterBag->get('app_uploads_dir'), '/') . '/';

        if (!is_dir($this->uploadsDir)) {
            throw new RuntimeException('Uploads directory does not exist');
        }

        if (!is_writable($this->uploadsDir)) {
            throw new RuntimeException('Uploads directory is not writable');
        }

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            // the command description shown when running "php bin/console list"
            ->setDescription('Regenerate thumbnails for products')
            // the command help shown when running the command with the "--help" option
            ->setHelp('This command allows you to regenerate thumbnails for products')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        foreach ($this->productRepository->findAll() as $product) {
            $file = $this->fileRepository->find($product->getThumbnailId());

            if (!$file) {
                continue;
            }

            $this->deleteFileSizes($file);

            try {
                $sizes = $this->imageCroppingService->createSizes($file->getName(), $this->uploadsDir);
                $this->fileRepository->updateSizes($file, $sizes);
            } catch (FileException $e) {
                throw new FileException('An error occurred while uploading the file: ' . $e->getMessage());
            }
        }

        $io->success('Completed successfully.');

        return Command::SUCCESS;
    }

    private function deleteFileSizes(File $file): void
    {
        foreach ($file->getSizes() as $size) {
            try {
                if ($this->filesystem->exists($size['path'])) {
                    $this->filesystem->remove($size['path']);
                }
            } catch (IOExceptionInterface $e) {
                throw new RuntimeException(sprintf('An error occurred while deleting the file at %s', $size['path']));
            }
        }
    }
}

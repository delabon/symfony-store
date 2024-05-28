<?php

namespace App\DataFixtures;

use App\Abstract\AbstractFixture;
use App\Entity\File;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Smknstd\FakerPicsumImages\FakerPicsumImagesProvider;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class FileFixtures extends AbstractFixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private string $uploadsDir = ''
    )
    {
        parent::__construct();
        $this->faker->addProvider(new FakerPicsumImagesProvider($this->faker));
        $this->uploadsDir = $this->parameterBag->get('kernel.project_dir') . '/' . trim($this->parameterBag->get('app_uploads_dir'), '/') . '/';
    }

    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < 30; $i++) {
            $image = $this->faker->image(dir: $this->uploadsDir, width: 1000);

            if (!$image) {
                continue;
            }

            $file = new File();
            $file->setUser($this->getReference('admin'));
            $file->setName(basename($image));
            $file->setUpdatedAt(new \DateTimeImmutable());
            $file->setCreatedAt(new \DateTimeImmutable());
            $manager->persist($file);
            $this->setReference('image_' . $i, $file);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class
        ];
    }
}

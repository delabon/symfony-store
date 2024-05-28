<?php

namespace App\DataFixtures;

use App\Abstract\AbstractFixture;
use App\Entity\Product;
use App\Enum\EntityStatusEnum;
use DateTimeImmutable;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < 30; $i++) {
            $product = new Product();
            $product->setName($this->faker->sentence());
            $product->setSlug($this->faker->slug());
            $product->setDescription(implode("\r\n\r\n", $this->faker->paragraphs(4)));
            $product->setPrice(mt_rand(1000, 10000) / 100);
            $product->setSalePrice($product->getPrice() - 0.1);
            $date = DateTimeImmutable::createFromMutable($this->faker->dateTime());
            $product->setCreatedAt($date);
            $product->setUpdatedAt($date);
            $product->setUser($this->getReference('admin'));
            $product->setCategory($this->getReference('category_' . mt_rand(0, 4)));
            $product->setStatus(EntityStatusEnum::toArray()[array_rand(EntityStatusEnum::toArray())]);

            if ($this->getReference('image_' . $i)) {
                $product->setThumbnailId($this->getReference('image_' . $i)->getId());
            }

            $manager->persist($product);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            CategoryFixtures::class,
            FileFixtures::class
        ];
    }
}

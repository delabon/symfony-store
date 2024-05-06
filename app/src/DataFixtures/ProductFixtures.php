<?php

namespace App\DataFixtures;

use App\Entity\Product;
use App\Enum\ProductStatusEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < 30; $i++) {
            $product = new Product();
            $product->setName("Product {$i}");
            $product->setSlug("product-{$i}");
            $product->setDescription("Description for product {$i}");
            $product->setPrice(mt_rand(1000, 10000) / 100);
            $product->setSalePrice($product->getPrice() - 0.1);
            $product->setCreatedAt(new \DateTimeImmutable());
            $product->setUpdatedAt(new \DateTimeImmutable());
            $product->setUser($this->getReference('user_' . $i));
            $product->setCategory($this->getReference('category_' . mt_rand(0, 4)));
            $product->setStatus(ProductStatusEnum::toArray()[array_rand(ProductStatusEnum::toArray())]);
            $manager->persist($product);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            CategoryFixtures::class,
        ];
    }
}

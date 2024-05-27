<?php

namespace App\DataFixtures;

use App\Entity\Product;
use App\Enum\EntityStatusEnum;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;

class ProductFixtures extends Fixture implements DependentFixtureInterface
{
    private Generator $faker;

    public function __construct()
    {
        $this->faker = Factory::create();
    }

    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < 30; $i++) {
            $product = new Product();
            $product->setName($this->faker->name());
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

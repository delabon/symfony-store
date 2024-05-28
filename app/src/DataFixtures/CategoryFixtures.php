<?php

namespace App\DataFixtures;

use App\Abstract\AbstractFixture;
use App\Entity\Category;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class CategoryFixtures extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < 45; $i++) {
            $category = new Category();
            $category->setUser($this->getReference('admin'));
            $category->setName($this->faker->sentence(rand(2, 4)));
            $category->setSlug("category-{$i}");
            $category->setCreatedAt(new \DateTimeImmutable());
            $category->setUpdatedAt(new \DateTimeImmutable());
            $manager->persist($category);
            $this->addReference("category_{$i}", $category);
        }

        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            UserFixtures::class
        ];
    }
}

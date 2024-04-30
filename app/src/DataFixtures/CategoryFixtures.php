<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CategoryFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < 45; $i++) {
            $category = new Category();
            $category->setName("Category {$i}");
            $category->setSlug("category-{$i}");
            $category->setCreatedAt(new \DateTimeImmutable());
            $category->setUpdatedAt(new \DateTimeImmutable());
            $manager->persist($category);
            $this->addReference("category_{$i}", $category);
        }

        $manager->flush();
    }
}

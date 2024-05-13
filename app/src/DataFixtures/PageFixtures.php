<?php

namespace App\DataFixtures;

use App\Entity\Page;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class PageFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 1; $i <= 25; $i++) {
            $page = new Page();
            $page->setName('Page ' . $i);
            $page->setSlug('page-' . $i);
            $page->setContent(
                'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam nec purus ut nunc vestibulum ultricies. Nullam nec purus ut nunc vestibulum ultricies. Nullam nec purus ut nunc vestibulum ultricies. Nullam nec purus ut nunc vestibulum ultricies.'
            );
            $page->setCreatedAt(new \DateTimeImmutable());
            $page->setUpdatedAt(new \DateTimeImmutable());
            $page->setUser($this->getReference('admin'));
            $manager->persist($page);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}

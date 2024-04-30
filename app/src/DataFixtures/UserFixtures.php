<?php

namespace App\DataFixtures;

use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        #[Autowire('%app_domain%')]
        private readonly string $appDomain,
        #[Autowire('%app_admin_email%')]
        private readonly string $adminEmail,
        #[Autowire('%app_admin_password%')]
        private readonly string $adminPassword,
        #[Autowire('%app_admin_username%')]
        private readonly string $adminUsername
    )
    {
    }

    public function load(ObjectManager $manager): void
    {
        // Add admin user
        $admin = new User();
        $admin->setEmail($this->adminEmail);
        $admin->setUsername($this->adminUsername);
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, $this->adminPassword));
        $admin->setCreatedAt(new DateTimeImmutable());
        $admin->setUpdatedAt(new DateTimeImmutable());
        $admin->setVerified(true);
        $manager->persist($admin);

        // Add regular users
        for ($i = 0; $i < 30; $i++) {
            $user = new User();
            $user->setEmail("user{$i}@{$this->appDomain}");
            $user->setUsername("user{$i}");
            $user->setRoles([]);
            $user->setPassword($this->passwordHasher->hashPassword($user, '12345'));
            $user->setCreatedAt(new DateTimeImmutable());
            $user->setUpdatedAt(new DateTimeImmutable());
            $user->setVerified(true);
            $manager->persist($user);
            $this->addReference("user_{$i}", $user);
        }

        $manager->flush();
    }
}

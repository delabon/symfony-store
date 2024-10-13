<?php

namespace App\Repository;

use App\Entity\File;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<File>
 *
 * @method File|null find($id, $lockMode = null, $lockVersion = null)
 * @method File|null findOneBy(array $criteria, array $orderBy = null)
 * @method File[]    findAll()
 * @method File[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, File::class);
    }

    public function saveFile(string $path, User $user, array $sizes): int
    {
        $file = new File();
        $file->setUser($user);
        $file->setName($path);
        $file->setSizes($sizes);
        $file->setCreatedAt(new DateTimeImmutable());
        $file->setUpdatedAt(new DateTimeImmutable());

        $this->getEntityManager()->persist($file);
        $this->getEntityManager()->flush();

        return $file->getId();
    }

    public function updateSizes(File $file, array $sizes): void
    {
        $file->setSizes($sizes);
        $this->getEntityManager()->flush();
    }

    public function removeFile(File $file): void
    {
        $this->getEntityManager()->remove($file);
        $this->getEntityManager()->flush();
    }
}

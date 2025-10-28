<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    /**
     * UserRepository
     *
     * Repositorio de usuarios. Contiene helper methods:
     * - `upgradePassword()` necesario para la interfaz `PasswordUpgraderInterface`.
     * - `findByEmailOrUsername()` para login con email o username.
     * - `findActiveSubscribers()` para obtener usuarios suscritos al boletín.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function findByEmailOrUsername(string $identifier): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.email = :identifier')
            ->orWhere('u.username = :identifier')
            ->setParameter('identifier', $identifier)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findActiveSubscribers(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.isSubscribedToNewsletter = :subscribed')
            ->andWhere('u.isActive = :active')
            ->setParameter('subscribed', true)
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();
    }
}
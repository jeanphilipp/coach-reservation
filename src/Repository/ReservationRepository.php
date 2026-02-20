<?php

namespace App\Repository;

use App\Entity\Reservation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    public function save(Reservation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Reservation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return Reservation[] Réservations à venir (aujourd'hui inclus), triées par date/heure.
     */
    public function findUpcomingByUser(User $user): array
    {
        $today = new \DateTimeImmutable('today');

        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->andWhere('r.sessionDate >= :today')
            ->setParameter('user', $user)
            ->setParameter('today', $today)
            ->orderBy('r.sessionDate', 'ASC')
            ->addOrderBy('r.startHour', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Reservation[] Réservations passées, triées par date/heure décroissantes.
     */
    public function findPastByUser(User $user, int $limit = 50): array
    {
        $today = new \DateTimeImmutable('today');

        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->andWhere('r.sessionDate < :today')
            ->setParameter('user', $user)
            ->setParameter('today', $today)
            ->orderBy('r.sessionDate', 'DESC')
            ->addOrderBy('r.startHour', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Reservation[] Réservations pour un jour donné (utile pour le planning).
     */
    public function findForDate(\DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.sessionDate = :date')
            ->setParameter('date', $date)
            ->orderBy('r.startHour', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve une réservation pour un créneau précis (anti-doublon).
     */
    public function findOneBySlot(\DateTimeInterface $date, int $startHour): ?Reservation
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.sessionDate = :date')
            ->andWhere('r.startHour = :hour')
            ->setParameter('date', $date)
            ->setParameter('hour', $startHour)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Reservation[] Réservations en attente (admin).
     */
    public function findPending(): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.status = :status')
            ->setParameter('status', 'PENDING')
            ->orderBy('r.sessionDate', 'ASC')
            ->addOrderBy('r.startHour', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Reservation[]
     */
    public function findByUserOrdered(\App\Entity\User $user): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.sessionDate', 'DESC')
            ->addOrderBy('r.startHour', 'DESC')
            ->getQuery()
            ->getResult();
    }


}

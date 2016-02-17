<?php
namespace Ecom\Terminal\Domain\Repository;

/*
 * This file is part of the Ecom.Terminal package.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\Repository;
use TYPO3\Flow\Persistence\QueryResultInterface;

/**
 * @Flow\Scope("singleton")
 */
class AppointmentRepository extends Repository
{

    protected $defaultOrderings = [
        'starttime' => \TYPO3\Flow\Persistence\QueryInterface::ORDER_DESCENDING
    ];

    /**
     * @param \DateTimeZone $dateTimeZone
     * @param boolean       $includeDisabled
     * @return QueryResultInterface
     */
    public function findActive(\DateTimeZone $dateTimeZone, $includeDisabled = true)
    {
        $query = $this->createQuery();

        $constraint = $query->greaterThan('endtime', new \DateTime('now', $dateTimeZone));
        if ($includeDisabled === false) {
            $constraint = $query->logicalAnd([
                $query->greaterThan('endtime', new \DateTime('now', $dateTimeZone)),
                $query->equals('disabled', false)
            ]);
        }

        return $query->matching($constraint)->execute();
    }

    /**
     * @param \DateTimeZone $dateTimeZone
     * @return QueryResultInterface
     */
    public function findTodaysAppointments(\DateTimeZone $dateTimeZone)
    {
        $query    = $this->createQuery();
        $now = new \DateTime('now', $dateTimeZone);

        return $query->matching(
            $query->logicalAnd([
                $query->lessThanOrEqual('starttime', $now),
                $query->greaterThanOrEqual('endtime', $now),
                $query->equals('disabled', false)
            ])
        )->execute();
    }

    /**
     * @param \DateTimeZone $dateTimeZone
     * @return object
     */
    public function findCurrentAppointment(\DateTimeZone $dateTimeZone)
    {
        return $this->findTodaysAppointments($dateTimeZone)->getFirst();
    }

    /**
     * @param \DateTimeZone $dateTimeZone
     * @param boolean       $includeDisabled
     * @return QueryResultInterface
     */
    public function findInactive(\DateTimeZone $dateTimeZone, $includeDisabled = true)
    {
        $query = $this->createQuery();

        $constraint = $query->lessThanOrEqual('endtime', new \DateTime('now', $dateTimeZone));
        if ($includeDisabled === false) {
            $constraint = $query->logicalAnd([
                $query->lessThanOrEqual('endtime', new \DateTime('now', $dateTimeZone)),
                $query->equals('disabled', false)
            ]);
        }

        return $query->matching($constraint)->execute();
    }

}

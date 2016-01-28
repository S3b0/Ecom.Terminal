<?php
namespace Ecom\Terminal\Domain\Repository;

/*
 * This file is part of the Ecom.Terminal package.
 */

use Ecom\Terminal\Domain\Model\Appointment;
use TYPO3\Flow\Annotations as Flow;
use \TYPO3\Flow\Persistence\QueryResultInterface;
use TYPO3\Flow\Persistence\Repository;

/**
 * @Flow\Scope("singleton")
 */
class ParticipantRepository extends Repository
{

    /**
     * @param Appointment $appointment
     * @return QueryResultInterface
     */
    public function findByAppointment(Appointment $appointment)
    {
        $query = $this->createQuery();

        return $query->matching(
            $query->equals('appointment', $appointment)
        )->execute();
    }

}

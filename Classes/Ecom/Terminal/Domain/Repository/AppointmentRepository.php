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
     * @return QueryResultInterface
     */
    public function findActive()
    {
        $query = $this->createQuery();

        return $query->matching(
            $query->greaterThan('endtime', new \DateTime())
        )->setOrderings([
            'starttime' => \TYPO3\Flow\Persistence\QueryInterface::ORDER_DESCENDING
        ])->execute();
    }

    /**
     * @return QueryResultInterface
     */
    public function findInactive()
    {
        $query = $this->createQuery();

        return $query->matching(
            $query->lessThanOrEqual('endtime', new \DateTime())
        )->execute();
    }

}

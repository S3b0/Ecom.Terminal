<?php
namespace Ecom\Terminal\Domain\Model;

/*
 * This file is part of the Ecom.Terminal package.
 */

use TYPO3\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;

/**
 * @Flow\Entity
 */
class Participant
{

    /**
     * @var integer
     */
    protected $salutation;

    /**
     * @Flow\Validate(type="NotEmpty")
     * @var string
     */
    protected $name;

    /**
     * @Flow\Validate(type="NotEmpty")
     * @ORM\ManyToOne(inversedBy="participants")
     * @var Appointment
     */
    protected $appointment;


    /**
     * @return integer
     */
    public function getSalutation()
    {
        return $this->salutation;
    }

    /**
     * @param integer $salutation
     * @return void
     */
    public function setSalutation($salutation)
    {
        $this->salutation = $salutation;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return \Ecom\Terminal\Domain\Model\Appointment
     */
    public function getAppointment()
    {
        return $this->appointment;
    }

    /**
     * @param \Ecom\Terminal\Domain\Model\Appointment $appointment
     * @return void
     */
    public function setAppointment($appointment)
    {
        $this->appointment = $appointment;
        $this->appointment->addParticipant($this);
    }

}

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
    protected $sorting = 0;

    /**
     * @var integer
     */
    protected $salutation = 0;

    /**
     * @var integer
     */
    protected $title = 0;

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
     * Participant constructor.
     *
     * @param array       $data
     * @param Appointment $appointment
     * @param integer     $sorting
     */
    public function __construct(array $data, Appointment $appointment, $sorting = 0)
    {
        $this->sorting = $sorting;
        $this->salutation = (int)$data[ 'salutation' ];
        $this->title = (int)$data[ 'title' ];
        $this->name = $data[ 'name' ];
        $this->appointment = $appointment;
    }

    /**
     * @return integer
     */
    public function getSorting()
    {
        return $this->sorting;
    }

    /**
     * @param integer $sorting
     */
    public function setSorting($sorting)
    {
        $this->sorting = $sorting;
    }

    /**
     * @return integer
     */
    public function getSalutation()
    {
        return $this->salutation;
    }

    /**
     * @param integer $salutation
     *
     * @return void
     */
    public function setSalutation($salutation)
    {
        $this->salutation = $salutation;
    }

    /**
     * @return integer
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param integer $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
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
     *
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
     *
     * @return void
     */
    public function setAppointment($appointment)
    {
        $this->appointment = $appointment;
        $this->appointment->addParticipant($this);
    }

}

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
class Appointment
{

    /**
     * @var \DateTime
     */
    protected $starttime;

    /**
     * @var \DateTime
     */
    protected $endtime;

    /**
     * @var \DateTime
     */
    protected $displayStarttime;

    /**
     * @var \DateTime
     */
    protected $displayEndtime;

    /**
     * @Flow\Validate(type="NotEmpty")
     * @Flow\Validate(type="StringLength", options={ "minimum"=3, "maximum"=80 })
     * @ORM\Column(length=80)
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $image;

    /**
     * @ORM\OneToMany(mappedBy="appointment", orphanRemoval=true)
     * @var \Doctrine\Common\Collections\Collection<Participant>
     */
    protected $participants;

    /**
     * Appointment constructor.
     */
    public function __construct()
    {
        $now = new \DateTime();
        $this->setStarttime($now);
    }

    /**
     * @return \DateTime
     */
    public function getStarttime()
    {
        return $this->starttime;
    }

    /**
     * @param \DateTime $starttime
     * @return Appointment
     */
    public function setStarttime($starttime)
    {
        $this->starttime = $starttime;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getEndtime()
    {
        return $this->endtime;
    }

    /**
     * @param \DateTime $endtime
     * @return Appointment
     */
    public function setEndtime($endtime)
    {
        $this->endtime = $endtime;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDisplayStarttime()
    {
        return $this->displayStarttime;
    }

    /**
     * @param \DateTime $displayStarttime
     * @return Appointment
     */
    public function setDisplayStarttime($displayStarttime)
    {
        $this->displayStarttime = $displayStarttime;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDisplayEndtime()
    {
        return $this->displayEndtime;
    }

    /**
     * @param \DateTime $displayEndtime
     * @return Appointment
     */
    public function setDisplayEndtime($displayEndtime)
    {
        $this->displayEndtime = $displayEndtime;
        return $this;
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
     * @return Appointment
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param string $image
     * @return Appointment
     */
    public function setImage($image)
    {
        $this->image = $image;
        return $this;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getParticipants()
    {
        return $this->participants;
    }

    /**
     * @param \Doctrine\Common\Collections\Collection $participants
     * @return Appointment
     */
    public function setParticipants(\Doctrine\Common\Collections\Collection $participants)
    {
        $this->participants = $participants;
        return $this;
    }

    /**
     * @param Participant $participant
     * @return void
     */
    public function addParticipant(Participant $participant)
    {
        $this->participants->add($participant);
    }

    /**
     * @param Participant $participant
     * @return void
     */
    public function removeParticipant(Participant $participant)
    {
        $this->participants->removeElement($participant);
    }

}

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
     * @Flow\Validate(type="NotEmpty")
     * @Flow\Validate(type="StringLength", options={ "minimum"=3, "maximum"=80 })
     * @ORM\Column(length=80)
     * @var string
     */
    protected $name;

    /**
     * @ORM\OneToOne(cascade={"persist"})
     * @ORM\Column(nullable=true)
     * @var \TYPO3\Flow\Resource\Resource
     */
    protected $image;

    /**
     * @ORM\OneToMany(mappedBy="appointment",cascade={"persist"},orphanRemoval=true)
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
     * @return string
     */
    public function _getIdentifier()
    {
        return $this->Persistence_Object_Identifier;
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
     * @return \TYPO3\Flow\Resource\Resource|null
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param \TYPO3\Flow\Resource\Resource $image
     * @return Appointment
     */
    public function setImage(\TYPO3\Flow\Resource\Resource $image = null)
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

    /**
     * @return bool
     */
    public function hasParticipants()
    {
        return $this->participants instanceof \Doctrine\Common\Collections\Collection && $this->participants->count();
    }

    /**
     * @return bool
     */
    public function isOverdue()
    {
        return $this->endtime <= new \DateTime();
    }

}

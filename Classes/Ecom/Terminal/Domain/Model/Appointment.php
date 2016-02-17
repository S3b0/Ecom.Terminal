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
     * @var boolean
     */
    protected $disabled = false;

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
     * @ORM\Column(length=2)
     * @var string
     */
    protected $displayLanguage = 'en';

    /**
     * @Flow\Validate(type="RegularExpression", options={ "regularExpression"="/^\#[0-9a-f]{6}$/i" })
     * @ORM\Column(length=7)
     * @var string
     */
    protected $fontColor = '#000000';

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
     * @return boolean
     */
    public function isDisabled()
    {
        return $this->disabled;
    }

    /**
     * @param boolean $disabled
     *
     * @return Appointment
     */
    public function setDisabled($disabled)
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isEnabled()
    {
        return !$this->disabled;
    }

    /**
     * @return Appointment
     */
    public function toggle()
    {
        $this->disabled = !$this->disabled;

        return $this;
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
     *
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
     *
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
     *
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
    public function getDisplayLanguage()
    {
        return $this->displayLanguage;
    }

    /**
     * @param string $displayLanguage
     *
     * @return Appointment
     */
    public function setDisplayLanguage($displayLanguage)
    {
        $this->displayLanguage = substr($displayLanguage, 0, 2);

        return $this;
    }

    /**
     * @return string
     */
    public function getFontColor()
    {
        return $this->fontColor;
    }

    /**
     * @param string $fontColor
     *
     * @return Appointment
     */
    public function setFontColor($fontColor)
    {
        $this->fontColor = $fontColor;

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
     *
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
     *
     * @return Appointment
     */
    public function setParticipants(\Doctrine\Common\Collections\Collection $participants)
    {
        $this->participants = $participants;

        return $this;
    }

    /**
     * @param Participant $participant
     *
     * @return void
     */
    public function addParticipant(Participant $participant)
    {
        $this->participants->add($participant);
    }

    /**
     * @param Participant $participant
     *
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
        return $this->participants instanceof \Countable && $this->participants->count();
    }

    /**
     * @param $data
     *
     * @return Participant|null
     */
    public function getParticipant($data)
    {
        if ($this->hasParticipants()) {
            /** @var Participant $participant */
            foreach ($this->participants as $participant) {
                if ($participant->getSalutation() === (int)$data[ 'salutation' ] && $participant->getName() === $data[ 'name' ]) {
                    return $participant;
                }
            }
        }

        return null;
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    public function participantExists($data)
    {
        if ($this->hasParticipants()) {
            /** @var Participant $participant */
            foreach ($this->participants as $participant) {
                if ($participant->getSalutation() === (int)$data[ 'salutation' ] && $participant->getName() === $data[ 'name' ]) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isOverdue()
    {
        return $this->endtime <= new \DateTime();
    }

}

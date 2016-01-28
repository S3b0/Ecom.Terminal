<?php
namespace Ecom\Terminal\Controller;

/*
 * This file is part of the Ecom.Terminal package.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Controller\ActionController;
use Ecom\Terminal\Domain\Model\Participant;

class ParticipantController extends ActionController
{

    /**
     * @Flow\Inject
     * @var \Ecom\Terminal\Domain\Repository\ParticipantRepository
     */
    protected $participantRepository;

    /**
     * @return void
     */
    public function newAction()
    {
    }

    /**
     * @param \Ecom\Terminal\Domain\Model\Participant $newParticipant
     * @return void
     */
    public function createAction(Participant $newParticipant)
    {
        $this->participantRepository->add($newParticipant);
        $this->addFlashMessage('Created a new participant.');
        $this->redirect('index');
    }

    /**
     * @param \Ecom\Terminal\Domain\Model\Participant $participant
     * @return void
     */
    public function deleteAction(Participant $participant)
    {
        $this->participantRepository->remove($participant);
        $this->addFlashMessage('Deleted a participant.');
        $this->redirect('index');
    }

}

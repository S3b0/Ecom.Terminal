<?php
namespace Ecom\Terminal\Controller;

/*
 * This file is part of the Ecom.Terminal package.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Controller\ActionController;
use Ecom\Terminal\Domain\Model\Appointment;
use Ecom\Terminal\Domain\Model\Participant;

class AppointmentController extends ActionController
{
    /**
     * @var \TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface
     * @Flow\Inject
     */
    protected $authenticationManager;

    /**
     * @Flow\Inject
     * @var \Ecom\Terminal\Domain\Repository\AppointmentRepository
     */
    protected $appointmentRepository;

    /**
     * @Flow\Inject
     * @var \Ecom\Terminal\Domain\Repository\ParticipantRepository
     */
    protected $participantRepository;

    /**
     * Initializes the controller before invoking an action method.
     *
     * @throws \TYPO3\Flow\Security\Exception\AuthenticationRequiredException
     */
    public function initializeAction()
    {
        if ($this->authenticationManager->isAuthenticated() === false) {
            $this->redirect('login', 'Authentication');
        }
    }

    /**
     * Initializes the view before invoking an action method.
     *
     * @param \TYPO3\Flow\Mvc\View\ViewInterface $view
     */
    public function initializeView(\TYPO3\Flow\Mvc\View\ViewInterface $view)
    {
        $this->view->assign('user', $this->authenticationManager->getSecurityContext()->getAccount()->getAccountIdentifier());
    }

    /**
     * @return void
     */
    public function indexAction()
    {
        $this->view->assign('appointments', $this->appointmentRepository->findAll());
    }

    /**
     * @param \Ecom\Terminal\Domain\Model\Appointment $appointment
     * @return void
     */
    public function showAction(Appointment $appointment)
    {
        $this->view->assign('appointment', $appointment);
    }

    /**
     * @return void
     */
    public function newAction()
    {
    }

    /**
     * Initializes the controller before invoking createAction.
     */
    public function initializeCreateAction()
    {
        $temp = $this->request->getArgument('newAppointment');
        $temp['starttime'] = date('Y-m-d\TH:i:sP', strtotime($temp['starttime']));
        $temp['endtime'] = date('Y-m-d\TH:i:sP', strtotime($temp['endtime']));
        $this->request->setArgument('newAppointment', $temp);
    }

    /**
     * @param \Ecom\Terminal\Domain\Model\Appointment $newAppointment
     * @param array                                   $participants
     * @return void
     */
    public function createAction(Appointment $newAppointment, array $participants = [])
    {
        $this->persistenceManager->whitelistObject($newAppointment);
        $this->appointmentRepository->add($newAppointment);
        if (sizeof($participants)) {
            foreach ($participants as $participant) {
                $newParticipant = new Participant($participant, $newAppointment);
                $this->persistenceManager->whitelistObject($newParticipant);
                $this->participantRepository->add($newParticipant);
            }
        }
        if ($this->persistenceManager->hasUnpersistedChanges())
            $this->persistenceManager->persistAll();
        $this->addFlashMessage('Created a new appointment.');
        $this->redirect('index');
    }

    /**
     * @param \Ecom\Terminal\Domain\Model\Appointment $appointment
     * @return void
     */
    public function editAction(Appointment $appointment)
    {
        $this->view->assign('appointment', $appointment);
    }

    /**
     * Initializes the controller before invoking updateAction.
     */
    public function initializeUpdateAction()
    {
        $temp = $this->request->getArgument('appointment');
        $temp['starttime'] = date('Y-m-d\TH:i:sP', strtotime($temp['starttime']));
        $temp['endtime'] = date('Y-m-d\TH:i:sP', strtotime($temp['endtime']));
        $this->request->setArgument('appointment', $temp);
    }

    /**
     * @param \Ecom\Terminal\Domain\Model\Appointment $appointment
     * @return void
     */
    public function updateAction(Appointment $appointment)
    {
        $this->appointmentRepository->update($appointment);
        $this->addFlashMessage('Updated the appointment.');
        $this->redirect('index');
    }

    /**
     * @return void
     */
    public function cleanupAction()
    {
        if ($appointments = $this->appointmentRepository->findInActive()) {
            /** @var Appointment $appointment */
            foreach ($appointments as $appointment) {
                if ($appointment->hasParticipants()) {
                    /** @var Participant $participant */
                    foreach ($appointment->getParticipants() as $participant) {
                        $this->participantRepository->remove($participant);
                        $appointment->removeParticipant($participant);
                    }
                }
                $this->appointmentRepository->remove($appointment);
            }
            $this->addFlashMessage('Deleted outdated appointments');
        } else {
            $this->addFlashMessage('Nothing to do');
        }
        $this->redirect('index');
    }

}

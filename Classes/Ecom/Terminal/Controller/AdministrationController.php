<?php
namespace Ecom\Terminal\Controller;

/*
 * This file is part of the Ecom.Terminal package.
 */

use TYPO3\Flow\Annotations as Flow;
use Ecom\Terminal\Domain\Model\Appointment;
use Ecom\Terminal\Domain\Model\Participant;

class AdministrationController extends StandardController
{
    /**
     * @var \TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface
     * @Flow\Inject
     */
    protected $authenticationManager;

    /**
     * @var \Ecom\Terminal\Domain\Repository\AppointmentRepository
     * @Flow\Inject
     */
    protected $appointmentRepository;

    /**
     * @var \Ecom\Terminal\Domain\Repository\ParticipantRepository
     * @Flow\Inject
     */
    protected $participantRepository;

    /**
     * @var \TYPO3\Flow\Resource\ResourceManager
     * @Flow\Inject
     */
    protected $resourceManager;

    /**
     * @var \TYPO3\Flow\Resource\ResourceRepository
     * @Flow\Inject
     */
    protected $resourceRepository;

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
        $this->view->assignMultiple([
            'appointments' => $this->appointmentRepository->findAll(),
            'slides'       => $this->getSlideResources()
        ]);
    }

    /**
     * @param \Ecom\Terminal\Domain\Model\Appointment $appointment
     * @return void
     */
    public function showAppointmentAction(Appointment $appointment)
    {
        $this->view->assign('appointment', $appointment);
    }

    /**
     * @return void
     */
    public function newAppointmentAction()
    {
    }

    /**
     * Initializes the controller before invoking createAppointmentAction.
     */
    public function initializeCreateAppointmentAction()
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
    public function createAppointmentAction(Appointment $newAppointment, array $participants = [])
    {
        $this->appointmentRepository->add($newAppointment);
        if (sizeof($participants)) {
            foreach ($participants as $participant) {
                $newParticipant = new Participant($participant, $newAppointment);
                $this->participantRepository->add($newParticipant);
                $this->persistenceManager->whitelistObject($newParticipant);
            }
        }
        $this->addFlashMessage('Created a new appointment.');
        $this->redirect('index');
    }

    /**
     * @param \Ecom\Terminal\Domain\Model\Appointment $appointment
     * @return void
     */
    public function editAppointmentAction(Appointment $appointment)
    {
        $this->view->assign('appointment', $appointment);
    }

    /**
     * Initializes the controller before invoking updateAction.
     */
    public function initializeUpdateAppointmentAction()
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
    public function updateAppointmentAction(Appointment $appointment)
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
        $appointments = $this->appointmentRepository->findInactive();
        if ($appointments->count()) {
            /** @var Appointment $appointment */
            foreach ($appointments as $appointment) {
                $this->persistenceManager->whitelistObject($appointment);
                if ($appointment->hasParticipants()) {
                    /** @var Participant $participant */
                    foreach ($appointment->getParticipants() as $participant) {
                        $this->persistenceManager->whitelistObject($participant);
                        $appointment->removeParticipant($participant);
                        $this->participantRepository->remove($participant);
                    }
                }
                $this->appointmentRepository->remove($appointment);
            }
            $this->addFlashMessage('Deleted outdated appointments');
        } else {
            $this->addFlashMessage('Nothing to do!');
        }
        $this->redirect('index');
    }

    /**
     * @return void
     */
    public function uploadSlidesAction()
    {
    }

    /**
     * @param array $slides
     * @return void
     */
    public function processSlideUploadAction(array $slides = [ ])
    {
        if (sizeof($slides)) {
            $collection = $this->resourceManager->getCollection($this->settings['slides']['collection']);
            foreach ($slides as $slide) {
                /** @var \TYPO3\Flow\Resource\Resource $resource */
                $resource = $this->resourceManager->importUploadedResource($slide, $this->settings['slides']['collection']);
                if (sizeof($collection->getObjects())) {
                    /** @var \TYPO3\Flow\Resource\Storage\Object $object */
                    foreach ($collection->getObjects() as $object) {
                        if ($resource->getSha1() === $object->getSha1()) {
                            $this->resourceManager->deleteResource($resource);
                            $this->addFlashMessage("{$resource->getFilename()} already added to collection.", 'Duplicate Entry!', \TYPO3\Flow\Error\Message::SEVERITY_WARNING);
                            continue 2;
                        }
                    }
                }
                $this->addFlashMessage("Added {$resource->getFilename()} to collection.", 'Slide added!');
            }
            $collection->publish();
        }
        $this->redirect('index');
    }

    /**
     * @param string $slide SHA-1 fingerprint of file (did not work with resource, returned null)
     * @todo check for possibility using resource argument afterwards
     * @return void
     */
    public function removeSlideAction($slide)
    {
        /** @var \TYPO3\Flow\Resource\Resource $resource */
        $resource = $this->resourceRepository->findOneBySha1($slide);
        if ($resource instanceof \TYPO3\Flow\Resource\Resource) {
            $this->persistenceManager->whitelistObject($resource);
            $this->resourceRepository->remove($resource);
            $this->addFlashMessage("Removed {$resource->getFilename()} from collection.", 'Slide removed!');
        } else {
            $this->addFlashMessage('Slide not found.', '', \TYPO3\Flow\Error\Message::SEVERITY_WARNING);
        }

        $this->redirect('index');
    }
}

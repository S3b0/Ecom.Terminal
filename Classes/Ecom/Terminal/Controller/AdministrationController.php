<?php
namespace Ecom\Terminal\Controller;

/*
 * This file is part of the Ecom.Terminal package.
 */

use TYPO3\Flow\Annotations as Flow;
use Ecom\Terminal\Domain\Model\Appointment;
use Ecom\Terminal\Domain\Model\Participant;
use TYPO3\Flow\Error\Message;

class AdministrationController extends StandardController
{
    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface
     */
    protected $authenticationManager;

    /**
     * @Flow\Inject
     * @var \Ecom\Terminal\Domain\Repository\ParticipantRepository
     */
    protected $participantRepository;

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Resource\ResourceRepository
     */
    protected $resourceRepository;

    /**
     * Initializes the controller before invoking an action method.
     *
     * @throws \TYPO3\Flow\Security\Exception\AuthenticationRequiredException
     */
    public function initializeAction()
    {
        parent::initializeAction();
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
        $this->addFlashMessage($this->translate('fm.appointmentCreated'));
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
     * @param array                                   $participants
     * @return void
     */
    public function updateAppointmentAction(Appointment $appointment, array $participants = [])
    {
        $this->appointmentRepository->update($appointment);
        if (sizeof($participants)) {
            $currentAmmountOfParticipants = $appointment->getParticipants() instanceof \Countable ? $appointment->getParticipants()->count() : 0;
            $keepParticipants = [];
            $keepExisitingParticipants = 0;
            foreach ($participants as $participant) {
                if ($appointment->participantExists($participant) || ($participant['salutation'] === '' && $participant['name'] === '')) {
                    $keepParticipants[] = $appointment->getParticipant($participant);
                    $keepExisitingParticipants++;
                    continue;
                }
                $newParticipant = new Participant($participant, $appointment);
                $this->participantRepository->add($newParticipant);
                $this->persistenceManager->whitelistObject($newParticipant);
                $keepParticipants[] = $newParticipant;
            }
            if ($currentAmmountOfParticipants > $keepExisitingParticipants) {
                /** @var \Ecom\Terminal\Domain\Model\Participant $participant */
                foreach ($appointment->getParticipants() as $participant) {
                    if (in_array($participant, $keepParticipants)) {
                        continue;
                    }
                    #$this->persistenceManager->whitelistObject($participant);
                    $appointment->removeParticipant($participant);
                }
            }
        }
        $this->addFlashMessage($this->translate('fm.appointmentUpdated', [ $appointment->getName() ]));
        $this->redirect('index');
    }

    /**
     * @param Appointment $appointment
     *
     * @throws \TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException
     */
    public function deleteAppointmentAction(Appointment $appointment)
    {
        $this->persistenceManager->whitelistObject($appointment);
        if ($appointment->hasParticipants()) {
            /** @var Participant $participant */
            foreach ($appointment->getParticipants() as $participant) {
                $this->persistenceManager->whitelistObject($participant);
            }
        }
        $this->appointmentRepository->remove($appointment);
        $this->addFlashMessage('', $this->translate('fm.appointmentRemoved.title', [ $appointment->getName() ]), Message::SEVERITY_WARNING);
        $this->redirect('index');
    }

    /**
     * @param Appointment $appointment
     *
     * @throws \TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException
     */
    public function deleteImageAction(Appointment $appointment)
    {
        $appointment->setImage();
        $this->persistenceManager->whitelistObject($appointment);
        $this->appointmentRepository->update($appointment);
        $this->addFlashMessage('', $this->translate('fm.imageRemoved.title', [ $appointment->getName() ]), Message::SEVERITY_ERROR);
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
                    }
                }
                $this->appointmentRepository->remove($appointment);
                $this->addFlashMessage($this->translate('fm.appointmentRemoved.message', [ $appointment->getEndtime()->format($this->settings['date']['format']['long']) ]), $this->translate('fm.appointmentRemoved.title', [ $appointment->getName() ]), Message::SEVERITY_WARNING);
            }
        } else {
            $this->addFlashMessage($this->translate('fm.noActionNeeded'));
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
                            $this->addFlashMessage($this->translate('fm.slideDuplicate.message', [ $resource->getFilename() ]), $this->translate('fm.slideDuplicate.title'), Message::SEVERITY_WARNING);
                            continue 2;
                        }
                    }
                }
                $this->addFlashMessage($this->translate('fm.slideAdded.message', [ $resource->getFilename() ]), $this->translate('fm.slideAdded.title'));
            }
            $collection->publish();
        }
        $this->redirect('index');
    }

    /**
     * @param string $slide SHA-1 fingerprint of file (not working with Resource type, returns null)
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
            $this->addFlashMessage($this->translate('fm.slideRemoved.message', [ $resource->getFilename() ]), $this->translate('fm.slideRemoved.title'));
        } else {
            $this->addFlashMessage($this->translate('fm.slideNotFound'), '', Message::SEVERITY_WARNING);
        }

        $this->redirect('index');
    }
}

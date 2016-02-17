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
        $appointments = $this->appointmentRepository->findAll();
        $this->orderParticipants($appointments);
        $outdated = $this->appointmentRepository->findInactive(new \DateTimeZone($this->settings[ 'date' ][ 'timezone' ]));
        $outdated = $outdated instanceof \Countable ? $outdated->count() : 0;

        $this->view->assignMultiple([
            'appointments' => $appointments,
            'current'      => $this->appointmentRepository->findCurrentAppointment(new \DateTimeZone($this->settings[ 'date' ][ 'timezone' ])),
            'outdated'     => $outdated,
            'slides'       => $this->getSlideResources(),
            'tstamp'       => time()
        ]);
    }

    /**
     * @param Appointment $appointment
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
     * @param Appointment $newAppointment
     * @param array       $participants
     * @return void
     */
    public function createAppointmentAction(Appointment $newAppointment, array $participants = [])
    {
        $this->appointmentRepository->add($newAppointment);
        if (sizeof($participants)) {
            $sorting = 0;
            foreach ($participants as $participant) {
                if ($participant['salutation'] === '' && $participant['name'] === '') {
                    continue;
                }
                $newParticipant = new Participant($participant, $newAppointment, $sorting);
                $this->participantRepository->add($newParticipant);
                $this->persistenceManager->whitelistObject($newParticipant);
                $sorting++;
            }
        }
        $this->addFlashMessage($this->translate('fm.appointmentCreated'));
        $this->redirect();
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
        if (array_key_exists('starttime', $temp) && array_key_exists('endtime', $temp)) {
            $temp['starttime'] = date('Y-m-d\TH:i:sP', strtotime($temp['starttime']));
            $temp['endtime'] = date('Y-m-d\TH:i:sP', strtotime($temp['endtime']));
            $this->request->setArgument('appointment', $temp);
        }
    }

    /**
     * @param Appointment $appointment
     * @param array       $participants
     * @param boolean     $deleteImage
     * @return void
     */
    public function updateAppointmentAction(Appointment $appointment, array $participants = [], $deleteImage = false)
    {
        if ($deleteImage) {
            $appointment->setImage();
        }
        if (sizeof($participants)) {
            $currentAmountOfParticipants = $appointment->getParticipants() instanceof \Countable ? $appointment->getParticipants()->count() : 0;
            $keepExistingParticipants = 0;
            $keepParticipants = [];
            $sorting = 0;
            foreach ($participants as $participant) {
                if ($participant['salutation'] === '' && $participant['name'] === '') {
                    continue;
                }
                if ($appointment->participantExists($participant)) {
                    $participant = $appointment->getParticipant($participant);
                    $participant->setSorting($sorting);
                    $keepParticipants[] = $participant;
                    $keepExistingParticipants++;
                    $sorting++;
                    continue;
                }
                $newParticipant = new Participant($participant, $appointment, $sorting);
                $this->participantRepository->add($newParticipant);
                $this->persistenceManager->whitelistObject($newParticipant);
                $keepParticipants[] = $newParticipant;
                $sorting++;
            }
            if ($currentAmountOfParticipants > $keepExistingParticipants) {
                /** @var Participant $participant */
                foreach ($appointment->getParticipants() as $participant) {
                    if (in_array($participant, $keepParticipants)) {
                        continue;
                    }
                    $appointment->removeParticipant($participant);
                }
            }
        } elseif ($appointment->hasParticipants()) {
            /** @var Participant $participant */
            foreach ($appointment->getParticipants() as $participant) {
                $appointment->removeParticipant($participant);
            }
        }

        $this->appointmentRepository->update($appointment);
        $this->addFlashMessage($this->translate('fm.appointmentUpdated', [ $appointment->getTitle() ]));
        $this->redirect();
    }

    /**
     * @param Appointment $appointment
     * @param int         $t           Timestamp >> Prevent browser from caching
     *
     * @throws \TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException
     */
    public function toggleAppointmentAction(Appointment $appointment, $t = 0)
    {
        $appointment->toggle();
        $this->appointmentRepository->update($appointment);
        $this->persistenceManager->whitelistObject($appointment);
        $this->persistenceManager->persistAll(true);
        $this->redirect();
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
        $this->addFlashMessage('', $this->translate('fm.appointmentRemoved.title', [ $appointment->getTitle() ]), Message::SEVERITY_ERROR);
        $this->redirect();
    }

    /**
     * @return void
     */
    public function cleanupAction()
    {
        $appointments = $this->appointmentRepository->findInactive(new \DateTimeZone($this->settings[ 'date' ][ 'timezone' ]));
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
                $this->addFlashMessage($this->translate('fm.odAppointmentRemoved.message', [ $appointment->getEndtime()->format($this->settings['date']['format']['long']) ]), $this->translate('fm.odAppointmentRemoved.title', [ $appointment->getTitle() ]), Message::SEVERITY_ERROR);
            }
        } else {
            $this->addFlashMessage($this->translate('fm.noActionNeeded'));
        }
        $this->redirect();
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
        $this->redirect();
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

        $this->redirect();
    }

    /**
     * @param \TYPO3\Flow\Persistence\QueryResultInterface|null $appointments
     */
    private function orderParticipants($appointments = null)
    {
        if (!$appointments instanceof \Countable || $appointments->count() === 0) {
            return;
        }

        /** @var Appointment $appointment */
        foreach ($appointments as $appointment) {
            if ($appointment->hasParticipants() && $appointment->getParticipants()->count() > 1) {
                $finalOrder = [];
                /** @var Participant $participant */
                foreach ($appointment->getParticipants() as $participant) {
                    $finalOrder[$participant->getSorting()] = $participant;
                }
                ksort($finalOrder);
                $appointment->setParticipants(new \Doctrine\Common\Collections\ArrayCollection($finalOrder));
            }
        }
    }

    /**
     * Redirects the request to another action and / or controller.
     *
     * Redirect will be sent to the client which then performs another request to the new URI.
     *
     * @param string $actionName Name of the action to forward to
     * @param string $controllerName Unqualified object name of the controller to forward to. If not specified, the current controller is used.
     * @param string $packageKey Key of the package containing the controller to forward to. If not specified, the current package is assumed.
     * @param array $arguments Array of arguments for the target action
     * @param integer $delay (optional) The delay in seconds. Default is no delay.
     * @param integer $statusCode (optional) The HTTP status code for the redirect. Default is "303 See Other"
     * @param string $format The format to use for the redirect URI
     * @return void
     * @throws \TYPO3\Flow\Mvc\Exception\StopActionException
     */
    protected function redirect($actionName = 'index', $controllerName = null, $packageKey = null, array $arguments = null, $delay = 0, $statusCode = 301, $format = null)
    {
        parent::redirect($actionName, $controllerName, $packageKey, $arguments, $delay, $statusCode, $format);
    }

}

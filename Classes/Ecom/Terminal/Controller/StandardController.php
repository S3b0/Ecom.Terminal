<?php
namespace Ecom\Terminal\Controller;

/*
 * This file is part of the Ecom.Terminal package.
 */

use TYPO3\Flow\Annotations as Flow;

class StandardController extends \TYPO3\Flow\Mvc\Controller\ActionController
{

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
     * @Flow\Inject
     * @var \TYPO3\Flow\Resource\ResourceManager
     */
    protected $resourceManager;

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\I18n\Translator
     */
    protected $translator;

    /**
     * @var \TYPO3\Flow\I18n\Locale
     */
    protected $lang;

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\I18n\Service
     */
    protected $languageService;

    /**
     * Initializes the controller before invoking an action method.
     */
    public function initializeAction()
    {
        $detector = new \TYPO3\Flow\I18n\Detector();
        $this->lang = $detector->detectLocaleFromHttpHeader($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        $this->settings['i18n']['defaultLocale'] = $this->lang->getLanguage();
        $this->languageService->getConfiguration()->setCurrentLocale($this->lang);
    }

    /**
     * @return void
     */
    public function indexAction()
    {
        if (sizeof($this->getSlideResources())) {
            $this->assignSlides();
        } else {
            $this->view->assign('bodyStyle', " style=\"background: transparent url({$this->resourceManager->getPublicPackageResourceUri('Ecom.Terminal', 'Images/default.jpg')}) no-repeat center fixed\"");
        }

        /** @var \Ecom\Terminal\Domain\Model\Appointment $appointment */
        if (($appointment = $this->appointmentRepository->findCurrentAppointment(new \DateTimeZone($this->settings[ 'date' ][ 'timezone' ]))) instanceof \Ecom\Terminal\Domain\Model\Appointment) {
            /** @var \TYPO3\Flow\I18n\Locale $displayLanguage */
            $displayLanguage = new \TYPO3\Flow\I18n\Locale($appointment->getDisplayLanguage());
            if ($displayLanguage !== $this->lang) {
                $this->languageService->getConfiguration()->setCurrentLocale($displayLanguage);
            }
            if ($appointment->getImage() instanceof \TYPO3\Flow\Resource\Resource) {
                $this->view->assignMultiple([
                    'bodyStyle'              => " style=\"background: transparent url({$this->resourceManager->getPublicPersistentResourceUri($appointment->getImage())}) no-repeat center fixed\"",
                    'inlineStyleColor'       => " style=\"color: {$appointment->getFontColor()}\""
                ]);
            } else {
                $this->view->assign('bodyStyle', " style=\"background: #000 url({$this->resourceManager->getPublicPackageResourceUri('Ecom.Terminal', 'Images/glow.jpg')}) no-repeat center fixed\"");
            }
            $this->view->assignMultiple([
                'appointment'  => $appointment,
                'participants' => $this->participantRepository->findByAppointment($appointment),
                'mode'         => 1
            ]);
        }
    }

    /**
     * @return void
     */
    public function assignSlides()
    {
        $vegasSlidesJs = '[]';

        if ($slides = $this->getSlideResources()) {
            $sources = [];
            /** Add video, if available */
            if (is_dir(FLOW_PATH_WEB . '_Resources/Static/Video')) {
                $directoryContent = array_diff(scandir(FLOW_PATH_WEB . '_Resources/Static/Video'), [ '.', '..' ]);
                natsort($directoryContent);
                if (sizeof($directoryContent)) {
                    foreach ($directoryContent as $file) {
                        if (preg_match('/\.mp4$/i', $file)) {
                            $sources[] = "{video:{src:['/_Resources/Static/Video/{$file}'],loop: false,mute: true}}";
                        }
                    }
                }
            }
            /** @var \TYPO3\Flow\Resource\Resource $slide */
            foreach ($slides as $slide) {
                $sources[] = "{ src: '{$this->resourceManager->getPublicPersistentResourceUri($slide)}' }";
            }
            $vegasSlidesJs = '[' . implode(',', $sources) . ']';
        }

        $this->view->assignMultiple([
            'mode'          => 2,
            'vegasSlidesJs' => $vegasSlidesJs
        ]);
    }

    /**
     * @return array
     */
    protected function getSlideResources()
    {
        $return = [];

        if ($slides = $this->resourceManager->getCollection($this->settings[ 'slides' ][ 'collection' ])->getObjects()) {
            /** @var \TYPO3\Flow\Resource\Storage\Object $slide */
            foreach ($slides as $slide) {
                /** @var \TYPO3\Flow\Resource\Resource $resource */
                $resource = $this->resourceManager->getResourceBySha1($slide->getSha1());
                /** Check for instance match before continuing */
                if (!$resource instanceof \TYPO3\Flow\Resource\Resource) {
                    continue;
                }
                $return[ preg_replace('![^a-z0-_9\s+]+!', '', strtolower($resource->getFilename())) ] = $resource;
            }
        }
        ksort($return, SORT_NATURAL);

        return $return;
    }

    /**
     * @param string $id
     * @param array  $arguments
     *
     * @return string
     */
    protected function translate($id, array $arguments = [])
    {
        return $this->translator->translateById($id, $arguments, null, $this->lang, 'Main', $this->request->getControllerPackageKey());
    }

}

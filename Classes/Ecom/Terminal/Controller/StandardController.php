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
     * @var \TYPO3\Flow\Resource\ResourceManager
     */
    protected $resourceManager;

    /**
     * @return void
     */
    public function indexAction()
    {
        $this->view->assignMultiple([
            'appointments'  => $this->appointmentRepository->findActive(),
            'hideUserPanel' => true,
            'slides'        => sizeof($this->getSlideResources()) > 0
        ]);
    }

    /**
     * @return void
     */
    public function slideShowAction()
    {
        $vegasJsOptions = '[]';

        if ($slides = $this->getSlideResources()) {
            $sources = [ ];
            /** @var \TYPO3\Flow\Resource\Resource $slide */
            foreach ($slides as $slide) {
                $sources[] = "{ src: '{$this->resourceManager->getPublicPersistentResourceUri($slide)}' }";
            }
            $vegasJsOptions = '[' . implode(',', $sources) . ']';
        }

        $this->view->assign('vegasJsOptions', $vegasJsOptions);
    }

    /**
     * @return array
     */
    protected function getSlideResources()
    {
        $return = [ ];

        if ($slides = $this->resourceManager->getCollection($this->settings['slides']['collection'])->getObjects()) {
            /** @var \TYPO3\Flow\Resource\Storage\Object $slide */
            foreach ($slides as $slide) {
                /** @var \TYPO3\Flow\Resource\Resource $resource */
                $resource = $this->resourceManager->getResourceBySha1($slide->getSha1());
                /** Check for instance match before continuing */
                if (!$resource instanceof \TYPO3\Flow\Resource\Resource) {
                    continue;
                }
                $return[preg_replace('![^a-z0-_9\s+]+!', '', strtolower($resource->getFilename()))] = $resource;
            }
        }
        ksort($return, SORT_NATURAL);

        return $return;
    }

}

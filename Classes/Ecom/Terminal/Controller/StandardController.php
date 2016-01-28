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
     * @return void
     */
    public function indexAction()
    {
        $server = explode('.', $_SERVER['SERVER_ADDR']);
        array_pop($server);
        if (sizeof((array) $server) && !preg_match('/^' . implode('\.', $server) . '/i', $_SERVER['REMOTE_ADDR'])) {
            die('Access denied');
        }

        $this->view->assign('appointments', $this->appointmentRepository->findAll());
    }

}

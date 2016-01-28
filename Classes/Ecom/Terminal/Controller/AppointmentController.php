<?php
namespace Ecom\Terminal\Controller;

/*
 * This file is part of the Ecom.Terminal package.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Controller\ActionController;
use Ecom\Terminal\Domain\Model\Appointment;

class AppointmentController extends ActionController
{

    /**
     * @Flow\Inject
     * @var \Ecom\Terminal\Domain\Repository\AppointmentRepository
     */
    protected $appointmentRepository;

    protected $limitedAccessActions = [ 'new', 'create', 'edit', 'update', 'delete' ];

    /**
     * Initializes the controller before invoking an action method.
     *
     * @return void
     */
    public function initializeAction()
    {
        if (in_array($this->request->getControllerActionName(), $this->limitedAccessActions)) {
            /**
             * Ensure user is in the same network
             * According to IP-mask 255.255.255.0
             */
            $server = explode('.', $_SERVER['SERVER_ADDR']);
            array_pop($server);
            if (sizeof($server) && !preg_match('/^' . implode('\.', $server) . '/i', $_SERVER['REMOTE_ADDR'])) {
                die('Access denied');
            }
        }
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

    public function initializeCreateAction()
    {
        $temp = $this->request->getArgument('newAppointment');
        $temp['starttime'] = date('Y-m-d\TH:i:sP', strtotime($temp['starttime']));
        $temp['endtime'] = date('Y-m-d\TH:i:sP', strtotime($temp['endtime']));
        $temp['displayStarttime'] = date('Y-m-d\TH:i:sP', strtotime($temp['displayStarttime']));
        $temp['displayEndtime'] = date('Y-m-d\TH:i:sP', strtotime($temp['displayEndtime']));
        $this->request->setArgument('newAppointment', $temp);
    }

    /**
     * @param \Ecom\Terminal\Domain\Model\Appointment $newAppointment
     * @return void
     */
    public function createAction(Appointment $newAppointment)
    {
        $this->appointmentRepository->add($newAppointment);
        $this->addFlashMessage('Created a new appointment.');
        $this->redirect('index', 'Standard');
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
     * @param \Ecom\Terminal\Domain\Model\Appointment $appointment
     * @return void
     */
    public function deleteAction(Appointment $appointment)
    {
        $this->appointmentRepository->remove($appointment);
        $this->addFlashMessage('Deleted a appointment.');
        $this->redirect('index', 'Standard');
    }

}

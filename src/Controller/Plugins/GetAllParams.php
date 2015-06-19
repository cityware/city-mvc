<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Cityware\Mvc\Controller\Plugins;

use Zend\Mvc\Exception;
use Zend\Mvc\InjectApplicationEventInterface;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;

/**
 * Description of getParam
 *
 * @author fabricio.xavier
 */
class GetAllParams extends AbstractPlugin {
    
    private $event;

    public function __invoke($type = null) {
        $params = Array();

        if (!empty($type)) {
            switch (strtolower($type)) {
                case 'get':
                    $params += (array) $this->getEvent()->getRouteMatch()->getParams();
                    break;
                case 'post':
                    $params += (array) $this->getEvent()->getRequest()->getPost();
                    break;
                case 'query':
                    $params += (array) $this->getEvent()->getRequest()->getQuery();
                    break;
                case 'file':
                    $params += (array) $this->getEvent()->getRequest()->getFiles();
                    break;
                default:
                    throw new \Exception('Tipo definido desconhecido!', 500);
            }
        } else {
            // route parameters
            $params += (array) $this->getEvent()->getRouteMatch()->getParams();
            // query parameter
            $params += (array) $this->getEvent()->getRequest()->getQuery();
            // post parameter
            $params += (array) $this->getEvent()->getRequest()->getPost();
            // files parameter
            $params += (array) $this->getEvent()->getRequest()->getFiles();
        }

        return $params;
    }

    /**
     * Get the event
     *
     * @return MvcEvent
     * @throws Exception\DomainException if unable to find event
     */
    protected function getEvent() {
        if ($this->event) {
            return $this->event;
        }
        $controller = $this->getController();
        if (!$controller instanceof InjectApplicationEventInterface) {
            throw new Exception\DomainException('getParam plugin requires a controller that implements InjectApplicationEventInterface');
        }
        $event = $controller->getEvent();
        if (!$event instanceof MvcEvent) {
            $params = $event->getParams();
            $event = new MvcEvent();
            $event->setParams($params);
        }
        $this->event = $event;
        return $this->event;
    }

}

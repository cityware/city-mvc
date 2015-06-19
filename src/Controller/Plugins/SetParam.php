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
class SetParam extends AbstractPlugin {
    
    private $event;

    public function __invoke($name, $value) {
        $this->getEvent()->getRouteMatch()->setParam($name, $value);
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

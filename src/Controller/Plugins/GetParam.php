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
class GetParam extends AbstractPlugin {
    
    private $event;

    public function __invoke($name, $default = null, $type = null) {
        $params = Array();

        switch (strtolower($type)) {
            case 'get':
                $params[$name] = $this->getEvent()->getRouteMatch()->getParam($name, $default);
                break;
            case 'post':
                $params[$name] = $this->getEvent()->getRequest()->getPost($name, $default);
                break;
            case 'query':
                $params[$name] = $this->getEvent()->getRequest()->getQuery($name, $default);
                break;
            case 'file':
                $params[$name] = $this->getEvent()->getRequest()->getFiles($name, $default);
                break;
            default:
                // query parameter
                if ($this->getEvent()->getRequest()->getQuery($name, $default)) {
                    $params[$name] = $this->getEvent()->getRequest()->getQuery($name, $default);
                }
                // router parameter
                if ($this->getEvent()->getRouteMatch()->getParam($name, $default)) {
                    $params[$name] = $this->getEvent()->getRouteMatch()->getParam($name, $default);
                }
                // post parameter
                if ($this->getEvent()->getRequest()->getPost($name, $default)) {
                    $params[$name] = $this->getEvent()->getRequest()->getPost($name, $default);
                }
                break;
        }

        return (isset($params[$name]) and ! empty($params[$name])) ? $params[$name] : ((!empty($default)) ? $default : null);
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

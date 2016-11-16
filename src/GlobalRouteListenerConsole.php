<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Cityware\Mvc;

use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;
use Zend\Mvc\MvcEvent;

/**
 * Description of GlobalModuleRouteListener
 *
 * @author fsvxavier
 */
class GlobalRouteListenerConsole extends AbstractListenerAggregate {

    const ORIGINAL_CONTROLLER = '__CONTROLLER__';

    /**
     * {@inheritdoc}
     */
    public function attach(EventManagerInterface $events, $priority = 1) {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_ROUTE, [$this, 'onRoute'], $priority);
    }

    /**
     * Global Route /module/controller/action
     * @param MvcEvent $e
     */
    public function onRoute(MvcEvent $e) {
        $matches = $e->getRouteMatch();
        $module = $matches->getParam('module');
        $controller = $matches->getParam('controller');

        if ($module && $controller && strpos($controller, '\\') === false) {
            $matches->setParam(self::ORIGINAL_CONTROLLER, $controller);
            $controllerLoader = $e->getApplication()->getServiceManager()->get('ControllerManager');
            $ctrlClass = $this->convertName($module) . '\\Console\\';
            $ctrlClass .= $this->convertName($controller);
            $controller = $ctrlClass;
            $matches->setParam('controller', $controller);
            $ctrlClass .= 'Controller';

            if (!$controllerLoader->has($controller) && class_exists($ctrlClass)) {
                $controllerLoader->setInvokableClass($controller, $ctrlClass);
                $e->setController($controller);
                $e->setControllerClass($ctrlClass);
            }
        }
    }

    private static function convertName($name) {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $name)));
    }

}

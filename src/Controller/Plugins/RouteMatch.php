<?php

/**
 * @author Remi THOMAS 
 */

namespace Cityware\Mvc\Controller\Plugins;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;

class RouteMatch extends AbstractPlugin {

    /**
     *
     * @param string $title
     * @param type $setType
     * @return \RtHeadtitle\Controller\Plugin\HeadTitle 
     */
    public function __invoke() {

        $application = $this->getController()->getServiceLocator()->get('Application');
        $routematch = $application->getMvcEvent()->getRouteMatch();
        return $routematch;
    }

}

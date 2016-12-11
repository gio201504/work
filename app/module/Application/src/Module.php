<?php
/**
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application;

use Zend\Mvc\MvcEvent;
use Zend\Mvc\ModuleRouteListener;

class Module
{
    const VERSION = '3.0.2dev';

    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }
    
    public function onBootstrap(MvcEvent $e)
    {
    	$eventManager        = $e->getApplication()->getEventManager();
    	$moduleRouteListener = new ModuleRouteListener();
    	$moduleRouteListener->attach($eventManager);
    	 
    	// get the cache listener service
    	$sm = $e->getApplication()->getServiceManager();
    	$cacheListener = $sm->get('CacheListener');
    	 
    	// attach the listeners to the event manager
    	$eventManager->attach($cacheListener);
    }
}

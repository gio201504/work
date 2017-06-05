<?php
/**
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application;

use Zend\Mvc\MvcEvent;
use Zend\Mvc\ModuleRouteListener;
use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\Session\SessionManager;

class Module implements AutoloaderProviderInterface, ConfigProviderInterface
{
    const VERSION = '3.0.2dev';

    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }
    
    public function getAutoloaderConfig()
    {
    	return array(
//     			'Zend\Loader\ClassMapAutoloader' => array(
//     					__DIR__ . '/autoload_classmap.php',
//     			),
    			'Zend\Loader\StandardAutoloader' => array(
    					'namespaces' => array(
    							__NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
    					),
    			),
    	);
    }
    
    public function onBootstrap(MvcEvent $e)
    {
    	$application			= $e->getApplication();
    	$eventManager			= $application->getEventManager();
    	$serviceManager			= $application->getServiceManager();
    	$moduleRouteListener	= new ModuleRouteListener();
    	$moduleRouteListener->attach($eventManager);
    	 
    	// get the cache listener service
    	$sm = $e->getApplication()->getServiceManager();
    	$cacheListener = $sm->get('CacheListener');
    	 
    	// attach the listeners to the event manager
    	$cacheListener->attach($eventManager);
    	
    	// The following line instantiates the SessionManager and automatically
    	// makes the SessionManager the 'default' one.
    	$sessionManager = $serviceManager->get(SessionManager::class);
    }
}

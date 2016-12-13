<?php
namespace Application\Service\Factory;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Application\Model\CacheListener;
use Interop\Container\ContainerInterface;

class CacheListenerFactory implements FactoryInterface {
	
	public function __invoke(ContainerInterface $container, $requestedName, array $options = null) {
		$cacheService = $container->get('Zend\Cache');
		 
		return new CacheListener($cacheService);
	}

	public function createService(ServiceLocatorInterface $serviceLocator) {
		return new CacheListener($serviceLocator->get('Zend\Cache'));
	}
}

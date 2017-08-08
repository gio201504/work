<?php
namespace Application\Service\Factory;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Application\Service\Emplacements;
use Interop\Container\ContainerInterface;

class EmplacementsFactory implements FactoryInterface {
	
	public function __invoke(ContainerInterface $container, $requestedName, array $options = null) {
		return new Emplacements($container);
	}

	public function createService(ServiceLocatorInterface $serviceLocator) {
		return new Emplacements($serviceLocator);
	}
}

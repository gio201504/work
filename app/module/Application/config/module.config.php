<?php
/**
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application;

use Zend\Router\Http\Literal;
use Zend\Router\Http\Segment;
use Zend\ServiceManager\Factory\InvokableFactory;

return [
    'router' => [
        'routes' => [
            'home' => [
                'type' => Literal::class,
                'options' => [
                    'route'    => '/',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'application' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/application[/:action]',
                    'constraints' => array(
                    	'action' => '[scan|generate|check][a-zA-Z0-9_-]*',
                    ),
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'index',
                        'cache' => false
                    ],
                ],
            ],
            'get' => [
	            'type'    => Segment::class,
	            'options' => [
	            'route'    => '/application[/:action]',
	            'constraints' => array(
	            	'action' => '[get][a-zA-Z0-9_-]*',
	            ),
	            'defaults' => [
		            'controller' => Controller\IndexController::class,
		            'action'     => 'index',
		            'cache' => true
	            ],
	            ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\IndexController::class => InvokableFactory::class,
        ],
    ],
    'view_manager' => [
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => [
            'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
            'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/index'             => __DIR__ . '/../view/error/index.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
        'strategies' => [
        	'ViewJsonStrategy'
        ],
    ],
    
    'service_manager' => [
    		'factories' => [
    				'Zend\Cache' => 'Zend\Cache\Service\StorageCacheFactory',
    				'CacheListener' => 'Application\Service\Factory\CacheListenerFactory',
    		],
//     		'abstract_factories' => [
//     				'Zend\Cache\Service\StorageCacheAbstractServiceFactory',
//     				'Zend\Log\LoggerAbstractServiceFactory',
//     		],
//     		'aliases' => [
//     				'translator' => 'MvcTranslator',
//     		],
    ],
    
    'cache' => [
    		'adapter' => 'filesystem',
    		'options' => [
    				'cache_dir' => 'data/cache/fullpage'
    		]
    ],
];

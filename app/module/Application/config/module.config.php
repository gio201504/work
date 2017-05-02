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
use Zend\Log\Logger;
use Application\Controller\IndexController;
use Zend\Log\Writer\Stream;
use Application\Cache\Storage\Adapter\ApcuFilesytem;

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
                    	'action' => '(scan|generate|check|show|stream|transcode)[a-zA-Z0-9_-]*',
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
		            	'action' => '(get)[a-zA-Z0-9_-]*',
		            ),
		            'defaults' => [
			            'controller' => Controller\IndexController::class,
			            'action'     => 'index',
			            'cache' => false
		            ],
	            ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            //Controller\IndexController::class => InvokableFactory::class,
            Controller\IndexController::class => function($sm) {
            	return new IndexController($sm);
            }
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
    				'log' => function($sm) {
    					$filename = 'log_' . date('Ymd') . '.txt';
    					$log = new Logger();
    					$writer = new Stream(getcwd() . '/log/' . $filename);
    					$log->addWriter($writer);
    					return $log;
    				}
    		],
    		'abstract_factories' => [
    				'Zend\Cache\Service\StorageCacheAbstractServiceFactory',
    				'Zend\Log\LoggerAbstractServiceFactory',
    		],
    		'aliases' => [
    				'translator' => 'MvcTranslator',
    				'apcufilesystem' => ApcuFilesytem::class,
    		],
    ],
    
    'cache' => [
    		'adapter' => 'filesystem',
    		'options' => [
    				'cache_dir' => 'data/cache/fullpage'
    		]
    ],
    
    'caches' => array(
//     		'filecache' => array(
//     				'adapter' => 'filesystem',
//     				'options' => [
//     					'cache_dir' => 'data/cache/filecache'
//     				]
//     		),
    		'apcucache' => array(
    				'adapter' => 'apcu',
    		),
    ),
];

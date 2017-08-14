<?php

namespace BelceburAuth;

use BelceburAuth\Service\AuthenticationFactory;
use Doctrine\ORM\EntityManager;
use DoctrineModule\Authentication\Adapter\ObjectRepository;
use Zend\Authentication\AuthenticationService;
use Zend\Loader\StandardAutoloader;
use Zend\ModuleManager\Feature\DependencyIndicatorInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class Module implements DependencyIndicatorInterface
{

    public function getModuleDependencies(): array
    {
        return array(
            'DoctrineModule',
            'DoctrineORMModule',
        );
    }

    public function getAutoloaderConfig()
    {
        return [
            StandardAutoloader::class => [
                'namespaces' => [
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ],
            ],
        ];
    }

    public function getServiceConfig(): array
    {
        return [
            'factories' => [
                AuthenticationService::class => function (ServiceLocatorInterface $sm) {
                    return $sm->get('AuthenticationFactory');
                },
                'AuthenticationFactory' => function (ServiceLocatorInterface $sm) {
                    /**
                     * @var \DoctrineModule\Authentication\Adapter\ObjectRepository $adapter
                     * @var \Zend\Mvc\Application $application
                     */

                    $application = $sm->get('Application');
                    $config = $application->getConfig();
                    $baseConfig = $config['belcebur']['belcebur-auth'];
                    $service = new AuthenticationFactory($sm);

                    /**
                     * Config Factories
                     */
                    $adaptersConfig = (array)$baseConfig['auth-factories']['config-factories'];
                    foreach ($adaptersConfig as $serviceName => $adapterConfig) {
                        $service->addAuthAdapter(new ObjectRepository(array_merge([
                            'object_manager' => $sm->get(EntityManager::class)
                        ], $adapterConfig)));
                    }

                    /**
                     * Created Factories
                     */
                    $adapterNames = (array)$baseConfig['auth-factories']['sm-factories'];
                    foreach ($adapterNames as $adapterName) {
                        try {
                            $adapter = $sm->get($adapterName);
                            $service->addAuthAdapter($adapter);
                        } catch (\Exception $e) {
                            throw $e;
                        }
                    }


                    return $service;
                },
            ]
        ];

    }

    public function getConfig(): array
    {
        return include __DIR__ . '/config/module.config.php';
    }
}

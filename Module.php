<?php
namespace BelceburAuth;

use BelceburAuth\Service\AuthenticationFactory;
use DoctrineModule\Authentication\Adapter\ObjectRepository;
use Zend\ModuleManager\Feature\DependencyIndicatorInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class Module implements DependencyIndicatorInterface {

    public function getModuleDependencies() {
        return array(
            'DoctrineModule',
            'DoctrineORMModule',
        );
    }

    public function getAutoloaderConfig() {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    public function getServiceConfig() {
        return array(
            'factories' => array(
                'Zend\Authentication\AuthenticationService' => function (ServiceLocatorInterface $sm) {
                    return $sm->get('AuthenticationFactory');
                },
                'AuthenticationFactory'                     => function (ServiceLocatorInterface $sm) {
                    /**
                     * @var \DoctrineModule\Authentication\Adapter\ObjectRepository $adapter
                     * @var \Zend\Mvc\Application                                   $application
                     */

                    $application = $sm->get('Application');
                    $config      = $application->getConfig();
                    $baseConfig  = $config['belcebur']['belcebur-auth'];
                    $service     = new AuthenticationFactory($sm);

                    /**
                     * Config Factories
                     */
                    $adaptersConfig = $baseConfig['auth-factories']['config-factories'];
                    foreach ($adaptersConfig as $serviceName => $adapterConfig) {
                        $service->addAuthAdapter(new ObjectRepository(array_merge(array(
                            'object_manager' => $sm->get('Doctrine\ORM\EntityManager')
                        ), $adapterConfig)));
                    }

                    /**
                     * Created Factories
                     */
                    $adapterNames = $baseConfig['auth-factories']['sm-factories'];
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
            )
        );

    }

    public function getConfig() {
        return include __DIR__ . '/config/module.config.php';
    }
}

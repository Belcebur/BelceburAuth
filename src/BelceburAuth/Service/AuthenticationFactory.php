<?php
/**
 * Created by PhpStorm.
 * User: dgarcia
 * Date: 06/08/2015
 * Time: 11:00
 */

namespace BelceburAuth\Service;


use Doctrine\Common\Collections\ArrayCollection;
use DoctrineModule\Authentication\Adapter\ObjectRepository as Adapter;
use Zend\Authentication\Adapter\AdapterInterface;
use Zend\Authentication\AuthenticationService;
use Zend\ServiceManager\ServiceLocatorInterface;

class AuthenticationFactory extends AuthenticationService {


    /**
     * @var ArrayCollection
     */
    private $authAdapters;
    /**
     * @var ArrayCollection
     */
    private $authStorage;

    /**
     * @var \Zend\ServiceManager\ServiceManager
     */
    private $sm;

    function __construct(ServiceLocatorInterface $sm) {
        /**
         * @var \Zend\ServiceManager\ServiceManager $sm
         * @var \Zend\Mvc\Application               $application
         */
        $this->sm           = $sm;
        $this->authAdapters = new ArrayCollection();
        $this->authStorage  = new ArrayCollection();
    }

    /**
     * @param Adapter $authAdapter
     */
    public function addAuthAdapter($authAdapter) {
        if (!$this->getAuthAdapters()->contains($authAdapter)) {
            $this->authAdapters->add($authAdapter);
        }
    }

    /**
     * @return ArrayCollection
     */
    public function getAuthAdapters() {
        return $this->authAdapters;
    }

    /**
     * @param object|string $identityClass
     *
     * @return Adapter
     */
    public function getAuthAdapter($identityClass) {

        $identityClassName = is_object($identityClass) ? get_class($identityClass) : $identityClass;


        return $this->getAuthAdapters()->filter(function (Adapter $adapter) use ($identityClassName) {
            /**
             * @var \DoctrineModule\Options\Authentication $options
             */

            $return  = FALSE;
            $options = $adapter->getOptions();
            if ($options->getIdentityClass() == $identityClassName) {
                $return = TRUE;
            }

            return $return;
        })->first();
    }


    /**
     * @param AdapterInterface $adapter
     *
     * @return null|\Zend\Authentication\Result
     */
    public function authenticate(AdapterInterface $adapter = NULL) {

        if (!$adapter && $this->getAuthAdapters()->count()) {
            $adapter = $this->getAuthAdapters()->first();
        } elseif (!$adapter) {
            return NULL;
        }
        $result = parent::authenticate($adapter);

        return $result;
    }

    /**
     * @return ArrayCollection
     */
    public function getAuthStorage() {
        return $this->authStorage;
    }

    /**
     * @return mixed|null|object
     */
    public function getIdentity() {
        $identity = parent::getIdentity();
        if ($identity) {
            /**
             * @var \Doctrine\ORM\EntityManager         $em
             * @var \Doctrine\ORM\Mapping\ClassMetadata $meta
             */
            $em             = $this->getSm()->get('Doctrine\ORM\EntityManager');
            $meta           = $em->getClassMetadata(get_class($identity));
            $identifierName = current($meta->getIdentifier());

            $methodName = "get" . ucfirst($identifierName);
            if (method_exists($identity, $methodName)) {
                $identityValue = call_user_func(array($identity, $methodName));
                $identity      = $em->getRepository(get_class($identity))->find($identityValue);
            } else {
                $identity = $em->getRepository(get_class($identity))->find($identity->$identifierName);
            }
        }

        return $identity;
    }

    /**
     * @return \Zend\ServiceManager\ServiceManager
     */
    public function getSm() {
        return $this->sm;
    }


}
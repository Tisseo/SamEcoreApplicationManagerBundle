<?php

namespace CanalTP\SamEcoreApplicationManagerBundle\Services;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use CanalTP\SamEcoreApplicationManagerBundle\SamApplication;

/**
 * Allow to get application by several ways
 *
 * @author Kévin ZIEMIANSKI
 */
class ApplicationFinder
{
    protected $requestStack;
    protected $em;
    protected $applicationEntityName;
    protected $container;

    protected $currentApp = null;

    public function __construct($container, $appEntityName)
    {
        $this->requestStack = $container->get('request_stack');
        $this->em = $container->get('doctrine.orm.entity_manager');
        $this->applicationEntityName = $appEntityName;
        $this->container = $container;
    }

    public function findFromUrl()
    {
        if (is_null($this->currentApp)) {
            $res = array();
            preg_match('/\/(\w+)/', $this->requestStack->getMasterRequest()->getPathInfo(), $res);
            $appName = '';

            if (empty($res)) {
                //Get first user's app
                $userRoles = $this->container->get('security.context')->getToken()->getUser()->getUserRoles();

                if (empty($userRoles)) {
                    throw new AccessDeniedException('Votre profil n\'a pas de rôle. Contactez un administrateur.');
                }

                $appName = $userRoles->first()->getApplication()->getCanonicalName();
            } else {
                $appName = strtolower($res[1]);
            }

            //admin is a sam synonym
            if ($appName == 'admin') {
                $appName = 'samcore';
            }

            $app = $this->em->getRepository($this->applicationEntityName)->findOneBy(array('canonicalName' => $appName));

            $this->currentApp = $app;
        }

        return $this->currentApp;
    }

    public function getCurrentApp()
    {
        if ($this->requestStack->getCurrentRequest()->query->has('app')) {
            $app = $this->em->getRepository($this->applicationEntityName)->findOneBy(
                array(
                    'canonicalName' => $this->requestStack->getCurrentRequest()->query->get('app'),
                )
            );
            $this->currentApp = $app;
        } else {
            $this->findFromUrl();
        }

        return ($this->currentApp);
    }

    public function getUserApps(\FOS\UserBundle\Model\UserInterface $user)
    {
        $apps = array();
        foreach ($user->getUserRoles() as $role) {
            $app = $role->getApplication();
            $apps[$app->getId()] = $app->getCanonicalName() == 'samcore' ? 'admin' : $app->getCanonicalName();
        }

        return $apps;
    }

    public function getApplicationBundles()
    {
        $applications = array();
        $kernel = $this->container->get('kernel');
        $bundles = $kernel->getBundles();

        foreach ($bundles as $bundleName => $bundle) {
            if ($bundle instanceof SamApplication) {
                $applications[] = [
                    'bundle' => $bundleName,
                    'app' => $bundle->getCanonicalName()
                ];
            }
        }

        return $applications;
    }
}

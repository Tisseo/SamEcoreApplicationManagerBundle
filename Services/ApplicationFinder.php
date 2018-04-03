<?php

namespace CanalTP\SamEcoreApplicationManagerBundle\Services;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use CanalTP\SamEcoreApplicationManagerBundle\SamApplication;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Allow to get application by several ways
 *
 * @author Kévin ZIEMIANSKI
 */
class ApplicationFinder
{
    /**
     * @var \Symfony\Component\HttpFoundation\RequestStack
     */
    protected $requestStack;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var \Symfony\Component\HttpKernel\HttpKernel
     */
    protected $kernel;

    /**
     * @var TokenStorage
     */
    protected $tokenStorage;

    /**
     * @var string
     */
    protected $appEntityNameFqcn;


    protected $currentApp = null;

    public function __construct(EntityManager $em,  RequestStack $requestStack, Kernel $kernel, TokenStorage $tokenStorage, $appEntityNameFqcn)
    {
        $this->requestStack = $requestStack;
        $this->em = $em;
        $this->kernel = $kernel;
        $this->tokenStorage = $tokenStorage;
        $this->applicationEntityNameFqcn = $appEntityNameFqcn;
    }

    public function findFromUrl()
    {
        if (is_null($this->currentApp)) {
            $res = array();
            preg_match('/\/(\w+)/', $this->requestStack->getMasterRequest()->getPathInfo(), $res);

            if (empty($res)) {
                //Get first user's app
                $userRoles = $this->tokenStorage->getToken()->getUser()->getUserRoles();

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

            $app = $this->em->getRepository($this->applicationEntityNameFqcn)->findOneBy(array('canonicalName' => $appName));

            $this->currentApp = $app;
        }

        return $this->currentApp;
    }

    public function getCurrentApp()
    {
        if ($this->requestStack->getCurrentRequest()->query->has('app')) {
            $app = $this->em->getRepository($this->applicationEntityNameFqcn)->findOneBy(
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
        $bundles = $this->kernel->getBundles();

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

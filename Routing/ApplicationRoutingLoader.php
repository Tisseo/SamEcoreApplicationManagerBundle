<?php

/**
 * Description of RoutingLoader
 *
 * Cette classe charge les routing Yml pour un client donné
 *
 * @author akambi
 * @author Kévin Ziemianski <kevin.ziemianski@canaltp.fr>
 */
namespace CanalTP\SamEcoreApplicationManagerBundle\Routing;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\RouteCollection;
use CanalTP\SamEcoreApplicationManagerBundle\Services\ApplicationFinder;

class ApplicationRoutingLoader extends Loader
{
    private $loaded = false;
    private $applicationFinder;

    public function __construct(ApplicationFinder $applicationFinder)
    {
        $this->applicationFinder = $applicationFinder;
    }

    public function load($resource, $type = null)
    {
        if (true === $this->loaded) {
            throw new \RuntimeException('Do not add the "extra" loader twice');
        }

        $this->loaded = true;

        $collection = new RouteCollection();
        $applications = $this->applicationFinder->getApplicationBundles();

        foreach ($applications as $app) {
            $resource = sprintf('@%s/Resources/config/routing.yml', $app['bundle']);
            $applicationName = $app['app'];
            $type = 'yaml';

            try {
                $importedRoutes = $this->import($resource, $type);

                $route = $applicationName == 'samcore' ? '/admin' : '/'.$applicationName;
                //Change sam to admin for url
                $importedRoutes->addPrefix($route);

                $collection->addCollection($importedRoutes);
            } catch (\InvalidArgumentException $e) {
                //No routing for this bundle, skip
            }
        }

        return $collection;
    }

    public function supports($resource, $type = null)
    {
        return 'sam' === $type;
    }
}

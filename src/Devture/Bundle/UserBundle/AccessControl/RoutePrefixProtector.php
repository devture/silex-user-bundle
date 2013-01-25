<?php
namespace Devture\Bundle\UserBundle\AccessControl;

use Symfony\Component\HttpFoundation\Request;
use Devture\Bundle\UserBundle\AccessControl\RouteProtectorInterface;

class RoutePrefixProtector implements RouteProtectorInterface {

    private $app;
    private $routePrefix;
    private $requiredRole;
    private $whitelistedRoutes;

    public function __construct(\Silex\Application $app, $routePrefix, $requiredRole, array $whitelistedRoutes = array()) {
        $this->app = $app;
        $this->routePrefix = $routePrefix;
        $this->requiredRole = $requiredRole;
        $this->whitelistedRoutes = $whitelistedRoutes;
    }

    public function shouldProtect(Request $request) {
        $routeName = $request->attributes->get('_route');
        if (strpos($routeName, $this->routePrefix) !== 0) {
            return false;
        }
        if (in_array($routeName, $this->whitelistedRoutes)) {
            return false;
        }
        return !$this->app['user.access_control']->isGranted($this->requiredRole);
    }

}

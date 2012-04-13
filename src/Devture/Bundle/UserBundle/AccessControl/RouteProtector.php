<?php
namespace Devture\Bundle\UserBundle\AccessControl;
use Symfony\Component\HttpFoundation\Request;

class RouteProtector implements RouteProtectorInterface {

    private $app;
    private $routeName;
    private $requiredRole;

    public function __construct(\Silex\Application $app, $routeName, $requiredRole) {
        $this->app = $app;
        $this->routeName = $routeName;
        $this->requiredRole = $requiredRole;
    }

    public function shouldProtect(Request $request) {
        if ($request->attributes->get('_route') !== $this->routeName) {
            return false;
        }
        return !$this->app['user.access_control']->isGranted($this->requiredRole);
    }

}

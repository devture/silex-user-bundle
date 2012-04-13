<?php
namespace Devture\Bundle\UserBundle\AccessControl;
use Devture\Bundle\UserBundle\Model\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class AccessControl {

    private $app;
    private $routeProtectionBound = false;
    private $routeProtectors = array();

    public function __construct(\Silex\Application $app) {
        $this->app = $app;
    }

    public function isGranted($role) {
        $user = $this->app['user'];
        if (! ($user instanceof User)) {
            return false;
        }
        return $user->hasRole($role) || $user->hasRole(User::ROLE_MASTER);
    }

    public function isLoggedIn() {
        return ($this->app['user'] instanceof User);
    }

    public function redirectToLogin() {
        $next = $this->app['request']->getRequestUri();
        $url = $this->app['url_generator']->generate('user.login', array('next' => $next));
        return new RedirectResponse($url);
    }

    private function bindRouteProtection() {
        if ($this->routeProtectionBound) {
            return;
        }
        $this->routeProtectionBound = true;

        $self = $this;
        $app = $this->app;
        $routeProtectors = &$this->routeProtectors;
        $app->before(function (Request $request) use ($self, $app, &$routeProtectors) {
            foreach ($routeProtectors as $protector) {
                if ($protector->shouldProtect($request)) {
                    if ($self->isLoggedIn()) {
                        return $app->abort(401);
                    }
                    return $self->redirectToLogin();
                }
            }
        });
    }

    public function protectRoute($routeName, $requiredRole) {
        $this->addRouteProtector(new RouteProtector($this->app, $routeName, $requiredRole));
    }

    public function protectRoutePrefix($routePrefix, $requiredRole, array $whitelistedRoutes = array()) {
        $this->addRouteProtector(new RoutePrefixProtector($this->app, $routePrefix, $requiredRole, $whitelistedRoutes));
    }

    public function addRouteProtector(RouteProtectorInterface $protector) {
        $this->bindRouteProtection();
        $this->routeProtectors[] = $protector;
    }

}

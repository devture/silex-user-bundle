<?php
namespace Devture\Bundle\UserBundle\AccessControl;

use Devture\Bundle\UserBundle\Model\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class AccessControl {

	private $app;
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
		$url = $this->app['url_generator_localized']->generate('user.login', array('next' => $next));
		return new RedirectResponse($url);
	}

	public function getEnforcer() {
		return array($this, 'enforceProtection');
	}

	/**
	 * SilexEvents::BEFORE ($app->before()) filter,
	 * meant to return a response short-circuiting application execution,
	 * when working with a Request that should be protected in a way.
	 *
	 * When doing protection, a response could be of 2 types:
	 *  1. "401 Not Authorized"
	 *  2. Redirect to login page
	 **/
	public function enforceProtection(Request $request) {
		foreach ($this->routeProtectors as $protector) {
			if ($protector->shouldProtect($request)) {
				if ($this->isLoggedIn()) {
					return $this->app->abort(401);
				}
				return $this->redirectToLogin();
			}
		}
	}

	public function protectRoute($routeName, $requiredRole) {
		$this->addRouteProtector(new RouteProtector($this->app, $routeName, $requiredRole));
	}

	public function protectRoutePrefix($routePrefix, $requiredRole, array $whitelistedRoutes = array()) {
		$this->addRouteProtector(new RoutePrefixProtector($this->app, $routePrefix, $requiredRole, $whitelistedRoutes));
	}

	public function addRouteProtector(RouteProtectorInterface $protector) {
		$this->routeProtectors[] = $protector;
	}

}

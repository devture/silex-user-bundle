<?php
namespace Devture\Bundle\UserBundle\AccessControl;

use Devture\Bundle\UserBundle\Model\User;
use Symfony\Component\HttpFoundation\Request;

class AccessControl {

	private $app;
	private $routeProtectors = array();

	public function __construct(\Silex\Application $app) {
		$this->app = $app;
	}

	/**
	 * @return User|NULL
	 */
	public function getUser() {
		return $this->app['user'];
	}

	public function isLoggedIn() {
		return ($this->getUser() instanceof User);
	}

	public function isGranted($role) {
		if (!$this->isLoggedIn()) {
			return false;
		}
		$user = $this->getUser();
		return $user->hasRole($role) || $user->hasRole(User::ROLE_MASTER);
	}

	public function requireAuthForRoute($routeName) {
		$this->addRouteProtector(new RequiredAuthRouteProtector($routeName));
	}

	public function requireAuthForRoutePrefix($routePrefix, array $whitelistedRoutes = array()) {
		$this->addRouteProtector(new RequiredAuthRoutePrefixProtector($routePrefix, $whitelistedRoutes));
	}

	public function requireRoleForRoute($routeName, $requiredRole) {
		$this->addRouteProtector(new RequiredRoleRouteProtector($routeName, $requiredRole));
	}

	public function requireRoleForRoutePrefix($routePrefix, $requiredRole, array $whitelistedRoutes = array()) {
		$this->addRouteProtector(new RequiredRoleRoutePrefixProtector($routePrefix, $requiredRole, $whitelistedRoutes));
	}

	public function addRouteProtector(RouteProtectorInterface $protector) {
		$this->routeProtectors[] = $protector;
	}

	/**
	 * SilexEvents::BEFORE ($app->before()) filter,
	 * meant to return a response short-circuiting application execution,
	 * when working with a Request that should be protected in a way.
	 *
	 * When doing protection, a response could be of 2 types:
	 * 1. "401 Not Authorized" - when authenticated, but not authorized
	 * 2. Redirect to login page - when not authenticated
	 **/
	public function enforceProtection(Request $request) {
		foreach ($this->routeProtectors as $protector) {
			if (!$protector->isAllowed($this, $request)) {
				if ($this->isLoggedIn()) {
					return $this->app->abort(401);
				}
				return $this->redirectToLogin();
			}
		}
	}

	private function redirectToLogin() {
		$next = $this->getRequest()->getRequestUri();
		$url = $this->getUrlGenerator()->generate('devture_user.login', array('next' => $next));
		return $this->app->redirect($url);
	}

	/**
	 * @return \Symfony\Component\Routing\Generator\UrlGeneratorInterface
	 */
	private function getUrlGenerator() {
		return $this->app['url_generator'];
	}

	/**
	 * @return Request
	 */
	private function getRequest() {
		return $this->app['request'];
	}

}

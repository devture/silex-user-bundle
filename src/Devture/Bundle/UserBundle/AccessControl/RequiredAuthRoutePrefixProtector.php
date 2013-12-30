<?php
namespace Devture\Bundle\UserBundle\AccessControl;

use Symfony\Component\HttpFoundation\Request;

class RequiredAuthRoutePrefixProtector implements RouteProtectorInterface {

	private $routePrefix;
	private $whitelistedRoutes;

	public function __construct($routePrefix, array $whitelistedRoutes = array()) {
		$this->routePrefix = $routePrefix;
		$this->whitelistedRoutes = $whitelistedRoutes;
	}

	public function isAllowed(AccessControl $accessControl, Request $request) {
		$routeName = $request->attributes->get('_route');
		if (strpos($routeName, $this->routePrefix) !== 0) {
			return true;
		}
		if (in_array($routeName, $this->whitelistedRoutes)) {
			return true;
		}
		return $accessControl->isLoggedIn();
	}

}
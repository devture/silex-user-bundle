<?php
namespace Devture\Bundle\UserBundle\AccessControl;

use Symfony\Component\HttpFoundation\Request;

class RoutePrefixProtector implements RouteProtectorInterface {

	private $accessControl;
	private $routePrefix;
	private $requiredRole;
	private $whitelistedRoutes;

	public function __construct(AccessControl $accessControl, $routePrefix, $requiredRole, array $whitelistedRoutes = array()) {
		$this->accessControl = $accessControl;
		$this->routePrefix = $routePrefix;
		$this->requiredRole = $requiredRole;
		$this->whitelistedRoutes = $whitelistedRoutes;
	}

	public function isAllowed(Request $request) {
		$routeName = $request->attributes->get('_route');
		if (strpos($routeName, $this->routePrefix) !== 0) {
			return true;
		}
		if (in_array($routeName, $this->whitelistedRoutes)) {
			return true;
		}
		return $this->accessControl->isGranted($this->requiredRole);
	}

}

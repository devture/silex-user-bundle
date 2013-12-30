<?php
namespace Devture\Bundle\UserBundle\AccessControl;

use Symfony\Component\HttpFoundation\Request;

class RequiredRoleRouteProtector implements RouteProtectorInterface {

	private $routeName;
	private $requiredRole;

	public function __construct($routeName, $requiredRole) {
		$this->routeName = $routeName;
		$this->requiredRole = $requiredRole;
	}

	public function isAllowed(AccessControl $accessControl, Request $request) {
		if ($request->attributes->get('_route') !== $this->routeName) {
			return true;
		}
		return $accessControl->isGranted($this->requiredRole);
	}

}

<?php
namespace Devture\Bundle\UserBundle\AccessControl;

use Symfony\Component\HttpFoundation\Request;

class RouteProtector implements RouteProtectorInterface {

	private $accessControl;
	private $routeName;
	private $requiredRole;

	public function __construct(AccessControl $accessControl, $routeName, $requiredRole) {
		$this->accessControl = $accessControl;
		$this->routeName = $routeName;
		$this->requiredRole = $requiredRole;
	}

	public function isAllowed(Request $request) {
		if ($request->attributes->get('_route') !== $this->routeName) {
			return true;
		}
		return $this->accessControl->isGranted($this->requiredRole);
	}

}

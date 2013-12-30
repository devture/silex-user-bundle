<?php
namespace Devture\Bundle\UserBundle\AccessControl;

use Symfony\Component\HttpFoundation\Request;

class RequiredAuthRouteProtector implements RouteProtectorInterface {

	private $routeName;

	public function __construct($routeName) {
		$this->routeName = $routeName;
	}

	public function isAllowed(AccessControl $accessControl, Request $request) {
		if ($request->attributes->get('_route') !== $this->routeName) {
			return true;
		}
		return $accessControl->isLoggedIn();
	}

}
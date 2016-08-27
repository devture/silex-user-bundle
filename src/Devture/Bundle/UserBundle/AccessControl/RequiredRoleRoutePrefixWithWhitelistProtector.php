<?php
namespace Devture\Bundle\UserBundle\AccessControl;

use Symfony\Component\HttpFoundation\Request;

/**
 * Similar to \Devture\Bundle\UserBundle\AccessControl\RequiredRoleRoutePrefixProtector,
 * but takes a whitelisted-route-prefix, instead of a single whitelisted-route's name.
 */
class RequiredRoleRoutePrefixWithWhitelistProtector implements RouteProtectorInterface {

	private $routePrefix;
	private $requiredRole;
	private $whitelistedPrefixes;

	public function __construct($routePrefix, $requiredRole, array $whitelistedPrefixes) {
		$this->routePrefix = $routePrefix;
		$this->requiredRole = $requiredRole;
		$this->whitelistedPrefixes = $whitelistedPrefixes;
	}

	public function isAllowed(\Devture\Bundle\UserBundle\AccessControl\AccessControl $accessControl, Request $request) {
		$routeName = $request->attributes->get('_route');
		if (strpos($routeName, $this->routePrefix) !== 0) {
			//Not applicable.
			return true;
		}
		foreach ($this->whitelistedPrefixes as $whitelistedPrefix) {
			if (strpos($routeName, $whitelistedPrefix) === 0) {
				//Whitelisted
				return true;
			}
		}
		return $accessControl->isGranted($this->requiredRole);
	}

}

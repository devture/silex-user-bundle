<?php
namespace Devture\Bundle\UserBundle\AccessControl;

use Symfony\Component\HttpFoundation\Request;

interface RouteProtectorInterface {

	public function isAllowed(AccessControl $accessControl, Request $request);

}

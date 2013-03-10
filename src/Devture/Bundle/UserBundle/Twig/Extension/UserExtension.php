<?php
namespace Devture\Bundle\UserBundle\Twig\Extension;

use Devture\Bundle\UserBundle\AccessControl\AccessControl;

class UserExtension extends \Twig_Extension {

	private $control;
	private $container;

	public function __construct(AccessControl $control, \Pimple $container) {
		$this->control = $control;
		$this->container = $container;
	}

	public function getName() {
		return 'user';
	}

	public function getFunctions() {
		return array(
			'get_user' => new \Twig_Function_Method($this, 'getUser'),
			'is_logged_in' => new \Twig_Function_Method($this, 'isLoggedIn'),
			'is_granted' => new \Twig_Function_Method($this, 'isGranted'),
		);
	}

	public function getUser() {
		return $this->container['user'];
	}

	public function isLoggedIn() {
		return $this->container['user'] !== null;
	}

	public function isGranted($role) {
		return $this->control->isGranted($role);
	}

}


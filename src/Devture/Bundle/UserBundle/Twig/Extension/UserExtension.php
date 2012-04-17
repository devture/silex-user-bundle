<?php
namespace Devture\Bundle\UserBundle\Twig\Extension;

class UserExtension extends \Twig_Extension {

	protected $container;

	public function __construct(\Pimple $container) {
		$this->container = $container;
	}

	public function getName() {
		return 'user';
	}

	public function getFunctions() {
		return array(
			'get_user' => new \Twig_Function_Method($this, 'getUser'),
			'is_logged_in' => new \Twig_Function_Method($this, 'isLoggedIn'),
		);
	}

	public function getUser() {
		return $this->container['user'];
	}

	public function isLoggedIn() {
		return $this->container['user'] !== null;
	}

}


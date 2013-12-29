<?php
namespace Devture\Bundle\UserBundle\Twig;

use Devture\Bundle\UserBundle\AccessControl\AccessControl;

class UserExtension extends \Twig_Extension {

	private $control;

	public function __construct(AccessControl $control) {
		$this->control = $control;
	}

	public function getName() {
		return 'devture_user_user_extension';
	}

	public function getFunctions() {
		return array(
			'get_user' => new \Twig_Function_Method($this, 'getUser'),
			'is_logged_in' => new \Twig_Function_Method($this, 'isLoggedIn'),
			'is_granted' => new \Twig_Function_Method($this, 'isGranted'),
		);
	}

	/**
	 * @return \Devture\Bundle\UserBundle\Model\User|NULL
	 */
	public function getUser() {
		return $this->control->getUser();
	}

	/**
	 * @return boolean
	 */
	public function isLoggedIn() {
		return $this->control->isLoggedIn();
	}

	/**
	 * @param string $role
	 * @return boolean
	 */
	public function isGranted($role) {
		return $this->control->isGranted($role);
	}

}


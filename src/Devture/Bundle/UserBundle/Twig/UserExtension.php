<?php
namespace Devture\Bundle\UserBundle\Twig;

use Devture\Bundle\UserBundle\AccessControl\AccessControl;

class UserExtension extends \Twig\Extension\AbstractExtension {

	private $control;

	public function __construct(AccessControl $control) {
		$this->control = $control;
	}

	public function getName() {
		return 'devture_user_user_extension';
	}

	public function getFunctions() {
		return array(
			new \Twig\TwigFunction('get_user', array($this, 'getUser')),
			new \Twig\TwigFunction('is_logged_in', array($this, 'isLoggedIn')),
			new \Twig\TwigFunction('is_granted', array($this, 'isGranted')),
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


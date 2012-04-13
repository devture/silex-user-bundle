<?php
namespace Devture\Bundle\UserBundle\Twig\Extension;
use Devture\Bundle\UserBundle\AccessControl\AccessControl;

class AccessControlExtension extends \Twig_Extension {

	protected $control;

	public function __construct(AccessControl $control) {
		$this->control = $control;
	}

	public function getName() {
		return 'user_access_control';
	}

	public function getFunctions() {
		return array(
				'is_granted' => new \Twig_Function_Method($this, 'isGranted'),);
	}

	public function isGranted($role) {
		return $this->control->isGranted($role);
	}

}


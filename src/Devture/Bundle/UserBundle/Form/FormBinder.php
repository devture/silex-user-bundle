<?php
namespace Devture\Bundle\UserBundle\Form;

use Symfony\Component\HttpFoundation\Request;
use Devture\Component\Form\Binder\SetterRequestBinder;
use Devture\Bundle\UserBundle\Model\User;
use Devture\Bundle\UserBundle\Validator\UserValidator;
use Devture\Bundle\UserBundle\Helper\BlowfishPasswordEncoder;

class FormBinder extends SetterRequestBinder {

	private $encoder;

	public function __construct(UserValidator $validator, BlowfishPasswordEncoder $encoder) {
		parent::__construct($validator);
		$this->encoder = $encoder;
	}

	/**
	 * @param User $entity
	 * @param Request $request
	 * @param array $options
	 */
	protected function doBindRequest($entity, Request $request, array $options = array()) {
		//Clear the roles first. If the request does not contain a "roles" value,
		//binding below will just skip it and keep them as they were, which is not what we want.
		$entity->setRoles(array());

		$whitelisted = array('username', 'email', 'name', 'roles');
		$this->bindWhitelisted($entity, $request->request->all(), $whitelisted);

		$password = $request->request->get('password');
		if ($password !== '') {
			if (strlen($password) > 4096) {
				$this->getViolations()->add('password', 'devture_user.validation.password_too_long');
			} else {
				$entity->setPassword($this->encoder->encodePassword($password));
			}
		}
	}

}

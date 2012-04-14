<?php
namespace Devture\Bundle\UserBundle\Twig\Extension;
use Devture\Bundle\UserBundle\Model\User;
use Devture\Bundle\UserBundle\Helper\TokenGenerator;

class UserExtension extends \Twig_Extension {

	protected $user;

	protected $generator;

	public function __construct(User $user = null, TokenGenerator $generator) {
		$this->user = $user;
		$this->generator = $generator;
	}

	public function getName() {
		return 'user';
	}

	public function getFunctions() {
		return array(
			'get_user' => new \Twig_Function_Method($this, 'getUser'),
			'is_logged_in' => new \Twig_Function_Method($this, 'isLoggedIn'),
			'user_token' => new \Twig_Function_Method($this, 'getUserToken'),
		);
	}

	public function getUser() {
		return $this->user;
	}

	public function isLoggedIn() {
		return $this->user !== null;
	}

	public function getUserToken($intention) {
		return $this->generator->generate($intention);
	}

}


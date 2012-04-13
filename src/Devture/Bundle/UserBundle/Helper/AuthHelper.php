<?php
namespace Devture\Bundle\UserBundle\Helper;
use Devture\Bundle\UserBundle\Repository\UserRepository;
use Devture\Bundle\UserBundle\Model\User;
use Devture\Bundle\SharedBundle\Exception\NotFound;

class AuthHelper {

	private $repository;
	private $encoder;
	private $passwordTokenSalt;

	public function __construct(UserRepository $repository, BlowfishPasswordEncoder $encoder, $passwordTokenSalt) {
		$this->repository = $repository;
		$this->encoder = $encoder;
		$this->passwordTokenSalt = $passwordTokenSalt;
	}

	protected function isPasswordMatching(User $user, $password) {
		return $this->encoder->isPasswordValid($user->getPassword(), $password);
	}

	public function authenticate($username, $password) {
		try {
			$user = $this->repository->find($username);
		} catch (NotFound $e) {
			return null;
		}
		if (!$this->isPasswordMatching($user, $password)) {
			return null;
		}
		return $user;
	}

	public function authenticateWithToken($username, $passwordToken) {
		try {
			$user = $this->repository->find($username);
		} catch (NotFound $e) {
			return null;
		}
		if ($this->createPasswordToken($user) !== $passwordToken) {
			return null;
		}
		return $user;
	}

	public function createPasswordToken(User $user) {
		return hash('sha256', $this->passwordTokenSalt . $user->getPassword());
	}

}

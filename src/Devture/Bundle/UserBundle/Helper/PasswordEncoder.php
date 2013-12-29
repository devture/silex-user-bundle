<?php
namespace Devture\Bundle\UserBundle\Helper;

use Devture\Component\Form\Helper\StringHelper;

class PasswordEncoder {

	private $cost;

	public function __construct($cost) {
		$cost = (int) $cost;

		if ($cost < 4 || $cost > 31) {
			throw new \InvalidArgumentException('Cost must be in the range of 4-31');
		}

		if (!function_exists('password_hash')) {
			throw new \RuntimeException('PHP 5.5 or the `ircmaxell/password-compat` composer package is required.');
		}

		$this->cost = $cost;
	}

	public function encodePassword($plain) {
		return password_hash($plain, PASSWORD_BCRYPT, array('cost' => $this->cost));
	}

	public function isPasswordValid($plain, $encoded) {
		return password_verify($plain, $encoded);
	}

}

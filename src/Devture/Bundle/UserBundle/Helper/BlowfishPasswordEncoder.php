<?php
namespace Devture\Bundle\UserBundle\Helper;

use Devture\Component\Form\Helper\StringHelper;

class BlowfishPasswordEncoder {

	private $cost;

	public function __construct($cost) {
		$cost = (int) $cost;

		if ($cost < 4 || $cost > 31) {
			throw new \InvalidArgumentException('Cost must be in the range of 4-31');
		}

		$this->cost = sprintf("%02d", $cost);
	}

	public function encodePassword($raw, $salt = null) {
		$salt = substr(base_convert(sha1(uniqid(mt_rand(), true)), 16, 36), 0, 22);
		return crypt($raw, '$2a$' . $this->cost . '$' . $salt . '$');
	}

	public function isPasswordValid($encoded, $raw, $salt = null) {
		return StringHelper::equals($encoded, crypt($raw, $encoded));
	}

}

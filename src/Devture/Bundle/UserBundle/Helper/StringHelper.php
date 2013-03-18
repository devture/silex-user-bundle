<?php
namespace Devture\Bundle\UserBundle\Helper;

class StringHelper {

	/**
	 * Compares two passwords.
	 *
	 * This method implements a constant-time algorithm to compare strings
	 * (most commonly passwords) to avoid (remote) timing attacks.
	 *
	 * @param string $string1
	 * @param string $string2
	 *
	 * @return Boolean true if the two strings are the same, false otherwise
	 */
	public static function equals($string1, $string2) {
		if (strlen($string1) !== strlen($string2)) {
			return false;
		}

		$result = 0;
		for ($i = 0; $i < strlen($string1); $i++) {
			$result |= ord($string1[$i]) ^ ord($string2[$i]);
		}

		return 0 === $result;
	}

}
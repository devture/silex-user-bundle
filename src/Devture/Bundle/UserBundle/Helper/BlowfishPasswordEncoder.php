<?php
namespace Devture\Bundle\UserBundle\Helper;

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
        return $this->comparePasswords($encoded, crypt($raw, $encoded));
    }

     /**
     * Compares two passwords.
     *
     * This method implements a constant-time algorithm to compare passwords to
     * avoid (remote) timing attacks.
     *
     * @param string $password1 The first password
     * @param string $password2 The second password
     *
     * @return Boolean true if the two passwords are the same, false otherwise
     */
    protected function comparePasswords($password1, $password2) {
        if (strlen($password1) !== strlen($password2)) {
            return false;
        }

        $result = 0;
        for ($i = 0; $i < strlen($password1); $i++) {
            $result |= ord($password1[$i]) ^ ord($password2[$i]);
        }

        return 0 === $result;
    }
}

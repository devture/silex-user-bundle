<?php
namespace Devture\Bundle\UserBundle\Helper;

class TokenGenerator {

    public function __construct($validityTime, $salt) {
        $this->validityTime = $validityTime;
        $this->salt = $salt;
    }

    public function generate($resourceKey, $timestamp = null) {
        if ($timestamp === null) {
            $timestamp = time();
        }
        return $timestamp . '-' . hash('md5', $timestamp . $resourceKey . $this->salt);
    }

    public function isValid($resourceKey, $token) {
        if (strpos($token, '-') === false) {
            return false;
        }
        $parts = explode('-', $token);
        if (count($parts) !== 2) {
            return false;
        }
        list($timestamp, $_hash) = $parts;
        if (! is_numeric($timestamp)) {
            return false;
        }
        $timestamp = (int)$timestamp;
        if ($timestamp + $this->validityTime < time()) {
            //Expired token.. we don't even care if it matches..
            return false;
        }
        return ($this->generate($resourceKey, $timestamp) === $token);
    }


}

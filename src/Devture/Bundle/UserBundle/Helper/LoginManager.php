<?php
namespace Devture\Bundle\UserBundle\Helper;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Devture\Bundle\UserBundle\Model\User;
use Devture\Bundle\UserBundle\Helper\AuthHelper;

class LoginManager {

	const COOKIE_NAME = 'user';

	const FIELD_SIGNATURE = 's';
	const FIELD_PAYLOAD = 'p';
	const FIELD_PAYLOAD_USERNAME = 'u';
	const FIELD_PAYLOAD_TOKEN = 't';

	private $helper;
	private $cookiePayloadSignKey;
	private $cookiePath;

	public function __construct(AuthHelper $helper, $cookiePayloadSignKey, $cookiePath) {
		$this->helper = $helper;
		$this->cookiePayloadSignKey = $cookiePayloadSignKey;
		$this->cookiePath = $cookiePath;
	}

	public function createFromRequest(Request $request) {
		if (! $request->cookies->has(self::COOKIE_NAME)) {
			return null;
		}

		$base64 = $request->cookies->get(self::COOKIE_NAME);

		$json = base64_decode($base64);
		if ($json === false) {
			return null;
		}

		$data = json_decode($json, true, 5);
		if (!is_array($data)) {
			return null;
		}

		if (!isset($data[self::FIELD_SIGNATURE]) || !isset($data[self::FIELD_PAYLOAD]) || !is_array($data[self::FIELD_PAYLOAD])) {
			return null;
		}

		$payload = $data[self::FIELD_PAYLOAD];
		$signature = $data[self::FIELD_SIGNATURE];

		foreach (array(self::FIELD_PAYLOAD_USERNAME, self::FIELD_PAYLOAD_TOKEN) as $k) {
			if (!isset($payload[$k])) {
				return null;
			}
		}

		//See if we can trust that the data hasn't been tampered with.
		if ($this->sign($payload) !== $signature) {
			return null;
		}

		$username = $payload[self::FIELD_PAYLOAD_USERNAME];
		$passwordToken = $payload[self::FIELD_PAYLOAD_TOKEN];

		return $this->helper->authenticateWithToken($username, $passwordToken);
	}

	public function login(User $user, Response $response = null) {
		if ($response === null) {
			$response = new Response();
		}

		$payload = array(
			self::FIELD_PAYLOAD_USERNAME => $user->getUsername(),
			self::FIELD_PAYLOAD_TOKEN => $this->helper->createPasswordToken($user),
		);

		$data = array(self::FIELD_PAYLOAD => $payload, self::FIELD_SIGNATURE => $this->sign($payload));
		$json = json_encode($data);
		$base64 = base64_encode($json);

		$expireTime = 0; //at the end of the session
		$cookie = new Cookie(self::COOKIE_NAME, $base64, $expireTime, $this->cookiePath);
		$response->headers->setCookie($cookie);
		return $response;
	}

	public function logout(Response $response = null) {
		if ($response === null) {
			$response = new Response();
		}

		$expireTime = time() - 30 * 86400;
		$cookie = new Cookie(self::COOKIE_NAME, '', $expireTime, $this->cookiePath);
		$response->headers->setCookie($cookie);
		return $response;
	}

	private function sign(array $payload) {
		$payload = json_encode($payload);
		return hash('sha256', $this->cookiePayloadSignKey. $payload);
	}

}

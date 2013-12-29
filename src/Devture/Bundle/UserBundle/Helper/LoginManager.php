<?php
namespace Devture\Bundle\UserBundle\Helper;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Devture\Component\Form\Helper\StringHelper;
use Devture\Bundle\UserBundle\Model\User;
use Devture\Bundle\UserBundle\Helper\AuthHelper;

class LoginManager {

	const COOKIE_NAME = 'devture_user';
	const COOKIE_VALIDITY_TIME = 43200; //12 hours
	const COOKIE_EXTEND_AFTER_TIME = 1800; //30 minutes

	const FIELD_SIGNATURE = 's';
	const FIELD_PAYLOAD = 'p';
	const FIELD_CREATION_TIME = 'c';
	const FIELD_PAYLOAD_USERNAME = 'u';
	const FIELD_PAYLOAD_TOKEN = 't';

	const REQUEST_ATTRIBUTE_EXTEND_SESSION = 'devture_user.extend_session';

	private $helper;
	private $cookiePayloadSignKey;
	private $cookiePath;

	public function __construct(AuthHelper $helper, $cookiePayloadSignKey, $cookiePath) {
		$this->helper = $helper;
		$this->cookiePayloadSignKey = $cookiePayloadSignKey;
		$this->cookiePath = $cookiePath;
	}

	/**
	 * @param Request $request
	 * @return NULL|User
	 */
	public function createUserFromRequest(Request $request) {
		$payload = $this->getCookieData($request);

		if ($payload === null) {
			//No cookie or an invalid one.
			return null;
		}

		if ($payload[self::FIELD_CREATION_TIME] < time() - self::COOKIE_VALIDITY_TIME) {
			//The cookie is too old for us to trust it.
			//The browser session obviously stayed active for a long time and it hasn't expired.
			return null;
		}

		$user = $this->helper->authenticateWithToken($payload[self::FIELD_PAYLOAD_USERNAME], $payload[self::FIELD_PAYLOAD_TOKEN]);

		if ($user !== null && $payload[self::FIELD_CREATION_TIME] < time() - self::COOKIE_EXTEND_AFTER_TIME) {
			//The current cookie is still valid and can be tied to a user,
			//but is due for extension. Let's mark it as such.
			$request->attributes->set(self::REQUEST_ATTRIBUTE_EXTEND_SESSION, (string) $user->getId());
		}

		return $user;
	}

	/**
	 * @param User $user
	 * @param Response $response
	 * @return Response
	 */
	public function login(User $user, Response $response = null) {
		if ($response === null) {
			$response = new Response();
		}

		$payload = array(
			self::FIELD_PAYLOAD_USERNAME => $user->getUsername(),
			self::FIELD_PAYLOAD_TOKEN => $this->helper->createPasswordToken($user),
			self::FIELD_CREATION_TIME => time(),
		);

		$data = array(
			self::FIELD_PAYLOAD => $payload,
			self::FIELD_SIGNATURE => $this->sign($payload),
		);
		$base64 = base64_encode(json_encode($data));

		$expireTime = 0; //at the end of the session
		$cookie = new Cookie(self::COOKIE_NAME, $base64, $expireTime, $this->cookiePath);
		$response->headers->setCookie($cookie);
		return $response;
	}

	/**
	 * @param Response $response
	 * @return Response
	 */
	public function logout(Response $response = null) {
		if ($response === null) {
			$response = new Response();
		}

		$expireTime = time() - 30 * 86400;
		$cookie = new Cookie(self::COOKIE_NAME, '', $expireTime, $this->cookiePath);
		$response->headers->setCookie($cookie);
		return $response;
	}

	/**
	 * @param User $user
	 * @param Request $request
	 * @param Response $response
	 */
	public function extendSessionIfNeeded(User $user, Request $request, Response $response) {
		if (!$request->attributes->has(self::REQUEST_ATTRIBUTE_EXTEND_SESSION)) {
			return;
		}

		$id = $request->attributes->get(self::REQUEST_ATTRIBUTE_EXTEND_SESSION);
		if ((string) $user->getId() !== $id) {
			//The user whose session we were about to extend is different than the one given.
			//Something weird is going on. Don't perform the extension on this request/response cycle.
			return;
		}

		$this->login($user, $response);
	}

	private function sign(array $payload) {
		$payload = json_encode($payload);
		return hash('sha256', $this->cookiePayloadSignKey. $payload);
	}

	private function getCookieData(Request $request) {
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

		foreach (array(self::FIELD_PAYLOAD_USERNAME, self::FIELD_PAYLOAD_TOKEN, self::FIELD_CREATION_TIME) as $k) {
			if (!isset($payload[$k])) {
				return null;
			}
		}

		//See if we can trust that the data hasn't been tampered with.
		if (!StringHelper::equals($this->sign($payload), $signature)) {
			return null;
		}

		return $payload;
	}

}

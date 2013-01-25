<?php
namespace Devture\Bundle\UserBundle\Helper;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Devture\Bundle\UserBundle\Model\User;
use Devture\Bundle\UserBundle\Helper\AuthHelper;

class LoginManager {
    const COOKIE_NAME = 'user';

    protected $helper;

    protected $cookiePayloadSignKey;

    protected $cookiePath;

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

        //See if a signature and payload can be found.
        if (isset($data['s']) && isset($data['p']) && is_array($data['p'])) {
            $payload = $data['p'];
            $signature = $data['s'];

            //See if we can trust that the data hasn't been tampered with.
            if ($this->sign($payload) === $signature) {
                $username = $payload['u'];
                $passwordToken = $payload['t'];
                return $this->helper->authenticateWithToken($username, $passwordToken);
            }
        }

        return null;
    }

    protected function sign(array $payload) {
        $payload = json_encode($payload);
        return hash('sha256', $this->cookiePayloadSignKey. $payload);
    }

    public function login(User $user, Response $response = null) {
        if ($response === null) {
            $response = new Response();
        }

        $payload = array(
            'u' => $user->getUsername(),
            't' => $this->helper->createPasswordToken($user),
        );

        $data = array('p' => $payload, 's' => $this->sign($payload));
        $json = json_encode($data);
        $base64 = base64_encode($json);

        $expireTime = time() + 30 * 86400;
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

}

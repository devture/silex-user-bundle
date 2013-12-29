<?php
namespace Devture\Bundle\UserBundle\Controller;

use Devture\Bundle\UserBundle\Helper\AuthHelper;
use Devture\Bundle\UserBundle\Helper\LoginManager;
use Devture\Bundle\UserBundle\Repository\UserRepositoryInterface;

class BaseController extends \Devture\Bundle\FrameworkBundle\Controller\BaseController {

	/**
	 * @return AuthHelper
	 */
	protected function getAuthHelper() {
		return $this->getNs('auth_helper');
	}

	/**
	 * @return LoginManager
	 */
	protected function getLoginManager() {
		return $this->getNs('login_manager');
	}

	/**
	 * @return UserRepositoryInterface
	 */
	protected function getRepository() {
		return $this->getNs('repository');
	}

	protected function getHomepageUrl() {
		return $this->generateUrl('homepage');
	}

}
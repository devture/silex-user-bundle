<?php
namespace Devture\Bundle\UserBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Devture\Component\DBAL\Exception\NotFound;

class UserController extends BaseController {

	public function loginAction(Request $request) {
		$next = $request->query->has('next') ? $request->query->get('next') : $this->getHomepageUrl();

		if ($this->get('user') !== null) {
			return $this->redirect($next);
		}

		$error = null;
		$username = null;
		if ($request->getMethod() === 'POST') {
			$username = $request->request->get('username');
			$password = $request->request->get('password');

			if ($user = $this->getAuthHelper()->authenticate($username, $password)) {
				$response = $this->redirect($next);
				return $this->getLoginManager()->login($user, $response);
			} else {
				$error = 'devture_user.wrong_credentials';
			}
		}

		return $this->renderView('DevtureUserBundle/login.html.twig', array(
			'error' => $error,
			'username' => $username,
		));
	}

	public function logoutAction($token) {
		$response = $this->redirect($this->getHomepageUrl());
		if ($this->get('devture_framework.csrf_token_manager')->isValid('logout', $token)) {
			if ($this->get('user') !== null) {
				$response = $this->redirect($this->generateUrlNs('logged_out'));
				$this->getLoginManager()->logout($response);
			}
		}
		return $response;
	}

	public function loggedOutAction() {
		if ($this->get('user') !== null) {
			return $this->redirect($this->getHomepageUrl());
		}
		return $this->renderView('DevtureUserBundle/logged_out.html.twig');
	}

	public function manageAction() {
		$this->getRepository()->ensureIndexes();

		return $this->renderView('DevtureUserBundle/index.html.twig', array(
			'items' => $this->getRepository()->findAll(),
		));
	}

	public function addAction(Request $request) {
		$entity = $this->getRepository()->createModel(array());

		$binder = $this->getFormBinder();

		if ($request->getMethod() === 'POST' && $binder->bind($entity, $request)) {
			$this->getRepository()->add($entity);
			return $this->redirect($this->generateUrlNs('manage'));
		}

		return $this->renderView('DevtureUserBundle/record.html.twig', array(
			'entity' => $entity,
			'isAdded' => false,
			'form' => $binder,
			'roles' => $this->getNs('roles'),
		));
	}

	public function editAction(Request $request, $id) {
		try {
			$entity = $this->getRepository()->find($id);
		} catch (NotFound $e) {
			return $this->abort(404);
		}

		$binder = $this->getFormBinder();

		if ($request->getMethod() === 'POST' && $binder->bind($entity, $request)) {
			$this->getRepository()->update($entity);
			return $this->redirect($this->generateUrlNs('manage'));
		}

		return $this->renderView('DevtureUserBundle/record.html.twig', array(
			'entity' => $entity,
			'isAdded' => true,
			'form' => $binder,
			'roles' => $this->getNs('roles'),
		));
	}

	public function deleteAction($id, $token) {
		$intention = 'delete-user-' . $id;
		if ($this->get('devture_framework.csrf_token_manager')->isValid($intention, $token)) {
			try {
				$this->getRepository()->delete($this->getRepository()->find($id));
			} catch (NotFound $e) {

			}
			return $this->json(array('ok' => true));
		}
		return $this->json(array('ok' => false));
	}

	/**
	 * @return \Devture\Bundle\UserBundle\Form\FormBinder
	 */
	private function getFormBinder() {
		return $this->getNs('form_binder');
	}

}

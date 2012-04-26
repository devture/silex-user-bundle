<?php
namespace Devture\Bundle\UserBundle\Controller;
use Silex\ControllerCollection;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Devture\Bundle\SharedBundle\Exception\NotFound;

class ControllersProvider implements ControllerProviderInterface {

	public function connect(Application $app) {
		$controllers = new ControllerCollection();


		$controllers->match('/login', function (Request $request) use ($app) {
			if ($app['user'] !== null) {
				return $app->redirect($app['url_generator_localized']->generate('homepage'));
			}

			$error = null;
			$username = null;
			if ($request->getMethod() === 'POST') {
				$username = $request->request->get('username');
				$password = $request->request->get('password');

				if ($user = $app['user.auth_helper']->authenticate($username, $password)) {
					$next = $request->query->has('next') ? $request->query->get('next') : $app['url_generator_localized']->generate('homepage');
					$response = new RedirectResponse($next);
					return $app['user.login_manager']->login($user, $response);
				} else {
					$error = $app['translator']->trans('user.wrong_credentials');
				}
			}

			return $app['twig']->render('DevtureUserBundle/login.html.twig', array('error' => $error, 'username' => $username));
		})->value('locale', $app['default_locale'])->method('GET|POST')->bind('user.login');


		$controllers->post('/logout/{token}', function ($token) use ($app) {
			$response = new RedirectResponse($app['url_generator_localized']->generate('homepage'));
			if ($app['shared.csrf_token_generator']->isValid('logout', $token)) {
				if ($app['user'] !== null) {
					$app['user.login_manager']->logout($response);
				}
			}
			return $response;
		})->value('locale', $app['default_locale'])->bind('user.logout');


		$controllers->get('/manage', function () use ($app) {
			return $app['twig']->render('DevtureUserBundle/index.html.twig', array('items' => $app['user.repository']->findAll()));
		})->value('locale', $app['default_locale'])->bind('user.manage');


		$controllers->match('/add', function (Request $request) use ($app) {
			$entity = $app['user.repository']->createModel(array());

			$binder = $app['user.form_binder'];

			if ($request->getMethod() === 'POST' && $binder->bindProtectedRequest($entity, $request)) {
				$app['user.repository']->add($entity);
				return $app->redirect($app['url_generator_localized']->generate('user.manage'));
			}

			return $app['twig']->render('DevtureUserBundle/record.html.twig', array(
				'entity' => $entity,
				'isAdded' => false,
				'form' => $binder,
				'roles' => $app['user.roles'],
			));
		})->value('locale', $app['default_locale'])->method('GET|POST')->bind('user.add');


		$controllers->match('/edit/{id}', function (Request $request, $id) use ($app) {
			try {
				$entity = $app['user.repository']->find($id);
			} catch (NotFound $e) {
				return $app->abort(404);
			}

			$binder = $app['user.form_binder'];

			if ($request->getMethod() === 'POST' && $binder->bindProtectedRequest($entity, $request)) {
				$app['user.repository']->update($entity);
				return $app->redirect($app['url_generator_localized']->generate('user.manage'));
			}

			return $app['twig']->render('DevtureUserBundle/record.html.twig', array(
				'entity' => $entity,
				'isAdded' => true,
				'form' => $binder,
				'roles' => $app['user.roles'],
			));
		})->value('locale', $app['default_locale'])->method('GET|POST')->bind('user.edit');

		$controllers->post('/delete/{id}/{token}', function (Request $request, $id, $token) use ($app) {
			$intention = 'delete-user-' . $id;
			if ($app['shared.csrf_token_generator']->isValid($intention, $token)) {
				try {
					$app['user.repository']->delete($app['user.repository']->find($id));
				} catch (NotFound $e) {

				}
				return $app->json(array('ok' => true));
			}
			return $app->json(array());
		})->value('locale', $app['default_locale'])->bind('user.delete');


		return $controllers;
	}

}


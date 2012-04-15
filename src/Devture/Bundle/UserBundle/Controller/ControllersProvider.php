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
                return $app->redirect($app['url_generator']->generate('homepage'));
            }

            $error = null;
            $username = null;
            if ($request->getMethod() === 'POST') {
                $username = $request->request->get('username');
                $password = $request->request->get('password');

                if ($user = $app['user.auth_helper']->authenticate($username, $password)) {
                    $next = $request->query->has('next') ? $request->query->get('next') : $app['url_generator']->generate('homepage');
                    $response = new RedirectResponse($next);
                    return $app['user.login_manager']->login($user, $response);
                } else {
                    $error = $app['translator']->trans('user.wrong_credentials');
                }
            }

            return $app['twig']->render('DevtureUserBundle/login.html.twig', array('error' => $error, 'username' => $username));
        })->value('locale', $app['default_locale'])->method('GET|POST')->bind('user.login');


        $controllers->post('/logout/{token}', function ($token) use ($app) {
            $response = new RedirectResponse($app['url_generator']->generate('homepage'));
            if ($app['user.token_generator']->isValid('logout', $token)) {
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
            $validator = null;

            if ($request->getMethod() === 'POST') {
                $app['user.form_record_binder']->bind($entity, $request, array('bindUsername' => true));
                $validator = $app['user.validator'];
                if ($validator->isValid($entity)) {
                    $app['user.repository']->add($entity);
                    return $app->redirect($app['url_generator']->generate('user.manage'));
                }
            }

            return $app['twig']->render('DevtureUserBundle/record.html.twig', array(
                'entity' => $entity, 'isAdded' => false, 'validator' => $validator,
                'roles' => $app['user.roles'],
            ));
        })->value('locale', $app['default_locale'])->method('GET|POST')->bind('user.add');


        $controllers->match('/edit/{id}', function (Request $request, $id) use ($app) {
            try {
                $entity = $app['user.repository']->find($id);
            } catch (NotFound $e) {
                return $app->abort(404);
            }

            $validator = null;

            if ($request->getMethod() === 'POST') {
                $app['user.form_record_binder']->bind($entity, $request);
                $validator = $app['user.validator'];
                if ($validator->isValid($entity, array('skipUniquenessCheck' => true))) {
                    $app['user.repository']->update($entity);
                    return $app->redirect($app['url_generator']->generate('user.manage'));
                }
            }

            return $app['twig']->render('DevtureUserBundle/record.html.twig', array(
                'entity' => $entity, 'isAdded' => true, 'validator' => $validator,
                'roles' => $app['user.roles'],
            ));
        })->value('locale', $app['default_locale'])->method('GET|POST')->bind('user.edit');

        $controllers->post('/delete/{id}/{token}', function (Request $request, $id, $token) use ($app) {
            $intention = 'delete-user-' . $id;
            if ($app['user.token_generator']->isValid($intention, $token)) {
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


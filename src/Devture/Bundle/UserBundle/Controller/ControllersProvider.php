<?php
namespace Devture\Bundle\UserBundle\Controller;

use Silex\Application;

class ControllersProvider implements \Silex\Api\ControllerProviderInterface {

	public function connect(Application $app) {
		$controllers = $app['controllers_factory'];

		$controllers->match('/login', 'devture_user.controller.user:loginAction')
			->method('GET|POST')->bind('devture_user.login');

		$controllers->post('/logout/{token}', 'devture_user.controller.user:logoutAction')
			->bind('devture_user.logout');

		$controllers->get('/logged-out', 'devture_user.controller.user:loggedOutAction')
			->bind('devture_user.logged_out');

		$controllers->get('/manage', 'devture_user.controller.user:manageAction')
			->bind('devture_user.manage');

		$controllers->match('/add', 'devture_user.controller.user:addAction')
			->method('GET|POST')->bind('devture_user.add');

		$controllers->match('/edit/{id}', 'devture_user.controller.user:editAction')
			->method('GET|POST')->bind('devture_user.edit');

		$controllers->post('/_delete/{id}/{token}', 'devture_user.controller.user:deleteAction')
			->bind('devture_user.delete');

		return $controllers;
	}

}


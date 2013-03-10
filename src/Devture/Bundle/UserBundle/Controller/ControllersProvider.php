<?php
namespace Devture\Bundle\UserBundle\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;

class ControllersProvider implements ControllerProviderInterface {

	public function connect(Application $app) {
		$controllers = $app['controllers_factory'];

		$controllers->match('/login', 'user.controller.user:loginAction')
			->value('locale', $app['default_locale'])->method('GET|POST')->bind('user.login');

		$controllers->post('/logout/{token}', 'user.controller.user:logoutAction')
			->value('locale', $app['default_locale'])->bind('user.logout');

		$controllers->get('/manage', 'user.controller.user:manageAction')
			->value('locale', $app['default_locale'])->bind('user.manage');

		$controllers->match('/add', 'user.controller.user:addAction')
			->value('locale', $app['default_locale'])->method('GET|POST')->bind('user.add');

		$controllers->match('/edit/{id}', 'user.controller.user:editAction')
			->value('locale', $app['default_locale'])->method('GET|POST')->bind('user.edit');

		$controllers->post('/_delete/{id}/{token}', 'user.controller.user:deleteAction')
			->value('locale', $app['default_locale'])->bind('user.delete');

		return $controllers;
	}

}


<?php
namespace Devture\Bundle\UserBundle;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Silex\Application;

class ServicesProvider implements \Pimple\ServiceProviderInterface, \Silex\Api\BootableProviderInterface {

	private $config;

	public function __construct(array $config) {
		$config = array_merge(array(
			'database_type' => 'mongodb', //relational, mongodb
			'cookie_path' => '/',
			'blowfish_cost' => 13,
			'roles' => array(), //role key => description
		), $config);

		$requiredConfigKeys = array('database_service_id', 'password_token_salt', 'cookie_signing_secret');
		foreach ($requiredConfigKeys as $k) {
			if (!array_key_exists($k, $config)) {
				throw new \InvalidArgumentException(sprintf('The %s parameter passed to %s is missing.', $k, __CLASS__));
			}
		}

		$this->config = $config;
	}

	public function register(\Pimple\Container $container) {
		$config = $this->config;

		$container['user'] = null;

		$container['devture_user.roles'] = $config['roles'];

		$container['devture_user.db'] = function ($container) use ($config) {
			return $container[$config['database_service_id']];
		};

		if ($config['database_type'] === 'relational') {
			$container['devture_user.repository'] = function ($container) {
				return new Repository\Relational\UserRepository($container['devture_user.db']);
			};
		} else if ($config['database_type'] === 'mongodb') {
			$container['devture_user.repository'] = function ($container) {
				return new Repository\MongoDB\UserRepository($container['devture_user.db']);
			};
		} else {
			throw new \InvalidArgumentException('Unrecognized database type: ' . $config['database_type']);
		}

		$container['devture_user.password_encoder'] = function ($container) use ($config) {
			return new Helper\PasswordEncoder($config['blowfish_cost']);
		};

		$container['devture_user.auth_helper'] = function ($container) use ($config) {
			return new Helper\AuthHelper($container['devture_user.repository'], $container['devture_user.password_encoder'], $config['password_token_salt']);
		};

		$container['devture_user.login_manager'] = function ($container) use ($config) {
			return new Helper\LoginManager($container['devture_user.auth_helper'], $config['cookie_signing_secret'], $config['cookie_path']);
		};

		$container['devture_user.access_control'] = function ($container) {
			return new AccessControl\AccessControl($container);
		};

		$container['devture_user.validator'] = function ($container) {
			return new Validator\UserValidator($container['devture_user.repository'], $container['devture_user.roles']);
		};

		$container['devture_user.form_binder'] = function ($container) {
			$binder = new Form\FormBinder($container['devture_user.validator'], $container['devture_user.password_encoder']);
			$binder->setCsrfProtection($container['devture_framework.csrf_token_manager'], 'user');
			return $binder;
		};

		$container['devture_user.listener.user_from_request_initializer'] = $container->protect(function (Request $request) use ($container) {
			$container['user'] = $container['devture_user.login_manager']->createUserFromRequest($request);
		});

		$container['devture_user.listener.csrf_token_manager_salter'] = $container->protect(function (Request $request) use ($container) {
			if ($container['user'] instanceof Model\User) {
				$container['devture_framework.csrf_token_manager']->setSalt($container['user']->getUsername());
			}
		});

		$container['devture_user.listener.conditional_session_extender'] = $container->protect(function (Request $request, Response $response) use ($container) {
			if ($container['user'] instanceof Model\User) {
				$container['devture_user.login_manager']->extendSessionIfNeeded($container['user'], $request, $response);
			}
		});

		$container['devture_user.twig.user_extension'] = function ($container) {
			return new Twig\UserExtension($container['devture_user.access_control'], $container);
		};

		$this->registerConsoleServices($container);

		$this->registerControllerServices($container);
	}

	private function registerConsoleServices(Application $container) {
		$container['devture_user.console.command.add_user'] = function ($container) {
			return new ConsoleCommand\AddUserCommand($container);
		};
		$container['devture_user.console.command.change_user_password'] = function ($container) {
			return new ConsoleCommand\ChangeUserPasswordCommand($container);
		};
	}

	private function registerControllerServices(Application $container) {
		$container['devture_user.controllers_provider.management'] = function ($container) {
			return new Controller\ControllersProvider();
		};

		$container['devture_user.controller.user'] = function ($container) {
			return new Controller\UserController($container, 'devture_user');
		};

		$container['devture_user.public_routes'] = array('devture_user.login', 'devture_user.logout', 'devture_user.logged_out');
	}

	public function boot(\Silex\Application $app) {
		$app['devture_localization.translator.resource_loader']->addResources(dirname(__FILE__) . '/Resources/translations/');

		$app->before($app['devture_user.listener.user_from_request_initializer']);
		$app->before($app['devture_user.listener.csrf_token_manager_salter']);
		$app->before(array($app['devture_user.access_control'], 'enforceProtection'));
		$app->after($app['devture_user.listener.conditional_session_extender']);

		//Also register the templates path at a custom namespace, to allow templates overriding+extending.
		$app['twig.loader.filesystem']->addPath(__DIR__ . '/Resources/views/');
		$app['twig.loader.filesystem']->addPath(__DIR__ . '/Resources/views/', 'DevtureUserBundle');

		$app['twig']->addExtension($app['devture_user.twig.user_extension']);

		if (isset($app['console'])) {
			$app['console']->add($app['devture_user.console.command.add_user']);
			$app['console']->add($app['devture_user.console.command.change_user_password']);
		}
	}

}

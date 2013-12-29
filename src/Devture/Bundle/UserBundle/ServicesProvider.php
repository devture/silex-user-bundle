<?php
namespace Devture\Bundle\UserBundle;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ServicesProvider implements ServiceProviderInterface {

	private $config;

	public function __construct(array $config) {
		$config = array_merge(array(
			'database_type' => 'mongodb', //relational, mongodb
			'browser_id' => array(
				'enabled' => false,
				'audience' => null,
			)
		), $config);

		$this->config = $config;
	}

	public function register(Application $app) {
		$config = $this->config;

		$app['user'] = null;

		$app['devture_user.roles'] = $config['roles'];

		$app['devture_user.browser_id.enabled'] = $config['browser_id']['enabled'];
		$app['devture_user.browser_id.audience'] = $config['browser_id']['audience'];

		if ($app['devture_user.browser_id.enabled']) {
			$app['devture_user.browser_id.verifier'] = function ($app) {
				return new \browserid\Verifier($app['devture_user.browser_id.audience']);
			};
		}

		$app['devture_user.db'] = $app->share(function ($app) use ($config) {
			return $app[$config['database_service_id']];
		});

		if ($config['database_type'] === 'relational') {
			$app['devture_user.repository'] = $app->share(function ($app) {
				return new Repository\Relational\UserRepository($app['devture_user.db']);
			});
		} else if ($config['database_type'] === 'mongodb') {
			$app['devture_user.repository'] = $app->share(function ($app) {
				return new Repository\MongoDB\UserRepository($app['devture_user.db']);
			});
		} else {
			throw new \InvalidArgumentException('Unrecognized database type: ' . $config['database_type']);
		}

		$app['devture_user.password_encoder'] = $app->share(function ($app) use ($config) {
			return new Helper\BlowfishPasswordEncoder($config['blowfish_cost']);
		});

		$app['devture_user.auth_helper'] = $app->share(function ($app) use ($config) {
			$helper = new Helper\AuthHelper($app['devture_user.repository'], $app['devture_user.password_encoder'], $config['password_token_salt']);
			if ($app['devture_user.browser_id.enabled']) {
				$helper->setBrowserIdVerifier($app['devture_user.browser_id.verifier']);
			}
			return $helper;
		});

		$app['devture_user.login_manager'] = $app->share(function ($app) use ($config) {
			return new Helper\LoginManager($app['devture_user.auth_helper'], $config['cookie_signing_secret'], $config['cookie_path']);
		});

		$app['devture_user.access_control'] = $app->share(function ($app) {
			return new AccessControl\AccessControl($app);
		});

		$app['devture_user.validator'] = function ($app) {
			return new Validator\UserValidator($app['devture_user.repository'], $app['devture_user.roles']);
		};

		$app['devture_user.form_binder'] = function ($app) {
			$binder = new Form\FormBinder($app['devture_user.validator'], $app['devture_user.password_encoder']);
			$binder->setCsrfProtection($app['devture_framework.csrf_token_manager'], 'user');
			return $binder;
		};

		$app['devture_user.listener.user_from_request_initializer'] = $app->protect(function (Request $request) use ($app) {
			$app['user'] = $app['devture_user.login_manager']->createUserFromRequest($request);
		});

		$app['devture_user.listener.csrf_token_manager_salter'] = $app->protect(function (Request $request) use ($app) {
			if ($app['user'] instanceof Model\User) {
				$app['devture_framework.csrf_token_manager']->setSalt($app['user']->getUsername());
			}
		});

		$app['devture_user.listener.conditional_session_extender'] = $app->protect(function (Request $request, Response $response) use ($app) {
			if ($app['user'] instanceof Model\User) {
				$app['devture_user.login_manager']->extendSessionIfNeeded($app['user'], $request, $response);
			}
		});

		$this->registerConsoleServices($app);

		$this->registerControllerServices($app);
	}

	private function registerConsoleServices(Application $app) {
		$app['devture_user.console.command.add_user'] = function ($app) {
			return new ConsoleCommand\AddUserCommand($app);
		};
		$app['devture_user.console.command.change_user_password'] = function ($app) {
			return new ConsoleCommand\ChangeUserPasswordCommand($app);
		};
	}

	private function registerControllerServices(Application $app) {
		$app['devture_user.controllers_provider.management'] = $app->share(function ($app) {
			return new Controller\ControllersProvider($app['devture_user.browser_id.enabled']);
		});

		$app['devture_user.controller.user'] = $app->share(function ($app) {
			return new Controller\UserController($app, 'devture_user');
		});

		if ($app['devture_user.browser_id.enabled']) {
			$app['devture_user.controller.browser_id'] = $app->share(function ($app) {
				return new Controller\BrowserIdController($app, 'devture_user');
			});
		}

		$app['devture_user.public_routes'] = array('devture_user.login', 'devture_user.browser_id.login', 'devture_user.logout', 'devture_user.logged_out');
	}

	public function boot(Application $app) {
		$app['devture_localization.translator.resource_loader']->addResources(dirname(__FILE__) . '/Resources/translations/');

		$app->before($app['devture_user.listener.user_from_request_initializer']);
		$app->before($app['devture_user.listener.csrf_token_manager_salter']);
		$app->before($app['devture_user.access_control']->getEnforcer());
		$app->after($app['devture_user.listener.conditional_session_extender']);

		$app['twig.loader.filesystem']->addPath(dirname(__FILE__) . '/Resources/views/');
		$app['twig']->addExtension(new Twig\Extension\UserExtension($app['devture_user.access_control'], $app));

		if (isset($app['console'])) {
			$app['console']->add($app['devture_user.console.command.add_user']);
			$app['console']->add($app['devture_user.console.command.change_user_password']);
		}
	}

}

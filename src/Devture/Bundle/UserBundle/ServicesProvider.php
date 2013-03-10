<?php
namespace Devture\Bundle\UserBundle;

use Symfony\Component\HttpFoundation\Request;
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

		$app['user.roles'] = $config['roles'];

		$app['user.browser_id.enabled'] = $config['browser_id']['enabled'];
		$app['user.browser_id.audience'] = $config['browser_id']['audience'];

		if ($app['user.browser_id.enabled']) {
			$app['user.browser_id.verifier'] = function ($app) {
				return new \browserid\Verifier($app['user.browser_id.audience']);
			};
		}

		$app['user.db'] = $app->share(function ($app) use ($config) {
			return $app[$config['database_service_id']];
		});

		if ($config['database_type'] === 'relational') {
			$app['user.repository'] = $app->share(function ($app) {
				return new Repository\Relational\UserRepository($app['user.db']);
			});
		} else if ($config['database_type'] === 'mongodb') {
			$app['user.repository'] = $app->share(function ($app) {
				return new Repository\MongoDB\UserRepository($app['user.db']);
			});
		} else {
			throw new \InvalidArgumentException('Unrecognized database type: ' . $config['database_type']);
		}

		$app['user.password_encoder'] = $app->share(function ($app) use ($config) {
			return new Helper\BlowfishPasswordEncoder($config['blowfish_cost']);
		});

		$app['user.auth_helper'] = $app->share(function ($app) use ($config) {
			$helper = new Helper\AuthHelper($app['user.repository'], $app['user.password_encoder'], $config['password_token_salt']);
			if ($app['user.browser_id.enabled']) {
				$helper->setBrowserIdVerifier($app['user.browser_id.verifier']);
			}
			return $helper;
		});

		$app['user.login_manager'] = $app->share(function ($app) use ($config) {
			return new Helper\LoginManager($app['user.auth_helper'], $config['cookie_signing_secret'], $config['cookie_path']);
		});

		$app['user.access_control'] = $app->share(function ($app) {
			return new AccessControl\AccessControl($app);
		});

		$app['user.validator'] = function ($app) {
			return new Validator\UserValidator($app['user.repository'], $app['user.roles']);
		};

		$app['user.form_binder'] = function ($app) {
			$binder = new Form\FormBinder($app['user.validator'], $app['user.password_encoder']);
			$binder->setCsrfProtection($app['shared.csrf_token_generator'], 'user');
			return $binder;
		};

		$this->registerControllerServices($app);
	}

	private function registerControllerServices(Application $app) {
		$app['user.controllers_provider.management'] = $app->share(function ($app) {
			return new Controller\ControllersProvider($app['user.browser_id.enabled']);
		});

		$app['user.controller.user'] = $app->share(function ($app) {
			return new Controller\UserController($app, 'user');
		});

		if ($app['user.browser_id.enabled']) {
			$app['user.controller.browser_id'] = $app->share(function ($app) {
				return new Controller\BrowserIdController($app, 'user');
			});
		}

		$app['user.public_routes'] = array('user.login', 'user.browser_id.login', 'user.logout', 'user.logged_out');
	}

	public function boot(Application $app) {
		$app['localization.translator.resource_loader']->addResources(dirname(__FILE__) . '/Resources/translations/');

		$app->before(function (Request $request) use ($app) {
			$app['user'] = $app['user.login_manager']->createFromRequest($request);
		});

		$app->before($app['user.access_control']->getEnforcer());

		$app['twig.loader.filesystem']->addPath(dirname(__FILE__) . '/Resources/views/');
		$app['twig']->addExtension(new Twig\Extension\UserExtension($app['user.access_control'], $app));
	}

}

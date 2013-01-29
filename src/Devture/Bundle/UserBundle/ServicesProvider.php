<?php
namespace Devture\Bundle\UserBundle;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ServicesProvider implements ServiceProviderInterface {

	private $config;

	public function __construct(array $config) {
		$config = array_merge(array(
			'database_type' => 'mongodb', //relational, mongodb
		), $config);

		$this->config = $config;
	}

	public function register(Application $app) {
		$config = $this->config;

		$app['user'] = null;

		$app['user.roles'] = $config['roles'];

		$app['user.db'] = $app->share(function () use ($app, $config) {
			return $app[$config['database_service_id']];
		});

		if ($config['database_type'] === 'relational') {
			$app['user.repository'] = $app->share(function () use ($app) {
				return new \Devture\Bundle\UserBundle\Repository\Relational\UserRepository($app['user.db']);
			});
		} else if ($config['database_type'] === 'mongodb') {
			$app['user.repository'] = $app->share(function () use ($app) {
				return new \Devture\Bundle\UserBundle\Repository\MongoDB\UserRepository($app['user.db']);
			});
		} else {
			throw new \InvalidArgumentException('Unrecognized database type: ' . $config['database_type']);
		}

		$app['user.password_encoder'] = $app->share(function () use ($app, $config) {
			return new \Devture\Bundle\UserBundle\Helper\BlowfishPasswordEncoder($config['blowfish_cost']);
		});

		$app['user.auth_helper'] = $app->share(function () use ($app, $config) {
			return new \Devture\Bundle\UserBundle\Helper\AuthHelper($app['user.repository'], $app['user.password_encoder'], $config['password_token_salt']);
		});

		$app['user.login_manager'] = $app->share(function () use ($app, $config) {
			return new \Devture\Bundle\UserBundle\Helper\LoginManager($app['user.auth_helper'], $config['cookie_signing_secret'], $config['cookie_path']);
		});

		$app['user.access_control'] = $app->share(function () use ($app) {
			return new \Devture\Bundle\UserBundle\AccessControl\AccessControl($app);
		});

		$app['user.validator'] = function () use ($app) {
			return new \Devture\Bundle\UserBundle\Validator\UserValidator($app['user.repository'], $app['user.roles']);
		};

		$app['user.form_binder'] = function () use ($app) {
			$binder = new \Devture\Bundle\UserBundle\Form\FormBinder($app['user.validator'], $app['user.password_encoder']);
			$binder->setCsrfProtection($app['shared.csrf_token_generator'], 'user');
			return $binder;
		};

		$app['user.controllers_provider.management'] = $app->share(function () {
			return new \Devture\Bundle\UserBundle\Controller\ControllersProvider();
		});
	}

	public function boot(Application $app) {
		$app['localization.translator.resource_loader']->addResources(dirname(__FILE__) . '/Resources/translations/');

		$app->before(function () use ($app) {
			$app['user'] = $app['user.login_manager']->createFromRequest($app['request']);
		});

		$app->before($app['user.access_control']->getEnforcer());

		$app['twig.loader.filesystem']->addPath(dirname(__FILE__) . '/Resources/views/');
		$app['twig']->addExtension(new \Devture\Bundle\UserBundle\Twig\Extension\UserExtension($app));
		$app['twig']->addExtension(new \Devture\Bundle\UserBundle\Twig\Extension\AccessControlExtension($app['user.access_control']));
	}

}

<?php
namespace Devture\Bundle\UserBundle;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ServicesProvider implements ServiceProviderInterface {

	private $config;

	public function __construct(array $config) {
		$this->config = $config;
	}

	public function register(Application $app) {
		$config = $this->config;

		$app['user'] = null;

		$app['user.roles'] = $config['roles'];

		$app['user.token_generator'] = $app->share(function () use ($config) {
			return new \Devture\Bundle\UserBundle\Helper\TokenGenerator($config['token.validity_time'], $config['token.salt']);
		});

		$app['user.repository'] = $app->share(function () use ($app, $config) {
			return new \Devture\Bundle\UserBundle\Repository\UserRepository($app[$config['database_service_id']]);
		});

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

		$app['user.form_record_binder'] = $app->share(function () use ($app) {
			return new \Devture\Bundle\UserBundle\Helper\FormRecordBinder($app['user.password_encoder']);
		});

		foreach (glob(dirname(__FILE__) . '/Resources/translations/*.json') as $filePath) {
			$parts = explode('/', $filePath);
			list($localeKey, $_extension) = explode('.', array_pop($parts));
			$app['translator']->addResource('array', $filePath, $localeKey);
		}

		$viewsPath = dirname(__FILE__) . '/Resources/views/';
		$app['twig.loader']->addLoader(new \Twig_Loader_Filesystem($viewsPath));

		$app->before(function () use ($app) {
			$app['user'] = $app['user.login_manager']->createFromRequest($app['request']);
		});

		$app['twig']->addExtension(new \Devture\Bundle\UserBundle\Twig\Extension\UserExtension($app));
		$app['twig']->addExtension(new \Devture\Bundle\UserBundle\Twig\Extension\AccessControlExtension($app['user.access_control']));
	}

}

<?php
namespace Devture\Bundle\UserBundle\ConsoleCommand;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\DialogHelper;
use Devture\Component\DBAL\Exception\NotFound;
use Devture\Bundle\UserBundle\Model\User;
use Devture\Bundle\UserBundle\Repository\UserRepositoryInterface;

class AddUserCommand extends Command {

	private $container;

	public function __construct(\Pimple $container) {
		parent::__construct('devture-user:add');
		$this->container = $container;
	}

	protected function configure() {
		$this->addArgument('username', InputArgument::REQUIRED, 'The username of the new account.');
		$this->addArgument('email', InputArgument::OPTIONAL, 'The email address of the new account.');
		$this->setDescription('Adds a new user account (with full privileges).');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$username = $input->getArgument('username');
		$email = $input->getArgument('email');

		$repository = $this->getRepository();

		try {
			$repository->findByUsername($username);
			$output->writeln(sprintf('A user with the username %s already exists.', $username));
			return 1;
		} catch (NotFound $e) {

		}

		if ($email) {
			try {
				$repository->findByEmail($email);
				$output->writeln(sprintf('A user with the email %s already exists.', $email));
				return 1;
			} catch (NotFound $e) {

			}
		}

		$dialog = new DialogHelper();
		$dialog->setInput($input);
		$password = $dialog->askHiddenResponse(
			$output,
			'Enter a password: ',
			false
		);

		/* @var $entity User */
		$entity = $repository->createModel(array());
		$entity->setUsername($username);
		$entity->setEmail($email ?: null);
		$entity->setPassword($this->getPasswordEncoder()->encodePassword($password));
		$entity->setRoles(array(User::ROLE_MASTER));

		$repository->add($entity);

		$output->writeln(sprintf('User %s added successfully.', $username));
	}

	/**
	 * @return UserRepositoryInterface
	 */
	private function getRepository() {
		return $this->container['devture_user.repository'];
	}

	/**
	 * @return \Devture\Bundle\UserBundle\Helper\BlowfishPasswordEncoder
	 */
	private function getPasswordEncoder() {
		return $this->container['devture_user.password_encoder'];
	}

}

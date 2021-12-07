<?php
namespace Devture\Bundle\UserBundle\ConsoleCommand;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\Question;
use Devture\Component\DBAL\Exception\NotFound;
use Devture\Bundle\UserBundle\Model\User;
use Devture\Bundle\UserBundle\Repository\UserRepositoryInterface;

class AddUserCommand extends Command {

	private $container;

	public function __construct(\Pimple\Container $container) {
		parent::__construct('devture-user:add');
		$this->container = $container;
	}

	protected function configure() {
		$this->addArgument('username', InputArgument::REQUIRED, 'The username of the new account.');
		$this->addArgument('email', InputArgument::OPTIONAL, 'The email address of the new account.');
		$this->setDescription('Adds a new user account (with full privileges).');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$username = $input->getArgument('username');
		$email = $input->getArgument('email');

		$repository = $this->getRepository();

		/* @var $entity User */
		$entity = $repository->createModel(array());

		try {
			$repository->findByUsername($username);
			$output->writeln(sprintf('A user with the username %s already exists.', $username));
			return 1;
		} catch (NotFound $e) {

		}
		$entity->setUsername($username);

		if ($email) {
			try {
				$repository->findByEmail($email);
				$output->writeln(sprintf('A user with the email %s already exists.', $email));
				return 1;
			} catch (NotFound $e) {

			}
			$entity->setEmail($email);
		}

		$questionHelper = new QuestionHelper();

		$question = new Question(sprintf('<question>%s</question>: ', 'Name (not required):'));
		$entity->setName($questionHelper->ask($input, $output, $question));

		$question = new Question(sprintf('<question>%s</question>: ', 'Enter a password:'));
		$question->setHidden(true);
		$password = $questionHelper->ask($input, $output, $question);
		$entity->setPassword($this->getPasswordEncoder()->encodePassword($password));

		$entity->setRoles(array(User::ROLE_MASTER));

		$repository->add($entity);

		$output->writeln(sprintf('User %s added successfully.', $username));

		return 0;
	}

	/**
	 * @return UserRepositoryInterface
	 */
	private function getRepository() {
		return $this->container['devture_user.repository'];
	}

	/**
	 * @return \Devture\Bundle\UserBundle\Helper\PasswordEncoder
	 */
	private function getPasswordEncoder() {
		return $this->container['devture_user.password_encoder'];
	}

}

<?php
namespace Devture\Bundle\UserBundle\ConsoleCommand;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\DialogHelper;
use Devture\Component\DBAL\Exception\NotFound;
use Devture\Bundle\UserBundle\Repository\UserRepositoryInterface;

class ChangeUserPasswordCommand extends Command {

	private $container;

	public function __construct(\Pimple $container) {
		parent::__construct('devture-user:change-password');
		$this->container = $container;
	}

	protected function configure() {
		$this->addArgument('username', InputArgument::REQUIRED, 'The username whose password to change.');
		$this->setDescription("Changes an existing user account's password.");
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$username = $input->getArgument('username');

		$repository = $this->getRepository();

		try {
			$entity = $repository->findByUsername($username);
		} catch (NotFound $e) {
			$output->writeln(sprintf('Cannot find user: %s', $username));
			return 1;
		}

		$dialog = new DialogHelper();
		$dialog->setInput($input);
		$password = $dialog->askHiddenResponse(
			$output,
			'Enter a password: ',
			false
		);
		$entity->setPassword($this->getPasswordEncoder()->encodePassword($password));

		$repository->update($entity);

		$output->writeln(sprintf('Password for user %s updated successfully.', $username));
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

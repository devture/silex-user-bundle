<?php
namespace Devture\Bundle\UserBundle\Validator;
use Devture\Bundle\UserBundle\Model\User;
use Devture\Bundle\UserBundle\Repository\UserRepository;
use Devture\Bundle\SharedBundle\Validator\BaseValidator;
use Devture\Bundle\SharedBundle\Exception\NotFound;

class UserValidator extends BaseValidator {

    private $repository;
    private $knownRoles;

    public function __construct(UserRepository $repository, array $knownRoles) {
        $this->repository = $repository;
        $this->knownRoles = array_keys($knownRoles);
    }

    public function validate(User $entity, array $options = array()) {
        parent::validate($entity, $options);

        if ($entity->getPassword() === '' || $entity->getPassword() === null) {
            $this->set('password', 'user.validation.password_empty');
        }

        $username = $entity->getUsername();
        if (strlen($username) < 3
                || !preg_match("/^[a-z][a-z0-9\._]+$/", $username)) {
            $this->set('username', 'user.validation.invalid_username');
        }

        foreach ($entity->getRoles() as $role) {
            if (!in_array($role, $this->knownRoles)) {
                $this->set('roles', 'user.validation.invalid_roles');
                break;
            }
        }

        if (!isset($options['skipUniquenessCheck'])
                || !$options['skipUniquenessCheck']) {
            try {
                $this->repository->find($username);
                $this->set('username', 'user.validation.username_in_use');
            } catch (NotFound $e) {

            }
        }
    }

}

<?php
namespace Devture\Bundle\UserBundle\Repository\Relational;

use Devture\Component\DBAL\Model\BaseModel;
use Devture\Component\DBAL\Repository\BaseSqlRepository;
use Devture\Bundle\UserBundle\Repository\UserRepositoryInterface;
use Devture\Bundle\UserBundle\Mode\User;

class UserRepository extends BaseSqlRepository implements UserRepositoryInterface {

	protected function getModelClass() {
		return '\\Devture\Bundle\\UserBundle\\Model\\User';
	}

	protected function getTableName() {
		return 'user';
	}

	public function findByUsername($username) {
		return $this->findOneByQuery("SELECT * FROM " . $this->getTableName() . " WHERE username = ? LIMIT 1", array($username));
	}

	public function findByEmail($email) {
		return $this->findOneByQuery("SELECT * FROM " . $this->getTableName() . " WHERE email = ? LIMIT 1", array($email));
	}

	/**
	 * @param User $model
	 * @return array
	 */
	protected function exportModel($model) {
		$data = parent::exportModel($model);
		$data['roles'] = json_encode(isset($data['roles']) ? $data['roles'] : array());
		$data['email'] = ($model->getEmail() ? $model->getEmail() : null);
		return $data;
	}

	protected function hydrateModel(array $data) {
		$data['roles'] = isset($data['roles']) ? json_decode($data['roles'], 1) : array();
		return parent::hydrateModel($data);
	}

}

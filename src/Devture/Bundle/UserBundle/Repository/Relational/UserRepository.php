<?php
namespace Devture\Bundle\UserBundle\Repository\Relational;

use Devture\Bundle\SharedBundle\Model\BaseModel;
use Devture\Bundle\SharedBundle\Repository\BaseSqlRepository;
use Devture\Bundle\UserBundle\Repository\UserRepositoryInterface;

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

	protected function exportModel(BaseModel $model) {
		$data = parent::exportModel($model);
		$data['roles'] = json_encode(isset($data['roles']) ? $data['roles'] : array(), 1);
		$data['email'] = $model->getEmail() ? $model->getEmail() : NULL;
		return $data;
	}

	public function createModel(array $data) {
		$data['roles'] = isset($data['roles']) ? json_decode($data['roles'], 1) : array();
		return parent::createModel($data);
	}

}

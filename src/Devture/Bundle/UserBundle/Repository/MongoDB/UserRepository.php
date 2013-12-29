<?php
namespace Devture\Bundle\UserBundle\Repository\MongoDB;

use Devture\Component\DBAL\Model\BaseModel;
use Devture\Component\DBAL\Repository\BaseMongoRepository;
use Devture\Bundle\UserBundle\Repository\UserRepositoryInterface;
use Devture\Bundle\UserBundle\Mode\User;

class UserRepository extends BaseMongoRepository implements UserRepositoryInterface {

	protected function getModelClass() {
		return '\\Devture\Bundle\\UserBundle\\Model\\User';
	}

	protected function getCollectionName() {
		return 'devture_user';
	}

	/**
	 * @param User $model
	 * @return array
	 */
	protected function exportModel($model) {
		$data = parent::exportModel($model);
		$data['email'] = ($model->getEmail() ? $model->getEmail() : null);
		return $data;
	}

	public function ensureIndexes() {
		$userCollection = $this->db->selectCollection($this->getCollectionName());
		$userCollection->ensureIndex(array('username' => 1), array('unique' => true));

		//This field should be "unique, unless NULL".
		//Because MongoDB's unique indexes do not work that way, do not specify it as unique.
		$userCollection->ensureIndex(array('email' => 1));
	}

	public function findByUsername($username) {
		return $this->findOneBy(array('username' => $username));
	}

	public function findByEmail($email) {
		return $this->findOneBy(array('email' => $email));
	}

}

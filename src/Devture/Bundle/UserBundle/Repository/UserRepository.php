<?php
namespace Devture\Bundle\UserBundle\Repository;
use Devture\Bundle\SharedBundle\Repository\BaseMongoRepository;

class UserRepository extends BaseMongoRepository {

	public function getModelClass() {
		return '\\Devture\Bundle\\UserBundle\\Model\\User';
	}

	public function getCollectionName() {
		return 'user';
	}

	public function ensureIndexes() {

	}

}

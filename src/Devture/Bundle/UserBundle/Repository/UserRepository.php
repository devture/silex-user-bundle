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
        $userCollection = $this->db->selectCollection($this->getCollectionName());
        $userCollection->ensureIndex(array('username' => 1), array('unique' => true));
    }

    public function findByUsername($username) {
        return $this->findOneBy(array('username' => $username));
    }

}

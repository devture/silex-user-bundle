<?php
namespace Devture\Bundle\UserBundle\Helper;
use Devture\Bundle\UserBundle\Model\User;
use Devture\Bundle\UserBundle\Helper\BlowfishPasswordEncoder;
use Devture\Bundle\SharedBundle\Helper\SetterRequestBinder;
use Symfony\Component\HttpFoundation\Request;

class FormRecordBinder extends SetterRequestBinder {

    private $encoder;

    public function __construct(BlowfishPasswordEncoder $encoder) {
        $this->encoder = $encoder;
    }

    public function bind(User $entity, Request $request, array $options = array()) {
        $whitelisted = array('username', 'name', 'roles');
        $this->bindWhitelisted($entity, $request->request->all(), $whitelisted);

        $password = $request->request->get('password');
        if ($password !== '') {
            $entity->setPassword($this->encoder->encodePassword($password));
        }
    }

}

<?php
require_once('config.php');
require_once('Storage.class.php');

class User extends Storage {
    protected $id, $login, $password, $admin;
    protected $TABLE_NAME = "Users";
    protected $fields = array(
        'id'=>'key',
        'login'=>'string',
        'password'=>'password',
        'admin'=>'bool'
        );

    public function __construct() {
        parent::__construct();
    }

    public function getLogin() {
        return $this->login;
    }

    public function getId() {
        return $this->id;
    }

    public function getAdmin() {
        return $this->admin;
    }

    public function setId($id) {
        $this->id = (int) $id;
    }

    public function setLogin($login) {
        $this->login = $login;
    }

    public function setPassword($password) {
        $this->password = $password;
    }

    public function setAdmin($admin) {
        $this->admin = (bool) $admin;
    }

    public function encrypt($text) {
        return crypt($text, SALT);
    }

    public function checkPassword($password) {
        return User::encrypt($password) == $this->password;
    }

    public function exists() {
        $user_data = $this->load(array('login'=>$this->login));
        if(count($user_data) == 1) {
            $this->setId($user_data[0]['id']);
            $this->setAdmin($user_data[0]['admin']);
            $this->setPassword($user_data[0]['password']);

            return true;
        }
        else {
            return false;
        }
    }

    public function sessionStore() {
        return serialize(array('id'=>$this->id, 'login'=>$this->login, 'password'=>$this->password, 'admin'=>$this->admin));
    }

    public function sessionRestore($data, $serialized) {
        if($serialized)
            $user_data = unserialize($serialized_data);
        else
            $user_data = $data;

        $this->setId($user_data['id']);
        $this->setLogin($user_data['login']);
        $this->setPassword($user_data['password']);
        $this->setAdmin($user_data['admin']);
    }
}

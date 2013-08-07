<?php
require_once('config.php');

class User extends Storage {
    protected $id, $login, $password;
    protected $TALE_NAME = "users";
    protected $fields = array(
        'id'=>'key',
        'nom'=>'string',
        'password'=>'string',
        'admin'=>'bool'
        );

    private function __construct() {
        parent::__construct();
    }

    public function getLogin() {
        return $this->login;
    }

    public function getId() {
        return $this->id;
    }
    
    public function setLogin($login) {
        $this->login = $login;
    }

    public function setPassword($password) {
        $this->password = User::encrypt($password);
    }

    public function encrypt($text) {
        return crypt($text, SALT);
    }

    public function check_password($password) {
        return User::encrypt($password) == $this->password;
    }
}

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
    
    public function setLogin($login) {
        $this->login = $login;
    }

    public function setPassword($password) {
        $this->password = User::encrypt($password);
    }

    public function setAdmin($admin) {
        $this->admin = $admin;
    }

    public function encrypt($text) {
        return crypt($text, SALT);
    }

    public function check_password($password) {
        return User::encrypt($password) == $this->password;
    }
}

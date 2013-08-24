<?php
require_once('data/config.php');
require_once('Storage.class.php');

class User extends Storage {
    protected $id = 0, $login, $display_name, $password, $admin;
    protected $TABLE_NAME = "Users";
    protected $fields = array(
        'id'=>'key',
        'login'=>'string',
        'display_name'=>'string',
        'password'=>'password',
        'admin'=>'bool'
        );

    public function __construct() {
        parent::__construct();
    }

    public function getLogin() {
        return $this->login;
    }

    public function getDisplayName() {
        return $this->display_name;
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

    public function setDisplayName($display_name) {
        $this->display_name = $display_name;
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
            $this->setDisplayName($user_data[0]['admin']);
            $this->setAdmin($user_data[0]['admin']);
            $this->setPassword($user_data[0]['password']);

            return true;
        }
        else {
            return false;
        }
    }

    public function sessionStore() {
        return serialize(array('id'=>$this->id, 'login'=>$this->login, 'display_name'=>$this->display_name, 'password'=>$this->password, 'admin'=>$this->admin));
    }

    public function sessionRestore($data, $serialized = false) {
        if($serialized)
            $user_data = unserialize($data);
        else
            $user_data = $data;

        $this->setId($user_data['id']);
        $this->setLogin($user_data['login']);
        $this->setDisplayName($user_data['display_name']);
        $this->setPassword($user_data['password']);
        $this->setAdmin($user_data['admin']);
    }

    public function load_users($fields = NULL) {
        $return = array();
        $users = $this->load($fields);

        foreach($users as $user) {
            $return[$user['id']] = new User();
            $return[$user['id']]->sessionRestore($user);
        }
        return $return;
    }

    public function load_user($fields = NULL) {
        $fetch = $this->load($fields);

        if(count($fetch) > 0) {
            $this->setId($fetch[0]['id']);
            $this->setLogin($fetch[0]['login']);
            $this->setDisplayName($fetch[0]['display_name']);
            $this->setPassword($fetch[0]['password']);
            $this->setAdmin($fetch[0]['admin']);

            return true;
        }
        else {
            return false;
        }
    }

    public function isUnique() {
        if(count($this->load_users(array('login'=>$this->login))) == 0) {
            return true;
        }
        else {
            return false;
        }
    }
}

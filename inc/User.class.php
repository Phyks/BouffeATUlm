<?php
require_once('data/config.php');
require_once('Storage.class.php');

class User extends Storage {
    protected $id = 0, $login, $display_name, $password, $admin, $json_token;
    protected $TABLE_NAME = "Users";
    protected $fields = array(
        'id'=>'key',
        'login'=>'string',
        'display_name'=>'string',
        'password'=>'password',
        'admin'=>'bool',
        'json_token'=>'string',
        );

    public function __construct() {
        parent::__construct();
    }

    // Getters
    // =======
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

    public function getJsonToken() {
        return $this->json_token;
    }

    // Setters
    // =======
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

    public function setJsonToken($token) {
        $this->json_token = $token;
    }

    // Password functions
    // ==================
    public function encrypt($text) {
        return crypt($text, SALT);
    }

    public function checkPassword($password) {
        return User::encrypt($password) == $this->password;
    }

    // JSON token functions
    // ====================
    public function newJsonToken() {
        $this->json_token = md5(uniqid(mt_rand(), true));
    }

    // Check if a user exists by login and load it
    // ===========================================
    public function exists() {
        $user_data = $this->load(array('login'=>$this->login), true);

        if(count($user_data) == 1) { 
            return $user_data;
        }
        else {
            return false;
        }
    }

    // Session storage
    // ===============
    public function sessionStore() {
        return serialize(array('id'=>$this->id, 'login'=>$this->login, 'display_name'=>$this->display_name, 'password'=>$this->password, 'admin'=>$this->admin, 'json_token'=>$this->json_token));
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
        $this->setJsonToken($user_data['json_token']);
    }

    // Check wether a user already exists or not 
    // (a user = a unique login and display_name)
    // =========================================
    public function isUnique() {
        if($this->load(array('login'=>$this->login)) === false && $this->load(array('display_name'=>$this->display_name)) === false) {
            return true;
        }
        else {
            return false;
        }
    }

    // Maps htmlspecialchars on the class before display
    // =================================================
    public function secureDisplay() {
        $this->id = (int) $this->id;
        $this->login = htmlspecialchars($this->login);
        $this->display_name = htmlspecialchars($this->display_name);
        $this->admin = (int) $this->admin;
        $this->json_token = htmlspecialchars($this->json_token);

        return $this;
    }
}

<?php
require_once('data/config.php');
require_once('Storage.class.php');

class User extends Storage {
    protected $id = 0, $login, $email, $display_name, $password, $admin, $json_token, $notifications;
    protected $TABLE_NAME = "Users";
    protected $fields = array(
        'id'=>'key',
        'login'=>'string',
        'email'=>'string',
        'display_name'=>'string',
        'password'=>'password',
        'admin'=>'bool',
        'json_token'=>'string',
        'notifications'=>'int'
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

    public function getEmail() {
        return $this->email;
    }

    public function getAdmin() {
        return $this->admin;
    }

    public function getJsonToken() {
        return $this->json_token;
    }

    public function getNotifications() {
        return $this->notifications;
    }

    // Setters
    // =======
    public function setId($id) {
        $this->id = (int) $id;
    }

    public function setLogin($login) {
        $this->login = $login;
    }

    public function setEmail($email) {
        if(filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
            $this->email = $email;
            return true;
        }
        else {
            return false;
        }
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

    public function setNotifications($notifications) {
        switch($notifications) {
            case 1:
                $this->notifications = 1;
                break;

            case 2:
                $this->notifications = 2;
                break;
            
            case 3:
                $this->notifications = 3;
                break;

            default:
                $this->notifications = 3;
                break;
        }
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
    public function sessionStore($serialize = true) {
        if($serialize) {
            return serialize(array('id'=>$this->id, 'login'=>$this->login, 'email'=>$this->email, 'display_name'=>$this->display_name, 'password'=>$this->password, 'admin'=>$this->admin, 'json_token'=>$this->json_token, 'notifications'=>$this->notifications));
        }
        else {
            return array('id'=>$this->id, 'login'=>$this->login, 'email'=>$this->email, 'display_name'=>$this->display_name, 'password'=>$this->password, 'admin'=>$this->admin, 'json_token'=>$this->json_token, 'notifications'=>$this->notifications);
        }
    }

    public function sessionRestore($data, $serialized = false) {
        if($serialized)
            $user_data = unserialize($data);
        else
            $user_data = $data;

        $this->setId($user_data['id']);
        $this->setLogin($user_data['login']);
        $this->setEmail($user_data['email']);
        $this->setDisplayName($user_data['display_name']);
        $this->setPassword($user_data['password']);
        $this->setAdmin($user_data['admin']);
        $this->setJsonToken($user_data['json_token']);
        $this->setNotifications($user_data['notifications']);
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
        $this->email = htmlspecialchars($this->email);
        $this->display_name = htmlspecialchars($this->display_name);
        $this->admin = (int) $this->admin;
        $this->json_token = htmlspecialchars($this->json_token);
        $this->notifications = (int) $this->notifications;

        return $this;
    }
}

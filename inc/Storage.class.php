<?php
require_once('config.php');

class Storage {
    private $host, $login, $password, $db;
    private $connection = null;

    private function __construct($connection_params = null) {
        if(is_array($connection_params) && !empty($connection_params)) {
            $this->setHost($connection_params['host']);
            $this->setLogin($connection_params['login']);
            $this->setPassword($connection_params['password']);
            $this->setDb($connection_params['db']);

            $this->connect();
        }
    }

    private function __destruct() {
        $this->disconnect();
    }

    //Connect / Disconnect functions
    public function connect() {
        $this->connection = new PDO('mysql:host='.MYSQL_HOST.';dbname='.MYSQL_DB, MYSQL_LOGIN, MYSQL_PASSWORD);
        $this->connection->query('SET NAMES utf8');
    }

    public function disconnect() {
        $this->connection = null;
    }

    //Function to get and set vars
    public function getHost() {
        return $this->host;
    }

    public function getLogin() {
        return $this->login;
    }

    public function getPassword() {
        return $this->password;
    }

    public function getDb() {
        return $this->db;
    }

    public function setHost($host) {
        $this->host = host;
    }

    public function setLogin($login) {
        $this->login = $login;
    }

    public function setPassword($password) {
        $this->password = $password;
    }

    public function setDb($db) {
        this->db = $db;
    }

    public function typeToSQL($type) {
        $return = false;
        switch($type) {
            case 'key':
                    $return = 'INT(11) NOT NULL AUTO_INCREMENT PRIMARY_KEY'; 
                break;

            case 'string':
                    $return = 'VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci';
                break;

            case 'bool':
                    $return = 'TINYINT(1)';
                break;

            default:
                    $return = 'TEXT CHARACTER SET utf8 COLLATE utf8_general_ci';
                break;
        }
    }

    public function createTable($table_name = null) {

    }

    public function initTables() {
        $this->createTable('users');
        $this->createTable('invoices');
    } 
}

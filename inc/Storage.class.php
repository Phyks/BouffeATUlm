<?php
require_once('config.php');

class Storage {
    private $connection = null;

    public function __construct() {
        $this->connect();
    }

    public function __destruct() {
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
        $this->db = $db;
    }

    public function typeToSQL($type) {
        $return = false;
        switch($type) {
            case 'key':
                $return = 'INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY'; 
                break;

            case 'string':
                $return = 'VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci';
                break;

            case 'bool':
                $return = 'TINYINT(1)';
                break;

            case 'password':
                $return = 'VARCHAR(130)';
                break;

            default:
                $return = 'TEXT CHARACTER SET utf8 COLLATE utf8_general_ci';
                break;
        }
    }

    public function load($fields = NULL) {
        $query = 'SELECT ';
        $i = false;
        foreach($this->fields as $field=>$type) {
            if($i) { $query .= ','; } else { $i = true; }

            $query .= $field;
        }
        $query .= ' FROM '.MYSQL_PREFIX.$this->TABLE_NAME;

        if(!empty($fields) && is_array($fields)) {
            $i = true;
            foreach($fields as $field=>$value) {
                if($i) { $query .= ' WHERE '; $i = false; } else { $query .= ' AND ';  }

                $query .= $field.'=:'.$field;
            }
        }

        $query = $this->connection->prepare($query);

        if(!empty($fields) && is_array($fields)) {
            foreach($fields as $field=>$value) {
                $query->bindParam(':'.$field, $value);
            }
        }

        $query->execute();
        
        return $query->fetchAll();
    }

    public function save() {
        if(!empty($this->id)) {
            $query = 'UPDATE '.MYSQL_PREFIX.$this->TABLE_NAME.' SET ';

            $i = false;
            foreach($this->fields as $field=>$type) {
                if($i) { $query .= ','; } else { $i = true; }

                $query .= $field.'=:'.$field;
            }

            $query .= 'WHERE id='.$this->id;
        }
        else {
            $query = 'INSERT INTO '.MYSQL_PREFIX.$this->TABLE_NAME.'(';

            $i = false;
            foreach($this->fields as $field=>$type) {
                if($i) { $query .= ','; } else { $i = true; }

                $query .= $field;
            }

            $query .= ') VALUES(';
            
            $i = false;
            foreach($this->fields as $field=>$type) {
                if($i) { $query .= ','; } else { $i = true; }
                
                $query .= ':'.$field;
            }

            $query .= ')';
        }

        $query = $this->connection->prepare($query);

        foreach($this->fields as $field=>$type) {
            $query->bindParam(':'.$field, $this->$field);
        }
        
        $query->execute();

        $this->id = (!isset($this->id) ? $this->connection->lastInsertId() : $this->id);
    }
}

<?php
require_once('data/config.php');

class Storage {
    private $connection = null;

    public function __construct() {
        $this->connect();
    }

    public function __destruct() {
        $this->disconnect();
    }

    // Connection functions
    // ====================
    public function connect() {
        $this->connection = new PDO('mysql:host='.MYSQL_HOST.';dbname='.MYSQL_DB, MYSQL_LOGIN, MYSQL_PASSWORD);
        $this->connection->query('SET NAMES utf8');
    }

    public function disconnect() {
        $this->connection = null;
    }

    // Getters
    // =======
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

    // Setters
    // =======
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

    // Translates types in class to SQL types
    // ======================================
    public function typeToSQL($type) {
        $return = false;
        switch($type) {
            case 'int':
                $return = 'INT(11)';
                break;

            case 'key':
                $return = 'INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY'; 
                break;

            case 'float':
                $return = 'FLOAT';
                break;

            case 'string':
                $return = 'VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci';
                break;

            case 'date':
                $return = 'DATETIME NOT NULL';
                break;

            case 'bool':
                $return = 'TINYINT(1)';
                break;

            case 'password':
                $return = 'VARCHAR(130)';
                break;

            case 'text':
            default:
                $return = 'TEXT CHARACTER SET utf8 COLLATE utf8_general_ci';
                break;
        }
    }

    // Load function
    // =============
    public function load($fields = NULL, $first_only = false) {
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

                if(!is_array($value)) {
                    $value = array($value);
                }

                foreach($value as $value_array) {
                    if($value_array == 'AND' || $value_array = 'OR') {
                        $query .= ' '.$value_array.' ';
                        continue;
                    }

                    if(substr($value_array, 0, 1) == "<")
                        $query .= $field.'<:'.$field;
                    elseif(substr($value_array, 0, 1) == ">")
                        $query .= $field.'>:'.$field;
                    else
                        $query .= $field.'=:'.$field;
                }
            }
        }

        $query = $this->connection->prepare($query);

        if(!empty($fields) && is_array($fields)) {
            foreach($fields as $field=>$value) {
                if(!is_array($value))
                    $value = array($value);

                if($fields[$field] == 'date')
                    $value = $value->format('Y-m-d H:i:s');

                foreach($value as $value_array) {
                    if($value_array == 'AND' || $value_array == 'OR')
                        continue;

                    if(substr($value, 0, 1) == ">" || substr($value, 0, 1) == "<")
                        $query->bindParam(':'.$field, substr($value, 0, 1);
                    else
                        $query->bindParam(':'.$field, $value);
                }
            }
        }

        $query->execute();
        
        $results = $query->fetchAll();

        if(count($results) > 0) {
            $return = array();
            $class = get_class($this);

            foreach($results as $result) {
                $return[$result['id']] = new $class();
                $return[$result['id']]->sessionRestore($result);
            }

            if($first_only)
                return $return[$result['id']];
            else
                return $return;
        }
        else {
            return false;
        }
    }

    // Storing function
    // ================
    public function save() {
        if(!empty($this->id)) {
            $query = 'UPDATE '.MYSQL_PREFIX.$this->TABLE_NAME.' SET ';

            $i = false;
            foreach($this->fields as $field=>$type) {
                if(isset($this->$field))
                {
                    if($i) { $query .= ','; } else { $i = true; }

                    $query .= $field.'=:'.$field;
                }
            }

            $query .= ' WHERE id='.$this->id;
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
                if(isset($this->$field)) {
                    if($i) { $query .= ','; } else { $i = true; }
                
                    $query .= ':'.$field;
                }
            }

            $query .= ')';
        }

        $this->connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
        $query = $this->connection->prepare($query);

        foreach($this->fields as $field=>$type) {
            if(isset($this->$field)) {
                if($fields[$field] == 'date')
                    $value = $value->format('Y-m-d H:i:s');

                $query->bindParam(':'.$field, $this->$field);
            }
        }
        
        $query->execute();

        $this->id = (!isset($this->id) ? $this->connection->lastInsertId() : $this->id);
    }

    // Delete function
    // ===============
    public function delete() {
        $query = 'DELETE FROM '.MYSQL_PREFIX.$this->TABLE_NAME.' WHERE ';

        $i = false;
        foreach($this->fields as $field=>$type) {
            if(!empty($this->$field)) {
                if($i) { $query .= ' AND '; } else { $i = true; }
                
                $query .= $field.'=:'.$field;
            }
        }

        $query = $this->connection->prepare($query);

        foreach($this->fields as $field=>$type) {
            if(!empty($this->$field)) {
                if($fields[$field] == 'date')
                    $value = $value->format('Y-m-d H:i:s');

                $query->bindParam(':'.$field, $this->$field);
            }
        }

        $query->execute();
    }
}

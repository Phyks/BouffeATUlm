<?php
require_once('data/config.php');
require_once('inc/MysqlConnector.php');

class Storage {
    private $connection = null;
    private $mysql_instance = null;

    public function __construct() {
        $this->mysql_instance = MysqlConnector::getInstance();
        $this->connection = $this->mysql_instance->getConnection();
    }

    public function __destruct() {
    }

    public function getConnection() {
        return $this->connection;
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
    public function load($fields = NULL, $first_only = false, $key_array = 'id') {
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

                foreach($value as $key=>$value_array) {
                    if($value_array == 'AND' || $value_array == 'OR') {
                        $query .= ' '.$value_array.' ';
                        continue;
                    }

                    if(substr($value_array, 0, 2) == "<=")
                        $query .= $field.'<=:'.$field.$key;
                    elseif(substr($value_array, 0, 1) == "<")
                        $query .= $field.'<:'.$field.$key;
                    elseif(substr($value_array, 0, 2) == ">=")
                        $query .= $field.'>=:'.$field.$key;
                    elseif(substr($value_array, 0, 1) == ">")
                        $query .= $field.'>:'.$field.$key;
                    else
                        $query .= $field.'=:'.$field.$key;
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

                foreach($value as $key=>$value_array) {
                    if($value_array == 'AND' || $value_array == 'OR')
                        continue;

                    if(substr($value_array, 0, 2) == ">=" || substr($value_array, 0, 2) == "<=")
                        $value_array = substr($value_array, 2);
                    elseif(substr($value_array, 0, 1) == ">" || substr($value_array, 0, 1) == "<")
                        $value_array = substr($value_array, 1);
                    
                    $query->bindValue(':'.$field.$key, $value_array);
                }
            }
        }

        $query->execute();

        $results = $query->fetchAll();

        if(count($results) > 0) {
            $return = array();
            $class = get_class($this);

            foreach($results as $result) {
                $return[$result[$key_array]] = new $class();
                $return[$result[$key_array]]->sessionRestore($result);
            }

            if($first_only)
                return $return[$result[$key_array]];
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
                if($i) { $query .= ','; } else { $i = true; }

                $query .= $field.'=:'.$field;
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
                if($i) { $query .= ','; } else { $i = true; }
                
                $query .= ':'.$field;
            }

            $query .= ')';
        }

        $this->connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
        $query = $this->connection->prepare($query);

        foreach($this->fields as $field=>$type) {
            if($type == 'date')
                $value = $this->$field->format('Y-m-d H:i:s');
            else
                $value = $this->$field;

            $query->bindValue(':'.$field, $value);
        }
        
        $query->execute();

        (empty($this->id) ? $this->setId($this->connection->lastInsertId()) : $this->setId($this->id));
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
                if($this->fields[$field] == 'date')
                    $value = $this->$field->format('Y-m-d H:i:s');
                else
                    $value = $this->$field;

                $query->bindValue(':'.$field, $value);
            }
        }

        $query->execute();
    }
}

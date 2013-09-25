<?php
    require_once('data/config.php');
    require_once('Storage.class.php');

    class UsersInGlobalPayback extends Storage {
        protected $payback_id = 0, $users_list;
        //users_list is a 2D array of users_id and amount between them
        //user1 owes amount to user2
        protected $TABLE_NAME = "Users_in_GlobalPaybacks";
        protected $fields = array(
            'global_payback_id'=>'int',
            'user1_id'=>'int',
            'user2_id'=>'int',
            'amount'=>'int'
            );

        public function __construct() {
            parent::__construct();
            $users_list = array();
        }

        // Getters
        // =======
        public function getPaybackId() {
            return $this->payback_id;
        }

        public function get() {
            return $this->users_list;
        }

        // Setters
        // =======
        public function setPaybackId($id) {
            $this->payback_id = (int) $id;
        }

        public function set($users_in) {
            $this->users_list = $users_in;
        }

        // Maps htmlspecialchars on the class before display
        // =================================================
        public function secureDisplay() {
            $this->payback_id = (int) $this->payback_id;

            $temp_array = array();
            foreach($this->users_list as $user1=>$temp) {
                foreach($temp as $user2=>$amount) {
                    $temp_array[(int) $user1][(int) [$user2]] = (float) $amount;
                }
            }
            $this->users_list = $temp_array;

            return $this;
        }

        // Test if the payback should be closed
        // ====================================
        public function isEmpty() {
            foreach($this->users_list as $user1=>$temp) {
                foreach($temp as $user2=>$amount) {
                    if($amount != 0) {
                        return false;
                    }
                }
            }
            return true;
        }

        // Override load() method
        // ======================
        public function load() {
            $query = 'SELECT ';

            $i = false;
            foreach($this->fields as $field=>$type) {
                if($i) { $query .= ','; } else { $i = true; }

                $query .= $field;
            }

            $query .= ' FROM '.MYSQL_PREFIX.$this->TABLE_NAME.' WHERE global_payback_id=:global_payback_id';

            $query = $this->getConnection()->prepare($query);
            $query->bindParam(':global_payback_id', $this->payback_id);
            $query->execute();

            $results = $query->fetchAll();
            $this->users_list = array();
            foreach($results as $result) {
                $this->users_list[(int) $result['user1_id']][(int) $result['user2_id']] = (float) $result['amount'];
            }
        }

        // Override save() method
        // ======================
        public function save() {
            // TODO : Optimize ?

            $query = 'SELECT COUNT(global_payback_id) FROM '.MYSQL_PREFIX.$this->TABLE_NAME.' WHERE global_payback_id=:payback_id';
            $query = $this->getConnection()->prepare($query);
            $query->bindParam(':payback_id', $this->payback_id);
            $query->execute();

            $count = $query->fetchColumn(0);

            if($count != 0) {
                // If there are already some records, delete them first
                $query = $this->getConnection()->prepare('DELETE FROM '.MYSQL_PREFIX.$this->TABLE_NAME.' WHERE global_payback_id=:payback_id');
                $query->bindParam(':payback_id', $this->payback_id);
                $query->execute();
            }

            $query = 'INSERT INTO '.MYSQL_PREFIX.$this->TABLE_NAME.'(';
            $i = false;
            foreach($this->fields as $field=>$type) {
                if($i) { $query .= ','; } else { $i = true;}
                $query .= $field;
            }

            $query .= ') VALUES(:payback_id, :user1_id, :user2_id, :amount)';

            $query = $this->getConnection()->prepare($query);

            $query->bindParam(':payback_id', $this->payback_id);

            foreach($this->users_list as $user1=>$temp) {
                foreach($temp as $user2=>$amount) {
                    $query->bindValue(':user1_id', intval($user1));
                    $query->bindValue(':user2_id', intval($user2));
                    $query->bindValue(':amount', floatval($amount));
                    $query->execute();
                }
            }
        }

        // Override delete() method
        // ========================
        public function delete() {
            $query = $this->getConnection()->prepare('DELETE FROM '.MYSQL_PREFIX.$this->TABLE_NAME.' WHERE payback_id=:payback_id');
            $query->bindParam(':payback_id', $this->payback_id);
            $query->execute();
        }
    }

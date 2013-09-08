<?php
    require_once('data/config.php');
    require_once('Storage.class.php');

    class UsersIn extends Storage {
        protected $invoice_id = 0, $users_list;
        //users_list is an array of users_id and number of guest per user
        protected $TABLE_NAME = "Users_in_invoices";
        protected $fields = array(
            'invoice_id'=>'int',
            'user_id'=>'int',
            'guests'=>'int'
            );

        public function __construct() {
            parent::__construct();
            $users_list = array();
        }

        // Getters
        // =======
        public function getInvoiceId() {
            return $this->invoice_id;
        }

        public function get() {
            return $this->users_list;
        }

        // Setters
        // =======
        public function setInvoiceId($id) {
            $this->invoice_id = (int) $id;
        }

        public function set($users_in) {
            $this->users_list = $users_in;
        }

        // Maps htmlspecialchars on the class before display
        // =================================================
        public function secureDisplay() {
            $this->invoice_id = (int) $this->invoice_id;

            $temp_array = array();
            foreach($this->users_list as $user=>$guests) {
                $temp_array[(int) $user] = (int) $guests;
            }
            $this->users_in = $temp_array;

            return $this;
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

            $query .= ' FROM '.MYSQL_PREFIX.$this->TABLE_NAME.' WHERE invoice_id=:invoice_id';

            $query = $this->getConnection()->prepare($query);
            $query->bindParam(':invoice_id', $this->invoice_id);
            $query->execute();

            $results = $query->fetchAll();
            $this->users_list = array();
            foreach($results as $result) {
                $this->users_list[(int) $result['user_id']] = (int) $result['guests'];
            }
        }

        // Override save() method
        // ======================
        public function save() {
            // TODO : Optimize ?

            $query = 'SELECT COUNT(invoice_id) FROM '.MYSQL_PREFIX.$this->TABLE_NAME.' WHERE invoice_id=:invoice_id';
            $query = $this->getConnection()->prepare($query);
            $query->bindParam(':invoice_id', $this->invoice_id);
            $query->execute();

            $count = $query->fetchColumn(0);

            if($count != 0) {
                // If there are already some records, delete them first
                $query = $this->getConnection()->prepare('DELETE FROM '.MYSQL_PREFIX.$this->TABLE_NAME.' WHERE invoice_id=:invoice_id');
                $query->bindParam(':invoice_id', $this->invoice_id);
                $query->execute();
            }

            $query = 'INSERT INTO '.MYSQL_PREFIX.$this->TABLE_NAME.'(';
            $i = false;
            foreach($this->fields as $field=>$type) {
                if($i) { $query .= ','; } else { $i = true;}
                $query .= $field;
            }

            $query .= ') VALUES(:invoice_id, :user_id, :guests)';

            $query = $this->getConnection()->prepare($query);

            $query->bindParam(':invoice_id', $this->invoice_id);

            foreach($this->users_list as $user=>$guests) {
                $query->bindParam(':user_id', intval($user));
                $query->bindParam(':guests', intval($guests));
                $query->execute();
            }
        }
    }

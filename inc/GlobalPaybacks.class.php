<?php
    require_once('data/config.php');
    require_once('Storage.class.php');
    require_once('UsersInGlobalPayback.class.php');

    class GlobalPayback extends Storage {
        protected $id = 0, $date, $users_in, $closed;
        // date is a DateTime object
        // buyer is a User object
        // users_in is a UsersIn objects
        protected $TABLE_NAME = "GlobalPaybacks";
        protected $fields = array(
            'id'=>'key',
            'date'=>'date',
            'closed'=>'bool'
            );

        public function __construct() {
            parent::__construct();
            $this->users_in = new UsersInGlobalPayback();
            $this->date = new Datetime();
        }

        // Getters
        // =======
        public function getId() {
            return $this->id;
        }

        public function getDate($format = "d-m-Y H:i") {
            if(!empty($this->date))
                return $this->date->format($format);
            else
                return false;
        }

        public function getUsersIn() {
            return $this->users_in;
        }

        public function getClosed() {
            return (bool) $this->closed;
        }

        // Setters
        // =======
        public function setId($id) {
            $this->users_in->setPaybackId($id);
            $this->id = (int) $id;
        }

        public function setDate($minute, $hour, $day, $month, $year) {
            if((int) $minute < 10) $minute = '0'.(int) $minute;

            $this->date = DateTime::createFromFormat('Y-n-j G:i', $year.'-'.(int) $month.'-'.(int) $day.' '.(int) $hour.':'.$minute);
        }

        public function setUsersIn($users_in) {
            // Note : users_in in param is an array with users in listed and guests for each user
            $this->users_in->set($users_in);
        }

        public function setClosed($closed) {
            $this->closed = (bool) $closed;
        }

        // Maps htmlspecialchars on the class before display
        // =================================================
        public function secureDisplay() {
            $this->id = (int) $this->id;

            return $this;
        }

        // Restores object from array
        // ==========================
        public function sessionRestore($data, $serialized = false) {
            if($serialized) {
                $data = unserialize($data);
            }

            $this->setId($data['id']);
            $this->date = DateTime::createFromFormat('Y-m-d H:i:s', $data['date']);
            $this->setClosed($data['closed']);
        }

        // Override parent load() method
        // =============================
        public function load($fields = NULL, $first_only = false, $key_array = 'id') {
            $return = parent::load($fields, $first_only); // Execute parent load

            if(is_array($return)) {
                foreach(array_keys($return) as $key) {
                    $return[$key]->users_in->load(); // Load users in for each global paybacks
                }
            }
            elseif(is_a($return, 'GlobalPayback')) {
                $return->users_in->load();
            }

            return $return; // Return the loaded elements
        }

        // Override parent save() method
        // ============================
        public function save() {
            parent::save(); // Save invoice element

            $this->users_in->save(); // Save users in
        }

        // Override parent delete() method
        // ===============================
        public function delete() {
            parent::delete(); // Delete invoice element

            $this->users_in->delete(); // Also delete users in
        }
    }

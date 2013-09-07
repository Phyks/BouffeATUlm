<?php
    //TODO : load() and save() overload
    require_once('data/config.php');
    require_once('Storage.class.php');

    class UsersIn extends Storage {
        protected $invoice_id = 0, $users_list;
        //users_list is an array of users_id and number of guest per user
        protected $TABLE_NAME = "Users_in";
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
    }

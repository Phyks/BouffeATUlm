<?php
    require_once('data/config.php');
    require_once('Storage.class.php');
    require_once('UsersIn.class.php');

    class Invoice extends Storage {
        protected $id = 0, $date, $users_in, $buyer, $amount, $what;
        // date is a DateTime object
        // buyer is a User object
        // users_in is a UsersIn objects
        protected $TABLE_NAME = "Invoices";
        protected $fields = array(
            'id'=>'key',
            'date'=>'date',
            'buyer'=>'int',
            'amount'=>'int',
            'what'=>'text'
            );

        public function __construct() {
            parent::__construct();
            $this->users_in = new UsersIn('invoice');
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

        public function getBuyer() {
            return $this->buyer;
        }

        public function getUsersIn() {
            return $this->users_in;
        }

        public function getAmount() {
            return (float) $this->amount / 100; // Amount is stored in cents
        }

        public function getWhat() {
            return $this->what;
        }

        // Setters
        // =======
        public function setId($id) {
            $this->users_in->setInvoiceId($id);
            $this->id = (int) $id;
        }

        public function setDate($minute, $hour, $day, $month, $year) {
            if((int) $minute < 10) $minute = '0'.(int) $minute;

            $this->date = DateTime::createFromFormat('Y-n-j G:i', $year.'-'.(int) $month.'-'.(int) $day.' '.(int) $hour.':'.$minute);
        }

        public function setGuests($guests) {
            $this->guests = $guests;
        }

        public function setBuyer($buyer) {
            $this->buyer = (int) $buyer;
        }

        public function setAmount ($amount) {
            $amount = str_replace(',', '.', $amount);
            $this->amount = (int) ($amount * 100); // Amount is stored in cents
        }

        public function setWhat($what) {
            $this->what = $what;
        }

        public function setUsersIn($users_in) {
            // Note : users_in in param is an array with users in listed and guests for each user
            $this->users_in->set($users_in);
        }

        // Get the amount to pay by person
        // ===============================
        public function getAmountPerPerson($id, $round = true) {
            $users_in = $this->users_in->get();
            $guests = 0;

            foreach($users_in as $user=>$guests_user) {
                $guests += (int) $guests_user;
            }

            // Amount is stored in cents
            if($round) {
	            return (isset($users_in[(int) $id])) ? round($this->amount / 100 / (count($users_in) + $guests) * (1 + $users_in[(int) $id]), 2) : 0; // Note : $users_in[(int) $id] is the number of guests for user $id
	        }
	        else {
		        return (isset($users_in[(int) $id])) ? $this->amount / 100 / (count($users_in) + $guests) * (1 + $users_in[(int) $id]) : 0; // Note : $users_in[(int) $id] is the number of guests for user $id
	        }
        }

        // Maps htmlspecialchars on the class before display
        // =================================================
        public function secureDisplay() {
            $this->id = (int) $this->id;
            $this->what = htmlspecialchars($this->what);
            $this->amount = (float) $this->amount;
            $this->buyer = (int) $this->buyer;

            return $this;
        }

        // Restores object from array
        // ==========================
        public function sessionRestore($data, $serialized = false) {
            if($serialized) {
                $data = unserialize($data);
            }

            $this->setId($data['id']);
            $this->setWhat($data['what']);
            $this->amount = (int) $data['amount'];
            $this->setBuyer($data['buyer']);

            $this->date = DateTime::createFromFormat('Y-m-d H:i:s', $data['date']);
        }

        // Override parent load() method
        // =============================
        public function load($fields = NULL, $first_only = false, $key_array = 'id') {
            $return = parent::load($fields, $first_only); // Execute parent load

            if(is_array($return)) {
                foreach(array_keys($return) as $key) {
                    $return[$key]->users_in->load(); // Load users in for each invoice
                }
            }
            elseif(is_a($return, 'Invoice')) {
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

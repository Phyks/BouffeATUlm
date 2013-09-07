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
            'amount'=>'float',
            'what'=>'text'
            );

        public function __construct() {
            parent::__construct();
            $users_in = new UsersIn();
        }

        // Getters
        // =======
        public function getId() {
            return $this->id;
        }

        public function getDate($format = "d-m-Y H:i") {
            return $this->date->format($format);
        }

        public function getGuests() {
            return $this->guests;
        }

        public function getBuyer() {
            return $this->buyer;
        }

        public function getUsersIn() {
            return $this->users_in;
        }

        public function getAmount() {
            return $this->amount;
        }

        public function getWhat() {
            return $this->what;
        }

        // Setters
        // =======
        public function setId($id) {
            $this->users_in->setId($id);
            $this->id = (int) $id;
        }

        public function setDate($minute, $hour, $day, $month, $year) {
            if((int) $minute < 10) $minute = '0'.$minute;

            $this->date = DateTime::createFromFormat('Y-n-j G:i', $year.'-'.(int) $month.'-'.(int) $day.' '.(int) $hour.':'.$minute);
        }

        public function setGuests($guests) {
            $this->guests = $guests;
        }

        public function setBuyer($buyer) {
            $this->buyer = (int) $buyer;
        }

        public function setAmount ($amount) {
            $this->amount = (float) $amount;
        }

        public function setWhat($what) {
            $this->what = $what;
        }

        public function setUsersIn($users_in) {
            // Note : users_in in param is an array with users in listed and guests for each user
            $this->users_in->set($users_in);
        }

        // Maps htmlspecialchars on the class before display
        // =================================================
        public function secureDisplay() {
            $this->id = (int) $this->id;
            $this->what = htmlspecialchars($this->what);
            $this->amount = (float) $this->amount;
            $this->buyer = (int) $this->buyer;
            $this->date = htmlspecialchars($this->date);

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
            $this->setAmount($data['amount']);
            $this->setBuyer($data['buyer']);
            $this->setDate($data['date']);
        }
    }

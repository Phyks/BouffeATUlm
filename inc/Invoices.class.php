<?php
    // TODO : Users in
    // TODO : date format

    require_once('data/config.php');
    require_once('Storage.class.php');

    class Invoice extends Storage {
        protected $id = 0, $date, $users_in, $buyer, $amount, $what;
        protected $TABLE_NAME = "Invoices";
        protected $fields = array(
            'id'=>'key',
            'date'=>'date',
            'users_in'=>'string',
            'buyer'=>'int',
            'amount'=>'float',
            'what'=>'text'
            );

        // Getters
        // =======
        public function getId() {
            return $this->id;
        }

        public function getDate() {
            return $this->date;
        }

        public function getUsersIn() {
            return $this->users_in;
        }

        public function getBuyer() {
            return $this->buyer;
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
            $this->id = (int) $id;
        }

        public function setDate($date_day, $date_month, $date_year) {
            if((int) $date_day < 10) $date_day = "0".(int) $date_day;
            if((int) $date_month < 10) $date_month = "0".(int) $date_month;

            $this->date = $date_year.$date_month.$date_day;
        }

        public function setUsersIn($users_in) {
            $this->users_in = $users_in;
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

        // Maps htmlspecialchars on the class before display
        // =================================================
        public function secureDisplay() {
            $this->id = (int) $this->id;
            $this->what = htmlspecialchars($this->what);
            $this->amount = (float) $this->amount;
            $this->buyer = (int) $this->buyer;
            $this->users_in = htmlspecialchars($this->users_in);
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
            $this->setUsersIn($data['users_in']);
            $this->setDate($data['date']);
        }
    }

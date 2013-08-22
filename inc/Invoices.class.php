<?php
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


        public function load_invoices($fields = NULL) {
            $return = array();
            $invoices = $this->load($fields);

            foreach($invoices as $invoice) {
                $return[$invoice['id']] = new Invoice();

                $return[$invoice['id']]->setId($invoice['id']);
                $return[$invoice['id']]->setDate($invoice['date']);
                $return[$invoice['id']]->setUsersIn($invoice['users_in']);
                $return[$invoice['id']]->setBuyer($invoice['buyer']);
                $return[$invoice['id']]->setAmount($invoice['amount']);
                $return[$invoice['id']]->setWhat($invoice['what']);
            }

            return $return;
        }
        
        public function load_invoice($fields = NULL) {
            $fetch = $this->load($fields);

            if(count($fetch) > 0) {
                $this->setId($fetch[0]['id']);
                $this->setWhat($fetch[0]['what']);
                $this->setAmount($fetch[0]['amount']);
                $this->setBuyer($fetch[0]['buyer']);
                $this->setUsersIn($fetch[0]['users_in']);
                $this->setDate($fetch[0]['date']);

                return true;
            }
            else {
                return false;
            }
        }
    }

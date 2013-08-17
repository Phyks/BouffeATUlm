<?php
    require_once('data/config.php');
    require_once('Storage.class.php');

    class Invoice extends Storage {
        protected $id, $date, $users_in, $buyer, $amount, $what;
        protected $TABLE_NAME = "Invoices";
        protected $fields = array(
            'id'=>'key',
            'date'=>'int',
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

        public function setDate($date) {
            $this->date = $date;
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
    }

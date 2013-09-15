<?php
    require_once('data/config.php');
    require_once('Storage.class.php');

    class Payback extends Storage {
        protected $id = 0, $date, $invoice_id, $amount, $from_user, $to_user;
        protected $TABLE_NAME = "Paybacks";
        protected $fields = array(
            'id'=>'key',
            'date'=>'date',
            'invoice_id'=>'int',
            'amount'=>'int',
            'from_user'=>'int',
            'to_user'=>'int'
        );

        public function __construct() {
            parent::__construct();
        }

        // Getters
        // =======

        public function getId() {
            return (int) $this->id;
        }

        public function getDate($format = 'd-m-Y H:i') {
            if(!empty($this->date))
                return $this->date->format($format);
            else
                return false;
        }

        public function getInvoice() {
            return (int) $this->invoice_id;
        }

        public function getAmount() {
            return (float) $this->amount / 100; // Amount is stored in cents
        }

        public function getFrom() {
            return (int) $this->from_user;
        }

        public function getTo() {
            return (int) $this->to_user;
        }

        // Setters
        // =======

        public function setId($id) {
            $this->id = (int) $id;
        }

        public function setDate($minute, $hour, $day, $month, $year) {
            $this->date = DateTime::createFromFormat('Y-n-j G:i', (int) $year.'-'.(int) $month.'-'.(int) $day.' '.(int) $hour.':'.$minute);
        }

        public function setInvoice($invoice_id) {
            $this->invoice_id = (int) $invoice_id;
        }

        public function setAmount($amount) {
            $this->amount = (int) ($amount * 100); // Amount is stored in cents
        }

        public function setFrom($from) {
            $this->from_user = (int) $from;
        }

        public function setTo($to) {
            $this->to_user = (int) $to;
        }

        // Restores object from array
        // ==========================

        public function sessionRestore($data, $serialized = false) {
            if($serialized)
                $data = unserialize($data);

            $this->setId($data['id']);
            $this->setInvoice($data['invoice_id']);
            $this->amount = (int) $data['amount'];
            $this->setFrom($data['from_user']);
            $this->setTo($data['to_user']);

            $this->date = DateTime::createFromFormat('Y-m-d H:i:s', $data['date']);
        }

        // Maps htmlspecialchars on the class before display
        // =================================================

        public function secureDisplay() {
            $this->id = (int) $this->id;
            $this->invoice_id = (int) $this->invoice_id;
            $this->amount = (float) $this->amount;
            $this->from = (int) $this->from_user;
            $this->to = (int) $this->to_user;

            return $this;
        }
    }

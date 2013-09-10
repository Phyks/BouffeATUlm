<?php
    require_once('data/config.php');
    require_once('Storage.class.php');

    class Payback extends Storage {
        protected $id = 0, $date, $invoice_id, $amount, $from, $to;
        protected $TABLE_NAME = "Paybacks";
        protected $fields = array(
            'id'=>'key',
            'date'=>'date',
            'invoice_id'=>'int',
            'amount'=>'float',
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
            return (float) $this->amount;
        }

        public function getFrom() {
            return (int) $this->from;
        }

        public function getTo() {
            return (int) $this->to;
        }

        // Setters
        // =======

        public function setId($id) {
            $this->id = (int) $id;
        }

        public function setDate($minute, $hour, $day, $month, $year) {
            if((int) $minute < 10) $minute = '0'.$minute;
        
            $this->date = DateTime::createFromFormat('Y-n-j G:i', $year.'-'.(int) $month.'-'.(int) $day.' '.(int) $hour.':'.$minute);
        }

        public function setInvoice($invoice_id) {
            $this->invoice_id = (int) $invoice_id;
        }

        public function setAmount($amount) {
            $this->amount = (float) $amount;
        }

        public function setFrom($from) {
            $this->from = (int) $from;
        }

        public function setTo($to) {
            $this->to = (int) $to;
        }

        // Restores object from array
        // ==========================

        public function sessionRestore($data, $serialized = false) {
            if($serialized)
                $data = unserialize($data);

            $this->setId($data['id']);
            $this->setInvoice($data['invoice_id']);
            $this->setAmount($data['amount']);
            $this->setFrom($data['from']);
            $this->setTo($data['to']);

            $this->date = DateTime::createFromFormat('Y-m-d H:i:s', $data['date']);
        }

        // Maps htmlspecialchars on the class before display
        // =================================================

        public function secureDisplay() {
            $this->id = (int) $this->id;
            $this->invoice_id = (int) $this->invoice_id;
            $this->amount = (float) $this->amount;
            $this->from = (int) $this->from;
            $this->to = (int) $this->to;
        }
    }

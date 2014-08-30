<?php

class MysqlConnector {
    private $connection = null;
    private static $instance = null;

    private function __construct() {
        $this->connect();
    }

    public function connect() {
        try {
            $this->connection = new PDO('mysql:host='.MYSQL_HOST.';dbname='.MYSQL_DB, MYSQL_LOGIN, MYSQL_PASSWORD);
            $this->connection->query('SET NAMES utf8');
        } catch (Exception $e) {
            exit ('Unable to access database.');
        }
    }

    public function disconnect() {
        $this->connection = null;
    }

    public function getConnection() {
        return $this->connection;
    }

	public static function getInstance() {

		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}

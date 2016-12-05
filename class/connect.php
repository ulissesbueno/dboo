<?php 
class Database extends MySQLi {
    private static $instance = null ;

    private function __construct($host, $user, $password, $database){ 
        parent::__construct($host, $user, $password, $database);
    }

    public static function getInstance(){
        if (self::$instance == null){
            self::$instance = new self(HOST, USER, PASS, BASE);
        }
        return self::$instance ;
    }

    

}


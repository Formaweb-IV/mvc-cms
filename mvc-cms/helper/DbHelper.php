<?php

namespace App\Helper;

class DbHelper {
    
    var $db;
    
    function __construct(){
        
        //Conexión mediante PDO
        $opciones = [\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"];
        try {
            $this->db = new \PDO(
                'mysql:host=localhost;dbname=cms-mvc',
                'admin',
                'root',
            $opciones);
            $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            echo 'Fallou a conexión: ' . $e->getMessage();
        }
        
    }
    
}
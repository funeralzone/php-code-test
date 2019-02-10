<?php

/**
 * Created by PhpStorm.
 * User: jamesskywalker
 * Date: 09/02/2019
 * Time: 19:37
 */
class pdoConnect
{
    private $dbName;
    private $dbHost;
    private $dbUsername;
    private $dbPass;


    public function __construct() {

        $this->setConnectionVars();
    }

    private function setConnectionVars() {
        $dbDetail = (object) parse_ini_file("bookList.ini",true)["PDO_DETAILS"];
        $this->dbName = $dbDetail->dbName;
        $this->dbHost = $dbDetail->dbHost;
        $this->dbUsername = $dbDetail->dbUsername;
        $this->dbPass = $dbDetail->dbPass;
    }

    public function getPdoConnection() {
        try {
            $only = new PDO("mysql:host=$this->dbHost;dbname=$this->dbName", $this->dbUsername, $this->dbPass);
            $only->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
            return $only;
        } catch (PDOException $e) {
            log($e->getMessage());
            echo "We fell at the first hurdle.  Please refresh and try again, or contact support@findaplaydate.co.uk";
            die();
        }
    }
}
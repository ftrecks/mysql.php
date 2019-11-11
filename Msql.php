<?php
    class Msql
    {
        private $dbcon = null;
        private $dbret = array();
        private $encode = array();
        public function __construct(string $db_name)
        {
            try {
                $this->dbcon = new PDO(
                    "mysql:host=localhost;dbname=$db_name",
                    "root",
                    "password",
                    array(
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                    )
                );
            }
            catch(PDOException $e) {
                die('Connection error: ' . $e->getMessage());
            }
        }
        public function dbAlter($alter, $args = array())
        {
            if (stripos($alter, 'SELECT') !== false) {
                die('This method is not to be used to SELECT');
            }
            if(stripos($alter, 'INSERT') === false) {
                if (stripos($alter, 'WHERE') === false) {
                    die('This method requires the word WHERE');
                }
            }
            try {
                $this->dbcon->beginTransaction();
                $dbstm = $this->dbcon->prepare($alter);
                foreach ($args as $key => $value) {
                    Msql::dbParam($dbstm, $key, $value);
                }
                $dbstm->execute();
                $this->dbcon->commit();
                return $dbstm->rowCount();
            }
            catch(PDOException $e) {
                $this->dbcon->rollBack();
                //die('Change error: ' . $e->getMessage());
                return false;
            }
        }
        public function dbSelect($query, $args = array())
        {
            try {
                $dbstm = $this->dbcon->prepare($query);
                foreach ($args as $key => $value) {
                    Msql::dbParam($dbstm, $key, $value);
                }
                $dbstm->execute();
                $this->dbret = $dbstm->fetchAll(PDO::FETCH_ASSOC);
                if($this->dbret) {
                    return Msql::dbEncode($this->dbret);
                }
                return false;
            }
            catch(PDOException $e) {
                //die('Select error: ' . $e->getMessage());
                return false;
            }
        }
        public function dbColumn($query, $args = array(), $column = 0)
        {
            try {
                $dbstm = $this->dbcon->prepare($query);
                foreach ($args as $key => $value) {
                    Msql::dbParam($dbstm, $key, $value);
                }
                $dbstm->execute();
                $this->dbret = $dbstm->fetchColumn($column);
                if($this->dbret) {
                    return utf8_encode($this->dbret);
                }
                return false;
            }
            catch(PDOException $e) {
                //die('Select error: ' . $e->getMessage());
                return false;
            }
        }
        public function dbCall($query, $args = array())
        {
            try {
                $dbstm = $this->dbcon->prepare($query);
                foreach ($args as $key => $value) {
                    Msql::dbParam($dbstm, $key, $value);
                }
                $dbstm->execute();
                return $dbstm->rowCount();
            }
            catch(PDOException $e) {
                //die('Call error: ' . $e->getMessage());
                return false;
            }
        }
        private static function dbParam($dbstm, $key, $value)
        {
            if (is_numeric($value)) {
                $dbstm->bindParam($key, $value, PDO::PARAM_INT);
            }
            else {
                $argval = utf8_decode($value);
                $dbstm->bindParam($key, $argval, PDO::PARAM_STR);
            }
        }
        private static function dbEncode($dbret)
        {
            $dbquant = count($dbret);
            for($i = 0; $i < $dbquant; $i++) {
                foreach ($dbret[$i] as $key => $value) {
                    $dbencode[$i][$key] = utf8_encode($value);
                }
            }
            return $dbencode;
        }
        public function __toString()
        {
            if($this->dbret) {
                return json_encode(
                    $this->encode,
                    JSON_UNESCAPED_UNICODE
                );
            }
            else {
                return false;
            }
        }
        public function __destruct()
        {
            if($this->dbcon) {
                $this->dbcon = null;
            }
            if($this->encode) {
                $this->encode = null;
            }
            if($this->dbret) {
                $this->dbret = null;
            }
        }
    }
?>

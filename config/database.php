<?php
// File: config/database.php
// GANTI PORT 8111 DENGAN PORT MYSQL ANDA!

class Database {
    private $host = "localhost";
    private $port = "8111";  // GANTI DENGAN PORT MYSQL ANDA
    private $username = "root";
    private $password = "";  // Password MySQL (default kosong)
    private $database = "gametopup_db";
    public $conn;

    public function __construct() {
        // Koneksi dengan port
        $this->conn = new mysqli(
            $this->host . ":" . $this->port, 
            $this->username, 
            $this->password, 
            $this->database
        );
        
        // Jika gagal, coba tanpa port
        if ($this->conn->connect_error) {
            $this->conn = new mysqli(
                $this->host, 
                $this->username, 
                $this->password, 
                $this->database
            );
        }
        
        if ($this->conn->connect_error) {
            die("Koneksi database gagal: " . $this->conn->connect_error . 
                "<br>Pastikan:<br>" .
                "1. Database 'gametopup_db' sudah dibuat<br>" .
                "2. MySQL berjalan<br>" .
                "3. Port MySQL benar");
        }
        
        $this->conn->set_charset("utf8");
    }

    public function getConnection() {
        return $this->conn;
    }
}
?>
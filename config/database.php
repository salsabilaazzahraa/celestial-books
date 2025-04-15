<?php
$conn = null;

class Database {
    // Parameter database
    private $host = "localhost";
    private $db_name = "celestial_books";
    private $username = "root";
    private $password = "";
    public $conn;

    // Mendapatkan koneksi database
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8mb4");
        } catch (PDOException $exception) {
            echo "Koneksi error: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
}

// Inisialisasi koneksi database
function connectToDatabase() {
    global $conn;
    $database = new Database();
    $conn = $database->getConnection();
    return $conn;
}

// Buat koneksi
$conn = connectToDatabase();
?>

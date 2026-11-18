<?php

function connection(): mysqli
{
    $servername = "localhost";
    $user = "root";
    $password = ""; // isi pw nya klo ada
    $dbname = "sistem_sdm_projek_PW";

    $conn = new mysqli($servername, $user, $password);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    if (!$conn->query("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
        die("Error creating database: " . $conn->error);
    }

    $conn->select_db($dbname);

    $createUsers = "CREATE TABLE IF NOT EXISTS users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(150) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB";

    if (!$conn->query($createUsers)) {
        die("Error creating users table: " . $conn->error);
    }
    
    return $conn;
}
?>
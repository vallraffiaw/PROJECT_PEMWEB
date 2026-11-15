<?php

function connection(): mysqli
{
    $servername = "localhost";
    $user = "root";
    $password = ""; // isi pw nya klo ada
    $dbname = "nama_database"; // nama database e opo

    $conn = new mysqli($servername, $user, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    // jaga2 nek data base e gak connect atau buat tabel user yo oleh tinggal ganti nama
    $createnama_database = "CREATE TABLE IF NOT EXISTS nama_database (
    /* code */
    ) ENGINE=innoDB";

    if ($conn->query("$createnama_database") === false) {
        die("error creating nama_database table: ". $conn->error);
    }
    
    return $conn;
}
?>
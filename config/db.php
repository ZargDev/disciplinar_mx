<?php
define('BASE_URL', '/disciplinar_mx');

$servername = "localhost";
$username = "root";
$password = "AMZdv.21636";
$dbname = "db_disciplinar_mx";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

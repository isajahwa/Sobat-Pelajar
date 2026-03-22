<?php

session_start();
require_once("config.php");

if (isset($_POST["register"])) {
    $name = $_POST["nama"];
    $email = $_POST["email"];
    $password = $_POST["password"];
    $role = $_POST["role"];

    $checkEmail = $conn->query("SELECT email FROM users WHERE email = $email");
    if ($checkEmail->num_rows > 0) {
        $_SESSION["register_error"] = $checkEmail->fetch_assoc();
    }
}
?>
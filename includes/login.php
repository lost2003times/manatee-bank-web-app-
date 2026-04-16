<?php

include "database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['error'] = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $_SESSION['error'] = "All fields are required";
        exit();
    }

    // Get user
    $stmt = $mysqli->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $user = $result->fetch_assoc();

        // 🔐 Recreate hash
        $hashed_input = hash('sha256', $password . $user['salt']);

        if ($hashed_input === $user['password']) {

            $_SESSION['logged_in'] = true;
            $_SESSION['user'] = $username;
            $_SESSION['userid'] = $user['userid'];

            session_regenerate_id(true);

            header("Location: account.php");
            exit();

        } else {
            $_SESSION['error'] = "Invalid username or password";
        }

    } else {
        $_SESSION['error'] = "Invalid username or password";
    }
}
?>

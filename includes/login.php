<?php

include "database.php";

$error_message = "";

if (!array_key_exists("logged_in", $_SESSION)) {
    $_SESSION['logged_in'] = false;
}

// ✅ Only run when login form is submitted
if (isset($_POST['username']) && isset($_POST['password'])) {

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $_SESSION['error'] = "Username and password required";
        return;
    }

    // 🔐 Get user securely
    $stmt = $mysqli->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {

        $user = $result->fetch_assoc();

        // 🔐 Hash input with stored salt
        $hashed_input = hash('sha256', $password . $user['salt']);

        if ($hashed_input === $user['password']) {

            $_SESSION['logged_in'] = true;
            $_SESSION['user'] = $username;
            $_SESSION['userid'] = $user['userid'];
            $_SESSION['role'] = $user['role']; // ✅ ROLE ADDED

            session_regenerate_id(true);

            header("Location: account.php");
            exit();
        }
    }

    $_SESSION['error'] = "Invalid username or password";
}
?>

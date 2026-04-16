
<?php

$mysqli = new mysqli("127.0.0.1", $CONFIG['database_user'], $CONFIG['database_password'], $CONFIG['database']);

if ($mysqli->connect_errno) {
    die("Database connection failed: " . $mysqli->connect_error);
}

?>

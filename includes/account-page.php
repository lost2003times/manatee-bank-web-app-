<ul class="nav nav-pills">
    <li><a href="account.php?action=view">View Accounts</a></li>
    <li><a href="account.php?action=check_upload">Upload Checks</a></li>
    <li><a href="account.php?action=check_view">View Checks</a></li>
    <li><a href="account.php?action=logout">Logout</a></li>
</ul>

<?php

if (!isset($_GET['action']) && !isset($_POST['action'])) {
    $action = 'view';
} elseif (isset($_POST['action'])) {
    $action = $_POST['action'];
} else {
    $action = $_GET['action'];
}

/* ====================== ADD ACCOUNT ====================== */
if ($action == "add_account") {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "Access denied: Admin only";
    $action = "view";
    return;
}	

    $name = trim($_POST['name']);

    if (empty($name)) {
        echo "Account name required";
    } else {

        // Determine target user
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin' && isset($_POST['userid'])) {
            $target_user = (int)$_POST['userid'];
        } else {
            $target_user = (int)$_SESSION['userid'];
        }

        // Check duplicate
        $stmt = $mysqli->prepare("SELECT * FROM accounts WHERE userid = ? AND accountname = ?");
        $stmt->bind_param("is", $target_user, $name);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo "An account of that name already exists";
        } else {

            $stmt = $mysqli->prepare("INSERT INTO accounts (accountname, userid, balance) VALUES (?, ?, 0)");
            $stmt->bind_param("si", $name, $target_user);
            $success = $stmt->execute();

            if (!$success) {
                echo "Failed to add account.";
            }
        }
    }

    $action = "view";
}

/* ====================== UPLOAD CHECK ====================== */
else if ($action == "upload") {

    $accountid = $_POST['accountid'] ?? null;
    $name = trim($_POST['name']);

    if (empty($accountid)) {
        echo "Please select an account";
        exit();
    }

    if (empty($name)) {
        echo "Check name required";
        exit();
    }

    // Authorization check
    if ($_SESSION['role'] === 'admin') {
        $stmt = $mysqli->prepare("SELECT * FROM accounts WHERE accountid = ?");
        $stmt->bind_param("i", $accountid);
    } else {
        $stmt = $mysqli->prepare("SELECT * FROM accounts WHERE accountid = ? AND userid = ?");
        $stmt->bind_param("ii", $accountid, $_SESSION['userid']);
    }

    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows == 0) {
        echo "Invalid account";
        exit();
    }

    // Duplicate check
    $stmt = $mysqli->prepare("SELECT * FROM checks WHERE userid = ? AND name = ?");
    $stmt->bind_param("is", $_SESSION['userid'], $name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "A check of that name already exists<br>";
        exit();
    }

    // File validation
    if (isset($_FILES['check_file']) && $_FILES['check_file']['error'] == 0) {

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['check_file']['tmp_name']);

        $allowed_mime = ['image/jpeg', 'image/png'];

        if (!in_array($mime, $allowed_mime)) {
            echo "Invalid file type";
            exit();
        }

        if (getimagesize($_FILES['check_file']['tmp_name']) === false) {
            echo "File is not a valid image";
            exit();
        }

        $ext = strtolower(pathinfo($_FILES['check_file']['name'], PATHINFO_EXTENSION));
        $filename = uniqid("check_", true) . "." . $ext;

        $full_path = dirname($_SERVER['SCRIPT_FILENAME']) . "/checks/" . $filename;

        if (move_uploaded_file($_FILES['check_file']['tmp_name'], $full_path)) {

            $stmt = $mysqli->prepare("INSERT INTO checks (userid, accountid, name, filename) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $_SESSION['userid'], $accountid, $name, $filename);

            if ($stmt->execute()) {
                echo "<div>Check uploaded successfully!</div>";
            } else {
                echo "<div>Database insert failed</div>";
            }

        } else {
            echo "<div>File upload failed</div>";
        }

    } else {
        echo "<div>No file uploaded</div>";
    }

    $action = "check_view";
}

/* ====================== LOGOUT ====================== */
if ($action == "logout") {
    $_SESSION['logged_in'] = false;
    session_destroy();
    echo "You have been logged out";
}

/* ====================== VIEW ACCOUNTS ====================== */
elseif ($action == "view") {

    if ($_SESSION['role'] === 'admin') {

        $stmt = $mysqli->prepare("
            SELECT accounts.accountid, accounts.accountname, accounts.userid, 
                   COUNT(checks.checkid) AS total_checks 
            FROM accounts 
            LEFT JOIN checks ON accounts.accountid = checks.accountid 
            GROUP BY accounts.accountid
        ");

    } else {

        $stmt = $mysqli->prepare("
            SELECT accounts.accountid, accounts.accountname, 
                   COUNT(checks.checkid) AS total_checks 
            FROM accounts 
            LEFT JOIN checks ON accounts.accountid = checks.accountid 
            WHERE accounts.userid = ? 
            GROUP BY accounts.accountid
        ");

        $stmt->bind_param("i", $_SESSION['userid']);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    echo "<h3>Accounts</h3>\n<table class='table'>\n<tr><th>Account Name</th><th>Checks</th></tr>";

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {

            if ($_SESSION['role'] === 'admin') {
                echo "<tr><td>" . htmlspecialchars($row['accountname']) .  
                    "</td><td>" . $row['total_checks'] . "</td></tr>";
            } else {
                echo "<tr><td>" . htmlspecialchars($row['accountname']) . 
                     "</td><td>" . $row['total_checks'] . "</td></tr>";
            }
        }
    } else {
        echo "<tr><td>No accounts</td><td>-</td></tr>";
    }

    echo "</table>";

    // Form
    echo "<br><form method='post' action='account.php'>";

    if ($_SESSION['role'] === 'admin') {
        $users = $mysqli->query("SELECT userid, username FROM users");

        echo "<select name='userid'>";
        while ($u = $users->fetch_assoc()) {
            echo "<option value='{$u['userid']}'>" . htmlspecialchars($u['username']) . "</option>";
        }
        echo "</select><br>";
    }

    echo "
    <input type='text' name='name' />
    <input type='hidden' name='action' value='add_account'/>
    <button type='submit'>Add Account</button>
    </form>";
}

/* ====================== CHECK UPLOAD FORM ====================== */
elseif ($action == "check_upload") {

    if ($_SESSION['role'] === 'admin') {
        $stmt = $mysqli->prepare("SELECT * FROM accounts");
    } else {
        $stmt = $mysqli->prepare("SELECT * FROM accounts WHERE userid = ?");
        $stmt->bind_param("i", $_SESSION['userid']);
    }

    $stmt->execute();
    $accounts = $stmt->get_result();

    echo "<form method='post' action='account.php' enctype='multipart/form-data'>
    <input type='text' name='name' placeholder='Check name' />

    <select name='accountid'>";

    while ($row = $accounts->fetch_assoc()) {
        echo "<option value='" . $row['accountid'] . "'>" . htmlspecialchars($row['accountname']) . "</option>";
    }

    echo "</select>
    <input type='file' name='check_file' />
    <input type='hidden' name='action' value='upload'/>
    <button type='submit'>Upload Check</button>
    </form>";
}

/* ====================== VIEW CHECKS ====================== */
elseif ($action == "check_view") {

    if ($_SESSION['role'] === 'admin') {
        $stmt = $mysqli->prepare("SELECT * FROM checks");
    } else {
        $stmt = $mysqli->prepare("SELECT * FROM checks WHERE userid = ?");
        $stmt->bind_param("i", $_SESSION['userid']);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    echo "<h3>Checks</h3>\n<table class='table'>\n<tr><th>Name</th><th>View</th></tr>";

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>" . htmlspecialchars($row['name']) . 
                 "</td><td><a href='checks/" . $row['filename'] . "'>View</a></td></tr>";
        }
    } else {
        echo "<tr><td>No checks</td><td>-</td></tr>";
    }

    echo "</table>";
}

else {
    echo "Invalid action";
}

?>

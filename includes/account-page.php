<ul class="nav nav-pills">
    <li><a href="account.php?action=view">View Accounts</a></li>
    <li><a href="account.php?action=check_upload">Upload Checks</a></li>
    <li><a href="account.php?action=check_view">View Checks</a></li>
    <li><a href="account.php?action=logout">Logout</a></li>
</ul>

<?php

if (!array_key_exists('action', $_GET) && !array_key_exists('action', $_POST)) {
    $action = 'view';
} elseif (array_key_exists('action', $_POST)) {
    $action = $_POST['action'];
} else {
    $action = $_GET['action'];
}

// ====================== ADD ACCOUNT ======================
if ($action == "add_account") {

    $name = trim($_POST['name']);

    if (empty($name)) {
        echo "Account name required";
    } else {

        $stmt = $mysqli->prepare("SELECT * FROM accounts WHERE userid = ? AND accountname = ?");
        $stmt->bind_param("is", $_SESSION['userid'], $name);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo "An account of that name already exists";
        } else {

            $stmt = $mysqli->prepare("INSERT INTO accounts (accountname, userid, balance) VALUES (?, ?, 0)");
            $stmt->bind_param("si", $name, $_SESSION['userid']);
            $success = $stmt->execute();

            if (!$success) {
                echo "Failed to add account.";
            }
        }
    }

    $action = "view";
}

// ====================== UPLOAD CHECK ====================
else if ($action == "upload") {

    // Validate inputs
    $accountid = isset($_POST['accountid']) ? $_POST['accountid'] : null;
    $name = trim($_POST['name']);

    if (empty($accountid)) {
        echo "Please select an account";
        exit();
    }

    if (empty($name)) {
        echo "Check name required";
        exit();
    }

    // Verify account belongs to user (authorization check)
    $stmt = $mysqli->prepare("SELECT * FROM accounts WHERE accountid = ? AND userid = ?");
    $stmt->bind_param("ii", $accountid, $_SESSION['userid']);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows == 0) {
        echo "Invalid account";
        exit();
    }

    // Check duplicate check name
    $stmt = $mysqli->prepare("SELECT * FROM checks WHERE userid = ? AND name = ?");
    $stmt->bind_param("is", $_SESSION['userid'], $name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "A check of that name already exists<br>";
        exit();
    }

    // Validate file upload
    if (isset($_FILES['check_file']) && $_FILES['check_file']['error'] == 0) {

        // 🔐 MIME type validation
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['check_file']['tmp_name']);

        $allowed_mime = ['image/jpeg', 'image/png'];

        if (!in_array($mime, $allowed_mime)) {
            echo "Invalid file type";
            exit();
        }

        // 🔐 Verify real image
        if (getimagesize($_FILES['check_file']['tmp_name']) === false) {
            echo "File is not a valid image";
            exit();
        }

        // Get extension safely
        $ext = strtolower(pathinfo($_FILES['check_file']['name'], PATHINFO_EXTENSION));

        // 🔐 Generate safe filename
        $filename = uniqid("check_", true) . "." . $ext;

        // File path
        $full_path = dirname($_SERVER['SCRIPT_FILENAME']) . "/checks/" . $filename;

        // Move file
        if (move_uploaded_file($_FILES['check_file']['tmp_name'], $full_path)) {

            // Insert into database
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
// ====================== LOGOUT ======================
if ($action == "logout") {
    $_SESSION['logged_in'] = false;
    session_destroy();
    echo "You have been logged out";
}

// ====================== VIEW ACCOUNTS ======================
elseif ($action == "view") {

    $stmt = $mysqli->prepare("SELECT accounts.accountid, accounts.accountname, COUNT(checks.checkid) AS total_checks FROM accounts
LEFT JOIN checks ON accounts.accountid = checks.accountid WHERE accounts.userid = ? GROUP BY accounts.accountid");
    $stmt->bind_param("i", $_SESSION['userid']);
    $stmt->execute();
    $result = $stmt->get_result();

    echo "<h3>Accounts</h3>\n<table class='table'>\n<tr><th>Account Name</th><th>Checks</th></tr>";

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()){
            echo "<tr><td>" . htmlspecialchars($row['accountname']) . "</td><td>" . $row['total_checks'] . "</td></tr>";
        }
    } else {
        echo "<tr><td>You have no accounts</td><td>-</td></tr>";
    }
    echo "</table>";

    echo "<br><form method='post' action='account.php'>
    <input type='text' name='name' />
    <input type='hidden' name='action' value='add_account'/>
    <button type='submit'>Add Account</button>
    </form>";
}

// ====================== CHECK UPLOAD FORM ======================
elseif ($action == "check_upload") {
    $stmt = $mysqli->prepare("SELECT * FROM accounts WHERE userid = ?");

    $stmt->bind_param("i", $_SESSION['userid']);
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

// ====================== VIEW CHECKS ======================
elseif ($action == "check_view") {

    $stmt = $mysqli->prepare("SELECT * FROM checks WHERE userid = ?");
    $stmt->bind_param("i", $_SESSION['userid']);
    $stmt->execute();
    $result = $stmt->get_result();

    echo "<h3>Checks</h3>\n<table class='table'>\n<tr><th>Name</th><th>View</th></tr>";

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()){
            echo "<tr><td>" . htmlspecialchars($row['name']) . "</td><td><a href='checks/" . $row['filename'] . "'>View</a></td></tr>";
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

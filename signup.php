<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'config.php';
include 'includes/database.php';

$ERROR = "";
$OKAY = FALSE;

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST['username']);
    $fullname = trim($_POST['fullname']);
    $password = $_POST['password'];

    if (empty($username) || empty($password) || empty($fullname)) {
        $ERROR = "All fields are required";
    } else {

        // Generate salt
        $salt = bin2hex(random_bytes(16));

        // Hash password
        $hashed_password = hash('sha256', $password . $salt);

        // Check if exists
        $stmt = $mysqli->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $ERROR = "Username already exists";
        } else {

            // Insert user
            $stmt = $mysqli->prepare("INSERT INTO users (username, fullname, password, salt) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $fullname, $hashed_password, $salt);

            if ($stmt->execute()) {
                $OKAY = TRUE;
		// ✅ Get new user ID
    		$new_user_id = $stmt->insert_id;

    // ✅ Create default account
    		$defaultAccount = $fullname. " Account";

    		$stmt2 = $mysqli->prepare("INSERT INTO accounts (userid, accountname) VALUES (?, ?)");
    		$stmt2->bind_param("is", $new_user_id, $defaultAccount);
    		$stmt2->execute();
            } else {
                $ERROR = "Insert failed";
            }
        }
    }
}
?>

<?php include 'includes/header.php'; ?>

<main class="container">

<p>Signing up for an account is fast and easy!</p>

<?php if ($ERROR != ""): ?>
<div class="alert alert-danger">
<?php echo $ERROR; ?>
</div>
<?php endif; ?>

<?php if ($OKAY): ?>
<div class="alert alert-success">
Account created successfully!
</div>
<?php endif; ?>

<form method="post" action="signup.php">
    <div class="form-group">
        <label>Username</label>
        <input type="text" class="form-control" name="username">
    </div>

    <div class="form-group">
        <label>Full Name</label>
        <input type="text" class="form-control" name="fullname">
    </div>

    <div class="form-group">
        <label>Password</label>
        <input type="password" class="form-control" name="password">
    </div>

    <button type="submit" class="btn btn-primary">Sign Up</button>
</form>

</main>

<?php include 'includes/footer.php'; ?>

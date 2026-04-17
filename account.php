<?php include 'includes/header.php' ?>
<?php include 'config.php' ?>
<?php include 'includes/login.php' ?>

<main class="container">
    <?php

    // ✅ Ensure session variables exist
    if (!isset($_SESSION['logged_in'])) {
        $_SESSION['logged_in'] = false;
    }

    if (!isset($_SESSION['error'])) {
        $_SESSION['error'] = "";
    }

    // ✅ If logged in → show account page
    if ($_SESSION['logged_in'] === true) {

        include "includes/account-page.php";

    } else {

        // ✅ Safe error display
        if (!empty($_SESSION['error'])) {
            echo "<div>" . $_SESSION['error'] . "</div>";
            $_SESSION['error'] = ""; // clear after showing
        } else {
            echo "<div>Please login first</div>";
        }
    }
    ?>
</main>

<?php include 'includes/footer.php' ?>

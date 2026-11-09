<?php

session_start();

require_once __DIR__ . "/../config/database.php";

// Redirect already logged-in administrators
if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($email) || empty($password)) {

        $error = "Please enter your email and password.";

    } else {

        $stmt = $pdo->prepare(
            "SELECT * FROM admin_users WHERE email = ? LIMIT 1"
        );

        $stmt->execute([$email]);

        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {

            session_regenerate_id(true);

            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['full_name'];
            $_SESSION['admin_email'] = $admin['email'];

            header("Location: dashboard.php");
            exit;

        } else {

            $error = "Invalid email or password.";

        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Admin Login | Calz Connect</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f4f6f9;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
}

.login-box {
    background: white;
    width: calc(100% - 32px);
    max-width: 480px;
    padding: 40px 30px;
    border-radius: 16px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.08);
}

.login-box h2 {
    font-size: 25px;
}

.subtitle {
    font-size: 16px;
}

label {
    font-size: 15px;
}

input {
    min-height: 50px;
    font-size: 16px;
}

button {
    min-height: 52px;
    font-size: 17px;
}
h2 {
    text-align: center;
    color: #172554;
}

.subtitle {
    text-align: center;
    color: #777;
    margin-bottom: 30px;
}

label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
}

input {
    width: 100%;
    padding: 13px;
    margin-bottom: 20px;
    border: 1px solid #ddd;
    border-radius: 7px;
    font-size: 15px;
}

button {
    width: 100%;
    padding: 14px;
    background: #172554;
    color: white;
    border: none;
    border-radius: 7px;
    font-size: 16px;
    cursor: pointer;
}

button:hover {
    background: #243b78;
}

.error {
    background: #ffe5e5;
    color: #b00020;
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 20px;
}

</style>

</head>

<body>

<div class="login-box">

<h2>CALZ CONNECT</h2>

<p class="subtitle">Administrator Login</p>

<?php if (!empty($error)): ?>

<div class="error">
    <?= htmlspecialchars($error) ?>
</div>

<?php endif; ?>

<form method="POST">

<label>Email Address</label>

<input
    type="email"
    name="email"
    placeholder="Enter administrator email"
    required
>

<label>Password</label>

<input
    type="password"
    name="password"
    placeholder="Enter your password"
    required
>

<button type="submit">Login to Admin Panel</button>

</form>

</div>

</body>
</html>

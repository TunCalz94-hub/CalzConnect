<?php
session_start();

require_once __DIR__ . "/config/database.php";

$error = "";

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {

        $error = "Invalid form submission. Refresh and try again.";

    } else {

        try {

            $stmt = $pdo->prepare(
                "SELECT id, full_name, email, password_hash, account_status
                 FROM users
                 WHERE email = ?
                 LIMIT 1"
            );

            $stmt->execute([$email]);

            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {

                $error = "Incorrect email or password.";

            } elseif ($user['account_status'] === 'pending') {

                $error = "Your account is awaiting administrator approval.";

            } elseif ($user['account_status'] === 'suspended') {

                $error = "Your account has been suspended.";

            } elseif ($user['account_status'] === 'active') {

                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];

                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                header("Location: member/dashboard.php");
                exit;

            }

        } catch (PDOException $e) {

            error_log("Login error: " . $e->getMessage());

            $error = "Something went wrong. Please try again.";

        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Login | Calz Connect</title>


<style>
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

html {
    width: 100%;
    min-height: 100%;
}

body {
    font-family: Arial, sans-serif;
    background: #f4f7fc;
    color: #17243a;
    min-height: 100vh;
    min-height: 100svh;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 24px 16px;
}

.login-container {
    background: #ffffff;
    width: 100%;
    max-width: 460px;
    padding: 40px 32px;
    border-radius: 18px;
    box-shadow: 0 10px 35px rgba(0,0,0,0.08);
}

.logo {
    text-align: center;
    font-size: 28px;
    font-weight: 800;
    color: #173d78;
    margin-bottom: 22px;
}

h2 {
    text-align: center;
    font-size: 25px;
    margin-bottom: 12px;
}

.intro {
    text-align: center;
    color: #68758a;
    font-size: 15px;
    line-height: 1.6;
    margin-bottom: 28px;
}

label {
    display: block;
    margin: 20px 0 9px;
    font-weight: 600;
    font-size: 15px;
}

input {
    width: 100%;
    min-height: 52px;
    padding: 14px 16px;
    border: 1px solid #dce3ed;
    border-radius: 9px;
    font-size: 16px;
    background: #ffffff;
}

input:focus {
    outline: none;
    border-color: #173d78;
    box-shadow: 0 0 0 3px rgba(23,61,120,0.1);
}

button {
    width: 100%;
    min-height: 52px;
    padding: 14px;
    background: #173d78;
    color: white;
    border: none;
    border-radius: 9px;
    font-size: 16px;
    font-weight: bold;
    margin-top: 28px;
    cursor: pointer;
}

button:hover {
    background: #102d5a;
}

.error {
    background: #ffe8e8;
    color: #b42318;
    padding: 14px;
    border-radius: 8px;
    margin-bottom: 18px;
    font-size: 15px;
}

.links {
    text-align: center;
    margin-top: 26px;
    font-size: 15px;
    line-height: 1.6;
}

a {
    color: #173d78;
    text-decoration: none;
    font-weight: bold;
}

/* Improve sizing on touch-screen devices */
@media (pointer: coarse) {
    .login-container {
        width: 92vw;
        max-width: none;
        padding: 35px 25px;
    }
}

@media (max-width: 380px) {
    .login-container {
        padding: 28px 20px;
    }
}
</style>


</head>

<body>

<div class="login-container">

    <div class="logo">CALZ CONNECT</div>

    <h2>Welcome Back</h2>

    <p class="intro">
        Login to access your Calz Connect account.
    </p>

    <?php if ($error): ?>
        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
        >

        <label>Email Address</label>

        <input
            type="email"
            name="email"
            placeholder="Enter your email"
            required
        >

        <label>Password</label>

        <input
            type="password"
            name="password"
            placeholder="Enter your password"
            required
        >

        <button type="submit">LOGIN</button>

    </form>

    <div class="links">
        Don't have an account?
        <a href="register.php">Register</a>
    </div>

</div>

</body>
</html>

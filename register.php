<?php
session_start();

require_once __DIR__ . "/config/database.php";

$errors = [];
$success = "";
$newReferralLink ="";

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$referralCode = strtoupper(trim($_GET['ref'] ?? $_POST['referral_code'] ?? ''));

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $fullName = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $referralCode = strtoupper(trim($_POST['referral_code'] ?? ''));

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid form submission. Please refresh and try again.";
    }

    if (strlen($fullName) < 3 || strlen($fullName) > 120) {
        $errors[] = "Enter your full name (3–120 characters).";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter a valid email address.";
    }

    if (!preg_match('/^[0-9+\-\s()]{7,30}$/', $phone)) {
        $errors[] = "Enter a valid phone number.";
    }

    if (strlen($password) < 8) {
        $errors[] = "Your password must contain at least 8 characters.";
    }

    if ($password !== $confirmPassword) {
        $errors[] = "Your passwords do not match.";
    }

    if (empty($errors)) {
        try {

            // Check whether the email is already registered.
            $check = $pdo->prepare(
                "SELECT id FROM users WHERE email = ?"
            );
            $check->execute([$email]);

            if ($check->fetch()) {
                $errors[] = "This email address is already registered.";
            }

            // Verify the referral code if one was provided.
            $referredBy = null;

            if (!$errors && $referralCode !== '') {

                $refCheck = $pdo->prepare(
                    "SELECT referral_code FROM users WHERE referral_code = ?"
                );

                $refCheck->execute([$referralCode]);

                if ($refCheck->fetch()) {
                    $referredBy = $referralCode;
                } else {
                    $errors[] = "The referral code is invalid.";
                }
            }

            if (!$errors) {

                $passwordHash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                // Generate a unique referral code.
                do {
                    $newReferralCode = "CALZ" . strtoupper(bin2hex(random_bytes(4)));

                    $codeCheck = $pdo->prepare(
                        "SELECT id FROM users WHERE referral_code = ?"
                    );

                    $codeCheck->execute([$newReferralCode]);

                } while ($codeCheck->fetch());

                // Save the new member.
                $insert = $pdo->prepare(
                    "INSERT INTO users
                    (full_name, email, phone, password_hash, referral_code, referred_by)
                    VALUES (?, ?, ?, ?, ?, ?)"
                );

                $insert->execute([
                    $fullName,
                    $email,
                    $phone,
                    $passwordHash,
                    $newReferralCode,
                    $referredBy
                ]);

                $success = "Registration successful! Your account is awaiting approval.";
                $protocol = (
                !empty($_SERVER['HTTPS']) &&
              $_SERVER['HTTPS'] !== 'off'
              ) ? "https://" : "http://";

$newReferralLink = $protocol .
    $_SERVER['HTTP_HOST'] .
    "/register.php?ref=" .
    urlencode($newReferralCode);

                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                $_POST = [];
                $referralCode = '';
            }

        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            $errors[] = "Something went wrong. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Join Calz Connect</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f4f7fc;
            color: #17243a;
            padding: 25px 15px;
        }

        .container {
            max-width: 480px;
            margin: 35px auto;
            background: white;
            padding: 35px 25px;
            border-radius: 15px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.07);
        }

        .logo {
            text-align: center;
            font-size: 25px;
            font-weight: bold;
            color: #173d78;
            margin-bottom: 12px;
        }

        h2 {
            text-align: center;
            margin-bottom: 10px;
        }

        .intro {
            text-align: center;
            color: #68758a;
            font-size: 14px;
            margin-bottom: 25px;
            line-height: 1.5;
        }

        label {
            display: block;
            font-weight: bold;
            font-size: 14px;
            margin: 15px 0 7px;
        }

        input {
            width: 100%;
            padding: 13px;
            border: 1px solid #d9e0eb;
            border-radius: 8px;
            font-size: 15px;
            outline: none;
        }

        input:focus {
            border-color: #2463ad;
        }

        button {
            width: 100%;
            padding: 14px;
            background: #173d78;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            margin-top: 22px;
            cursor: pointer;
        }

        button:hover {
            background: #24569a;
        }

        .message {
            padding: 13px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 14px;
            line-height: 1.5;
        }

        .error {
            background: #ffe8e8;
            color: #a32121;
        }

        .success {
            background: #e4f8ec;
            color: #176536;
        }

        .bottom {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #68758a;
        }

        .bottom a {
            color: #173d78;
            font-weight: bold;
            text-decoration: none;
        }
    </style>
</head>
<script>
function copyReferralLink() {

    const link = document.getElementById("referralLink");

    link.select();

    navigator.clipboard.writeText(link.value)
        .then(() => {
            alert("Referral link copied successfully!");
        })
        .catch(() => {
            alert("Please copy the link manually.");
        });

}
</script>

<body>

<div class="container">

    <div class="logo">CALZ CONNECT</div>

    <h2>Create Your Account</h2>

    <p class="intro">
        Join our community and connect with opportunities.
    </p>

    <?php if ($errors): ?>
        <div class="message error">
            <?php foreach ($errors as $error): ?>
                <p><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>

    <div class="message success">

        <p><?= htmlspecialchars($success) ?></p>

        <h3>Your Referral Link</h3>

        <input
            type="text"
            id="referralLink"
            value="<?= htmlspecialchars($newReferralLink) ?>"
            readonly
        >

        <button
            type="button"
            onclick="copyReferralLink()"
        >
            COPY REFERRAL LINK
        </button>

    </div>

<?php endif; ?>
    <?php if (!$success): ?>

    <form method="POST" action="register.php">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
        >

        <label>Full Name</label>
        <input
            type="text"
            name="full_name"
            placeholder="Enter your full name"
            value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
            required
        >

        <label>Email Address</label>
        <input
            type="email"
            name="email"
            placeholder="Enter your email"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
            required
        >

        <label>Phone Number</label>
        <input
            type="tel"
            name="phone"
            placeholder="e.g. 2567XXXXXXXX"
            value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
            required
        >

        <label>Password</label>
        <input
            type="password"
            name="password"
            placeholder="At least 8 characters"
            required
        >

        <label>Confirm Password</label>
        <input
            type="password"
            name="confirm_password"
            placeholder="Enter password again"
            required
        >
     <input
        type="hidden"
       name="referral_code"
        value="<?= htmlspecialchars($referralCode) ?>"
       >


        <button type="submit">CREATE ACCOUNT</button>

    </form>

    <?php endif; ?>

    <div class="bottom">
        Already registered?
        <a href="login.php">Login</a>
    </div>

</div>

</body>
</html>

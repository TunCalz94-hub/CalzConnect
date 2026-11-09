<?php
session_start();

require_once __DIR__ . "/../config/database.php";

// Protect the dashboard.
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

try {

    // Get the logged-in member.
    $stmt = $pdo->prepare(
        "SELECT id, full_name, email, referral_code, account_status
         FROM users
         WHERE id = ?
         LIMIT 1"
    );

    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user || $user['account_status'] !== 'active') {
        session_unset();
        session_destroy();

        header("Location: ../login.php");
        exit;
    }

    // Count referrals.
    $countStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM users WHERE referred_by = ?"
    );

    $countStmt->execute([$user['referral_code']]);
    $totalReferrals = (int) $countStmt->fetchColumn();

    // Get referred members.
    $refStmt = $pdo->prepare(
        "SELECT full_name, created_at, account_status
         FROM users
         WHERE referred_by = ?
         ORDER BY created_at DESC"
    );

    $refStmt->execute([$user['referral_code']]);
    $referrals = $refStmt->fetchAll();

    // Generate personal referral link.
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    $scheme = $https ? 'https' : 'http';

    $host = $_SERVER['HTTP_HOST'];

    $projectPath = dirname(dirname($_SERVER['SCRIPT_NAME']));

    $projectPath = rtrim($projectPath, '/');

    $referralLink = $scheme . '://' . $host .
        $projectPath . '/register.php?ref=' .
        urlencode($user['referral_code']);

    // WhatsApp sharing.
    $shareMessage = "Join Calz Connect using my personal referral link: " .
        $referralLink;

    $whatsappLink = "https://wa.me/?text=" .
        urlencode($shareMessage);

} catch (PDOException $e) {

    error_log("Dashboard error: " . $e->getMessage());

    die("Unable to load your dashboard. Please try again later.");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Member Dashboard | Calz Connect</title>

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
}

.header {
    background: #173d78;
    color: white;
    padding: 20px;
}

.header-content {
    max-width: 1100px;
    margin: auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
}

.logo {
    font-size: 22px;
    font-weight: bold;
}

.logout {
    color: white;
    text-decoration: none;
    border: 1px solid white;
    padding: 9px 16px;
    border-radius: 7px;
}

.container {
    max-width: 1100px;
    margin: 35px auto;
    padding: 0 18px;
}

.welcome {
    margin-bottom: 25px;
}

.welcome h1 {
    font-size: 27px;
    margin-bottom: 8px;
}

.welcome p {
    color: #68758a;
}

.cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 18px;
    margin-bottom: 25px;
}

.card {
    background: white;
    padding: 25px;
    border-radius: 13px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.04);
}

.card h3 {
    color: #68758a;
    font-size: 14px;
    margin-bottom: 12px;
}

.card h2 {
    font-size: 30px;
    color: #173d78;
}

.referral-box {
    background: white;
    padding: 25px;
    border-radius: 13px;
    margin-bottom: 25px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.04);
}

.referral-box h2 {
    margin-bottom: 12px;
}

.referral-box p {
    color: #68758a;
    margin-bottom: 18px;
    line-height: 1.5;
}

.link-area {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.link-area input {
    flex: 1;
    min-width: 200px;
    padding: 13px;
    border: 1px solid #dce3ed;
    border-radius: 7px;
    font-size: 14px;
}

button, .whatsapp {
    padding: 13px 18px;
    border: none;
    border-radius: 7px;
    cursor: pointer;
    font-weight: bold;
    text-decoration: none;
    font-size: 14px;
}

.copy {
    background: #173d78;
    color: white;
}

.whatsapp {
    background: #168c48;
    color: white;
    display: inline-block;
    margin-top: 15px;
}

.table-box {
    background: white;
    padding: 25px;
    border-radius: 13px;
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 18px;
    min-width: 450px;
}

th, td {
    padding: 14px;
    text-align: left;
    border-bottom: 1px solid #edf0f5;
    font-size: 14px;
}

th {
    color: #68758a;
}

.empty {
    color: #68758a;
    padding: 20px 0;
}

@media(max-width: 500px) {

    .welcome h1 {
        font-size: 23px;
    }

    .link-area {
        flex-direction: column;
    }

    .link-area input,
    .link-area button {
        width: 100%;
    }

}

</style>

</head>

<body>

<header class="header">

    <div class="header-content">

        <div class="logo">CALZ CONNECT</div>

        <a href="../logout.php" class="logout">Logout</a>

    </div>

</header>

<main class="container">

    <section class="welcome">

        <h1>
            Welcome, <?= htmlspecialchars($user['full_name']) ?>!
        </h1>

        <p>
            Welcome to your Calz Connect member dashboard.
        </p>

    </section>

    <section class="cards">

        <div class="card">

            <h3>Total Referrals</h3>

            <h2><?= $totalReferrals ?></h2>

        </div>

        <div class="card">

            <h3>Your Referral Code</h3>

            <h2 style="font-size:21px">
                <?= htmlspecialchars($user['referral_code']) ?>
            </h2>

        </div>

        <div class="card">

            <h3>Account Status</h3>

            <h2 style="font-size:21px">
                <?= htmlspecialchars(ucfirst($user['account_status'])) ?>
            </h2>

        </div>

    </section>

    <section class="referral-box">

        <h2>Your Personal Referral Link</h2>

        <p>
            Invite people to Calz Connect using your personal link.
            When they register through it, their accounts will be
            associated with your referral account.
        </p>

        <div class="link-area">

            <input
                type="text"
                id="referralLink"
                value="<?= htmlspecialchars($referralLink) ?>"
                readonly
            >

            <button class="copy" onclick="copyReferralLink()">
                Copy Link
            </button>

        </div>

        <a
            href="<?= htmlspecialchars($whatsappLink) ?>"
            target="_blank"
            rel="noopener noreferrer"
            class="whatsapp"
        >
            Share on WhatsApp
        </a>

    </section>

    <section class="table-box">

        <h2>People You Referred</h2>

        <?php if ($referrals): ?>

        <table>

            <thead>

                <tr>
                    <th>Member Name</th>
                    <th>Registration Date</th>
                    <th>Account Status</th>
                </tr>

            </thead>

            <tbody>

                <?php foreach ($referrals as $ref): ?>

                <tr>

                    <td>
                        <?= htmlspecialchars($ref['full_name']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($ref['created_at']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars(ucfirst($ref['account_status'])) ?>
                    </td>

                </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

        <?php else: ?>

        <p class="empty">
            You haven't referred anyone yet.
            Share your personal link to start inviting members!
        </p>

        <?php endif; ?>

    </section>

</main>

<script>

function copyReferralLink() {

    const input = document.getElementById("referralLink");

    navigator.clipboard.writeText(input.value)
        .then(() => {
            alert("Your referral link has been copied!");
        })
        .catch(() => {
            input.select();
            document.execCommand("copy");
            alert("Your referral link has been copied!");
        });

}

</script>

</body>
</html>

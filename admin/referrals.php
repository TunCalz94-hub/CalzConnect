<?php

session_start();

require_once __DIR__ . "/../config/database.php";

// Protect administrator page
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Get referral statistics
$totalReferrals = $pdo->query(
    "SELECT COUNT(*) FROM users
     WHERE referred_by IS NOT NULL
     AND referred_by != ''"
)->fetchColumn();

// Get members and their referral counts
$stmt = $pdo->query(
    "SELECT
        u.id,
        u.full_name,
        u.email,
        u.referral_code,
        u.created_at,
        COUNT(r.id) AS total_invited
     FROM users u
     LEFT JOIN users r
     ON r.referred_by = u.referral_code
     GROUP BY
        u.id,
        u.full_name,
        u.email,
        u.referral_code,
        u.created_at
     ORDER BY total_invited DESC"
);

$members = $stmt->fetchAll();

// Get individual referral relationships
$relationshipStmt = $pdo->query(
    "SELECT
        referred.full_name AS member_name,
        referred.email AS member_email,
        referred.created_at,
        referrer.full_name AS referrer_name,
        referrer.referral_code
     FROM users referred
     INNER JOIN users referrer
     ON referred.referred_by = referrer.referral_code
     ORDER BY referred.created_at DESC
     LIMIT 100"
);

$relationships = $relationshipStmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Referral Management | Calz Connect</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f4f6f9;
    color: #222;
}

header {
    background: #172554;
    color: white;
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}

header h2 {
    margin: 0;
}

header a {
    background: #facc15;
    color: #172554;
    padding: 10px 15px;
    text-decoration: none;
    border-radius: 6px;
    font-weight: bold;
}

.container {
    max-width: 1300px;
    margin: auto;
    padding: 25px;
}

.stat-card {
    background: white;
    padding: 22px;
    border-radius: 10px;
    margin-bottom: 25px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.05);
}

.stat-card h3 {
    color: #777;
    font-size: 15px;
}

.stat-card p {
    color: #172554;
    font-size: 32px;
    font-weight: bold;
}

.section {
    margin-top: 30px;
}

.table-wrapper {
    overflow-x: auto;
    background: white;
    padding: 15px;
    border-radius: 10px;
}

table {
    width: 100%;
    min-width: 700px;
    border-collapse: collapse;
}

th, td {
    padding: 13px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

th {
    background: #f8fafc;
}

.empty {
    text-align: center;
    padding: 25px;
    color: #777;
}

@media(max-width: 600px) {

    .container {
        padding: 15px;
    }

}

</style>

</head>

<body>

<header>

    <h2>CALZ CONNECT</h2>

    <a href="dashboard.php">Dashboard</a>

</header>

<main class="container">

    <h2>Referral Management</h2>

    <p>Monitor member invitations and referral activity.</p>

    <div class="stat-card">

        <h3>Total Successful Referrals</h3>

        <p><?= (int)$totalReferrals ?></p>

    </div>

    <section class="section">

        <h2>Member Referral Performance</h2>

        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>
                        <th>Member</th>
                        <th>Email</th>
                        <th>Referral Code</th>
                        <th>People Invited</th>
                    </tr>

                </thead>

                <tbody>

                <?php if (!empty($members)): ?>

                    <?php foreach ($members as $member): ?>

                    <tr>

                        <td>
                            <?= htmlspecialchars($member['full_name']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($member['email']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($member['referral_code']) ?>
                        </td>

                        <td>
                            <?= (int)$member['total_invited'] ?>
                        </td>

                    </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="4" class="empty">
                            No members registered yet.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

    <section class="section">

        <h2>Referral Relationships</h2>

        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>
                        <th>Referred Member</th>
                        <th>Email</th>
                        <th>Invited By</th>
                        <th>Referrer's Code</th>
                        <th>Registration Date</th>
                    </tr>

                </thead>

                <tbody>

                <?php if (!empty($relationships)): ?>

                    <?php foreach ($relationships as $relationship): ?>

                    <tr>

                        <td>
                            <?= htmlspecialchars($relationship['member_name']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($relationship['member_email']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($relationship['referrer_name']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($relationship['referral_code']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($relationship['created_at']) ?>
                        </td>

                    </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="5" class="empty">
                            No referral relationships recorded yet.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

</body>
</html>

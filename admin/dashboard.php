<?php

session_start();

require_once __DIR__ . "/../config/database.php";

// Protect administrator dashboard

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Get dashboard statistics

$totalMembers = $pdo->query(
    "SELECT COUNT(*) FROM users"
)->fetchColumn();

$activeMembers = $pdo->query(
    "SELECT COUNT(*) FROM users WHERE account_status = 'active'"
)->fetchColumn();

$pendingMembers = $pdo->query(
    "SELECT COUNT(*) FROM users WHERE account_status = 'pending'"
)->fetchColumn();

$suspendedMembers = $pdo->query(
    "SELECT COUNT(*) FROM users WHERE account_status = 'suspended'"
)->fetchColumn();

$totalReferrals = $pdo->query(
    "SELECT COUNT(*) FROM users WHERE referred_by IS NOT NULL AND referred_by != ''"
)->fetchColumn();

// Recent registrations

$stmt = $pdo->query(
    "SELECT id, full_name, email, referral_code,
    account_status, created_at
    FROM users
    ORDER BY created_at DESC
    LIMIT 10"
);

$recentMembers = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Admin Dashboard | Calz Connect</title>

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
.admin-nav {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.admin-nav a {
    background: #facc15;
    color: #172554;
    padding: 10px 14px;
    text-decoration: none;
    border-radius: 6px;
    font-weight: bold;
}

.admin-nav a:hover {
    background: white;
}
.header {
    background: #172554;
    color: white;
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.header h2 {
    margin: 0;
}

.header a {
    background: #facc15;
    color: #172554;
    padding: 10px 16px;
    text-decoration: none;
    border-radius: 6px;
    font-weight: bold;
}

.container {
    padding: 25px;
    max-width: 1300px;
    margin: auto;
}

.welcome {
    margin-bottom: 25px;
}

.welcome h2 {
    margin-bottom: 5px;
}

.cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
    gap: 20px;
}

.card {
    background: white;
    padding: 22px;
    border-radius: 10px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.05);
}

.card h3 {
    font-size: 14px;
    color: #777;
    margin-top: 0;
}

.card p {
    font-size: 30px;
    font-weight: bold;
    margin-bottom: 0;
    color: #172554;
}

.section {
    margin-top: 35px;
}

.table-wrapper {
    overflow-x: auto;
    background: white;
    border-radius: 10px;
    padding: 15px;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 650px;
}

th, td {
    padding: 13px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

th {
    background: #f8fafc;
}

.status {
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 12px;
    text-transform: capitalize;
}

.active {
    background: #dcfce7;
    color: #166534;
}

.pending {
    background: #fef3c7;
    color: #92400e;
}

.suspended {
    background: #fee2e2;
    color: #991b1b;
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

    .header {
        padding: 16px;
    }

    .card {
        padding: 18px;
    }

}

</style>

</head>

<body>

<header class="header">

    <h2>CALZ CONNECT</h2>

    <nav class="admin-nav">

        <a href="dashboard.php">Dashboard</a>

        <a href="members.php">Manage Members</a>

        <a href="referrals.php">Referral Management</a>

        <a href="packages.php">Manage Packages</a>

        <a href="logout.php">Logout</a>

    </nav>

</header>
<main class="container">

    <div class="welcome">

        <h2>
            Welcome, <?= htmlspecialchars($_SESSION['admin_name']) ?>
        </h2>

        <p>Administrator Control Panel</p>

    </div>

    <div class="cards">

        <div class="card">
            <h3>Total Members</h3>
            <p><?= $totalMembers ?></p>
        </div>

        <div class="card">
            <h3>Active Members</h3>
            <p><?= $activeMembers ?></p>
        </div>

        <div class="card">
            <h3>Pending Members</h3>
            <p><?= $pendingMembers ?></p>
        </div>

        <div class="card">
            <h3>Suspended Members</h3>
            <p><?= $suspendedMembers ?></p>
        </div>

        <div class="card">
            <h3>Total Referrals</h3>
            <p><?= $totalReferrals ?></p>
        </div>

    </div>

    <section class="section">

        <h2>Recent Registrations</h2>

        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Referral Code</th>
                        <th>Status</th>
                        <th>Registered</th>
                    </tr>

                </thead>

                <tbody>

                <?php if (count($recentMembers) > 0): ?>

                    <?php foreach ($recentMembers as $member): ?>

                    <tr>

                        <td><?= (int)$member['id'] ?></td>

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

                            <span class="status <?= htmlspecialchars($member['account_status']) ?>">

                                <?= htmlspecialchars($member['account_status']) ?>

                            </span>

                        </td>

                        <td>
                            <?= htmlspecialchars($member['created_at']) ?>
                        </td>

                    </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="6" class="empty">
                            No members have registered yet.
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

<?php

session_start();

require_once __DIR__ . "/../config/database.php";

// Protect admin page
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION['csrf_token'];

// Search members
$search = trim($_GET['search'] ?? '');

if ($search !== '') {

    $stmt = $pdo->prepare(
        "SELECT u.*, r.full_name AS referrer_name
         FROM users u
         LEFT JOIN users r
         ON u.referred_by = r.referral_code
         WHERE u.full_name LIKE ?
         OR u.email LIKE ?
         OR u.referral_code LIKE ?
         ORDER BY u.created_at DESC
         LIMIT 100"
    );

    $term = "%$search%";
    $stmt->execute([$term, $term, $term]);

} else {

    $stmt = $pdo->query(
        "SELECT u.*, r.full_name AS referrer_name
         FROM users u
         LEFT JOIN users r
         ON u.referred_by = r.referral_code
         ORDER BY u.created_at DESC
         LIMIT 100"
    );
}

$members = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Manage Members | Calz Connect</title>

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
    color: #172554;
    background: #facc15;
    padding: 10px 15px;
    text-decoration: none;
    border-radius: 6px;
    font-weight: bold;
}

.container {
    max-width: 1400px;
    margin: auto;
    padding: 25px;
}

.search-box {
    background: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 25px;
}

.search-box form {
    display: flex;
    gap: 10px;
}

.search-box input {
    flex: 1;
    min-width: 0;
    padding: 13px;
    border: 1px solid #ddd;
    border-radius: 6px;
}

button {
    background: #172554;
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 6px;
    cursor: pointer;
}

.table-wrapper {
    overflow-x: auto;
    background: white;
    padding: 15px;
    border-radius: 10px;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1050px;
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

.actions {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.actions form {
    margin: 0;
}

.actions button {
    width: 100%;
}

.activate {
    background: #15803d;
}

.suspend {
    background: #dc2626;
}

.pending-btn {
    background: #d97706;
}

.notice {
    background: #dcfce7;
    color: #166534;
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 20px;
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

    .search-box form {
        flex-direction: column;
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

    <h2>Member Management</h2>

    <p>Manage registered Calz Connect members.</p>

    <?php if (isset($_GET['updated'])): ?>

        <div class="notice">
            Member account status updated successfully.
        </div>

    <?php endif; ?>

    <div class="search-box">

        <form method="GET">

            <input
                type="search"
                name="search"
                placeholder="Search by name, email or referral code"
                value="<?= htmlspecialchars($search) ?>"
            >

            <button type="submit">Search</button>

        </form>

    </div>

    <div class="table-wrapper">

        <table>

            <thead>

                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Referral Code</th>
                    <th>Referred By</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>

            </thead>

            <tbody>

            <?php if (!empty($members)): ?>

                <?php foreach ($members as $member): ?>

                    <tr>

                        <td><?= (int)$member['id'] ?></td>

                        <td>
                            <?= htmlspecialchars($member['full_name']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($member['email']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($member['phone']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($member['referral_code']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($member['referrer_name'] ?? 'Direct Registration') ?>
                        </td>

                        <td>

                            <span class="status <?= htmlspecialchars($member['account_status']) ?>">

                                <?= htmlspecialchars($member['account_status']) ?>

                            </span>

                        </td>

                        <td>

                            <div class="actions">

                                <?php foreach (['active', 'pending', 'suspended'] as $status): ?>

                                    <?php if ($member['account_status'] !== $status): ?>

                                        <form
                                            method="POST"
                                            action="update-member-status.php"
                                            onsubmit="return confirm('Change this member status to <?= $status ?>?')"
                                        >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= htmlspecialchars($csrf_token) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="member_id"
                                                value="<?= (int)$member['id'] ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="account_status"
                                                value="<?= $status ?>"
                                            >

                                            <button
                                                class="<?= $status === 'active' ? 'activate' : ($status === 'suspended' ? 'suspend' : 'pending-btn') ?>"
                                                type="submit"
                                            >
                                                <?= ucfirst($status) ?>
                                            </button>

                                        </form>

                                    <?php endif; ?>

                                <?php endforeach; ?>

                            </div>

                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>
                    <td colspan="8" class="empty">
                        No members found.
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</main>

</body>
</html>

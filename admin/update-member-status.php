<?php

session_start();

require_once __DIR__ . "/../config/database.php";

// Verify administrator authentication
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Accept POST requests only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit("Method not allowed.");
}

// Verify CSRF token
if (
    !isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    http_response_code(403);
    exit("Invalid security token.");
}

// Get submitted information
$member_id = filter_input(INPUT_POST, 'member_id', FILTER_VALIDATE_INT);

$status = $_POST['account_status'] ?? '';

// Validate member ID
if (!$member_id || $member_id < 1) {
    exit("Invalid member ID.");
}

// Allow only approved statuses
$allowed_statuses = [
    'active',
    'pending',
    'suspended'
];

if (!in_array($status, $allowed_statuses, true)) {
    exit("Invalid account status.");
}

try {

    // Update member account
    $stmt = $pdo->prepare(
        "UPDATE users
         SET account_status = ?
         WHERE id = ?"
    );

    $stmt->execute([$status, $member_id]);

    // Redirect back to members page
    header("Location: members.php?updated=1");
    exit;

} catch (PDOException $e) {

    error_log("Member status update failed: " . $e->getMessage());

    http_response_code(500);

    exit("Unable to update member account.");

}

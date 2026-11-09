<?php

session_start();

require_once __DIR__ . "/../config/database.php";

// Protect administrator page
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirectPackages($message, $type = 'success') {
    $_SESSION['package_message'] = $message;
    $_SESSION['package_message_type'] = $type;

    header("Location: packages.php");
    exit;
}

function uploadPackageImage($file) {

    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException("Image upload failed.");
    }

    if ($file['size'] > 3 * 1024 * 1024) {
        throw new RuntimeException("Image must not exceed 3MB.");
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (!isset($allowed[$mime])) {
        throw new RuntimeException("Only JPG, PNG and WebP images are allowed.");
    }

    if (getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException("Invalid image file.");
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];

    $folder = __DIR__ . '/../assets/images/packages/';

    if (!is_dir($folder)) {
        mkdir($folder, 0755, true);
    }

    if (!move_uploaded_file(
        $file['tmp_name'],
        $folder . $filename
    )) {
        throw new RuntimeException("Could not save uploaded image.");
    }

    return 'assets/images/packages/' . $filename;
}

function removePackageImage($path) {

    if (
        !$path ||
        strpos($path, 'assets/images/packages/') !== 0
    ) {
        return;
    }

    $filename = basename($path);

    if (!preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $filename)) {
        return;
    }

    $file = __DIR__ . '/../assets/images/packages/' . $filename;

    if (is_file($file)) {
        unlink($file);
    }
}

// CSRF protection
if (empty($_SESSION['package_csrf'])) {
    $_SESSION['package_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['package_csrf'], $_POST['csrf_token'])
    ) {
        redirectPackages("Security verification failed.", "error");
    }

    $action = $_POST['action'] ?? '';

    try {

        // ADD PACKAGE
        if ($action === 'add' || $action === 'update') {

            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $price = $_POST['price'] ?? '';
            $benefits = trim($_POST['benefits'] ?? '');
            $status = $_POST['status'] ?? 'active';
            $featured = isset($_POST['is_featured']) ? 1 : 0;
            $displayOrder = (int)($_POST['display_order'] ?? 0);

            if ($name === '' || strlen($name) > 100) {
                throw new RuntimeException("Enter a valid package name.");
            }

            if (
                $price === '' ||
                !is_numeric($price) ||
                (float)$price < 0
            ) {
                throw new RuntimeException("Enter a valid package price.");
            }

            if (!in_array($status, ['active', 'inactive'], true)) {
                throw new RuntimeException("Invalid package status.");
            }

            if ($displayOrder < 0) {
                throw new RuntimeException("Display order cannot be negative.");
            }

            $imagePath = null;

            if ($action === 'add') {

                $imagePath = uploadPackageImage(
                    $_FILES['package_image'] ?? null
                );

                $stmt = $pdo->prepare(
                    "INSERT INTO packages
                    (name, description, image_path, price,
                     currency, benefits, status, is_featured, display_order)
                    VALUES (?, ?, ?, ?, 'UGX', ?, ?, ?, ?)"
                );

                $stmt->execute([
                    $name,
                    $description,
                    $imagePath,
                    $price,
                    $benefits,
                    $status,
                    $featured,
                    $displayOrder
                ]);

                redirectPackages("Package created successfully!");
            }

            // UPDATE PACKAGE

            $id = (int)($_POST['package_id'] ?? 0);

            $stmt = $pdo->prepare(
                "SELECT image_path FROM packages WHERE id = ?"
            );

            $stmt->execute([$id]);
            $existing = $stmt->fetch();

            if (!$existing) {
                throw new RuntimeException("Package not found.");
            }

            $oldImage = $existing['image_path'];

            $imagePath = uploadPackageImage(
                $_FILES['package_image'] ?? null
            );

            if ($imagePath === null) {
                $imagePath = $oldImage;
            }

            $stmt = $pdo->prepare(
                "UPDATE packages SET
                name = ?,
                description = ?,
                image_path = ?,
                price = ?,
                benefits = ?,
                status = ?,
                is_featured = ?,
                display_order = ?
                WHERE id = ?"
            );

            $stmt->execute([
                $name,
                $description,
                $imagePath,
                $price,
                $benefits,
                $status,
                $featured,
                $displayOrder,
                $id
            ]);

            if ($imagePath !== $oldImage) {
                removePackageImage($oldImage);
            }

            redirectPackages("Package updated successfully!");
        }

        // ACTIVATE OR DEACTIVATE

        if ($action === 'toggle') {

            $id = (int)($_POST['package_id'] ?? 0);

            $stmt = $pdo->prepare(
                "UPDATE packages
                 SET status = IF(status = 'active', 'inactive', 'active')
                 WHERE id = ?"
            );

            $stmt->execute([$id]);

            redirectPackages("Package status updated.");
        }

        // DELETE PACKAGE

        if ($action === 'delete') {

            $id = (int)($_POST['package_id'] ?? 0);

            $stmt = $pdo->prepare(
                "SELECT image_path FROM packages WHERE id = ?"
            );

            $stmt->execute([$id]);
            $package = $stmt->fetch();

            if (!$package) {
                throw new RuntimeException("Package not found.");
            }

            $stmt = $pdo->prepare(
                "DELETE FROM packages WHERE id = ?"
            );

            $stmt->execute([$id]);

            removePackageImage($package['image_path']);

            redirectPackages("Package deleted successfully.");
        }

    } catch (Throwable $error) {

        redirectPackages($error->getMessage(), "error");
    }
}

// Edit mode

$editPackage = null;

if (isset($_GET['edit'])) {

    $stmt = $pdo->prepare(
        "SELECT * FROM packages WHERE id = ?"
    );

    $stmt->execute([(int)$_GET['edit']]);

    $editPackage = $stmt->fetch();

}

// Get all packages

$stmt = $pdo->query(
    "SELECT * FROM packages ORDER BY display_order ASC, id DESC"
);

$packages = $stmt->fetchAll();

$totalPackages = count($packages);

$activePackages = 0;

foreach ($packages as $package) {
    if ($package['status'] === 'active') {
        $activePackages++;
    }
}

$message = $_SESSION['package_message'] ?? '';
$messageType = $_SESSION['package_message_type'] ?? 'success';

unset(
    $_SESSION['package_message'],
    $_SESSION['package_message_type']
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Package Management | Calz Connect</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f4f6f9;
    color: #172554;
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

.header h2 { margin: 0; }

.header nav {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.header nav a {
    background: #facc15;
    color: #172554;
    text-decoration: none;
    padding: 10px 14px;
    border-radius: 6px;
    font-weight: bold;
}

.container {
    max-width: 1250px;
    margin: auto;
    padding: 25px;
}

.heading { margin-bottom: 25px; }
.heading h1 { margin-bottom: 5px; }
.heading p { color: #64748b; }

.stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 18px;
    margin-bottom: 30px;
}

.stat, .panel {
    background: white;
    padding: 22px;
    border-radius: 12px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.04);
}

.stat h3 {
    color: #64748b;
    font-size: 14px;
}

.stat strong { font-size: 28px; }

.panel { margin-bottom: 30px; }
.panel h2 { margin-top: 0; }

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-group.full { grid-column: 1 / -1; }

label { font-weight: bold; }

input, textarea, select {
    width: 100%;
    padding: 12px;
    border: 1px solid #cbd5e1;
    border-radius: 7px;
    font-size: 15px;
    font-family: inherit;
}

textarea {
    min-height: 100px;
    resize: vertical;
}

.checkbox-row {
    display: flex;
    align-items: center;
    gap: 10px;
}

.checkbox-row input {
    width: auto;
}

.btn {
    display: inline-block;
    padding: 11px 16px;
    border: none;
    border-radius: 7px;
    cursor: pointer;
    font-weight: bold;
    text-decoration: none;
    text-align: center;
    font-size: 14px;
}

.btn-primary {
    background: #172554;
    color: white;
}

.btn-yellow {
    background: #facc15;
    color: #172554;
}

.btn-danger {
    background: #dc2626;
    color: white;
}

.btn-gray {
    background: #e2e8f0;
    color: #172554;
}

.message {
    padding: 14px;
    border-radius: 7px;
    margin-bottom: 20px;
}

.success {
    background: #dcfce7;
    color: #166534;
}

.error {
    background: #fee2e2;
    color: #991b1b;
}

.package-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 20px;
}

.package-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
}

.package-card img {
    width: 100%;
    height: 170px;
    object-fit: cover;
    background: #e2e8f0;
}

.package-content { padding: 18px; }

.package-content h3 { margin-top: 0; }

.price {
    font-size: 23px;
    font-weight: bold;
    color: #172554;
}

.badge {
    display: inline-block;
    padding: 5px 9px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    margin: 3px;
}

.badge-active {
    background: #dcfce7;
    color: #166534;
}

.badge-inactive {
    background: #fee2e2;
    color: #991b1b;
}

.badge-featured {
    background: #fef3c7;
    color: #92400e;
}

.package-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 15px;
}

.package-actions form { margin: 0; }

.empty {
    text-align: center;
    color: #64748b;
    padding: 35px;
}

@media(max-width: 600px) {
    .container { padding: 15px; }
    .form-grid { grid-template-columns: 1fr; }
    .form-group.full { grid-column: auto; }
    .panel { padding: 17px; }
    .header { padding: 16px; }
}
</style>
</head>

<body>

<header class="header">
    <h2>CALZ CONNECT</h2>

    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="packages.php">Packages</a>
        <a href="../index.php">View Website</a>
    </nav>
</header>

<main class="container">

    <section class="heading">
        <h1>Package Management</h1>
        <p>Create, edit and manage Calz Connect membership packages.</p>
    </section>

    <?php if ($message): ?>
        <div class="message <?= e($messageType) ?>">
            <?= e($message) ?>
        </div>
    <?php endif; ?>

    <section class="stats">

        <div class="stat">
            <h3>Total Packages</h3>
            <strong><?= $totalPackages ?></strong>
        </div>

        <div class="stat">
            <h3>Active Packages</h3>
            <strong><?= $activePackages ?></strong>
        </div>

        <div class="stat">
            <h3>Inactive Packages</h3>
            <strong><?= $totalPackages - $activePackages ?></strong>
        </div>

    </section>

    <section class="panel" id="package-form">

        <h2>
            <?= $editPackage ? 'Edit Package' : 'Add New Package' ?>
        </h2>

        <form method="POST" enctype="multipart/form-data">

            <input type="hidden" name="csrf_token"
                   value="<?= e($_SESSION['package_csrf']) ?>">

            <input type="hidden" name="action"
                   value="<?= $editPackage ? 'update' : 'add' ?>">

            <?php if ($editPackage): ?>
                <input type="hidden" name="package_id"
                       value="<?= (int)$editPackage['id'] ?>">
            <?php endif; ?>

            <div class="form-grid">

                <div class="form-group">
                    <label>Package Name</label>
                    <input type="text" name="name" maxlength="100"
                           required value="<?= e($editPackage['name'] ?? '') ?>"
                           placeholder="e.g. Starter Package">
                </div>

                <div class="form-group">
                    <label>Package Price (UGX)</label>
                    <input type="number" name="price" min="0"
                           step="0.01" required
                           value="<?= e($editPackage['price'] ?? '') ?>"
                           placeholder="e.g. 50000">
                </div>

                <div class="form-group full">
                    <label>Description</label>
                    <textarea name="description"
                              placeholder="Describe this package..."><?= e($editPackage['description'] ?? '') ?></textarea>
                </div>

                <div class="form-group full">
                    <label>Package Benefits</label>
                    <textarea name="benefits"
                              placeholder="List the package benefits..."><?= e($editPackage['benefits'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label>Package Image</label>
                    <input type="file" name="package_image"
                           accept=".jpg,.jpeg,.png,.webp">

                    <small>JPG, PNG or WebP. Maximum 3MB.</small>

                    <?php if (!empty($editPackage['image_path'])): ?>
                        <p>Current image:</p>
                        <img src="../<?= e($editPackage['image_path']) ?>"
                             alt="Current package image"
                             style="width:110px;height:90px;object-fit:cover;">
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Package Status</label>
                    <select name="status">
                        <option value="active"
                            <?= ($editPackage['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>
                            Active
                        </option>

                        <option value="inactive"
                            <?= ($editPackage['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>
                            Inactive
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Display Order</label>
                    <input type="number" name="display_order"
                           min="0"
                           value="<?= e($editPackage['display_order'] ?? '0') ?>">
                </div>

                <div class="form-group">
                    <label>Featured Package</label>

                    <div class="checkbox-row">
                        <input type="checkbox" name="is_featured"
                               id="is_featured" value="1"
                               <?= !empty($editPackage['is_featured']) ? 'checked' : '' ?>>

                        <label for="is_featured">Mark as featured</label>
                    </div>
                </div>

            </div>

            <br>

            <button type="submit" class="btn btn-primary">
                <?= $editPackage ? 'Save Changes' : 'Create Package' ?>
            </button>

            <?php if ($editPackage): ?>
                <a href="packages.php" class="btn btn-gray">
                    Cancel Editing
                </a>
            <?php endif; ?>

        </form>

    </section>

    <section class="panel">

        <h2>Existing Packages</h2>

        <?php if (empty($packages)): ?>

            <div class="empty">
                <h3>No packages created yet.</h3>
                <p>Use the form above to create your first package.</p>
            </div>

        <?php else: ?>

            <div class="package-grid">

                <?php foreach ($packages as $package): ?>

                    <article class="package-card">

                        <?php if (!empty($package['image_path'])): ?>
                            <img src="../<?= e($package['image_path']) ?>"
                                 alt="<?= e($package['name']) ?>">
                        <?php else: ?>
                            <div style="height:170px;background:#e2e8f0;
                                        display:grid;place-items:center;">
                                No Package Image
                            </div>
                        <?php endif; ?>

                        <div class="package-content">

                            <h3><?= e($package['name']) ?></h3>

                            <div class="price">
                                UGX <?= number_format((float)$package['price']) ?>
                            </div>

                            <p><?= nl2br(e($package['description'])) ?></p>

                            <?php if (!empty($package['benefits'])): ?>
                                <p>
                                    <strong>Benefits:</strong><br>
                                    <?= nl2br(e($package['benefits'])) ?>
                                </p>
                            <?php endif; ?>

                            <span class="badge <?= $package['status'] === 'active' ? 'badge-active' : 'badge-inactive' ?>">
                                <?= e(ucfirst($package['status'])) ?>
                            </span>

                            <?php if (!empty($package['is_featured'])): ?>
                                <span class="badge badge-featured">Featured</span>
                            <?php endif; ?>

                            <div class="package-actions">

                                <a class="btn btn-yellow"
                                   href="packages.php?edit=<?= (int)$package['id'] ?>#package-form">
                                    Edit
                                </a>

                                <form method="POST">
                                    <input type="hidden" name="csrf_token"
                                           value="<?= e($_SESSION['package_csrf']) ?>">

                                    <input type="hidden" name="action" value="toggle">

                                    <input type="hidden" name="package_id"
                                           value="<?= (int)$package['id'] ?>">

                                    <button type="submit" class="btn btn-gray">
                                        <?= $package['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                                    </button>
                                </form>

                                <form method="POST"
                                      onsubmit="return confirm('Are you sure you want to delete this package?');">

                                    <input type="hidden" name="csrf_token"
                                           value="<?= e($_SESSION['package_csrf']) ?>">

                                    <input type="hidden" name="action" value="delete">

                                    <input type="hidden" name="package_id"
                                           value="<?= (int)$package['id'] ?>">

                                    <button type="submit" class="btn btn-danger">
                                        Delete
                                    </button>

                                </form>

                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </section>

</main>

</body>
</html>

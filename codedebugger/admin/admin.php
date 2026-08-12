<?php
session_start();
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit;
}
if (isset($_GET['logout']) && $_GET['logout'] === '1') {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_weekly') {
        $week = trim($_POST['week'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $date_badge = trim($_POST['date_badge'] ?? '');
        $tag = trim($_POST['tag'] ?? 'new');
        $items_text = trim($_POST['items'] ?? '');

        $items = [];
        if ($items_text !== '') {
            $lines = preg_split('/\r\n|\r|\n/', $items_text);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') continue;
                $items[] = ['tag' => $tag, 'text' => $line];
            }
        }
        if (empty($items)) {
            $items[] = ['tag' => $tag, 'text' => $title];
        }

        $stmt = $pdo->prepare("INSERT INTO weekly_updates (week, title, date_badge, tag, items) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$week, $title, $date_badge, $tag, json_encode($items)]);
        header('Location: admin.php?msg=added&panel=weekly');
        exit;
    }

    if ($action === 'add_system') {
        $module = trim($_POST['module'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status = trim($_POST['status'] ?? 'Live');
        $updated_at = 'Updated just now';

        $image = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image = uploadImage($_FILES['image']);
        }

        $stmt = $pdo->prepare("INSERT INTO system_updates (module, description, status, image, updated_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$module, $description, $status, $image, $updated_at]);
        header('Location: admin.php?msg=added&panel=system');
        exit;
    }

    if ($action === 'edit_weekly') {
        $id = (int) ($_POST['id'] ?? 0);
        $week = trim($_POST['week'] ?? '');
        $title = trim($_POST['title'] ?? '');
$date_badge = trim($_POST['date_badge'] ?? '');
        $items_text = trim($_POST['items'] ?? '');

        $tagRow = $pdo->prepare("SELECT tag FROM weekly_updates WHERE id=?");
        $tagRow->execute([$id]);
        $existing = $tagRow->fetchColumn();
        $tag = trim($_POST['tag'] ?? '') !== '' ? trim($_POST['tag']) : ($existing ?: 'new');

        $items = [];
        if ($items_text !== '') {
            $lines = preg_split('/\r\n|\r|\n/', $items_text);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') continue;
                $items[] = ['tag' => $tag, 'text' => $line];
            }
        }

        $stmt = $pdo->prepare("UPDATE weekly_updates SET week=?, title=?, date_badge=?, tag=?, items=? WHERE id=?");
        $stmt->execute([$week, $title, $date_badge, $tag, json_encode($items), $id]);
        header('Location: admin.php?msg=updated&panel=weekly');
        exit;
    }

    if ($action === 'edit_system') {
        $id = (int) ($_POST['id'] ?? 0);
        $module = trim($_POST['module'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status = trim($_POST['status'] ?? 'Live');
        $updated_at = 'Updated just now';

        $image = $_POST['existing_image'] ?? null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image = uploadImage($_FILES['image']);
        }

        $stmt = $pdo->prepare("UPDATE system_updates SET module=?, description=?, status=?, image=?, updated_at=? WHERE id=?");
        $stmt->execute([$module, $description, $status, $image, $updated_at, $id]);
        header('Location: admin.php?msg=updated&panel=system');
        exit;
    }

    if ($action === 'delete') {
        $type = $_POST['type'] ?? '';
        $id = (int) ($_POST['id'] ?? 0);
        if ($type === 'weekly') {
            $pdo->prepare("DELETE FROM weekly_updates WHERE id=?")->execute([$id]);
        } elseif ($type === 'system') {
            $pdo->prepare("DELETE FROM system_updates WHERE id=?")->execute([$id]);
        }
        $panel = ($type === 'weekly') ? 'weekly' : 'system';
        header('Location: admin.php?msg=deleted&panel=' . $panel);
        exit;
    }
}

function uploadImage($file) {
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed)) {
        return 'https://picsum.photos/seed/placeholder/600/340';
    }
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $safeExt = strtolower(preg_replace('/[^a-z0-9]/i', '', $ext));
    if ($safeExt === '') $safeExt = 'jpg';
    $filename = 'system_' . time() . '_' . rand(1000, 9999) . '.' . $safeExt;
    $uploadDir = __DIR__ . '/../image/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        return 'image/' . $filename;
    }
    return null;
}

$weeklyRows = $pdo->query("SELECT * FROM weekly_updates ORDER BY id ASC")->fetchAll();
$systemRows = $pdo->query("SELECT * FROM system_updates ORDER BY id ASC")->fetchAll();

$userCount   = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$gamesPlayed = (int) $pdo->query("SELECT COUNT(*) FROM game_scores")->fetchColumn();
$topScore    = (int) $pdo->query("SELECT COALESCE(MAX(score), 0) FROM game_scores")->fetchColumn();

$adminName = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
$adminName = $adminName !== '' ? $adminName : 'Administrator';

$userRows = [];
try {
    $userRows = $pdo->query(
        "SELECT id, first_name, last_name, email, score, status, role, last_seen
         FROM users
         ORDER BY last_seen DESC, id ASC"
    )->fetchAll();
} catch (PDOException $e) {
 
    try {
        $userRows = $pdo->query(
            "SELECT id, first_name, last_name, email, score, status, role, NULL AS last_seen
             FROM users
             ORDER BY id ASC"
        )->fetchAll();
    } catch (PDOException $e2) {
        $userRows = [];
    }
}

$onlineThreshold = 5 * 60;
function isOnline($lastSeen) {
    global $onlineThreshold;
    if (!$lastSeen) return false;
    $ts = strtotime((string) $lastSeen);
    if (!$ts) return false;
    return (time() - $ts) <= $onlineThreshold;
}

$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>⚙️</text></svg>">
    <title>Code Quest · Admin Hub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin.css">k
</head>
<body>

    <aside class="sidebar">
<div class="brand">
            <img class="logo-icon" src="../code quest logo.png" alt="Logo">
            <div>
                <span class="brand-text">Admin Hub</span>
                <span class="brand-sub">Code Quest v2.4</span>
            </div>
        </div>
        <button class="nav-item active" data-panel="dashboard">
            <span class="icon">📊</span><span class="label">Dashboard</span>
        </button>
        <button class="nav-item" data-panel="weekly">
            <span class="icon">📅</span><span class="label">Weekly Updates</span>
            <span class="badge" id="weeklyCount"><?php echo count($weeklyRows); ?></span>
        </button>
        <button class="nav-item" data-panel="system">
            <span class="icon">🖥️</span><span class="label">System Updates</span>
            <span class="badge" id="systemCount"><?php echo count($systemRows); ?></span>
        </button>
        <div class="footer">
            <a href="admin.php?logout=1"><span>🚪</span> <span>Logout</span></a>
        </div>
    </aside>

    <main class="main">
        <div class="main-header">
            <div>
                <h1>⚡ Admin Dashboard</h1>
                <p style="color:#8899b0;font-size:0.9rem;margin-top:0.25rem;">Welcome back, <strong style="color:#f5c87a;"><?php echo htmlspecialchars($adminName); ?></strong> 👋</p>
            </div>
            <span class="time" id="liveTime">Loading...</span>
        </div>

        <?php if ($msg === 'added'): ?>
            <div class="toast" style="border-left-color:#2a6b3a;margin-bottom:1.5rem;">✅ Item added successfully.</div>
        <?php elseif ($msg === 'updated'): ?>
            <div class="toast" style="border-left-color:#2a4a6b;margin-bottom:1.5rem;">ℹ️ Item updated successfully.</div>
        <?php elseif ($msg === 'deleted'): ?>
            <div class="toast" style="border-left-color:#6b2a2a;margin-bottom:1.5rem;">🗑️ Item deleted.</div>
        <?php endif; ?>

        <section class="panel active" id="panel-dashboard">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📅</div>
                    <div class="stat-info">
                        <div class="num" id="statWeekly"><?php echo count($weeklyRows); ?></div>
                        <div class="label">Weekly Updates</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🖥️</div>
                    <div class="stat-info">
                        <div class="num" id="statSystem"><?php echo count($systemRows); ?></div>
                        <div class="label">System Updates</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <div class="num" id="statUsers"><?php echo number_format($userCount); ?></div>
                        <div class="label">Registered Players</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🎮</div>
                    <div class="stat-info">
                        <div class="num" id="statGames"><?php echo number_format($gamesPlayed); ?></div>
                        <div class="label">Games Played</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🏆</div>
                    <div class="stat-info">
                        <div class="num" id="statTop"><?php echo number_format($topScore); ?></div>
                        <div class="label">Top Score</div>
                    </div>
                </div>
            </div>

<div style="background:#131a24;border:1px solid #2a3645;border-radius:16px;padding:1.5rem;">
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.75rem;margin-bottom:1rem;">
                    <h3 style="color:#f5c87a;">👥 User Interface</h3>
                    <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                        <span class="status-pill online"><span class="dot"></span> Online: <?php echo count(array_filter($userRows, function($u){ return isOnline($u['last_seen'] ?? null); })); ?></span>
                        <span class="status-pill offline"><span class="dot"></span> Offline: <?php echo count(array_filter($userRows, function($u){ return !isOnline($u['last_seen'] ?? null); })); ?></span>
                    </div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Points</th>
                                <th>Last Seen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($userRows)): ?>
                                <tr><td colspan="5"><div class="empty-state"><div class="empty-icon">👤</div><p>No users registered yet.</p></div></td></tr>
                            <?php else: ?>
                                <?php foreach ($userRows as $u):
                                    $online = isOnline($u['last_seen'] ?? null);
                                    $fullName = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
                                    $fullName = $fullName !== '' ? $fullName : ($u['email'] ?? 'Player');
                                ?>
                                <tr>
                                    <td>
                                        <span class="online-indicator <?php echo $online ? 'online' : 'offline'; ?>">
                                            <span class="dot"></span> <?php echo $online ? 'Online' : 'Offline'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:0.6rem;">
                                            <span class="user-avatar"><?php echo htmlspecialchars(strtoupper(substr($first = $u['first_name'] ?? '?', 0, 1))); ?></span>
                                            <div>
                                                <strong><?php echo htmlspecialchars($u['first_name'] ?? ''); ?> <?php echo htmlspecialchars($u['last_name'] ?? ''); ?></strong>
                                                <div style="font-size:0.7rem;color:#8899b0;"><?php echo htmlspecialchars(ucfirst($u['role'] ?? 'user')); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($u['email'] ?? ''); ?></td>
                                    <td><span class="tag" style="background:#1f2a36;color:#f5c87a;">⭐ <?php echo number_format((int)($u['score'] ?? 0)); ?></span></td>
                                    <td style="color:#8899b0;font-size:0.8rem;">
                                        <?php echo $u['last_seen'] ? htmlspecialchars(date('M j, Y g:i A', strtotime($u['last_seen']))) : 'Never'; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="panel" id="panel-weekly">
            <div class="panel-header">
                <h2>📅 Weekly Updates</h2>
                <div class="actions">
                    <button class="btn btn-primary btn-sm" onclick="openAddModal('weekly')">➕ Add New</button>
                </div>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Week</th>
                            <th>Title</th>
                            <th>Tag</th>
                            <th>Items</th>
                            <th style="width:150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($weeklyRows)): ?>
                            <tr><td colspan="5"><div class="empty-state"><div class="empty-icon">📭</div><p>No weekly updates found.</p></div></td></tr>
                        <?php else: ?>
                            <?php foreach ($weeklyRows as $w): ?>
                            <?php $decoded = json_decode($w['items'], true); $itemCount = is_array($decoded) ? count($decoded) : 0; ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($w['week']); ?></strong></td>
                                <td><?php echo htmlspecialchars($w['title']); ?></td>
                                <td><span class="tag <?php echo htmlspecialchars($w['tag']); ?>"><?php echo strtoupper(htmlspecialchars($w['tag'])); ?></span></td>
                                <td><span class="tag"><?php echo $itemCount; ?> items</span></td>
                                <td>
                                    <div class="actions-cell">
<button class="btn btn-success btn-sm" onclick='editWeekly(<?php echo json_encode($w); ?>)'>✏️ Edit</button>
                                        <button class="btn btn-danger btn-sm" onclick="deleteItem('weekly', <?php echo (int)$w['id']; ?>)">🗑️ Delete</button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel" id="panel-system">
            <div class="panel-header">
                <h2>🖥️ System Updates</h2>
                <div class="actions">
                    <button class="btn btn-primary btn-sm" onclick="openAddModal('system')">➕ Add New</button>
                </div>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Module</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th style="width:150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($systemRows)): ?>
                            <tr><td colspan="5"><div class="empty-state"><div class="empty-icon">📭</div><p>No system updates found.</p></div></td></tr>
                        <?php else: ?>
                            <?php foreach ($systemRows as $s): ?>
                            <tr>
                                <td><img class="thumb" src="<?php echo htmlspecialchars($s['image'] ?: 'https://picsum.photos/seed/placeholder/600/340'); ?>" alt=""></td>
                                <td><strong><?php echo htmlspecialchars($s['module']); ?></strong></td>
                                <td><?php echo strip_tags($s['description']); ?></td>
                                <td><span class="tag <?php echo strtolower($s['status']) === 'live' ? 'live' : 'fix'; ?>"><?php echo htmlspecialchars($s['status']); ?></span></td>
                                <td>
                                    <div class="actions-cell">
                                        <button class="btn btn-success btn-sm" onclick='editSystem(<?php echo json_encode($s); ?>)'>✏️ Edit</button>
                                        <button class="btn btn-danger btn-sm" onclick="deleteItem('system', <?php echo (int)$s['id']; ?>)">🗑️ Delete</button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- Add/Edit Modal -->
    <div class="modal-overlay" id="modalOverlay">
        <div class="modal">
            <h3 id="modalTitle">Add New</h3>
            <p class="modal-sub" id="modalSub">Fill in the details below.</p>
            <form id="modalForm" method="POST" enctype="multipart/form-data" onsubmit="return submitModal(event)">
                <input type="hidden" name="action" id="modalAction">
                <input type="hidden" name="id" id="modalId">

                <div class="form-group" id="groupWeek">
                    <label for="fieldWeek">Week</label>
                    <input type="text" id="fieldWeek" name="week" placeholder="Week 1">
                </div>

                <div class="form-group" id="groupTitle">
                    <label for="fieldTitle">Title</label>
                    <input type="text" id="fieldTitle" name="title" placeholder="Enter title...">
                </div>

                <div class="form-group" id="groupDate">
                    <label for="fieldDate">Date Badge (weekly only)</label>
                    <input type="text" id="fieldDate" name="date_badge" placeholder="Aug 1 – 7">
                </div>

                <div class="form-group" id="groupModule">
                    <label for="fieldModule">Module (system only)</label>
                    <input type="text" id="fieldModule" name="module" placeholder="Core Engine">
                </div>

                <div class="form-group">
                    <label for="fieldItems">Description / Items</label>
                    <textarea id="fieldItems" name="description" placeholder="Enter description or items..."></textarea>
                    <small id="fieldHint"></small>
                </div>

                <div class="form-group" id="groupStatus">
                    <label for="fieldStatus">Status (system only)</label>
                    <select id="fieldStatus" name="status">
                        <option value="Live">Live</option>
                        <option value="Pending">Pending</option>
                        <option value="Rollback">Rollback</option>
                    </select>
                </div>

                <div class="form-group" id="groupImage">
                    <label for="fieldImage">Upload Image (system only)</label>
                    <input type="file" id="fieldImage" name="image" accept="image/*">
                    <small>Accepts JPG, PNG, GIF, WEBP. Leave empty to keep current image.</small>
                    <input type="hidden" name="existing_image" id="existingImage">
                    <div id="imagePreview" style="margin-top:0.5rem;"></div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="modalSaveBtn">Save</button>
                </div>
            </form>
        </div>
    </div>

<div class="toast-container" id="toastContainer"></div>

    <script src="../js/admin.js"></script>
</body>
</html>


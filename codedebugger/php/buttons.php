<?php
session_start();
require_once __DIR__ . '/../db.php';

// ── Require login (any authenticated user) ──
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// ── Logout (destroy session) ──
if (isset($_GET['logout']) && $_GET['logout'] === '1') {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}

// ── Load leaderboard data (top players by best score) ──
$leaderboard = [];
try {
    $lb = $pdo->query(
        "SELECT u.first_name, u.last_name, u.email, u.score,
                COALESCE((SELECT COUNT(*) FROM game_scores gs WHERE gs.user_id = u.id), 0) AS games
         FROM users u
         ORDER BY u.score DESC, u.id ASC
         LIMIT 8"
    );
    $leaderboard = $lb ? $lb->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (PDOException $e) {
    // If game_scores table isn't installed yet, fall back to users data only.
    try {
        $lb = $pdo->query("SELECT first_name, last_name, email, score, 0 AS games FROM users ORDER BY score DESC, id ASC LIMIT 8");
        $leaderboard = $lb ? $lb->fetchAll(PDO::FETCH_ASSOC) : [];
    } catch (PDOException $e2) {
        $leaderboard = [];
    }
}

$topByRank = [1 => null, 2 => null, 3 => null];
if (count($leaderboard) > 0) $topByRank[1] = $leaderboard[0];
if (count($leaderboard) > 1) $topByRank[2] = $leaderboard[1];
if (count($leaderboard) > 2) $topByRank[3] = $leaderboard[2];

function lbName($row) {
    if (!$row) return '—';
    $n = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
    return $n !== '' ? $n : ($row['email'] ?? 'Player');
}

function lbScore($row) {
    return $row ? number_format((int)($row['score'] ?? 0)) : '0';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎮</text></svg>" />
    <title>Game Hub · Play</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,600;14..32,700;14..32,800;14..32,900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../css/buttons.css">
</head>
<body>
    <aside class="sidebar">
<div class="brand">
            <img class="logo-icon" src="../code quest logo.png" alt="CODE QUEST">
            <div>
                <span class="brand-text">CODE QUEST</span>
                <span class="brand-sub">v2.4 · arcade</span>
            </div>
        </div>
        <button class="nav-item active" data-panel="play">
            <span class="icon">▶️</span>
            <span class="label" onclick="window.location.href='games.php'">Play</span>
        </button>
        <button class="nav-item" data-panel="settings">
            <span class="icon">⚙️</span>
            <span class="label">Settings</span>
        </button>
        <button class="nav-item" data-panel="leaderboard">
            <span class="icon">🏆</span>
            <span class="label">Leaderboard</span>
        </button>
<div class="footer">
<a href="index.php?logout=1">
                <span>🚪</span> <span>Logout</span>
            </a>
        </div>
    </aside>
    <main class="main">
        <div class="main-header">
<h1>CODE QUEST</h1>
            <div style="display:flex;align-items:center;gap:0.75rem;">
                <span class="time" style="color:#f5c87a;">👤 <?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></span>
                <span class="time" id="liveTime">Loading…</span>
            </div>
        </div>
        <section class="panel active" id="panel-play">
            <div class="play-hero">
                <div class="game-logo">
                    <span class="logo-icon-big"> <img  src="../code quest logo.png" alt="CODE QUEST"></span>
                    <div class="main-title">
                        <span class="highlight">CODE</span>
                        <span class="light">QUEST</span>
                    </div>
                    <span class="sub">▶  play · compete · conquer  ◀</span>
                </div>
                <p class="hero-desc">
                    Jump into the action — choose your mode and start competing.
                </p>
<button class="btn-play" onclick="window.location.href='games.php'">
                    ▶️&nbsp; Play Now
                </button>
            </div>
        </section>
        <section class="panel" id="panel-settings">
            <div style="margin-bottom:1.5rem;">
                <h2 style="font-size:1.5rem;font-weight:700;color:#f5c87a;">⚙️ Settings</h2>
                <p style="color:#8899b0;font-size:0.9rem;">Customize your game experience.</p>
            </div>
            <div class="settings-group">
                <h3>🎮 Gameplay</h3>
                <div class="setting-row">
                    <div class="setting-label">
                        Sound Effects
                        <small>Enable / disable in-game audio</small>
                    </div>
                    <button class="toggle active" onclick="toggleSetting(this)" aria-label="Toggle sound"></button>
                </div>
                <div class="setting-row">
                    <div class="setting-label">
                        Music
                        <small>Background soundtrack</small>
                    </div>
                    <button class="toggle active" onclick="toggleSetting(this)" aria-label="Toggle music"></button>
                </div>
                <div class="setting-row">
                    <div class="setting-label">
                        Vibration
                        <small>Haptic feedback on controllers</small>
                    </div>
                    <button class="toggle" onclick="toggleSetting(this)" aria-label="Toggle vibration"></button>
                </div>
            </div>
            <div class="settings-group">
                <h3>🖥️ Display</h3>
                <div class="setting-row">
                    <div class="setting-label">
                        Fullscreen
                        <small>Launch in fullscreen mode</small>
                    </div>
                    <button class="toggle" onclick="toggleSetting(this)" aria-label="Toggle fullscreen"></button>
                </div>
                <div class="setting-row">
                    <div class="setting-label">
                        Frame Rate
                        <small>Target FPS</small>
                    </div>
                    <select class="setting-select" onchange="showToast('info', 'Frame rate set to ' + this.value)">
                        <option value="30">30 FPS</option>
                        <option value="60" selected>60 FPS</option>
                        <option value="120">120 FPS</option>
                        <option value="144">144 FPS</option>
                    </select>
                </div>
                <div class="setting-row">
                    <div class="setting-label">
                        Resolution
                        <small>Render scale</small>
                    </div>
                    <select class="setting-select" onchange="showToast('info', 'Resolution set to ' + this.value)">
                        <option value="720p">720p</option>
                        <option value="1080p" selected>1080p</option>
                        <option value="1440p">1440p</option>
                        <option value="4k">4K</option>
                    </select>
                </div>
            </div>
            <div class="settings-group">
                <h3>🔒 Privacy</h3>
                <div class="setting-row">
                    <div class="setting-label">
                        Show Online Status
                        <small>Visible to friends</small>
                    </div>
                    <button class="toggle active" onclick="toggleSetting(this)" aria-label="Toggle online status"></button>
                </div>
                <div class="setting-row">
                    <div class="setting-label">
                        Share Game Stats
                        <small>Allow leaderboard visibility</small>
                    </div>
                    <button class="toggle active" onclick="toggleSetting(this)" aria-label="Toggle stats sharing"></button>
                </div>
            </div>
            <div style="display:flex;gap:0.75rem;flex-wrap:wrap;margin-top:0.5rem;">
                <button class="btn btn-primary" onclick="showToast('success', 'Settings saved!')">💾 Save Settings</button>
                <button class="btn btn-outline" onclick="showToast('info', 'Settings reset to default.')">↩️ Reset Defaults</button>
            </div>
        </section>
        <section class="panel" id="panel-leaderboard">
            <div style="margin-bottom:1.5rem;">
                <h2 style="font-size:1.5rem;font-weight:700;color:#f5c87a;">🏆 Leaderboard</h2>
                <p style="color:#8899b0;font-size:0.9rem;">Top players from around the world.</p>
            </div>
<div class="lb-podium">
                <div class="podium-item silver">
                    <div class="rank">🥈</div>
                    <div class="p-name"><?php echo htmlspecialchars(lbName($topByRank[2])); ?></div>
                    <div class="p-score"><?php echo lbScore($topByRank[2]); ?> pts</div>
                </div>
                <div class="podium-item gold">
                    <div class="rank">🥇</div>
                    <div class="p-name"><?php echo htmlspecialchars(lbName($topByRank[1])); ?></div>
                    <div class="p-score"><?php echo lbScore($topByRank[1]); ?> pts</div>
                </div>
                <div class="podium-item bronze">
                    <div class="rank">🥉</div>
                    <div class="p-name"><?php echo htmlspecialchars(lbName($topByRank[3])); ?></div>
                    <div class="p-score"><?php echo lbScore($topByRank[3]); ?> pts</div>
                </div>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th style="width:50px;">#</th>
                            <th>Player</th>
                            <th>Score</th>
                            <th>Games</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($leaderboard)): ?>
                            <tr>
                                <td colspan="4" style="text-align:center;color:#8899b0;padding:2rem;">
                                    No scores recorded yet — be the first!
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $rankIdx = 0; foreach ($leaderboard as $row): $rankIdx++; ?>
                            <tr>
                                <td>
                                    <span class="rank-badge <?php echo $rankIdx === 1 ? 'top1' : ($rankIdx === 2 ? 'top2' : ($rankIdx === 3 ? 'top3' : '')); ?>"><?php echo $rankIdx; ?></span>
                                </td>
                                <td><strong><?php echo htmlspecialchars(lbName($row)); ?></strong></td>
                                <td><?php echo number_format((int)($row['score'] ?? 0)); ?></td>
                                <td><?php echo (int)($row['games'] ?? 0); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div style="margin-top:1rem;display:flex;gap:0.75rem;flex-wrap:wrap;">
                <button class="btn btn-primary btn-sm" onclick="showToast('success', 'Leaderboard refreshed!')">🔄 Refresh</button>
                <button class="btn btn-outline btn-sm" onclick="showToast('info', 'Showing global leaderboard.')">🌍 Global</button>
                <button class="btn btn-outline btn-sm" onclick="showToast('info', 'Showing friends leaderboard.')">👥 Friends</button>
            </div>
        </section>
    </main>
    <div class="modal-overlay" id="modalOverlay">
        <div class="modal">
            <h3 id="modalTitle">Modal</h3>
            <p class="modal-sub" id="modalSub">Placeholder</p>
            <div class="form-group">
                <label for="modalField1">Label</label>
                <input type="text" id="modalField1" placeholder="Enter…" />
            </div>
            <div class="form-actions">
                <button class="btn btn-outline" onclick="closeModal()">Cancel</button>
                <button class="btn btn-primary" id="modalSaveBtn" onclick="closeModal();showToast('success','Saved!')">Save</button>
            </div>
        </div>
    </div>
    <div class="alert-overlay" id="alertOverlay">
        <div class="alert-box">
            <div class="alert-icon">⚠️</div>
            <h3 id="alertTitle">Confirm</h3>
            <p id="alertMessage">Are you sure?</p>
            <div class="alert-actions">
                <button class="btn btn-outline" onclick="closeAlert()">Cancel</button>
                <button class="btn btn-danger" id="alertConfirmBtn" onclick="closeAlert();showToast('info','Done.')">Confirm</button>
            </div>
        </div>
    </div>
    <div class="toast-container" id="toastContainer"></div>
    <script src="../js/buttons.js"> </script>
</body>
</html>
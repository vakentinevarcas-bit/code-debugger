<?php
require_once __DIR__ . '/db.php';

$weeklyRows = [];
$systemRows = [];
try {
    $weeklyRows = $pdo->query("SELECT * FROM weekly_updates ORDER BY id ASC")->fetchAll();
} catch (PDOException $e) {
}
try {
    $systemRows = $pdo->query("SELECT * FROM system_updates ORDER BY id ASC")->fetchAll();
} catch (PDOException $e) {
}
$hasWeekly = !empty($weeklyRows);
$hasSystem = !empty($systemRows);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="code quest logo.png">
    <link rel="stylesheet" href="css/website.css">
    <title>Code Quest | Debugging Survival</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet" />

    <style>
        body {
    font-family: 'Segoe UI', Trebuchet MS, sans-serif;
    background: url('ba.png');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    background-attachment: fixed;
    color: var(--dark-text);
    line-height: 1.8;
    overflow-x: hidden;
    }

        nav {
            display: flex;
            align-items: center;
        }

        .nav-logo {
            margin-right: auto;
        }

        .nav-links {
            display: flex;
            gap: 10px;
        }

        .week-tabs {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            background: #1a2c3f;
            padding: 0.5rem;
            border-radius: 14px;
            border: 1px solid #2a4a6a;
            margin-bottom: 1.5rem;
        }

        .week-tab {
            padding: 0.6rem 1.4rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            background: transparent;
            color: #8899b0;
            border: none;
            cursor: pointer;
            transition: all 0.25s ease;
            font-family: 'Inter', sans-serif;
            letter-spacing: 0.01em;
        }

        .week-tab:hover {
            color: #e8edf5;
            background: #2a4a6a;
        }

        .week-tab.active {
            background: #f5a623;
            color: #0b0e14;
            box-shadow: 0 4px 16px rgba(245, 166, 35, 0.25);
        }

        .weekly-content {
            max-height: 320px;
            overflow-y: auto;
            padding-right: 0.5rem;
            scroll-behavior: smooth;
        }

        .weekly-content::-webkit-scrollbar {
            width: 6px;
        }
        .weekly-content::-webkit-scrollbar-track {
            background: #0b0e14;
            border-radius: 8px;
        }
        .weekly-content::-webkit-scrollbar-thumb {
            background: #f5a62388;
            border-radius: 8px;
        }
        .weekly-content::-webkit-scrollbar-thumb:hover {
            background: #f5a623;
        }

        .week-panel {
            display: none;
            animation: fadeSlide 0.35s ease;
        }
        .week-panel.active {
            display: block;
        }

        @keyframes fadeSlide {
            0% {
                opacity: 0;
                transform: translateY(8px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .week-panel h3 {
            font-size: 1.3rem;
            font-weight: 700;
            color: #f5c87a;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .week-panel .date-badge {
            font-size: 0.75rem;
            font-weight: 600;
            background: #1f2a36;
            color: #8899b0;
            padding: 0.2rem 0.9rem;
            border-radius: 40px;
            letter-spacing: 0.02em;
        }

        .week-panel ul {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
        }

        .week-panel ul li {
            padding: 0.6rem 1rem 0.6rem 1.8rem;
            background: #0b0e1480;
            border-left: 3px solid #f5a62366;
            border-radius: 8px;
            font-size: 0.95rem;
            line-height: 1.5;
            position: relative;
            transition: background 0.2s;
            color: #e0e1dd;
        }

        .week-panel ul li::before {
            content: "▹";
            position: absolute;
            left: 0.5rem;
            color: #f5a623;
            font-size: 0.9rem;
        }

        .week-panel ul li strong {
            color: #f5c87a;
            font-weight: 600;
        }

        .week-panel ul li .tag {
            display: inline-block;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 0.15rem 0.7rem;
            border-radius: 40px;
            margin-right: 0.5rem;
            background: #1f2a36;
            color: #8899b0;
        }

        .week-panel ul li .tag.new {
            background: #2a6b3a;
            color: #8bdd9b;
        }
        .week-panel ul li .tag.fix {
            background: #6b2a2a;
            color: #f58b8b;
        }
        .week-panel ul li .tag.update {
            background: #2a4a6b;
            color: #8bc4f5;
        }

        .system-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-top: 0.5rem;
        }

        .system-card {
            background: #0b0e14;
            border-radius: 18px;
            border: 1px solid #1f2a36;
            overflow: hidden;
            transition: transform 0.2s ease, border-color 0.2s ease;
            display: flex;
            flex-direction: column;
        }

        .system-card:hover {
            transform: translateY(-4px);
            border-color: #f5a62366;
        }

        .system-card .card-img {
            width: 100%;
            height: 160px;
            object-fit: cover;
            display: block;
            background: #1a2532;
        }

        .system-card .card-body {
            padding: 1.25rem 1.25rem 1rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .system-card .card-body h4 {
            font-size: 1.1rem;
            font-weight: 700;
            color: #f5c87a;
            margin-bottom: 0.4rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .system-card .card-body .card-meta {
            font-size: 0.75rem;
            color: #8899b0;
            margin-bottom: 0.6rem;
            letter-spacing: 0.02em;
        }

        .card-scroll {
            max-height: 130px;
            overflow-y: auto;
            padding-right: 0.5rem;
            font-size: 0.9rem;
            line-height: 1.6;
            color: #c8d0dc;
            scroll-behavior: smooth;
            mask-image: linear-gradient(to bottom, #000 70%, transparent 100%);
            -webkit-mask-image: linear-gradient(to bottom, #000 70%, transparent 100%);
            scrollbar-width: auto;
            scrollbar-color: #f5a62388 #1a2532;
        }

        .card-scroll::-webkit-scrollbar {
            width: 8px;
        }

        .card-scroll::-webkit-scrollbar-track {
            background: #1a2532;
            border-radius: 8px;
            margin: 4px 0;
            border: 1px solid #2a3645;
        }

        .card-scroll::-webkit-scrollbar-thumb {
            background: #f5a62388;
            border-radius: 8px;
            border: 1px solid #3a4a5a;
            transition: background 0.2s, border-color 0.2s;
            min-height: 40px;
        }

        .card-scroll::-webkit-scrollbar-thumb:hover {
            background: #f5a623;
            border-color: #f5a623;
        }

        .card-scroll .scroll-hint {
            display: none;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            margin-top: 0.3rem;
            font-size: 0.65rem;
            color: #f5a62388;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            animation: pulseHint 2s ease-in-out infinite;
            cursor: pointer;
        }

        .card-scroll .scroll-hint.visible {
            display: flex;
        }

        .card-scroll .scroll-hint .dot {
            display: inline-block;
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: #f5a623;
            animation: pulseDot 1.6s ease-in-out infinite;
        }

        @keyframes pulseHint {
            0%,
            100% {
                opacity: 0.5;
            }
            50% {
                opacity: 1;
            }
        }

        @keyframes pulseDot {
            0%,
            100% {
                transform: scale(0.8);
                opacity: 0.5;
            }
            50% {
                transform: scale(1.2);
                opacity: 1;
            }
        }

        .card-scroll p {
            margin-bottom: 0.6rem;
        }
        .card-scroll p:last-child {
            margin-bottom: 0;
        }

        .card-scroll ul {
            list-style: none;
            padding-left: 0.2rem;
            margin-bottom: 0.2rem;
        }

        .card-scroll ul li {
            padding: 0.2rem 0 0.2rem 1.3rem;
            position: relative;
            font-size: 0.85rem;
        }

        .card-scroll ul li::before {
            content: "•";
            position: absolute;
            left: 0.1rem;
            color: #f5a623;
            font-weight: 700;
        }

        .update-section {
            padding: 60px 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .update-section h2 {
            font-size: 2.5rem;
            font-weight: 700;
            text-align: center;
            color: #ffd89b;
            margin-bottom: 0.5rem;
        }

        .update-section .divider {
            width: 80px;
            height: 4px;
            background: #f5a623;
            margin: 0.5rem auto 2rem;
            border-radius: 4px;
        }

        .update-section .section-sub {
            text-align: center;
            color: #8899b0;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
        }

        .update-wrap {
            background: #131a24;
            border-radius: 24px;
            padding: 1.75rem 2rem 2rem;
            border: 1px solid #2a3645;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.6);
        }

        @media (max-width: 700px) {
            .update-wrap {
                padding: 1.25rem 1rem;
            }
            .update-section h2 {
                font-size: 1.8rem;
            }
            .week-tab {
                padding: 0.4rem 0.9rem;
                font-size: 0.8rem;
            }
            .system-grid {
                grid-template-columns: 1fr;
            }
            .weekly-content {
                max-height: 260px;
            }
            .card-scroll {
                max-height: 110px;
            }
        }

        @media (max-width: 480px) {
            .week-tab {
                flex: 1 0 auto;
                text-align: center;
            }
        }
    </style>
</head>

<body>

    <nav>
        <div class="nav-links">
            <a href="#home">🏠 Home</a>
            <a href="#Objective"> About</a>
            <a href="#features">⭐ Features</a>
            <a href="#gameplay">🎮 Gameplay</a>
            <a href="#weekly-update">📅 Weekly</a>
            <a href="#system-update">🖥️ System</a>
            <a href="#credit">👥 Credits</a>
        </div>
    </nav>

    <section id="home" class="hero">
        <div class="hero-inner" style="
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 60px;
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 40px;
        ">
            <div class="hero-logo" style="flex-shrink: 0;">
                <img src="code quest logo.png"
                alt="Code Quest Logo" style="
                    height: 300px;
                    width: 300px;
                    object-fit: cover;
                    border-radius: 50%;
                    border: 4px solid #ffd89b(255, 215, 0, 0.8);
                    box-shadow: 0 0 25px #ffd89b(255, 215, 0, 0.5), 0 0 60px rgba(255, 215, 0, 0.2);
                ">
            </div>
            <div class="hero-content">
                <h1 class="hero-title">🎮 CODE QUEST</h1>
                <p class="hero-subtitle">Debugging Survival</p>
                <div class="btn-group">
                    <a href="#features" class="btn btn-primary">Explore Now</a>
                    <a href="php/buttons.php" class="btn btn-secondary">Play Now</a>
                </div>
            </div>
        </div>
    </section>

    <section id="Objective">
        <h2>Objective</h2>
        <div class="divider"></div>
        <div class="objective-grid"
        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; max-width: 1200px; margin: 0 auto;">
        <div class="-box" style="
            background: #2a3b5c;
            border: 1.5px solid #87ceeb;
            border-radius: 20px;
            padding: 20px 30px;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            box-shadow: 0 8px 32px #2a3b5c;
            width: 100%;
            border-left: 6px solid #87ceeb;
            color: #87ceeb;
        ">
        <h3 style="color: #e0e1dd;"><strong>Code Quest Debugging Survival</strong></h3>
        <p style="color: #e0e1dd; font-size: 1.0rem; line-height: 1.5;">
            is an innovative 2D survival platform that combines classic gaming mechanics with educational debugging
            challenges. Players are chased by "bugs" (enemy characters) and must fight for survival while reaching safe
            zones to solve debugging quests.
            Games are more than fun, they are interactive learning tools. In Code Quest: Debugging Survival, coding
            challenges become part of the adventure. Students don't just write code, they survive, progress, and win by
            solving problems.
        </p>
    </div>
    <div class="-box" style="
        background: #2a3b5c;
        border: 1.5px solid #87ceeb;
        border-radius: 20px;
        padding: 15px 25px;
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        box-shadow: 0 8px 32px #2a3b5c;
        width: 100%;
        border-left: 6px solid #87ceeb;
        color: #87ceeb;
    ">
    <h3 style="color: #e0e1dd;"><strong>Current and Final Color Palette </strong></h3>
    <img src="color palette.png"
    alt="Color Palette" style="width: 100%; border-radius: 12px; margin-top: 10px;">
</div>
<div class="-box" style="
    background: #2a3b5c;
    border: 1.5px solid #87ceeb;
    border-radius: 20px;
    padding: 20px 30px;
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    box-shadow: 0 8px 32px #2a3b5c;
    width: 100%;
    border-left: 6px solid #87ceeb;
    color: #87ceeb;
">
<h3 style="color: #e0e1dd;"><strong>Current and Final Background</strong></h3>
<img src="../current and final background.png" alt="Current and Final Background"
style="width: 100%; border-radius: 12px; margin-top: 10px;">
</div>
</div>
</section>

<section id="features">
    <h2>Game Features</h2>
    <div class="divider"></div>
    <div class="feature-grid">
        <div class="feature-card">
            <h3>Survival Combat</h3>
            <p>Fight against intelligent bugs that chase and attack. Master dodge, attack, and survival mechanics to stay alive.</p>
        </div>
        <div class="feature-card">
            <h3>Debugging Quests</h3>
            <p>Complete coding challenges in safe zones. Fix bugs and solve problems to unlock the next level.</p>
        </div>
        <div class="feature-card">
            <h3>Progressive Difficulty</h3>
            <p>Start simple and build your skills. Each level introduces new challenges and more enemies to overcome.</p>
        </div>
        <div class="feature-card">
            <h3>Solo Campaign</h3>
            <p>Play through the engaging campaign alone in debugging survival modes with unique challenges.</p>
        </div>
        <div class="feature-card">
            <h3>Real-Time HUD</h3>
            <p>Track your health, bug count, quest progress, and achievements with a sleek in-game interface.</p>
        </div>
        <div class="feature-card">
            <h3>Progress Tracking</h3>
            <p>Save your progress and resume from the last completed level. Your achievements are always preserved.</p>
        </div>
    </div>
</section>

<section id="gameplay">
    <h2>How to Play</h2>
    <div class="divider"></div>
    <div class="gameplay-container">
        <div class="gameplay-section">
            <h3>🎮 Game Flow:</h3>
            <ul>
                <li><strong>🔄 RESTART:</strong> Press R to reset current level</li>
                <li><strong>❓ QUESTION BLOCKS:</strong> Hit from below to get a coding challenge. Type the corrected line exactly.</li>
                <li><strong>💰 COINS:</strong> Collect them for extra points</li>
                <li><strong>👾 ENEMIES:</strong> Jump on Goombas to squash them</li>
                <li><strong>🚩 GATE:</strong> Appears after all blocks solved — touch it to advance</li>
                <li><strong>⏱️ TIMER:</strong> 5 minutes per game — don't run out!</li>
            </ul>
        </div>
        <div class="gameplay-section">
            <h3>⌨️ Controls:</h3>
            <ul>
                <li><strong>Arrow Keys:</strong> Move left and right</li>
                <li><strong>Spacebar:</strong> Jump</li>
            </ul>
        </div>
    </div>
</section>

<section id="weekly-update" class="update-section">
    <h2>📅 Weekly Update</h2>
    <div class="divider"></div>
    <p class="section-sub">Stay up to date with the latest features, fixes, and improvements.</p>

<div class="update-wrap">
        <?php if (!$hasWeekly): ?>
            <div class="empty-state" style="text-align:center;padding:2rem;color:#8899b0;">
                <div style="font-size:3rem;">📭</div>
                <p>No weekly updates published yet.</p>
            </div>
        <?php else: ?>
        <div class="week-tabs" role="tablist">
            <?php $wi = 0; foreach ($weeklyRows as $w): $wi++; ?>
                <button class="week-tab <?php echo $wi === 1 ? 'active' : ''; ?>" data-week="<?php echo $w['id']; ?>" role="tab" aria-selected="<?php echo $wi === 1 ? 'true' : 'false'; ?>"><?php echo htmlspecialchars($w['week']); ?></button>
            <?php endforeach; ?>
        </div>

        <div class="weekly-content">
            <?php $wi = 0; foreach ($weeklyRows as $w): $wi++;
                $items = json_decode($w['items'], true);
                if (!is_array($items)) { $items = []; }
            ?>
            <div class="week-panel <?php echo $wi === 1 ? 'active' : ''; ?>" data-week="<?php echo $w['id']; ?>">
                <h3>
                    <?php echo htmlspecialchars($w['title']); ?>
                    <?php if (!empty($w['date_badge'])): ?>
                        <span class="date-badge"><?php echo htmlspecialchars($w['date_badge']); ?></span>
                    <?php endif; ?>
                </h3>
                <ul>
                    <?php foreach ($items as $item): ?>
                        <li>
                            <span class="tag <?php echo htmlspecialchars($item['tag'] ?? 'new'); ?>"><?php echo strtoupper(htmlspecialchars($item['tag'] ?? 'new')); ?></span>
                            <?php echo $item['text'] ?? ''; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<section id="system-update" class="update-section">
    <h2>🖥️ System Update</h2>
    <div class="divider"></div>
    <p class="section-sub">v2.4.1 · rolling — core engine, networking, security, and more.</p>

    <div class="update-wrap">
        <?php if (!$hasSystem): ?>
            <div class="empty-state" style="text-align:center;padding:2rem;color:#8899b0;">
                <div style="font-size:3rem;">📭</div>
                <p>No system updates published yet.</p>
            </div>
        <?php else: ?>
        <div class="system-grid">
            <?php foreach ($systemRows as $s): ?>
            <div class="system-card">
                <img class="card-img" src="<?php echo htmlspecialchars($s['image'] ?: 'https://picsum.photos/seed/sys' . $s['id'] . '/600/340'); ?>" alt="<?php echo htmlspecialchars($s['module']); ?>" loading="lazy" />
                <div class="card-body">
                    <h4><?php echo htmlspecialchars($s['module']); ?></h4>
                    <div class="card-meta"><?php echo htmlspecialchars($s['updated_at'] ?: 'Recently updated'); ?></div>
                    <div class="card-scroll" data-scroll="true">
                        <?php echo $s['description']; ?>
                        <div class="scroll-hint"><span class="dot"></span> scroll for more</div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<section id="credit">
    <h2>Credits</h2>
    <div class="divider"></div>
    <div class="credits">
        <div class="credit-card">
            <img src="onida.jpg" alt="Team Member 1">
            <p class="name">(PROJECT MANAGER)</p>
            <p class="name">KRIZEL MYREN ONIDA</p>
        </div>
        <div class="credit-card">
            <img src="tacugue.jpg" alt="Team Member 2">
            <p class="name">(BACK END)</p>
            <p class="name">STEFANY M. TACUGUE</p>
        </div>
        <div class="credit-card">
            <img src="micha.jpg" alt="Team Member 3">
            <p class="name">(BACK END)</p>
            <p class="name">MICHAELLA B. BURAY</p>
        </div>
        <div class="credit-card">
            <img src="daiz.jpg" alt="Team Member 4">
            <p class="name">(FRONT END)</p>
            <p class="name">TRESHA MAE DAIZ</p>
        </div>
    </div>
</section>

<footer>
    <p>&copy; 2026 Code Quest – Debugging Survival. All Rights Reserved.</p>
    <p>Version 1.0 | Developed by Group 7</p>
    <p>Educational Game | Made with ❤️ for learners and gamers</p>
</footer>
    <script src="js/web.js"></script>

</body>
</html>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="icon" href="C:\Users\NITRO ACER\Documents\final game code quest\code debugger - Copy\code quest logo.png">
    <link rel="stylesheet" href="../css/games.css">
    <title>⭐ Code Quest: Debugging Survival ⭐</title>
    <?php
    // Require login before the game session so scores can be saved.
    session_start();
    require_once __DIR__ . '/../db.php';
    if (!isset($_SESSION['user_id'])) {
        header('Location: index.php');
        exit;
    }
    $gameUserId = (int) $_SESSION['user_id'];
    ?>
</head>

<body>
    <canvas id="gameCanvas" width="800" height="400"></canvas>

    <!-- MAIN HUD PANEL -->
    <div class="info-panel">
        <div class="lives">❤️ <span id="livesDisplay">3</span></div>
        <div class="tokens-hud">🪙 TOKENS: <span id="tokenDisplay">000</span></div>
        <div class="fragments-hud">✨ FRAGMENTS: <span id="fragmentDisplay">0/4</span></div>
        <div class="timer">⏱️ <span id="timerDisplay">60s</span></div>
        <div class="level" id="levelDisplay">LEVEL 1</div>
        <div>💰 <span id="scoreDisplay">0</span> pts</div>
        <button class="settings-btn" id="settingsBtn">⚙️ SETTINGS</button>
        <button class="exit-btn" id="exitBtn">🚪 EXIT</button>
    </div>

    <!-- SETTINGS OVERLAY -->
    <div id="settingsOverlay" class="settings-overlay">
        <div class="settings-box" style="text-align:center; max-width:480px;">
            <h2 style="color:#7ab4ff; margin-bottom:20px;">⚙️ SETTINGS</h2>
            <div class="setting-row">
                <div style="font-weight:bold;">🔊 Sound Effects &amp; Music</div>
                <div style="display:flex; align-items:center; gap:10px;">
                    <span id="soundStatus" style="color:#4dff91; font-weight:bold;">ON</span>
                    <label class="toggle-switch">
                        <input type="checkbox" id="soundToggle" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>
            <button class="primary-btn" id="settingsCloseBtn">✅ DONE</button>
        </div>
    </div>

    <!-- EXIT CONFIRMATION OVERLAY -->
    <div id="exitOverlay" class="exit-overlay">
        <div class="exit-box" style="text-align:center; max-width:460px;">
            <h2 style="color:#ff6b6b; margin-bottom:14px;">🚪 EXIT GAME?</h2>
            <p style="margin-bottom:20px;">Return to menu? Unsaved progress will be lost.</p>
            <div>
                <button class="secondary-btn" id="exitCancelBtn">❌ CANCEL</button>
                <button class="primary-btn" id="exitConfirmBtn">✅ EXIT</button>
            </div>
        </div>
    </div>

    <!-- STORY & CINEMATIC OVERLAY -->
    <div id="storyOverlay" class="story-overlay">
        <div class="story-box">
            <div class="story-header">
                <div class="story-avatar">🤖</div>
                <div>
                    <h2 id="storyHeaderTitle" style="color:#ffd700; font-size:22px;">CODE GUARDIAN</h2>
                    <div id="storySubTitle" style="color:#87CEEB; font-size:14px; font-weight:bold;">PROLOGUE &bull;
                        PART 1</div>
                </div>
            </div>
            <div class="story-text" id="storyBodyText"></div>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:20px;">
                <button class="secondary-btn" id="storySkipBtn">⏭️ SKIP</button>
                <div>
                    <button class="secondary-btn" id="storyPrevBtn" style="display:none;">◀️ PREV</button>
                    <button class="primary-btn" id="storyNextBtn">▶️ NEXT</button>
                </div>
            </div>
        </div>
    </div>

    <!-- LEARNING SECTION MODAL (BEFORE LEVEL 1 & 2 CHALLENGES) -->
    <div id="learningOverlay" class="learning-overlay">
        <div class="learning-box">
            <h2 id="learningTitle" style="color:#ffd700; text-align:center; margin-bottom:15px;">📚 JAVA LESSON</h2>
            <div id="learningContent" style="max-height:360px; overflow-y:auto; line-height:1.6; padding-right:10px;">
            </div>
            <div style="text-align:center; margin-top:20px;">
                <button class="primary-btn" id="startChallengeBtn">⚔️ START CHALLENGE</button>
            </div>
        </div>
    </div>

    <!-- QUESTION / MCQ MODAL (LEVEL 1 & 2) -->
    <div id="questionModalOverlay" class="modal-overlay">
        <div class="question-box">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                <span class="status-badge" id="questionStepBadge">QUESTION 1/3</span>
                <span style="color:#87CEEB; font-weight:bold;" id="questionTopicTag">JAVA BASICS</span>
            </div>
            <h3 id="questionPrompt" style="color:#ffffff; font-size:20px; margin-bottom:15px;">What is Java?</h3>
            <div class="mcq-grid" id="mcqContainer"></div>
            <div id="questionFeedback"
                style="min-height:30px; text-align:center; font-weight:bold; font-size:18px; margin:10px 0;"></div>
        </div>
    </div>

    <!-- DEBUGGING WORKSPACE MODAL (LEVEL 3 & 4) -->
    <div id="debuggerModalOverlay" class="modal-overlay">
        <div class="question-box" style="max-width:850px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                <span class="status-badge" id="debugTimerBadge">⏱️ TIME: 01:00</span>
                <span class="status-badge" id="debugProgressBadge" style="background:#203454; color:#ffd700;">ERRORS
                    FIXED: 0/4</span>
            </div>
            <h3 id="debugTitle" style="color:#ffd700; margin-bottom:6px;">🛠️ REPAIR THE CORRUPTED CODE</h3>
            <p id="debugInstructions" style="font-size:14px; color:#87CEEB; margin-bottom:12px;">Click on broken lines
                or edit syntax to fix all errors before time expires!</p>

            <div class="code-container" id="codeEditorContainer"></div>

            <div id="debugFeedback"
                style="min-height:30px; text-align:center; font-weight:bold; font-size:16px; margin:10px 0;"></div>

            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                <div>
                    <button class="secondary-btn" id="debugHintBtn" style="font-size:14px; padding:8px 16px;">💡
                        HINT</button>
                    <button class="secondary-btn" id="debugResetBtn" style="font-size:14px; padding:8px 16px;">🔄 RESET
                        CODE</button>
                </div>
                <div>
                    <button class="primary-btn" id="debugCheckBtn" style="font-size:16px; padding:10px 24px;">🔍 CHECK
                        CODE</button>
                </div>
            </div>
        </div>
    </div>

    <!-- PORTAL & TRANSITION OVERLAY -->
    <div id="portalOverlay" class="portal-overlay">
        <div class="portal-box" style="text-align:center; max-width:550px;">
            <h2 id="portalStatusTitle" style="color:#ffd700; font-size:26px;">PORTAL LOCKED</h2>
            <div class="portal-animation-box">
                <div class="portal-icon">🌀</div>
                <div id="portalStepText" style="color:#87CEEB; font-size:18px; font-weight:bold; margin-top:10px;">
                    INSERTING SYNTAX FRAGMENT...</div>
            </div>
            <button class="primary-btn" id="enterPortalBtn" style="display:none; margin-top:15px;">🌀 ENTER NEXT
                WORLD</button>
        </div>
    </div>

    <!-- GAME OVER OVERLAY -->
    <div id="gameoverOverlay" class="gameover-overlay">
        <div class="gameover-box" style="text-align:center; max-width:480px;">
            <h1 id="gameoverTitle" style="color:#ff6b6b; font-size:42px; margin-bottom:10px;">TIME'S UP!</h1>
            <p id="gameoverMsg" style="margin-bottom:20px; font-size:18px;">The code corruption escaped!</p>
            <div>
                <button class="primary-btn" id="playAgainBtn">🔄 RETRY LEVEL</button>
                <button class="secondary-btn" id="menuAgainBtn">🏠 MENU</button>
            </div>
        </div>
    </div>

    <!-- FINAL VICTORY & STATS OVERLAY -->
    <div id="winOverlay" class="win-overlay">
        <div class="win-box" style="text-align:center; max-width:620px;">
            <h1 style="color:#ffd700; font-size:34px; margin-bottom:8px;">🏆 CONGRATULATIONS, BYTE!</h1>
            <h3 style="color:#4dff91; margin-bottom:15px;">YOU SURVIVED THE CODE QUEST!</h3>
            <p style="font-size:15px; color:#87CEEB; margin-bottom:20px;">"You have mastered the fundamentals of Java,
                learned how variables work, understood basic syntax, and defeated corrupted code." — Code Guardian</p>

            <div
                style="background:#0c2b69; border:3px solid #87CEEB; border-radius:16px; padding:18px; text-align:left; margin-bottom:20px; font-size:16px; line-height:1.8;">
                <div>🏅 <b>FINAL RANK:</b> <span id="finalRankSpan" style="color:#ffd700; font-weight:bold;">JAVA
                        MASTER</span></div>
                <div>⭐ <b>Levels Completed:</b> <span id="statLevels">4/4</span></div>
                <div>✨ <b>Syntax Fragments:</b> <span id="statFragments">4/4</span></div>
                <div>🪙 <b>Code Tokens Collected:</b> <span id="statTokens">0</span></div>
                <div>❓ <b>Questions Answered:</b> <span id="statQuestions">7/7</span></div>
                <div>🛠️ <b>Syntax Errors Fixed:</b> <span id="statErrors">14/14</span></div>
                <div>⏱️ <b>Debugging Time:</b> <span id="statTime">01:45</span></div>
            </div>

            <button class="primary-btn" id="playAgainWinBtn">🔄 PLAY AGAIN</button>
            <button class="secondary-btn" id="menuWinBtn">🏠 MAIN MENU</button>
        </div>
    </div>

    <!-- INLINE GAME ENGINE (SUPER MARIO PLATFORM ADVENTURE + CODE QUEST) -->
    <script src="../js/web.js">
       
    </script>
</body>

</html>
 (function () {
            // ============================================================
            // GAME METRICS & STATE
            // ============================================================
            let currentLevel = 1;
            let syntaxFragments = 0;
            let codeTokens = 0;
            let debugScore = 0;
            let lives = 3;
            let gamePaused = false;
            let totalQuestionsAnswered = 0;
            let totalErrorsFixed = 0;
            let startTimeMs = Date.now();
            let challengeCompletedForLevel = false;

            const levelNames = [
                "",
                "LEVEL 1 — JAVA ORIGIN FOREST",
                "LEVEL 2 — THE BROKEN CODE CAVE",
                "LEVEL 3 — THE 60-SECOND DEBUGGING ARENA",
                "LEVEL 4 — THE CODE APOCALYPSE"
            ];

            // ============================================================
            // AUDIO SYSTEM
            // ============================================================
            let soundMuted = false;
            let audioCtx = null;
            const soundToggle = document.getElementById('soundToggle');
            const soundStatus = document.getElementById('soundStatus');

            function getAudioCtx() {
                if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                if (audioCtx.state === 'suspended') audioCtx.resume();
                return audioCtx;
            }

            function applySoundState() {
                soundStatus.textContent = soundMuted ? 'OFF' : 'ON';
                soundStatus.style.color = soundMuted ? '#ff6b6b' : '#4dff91';
                soundToggle.checked = !soundMuted;
                if (soundMuted) pauseBGM(); else if (!gamePaused) resumeBGM();
            }

            soundToggle.addEventListener('change', () => {
                soundMuted = !soundToggle.checked;
                applySoundState();
            });

            function playTone(freq, duration, type = 'square', volume = 0.25) {
                if (soundMuted) return;
                try {
                    const c = getAudioCtx(), o = c.createOscillator(), g = c.createGain();
                    o.connect(g); g.connect(c.destination); o.type = type;
                    o.frequency.setValueAtTime(freq, c.currentTime);
                    g.gain.setValueAtTime(volume, c.currentTime);
                    g.gain.exponentialRampToValueAtTime(0.001, c.currentTime + duration);
                    o.start(c.currentTime); o.stop(c.currentTime + duration);
                } catch (e) { }
            }

            function soundJump() { playTone(440, 0.12, 'square', 0.2); }
            function soundCoin() { playTone(988, 0.1, 'sine', 0.3); }
            function soundStomp() { playTone(150, 0.15, 'triangle', 0.35); }
            function soundDie() { playTone(120, 0.3, 'sawtooth', 0.4); }
            function soundBlockHit() { playTone(300, 0.15, 'square', 0.3); }
            function soundCorrect() {
                if (soundMuted) return;
                try {
                    const c = getAudioCtx();
                    [523, 659, 784, 1047].forEach((f, i) => {
                        const o = c.createOscillator(), g = c.createGain();
                        o.connect(g); g.connect(c.destination); o.type = 'sine';
                        o.frequency.setValueAtTime(f, c.currentTime + i * 0.08);
                        g.gain.setValueAtTime(0.2, c.currentTime + i * 0.08);
                        g.gain.exponentialRampToValueAtTime(0.001, c.currentTime + i * 0.08 + 0.1);
                        o.start(c.currentTime + i * 0.08); o.stop(c.currentTime + i * 0.08 + 0.1);
                    });
                } catch (e) { }
            }
            function soundWrong() { playTone(110, 0.3, 'sawtooth', 0.35); }
            function soundLevelUp() { soundCorrect(); }

            let bgmPlaying = false;
            function startBGM() { bgmPlaying = true; }
            function stopBGM() { bgmPlaying = false; }
            function pauseBGM() { bgmPlaying = false; }
            function resumeBGM() { if (!soundMuted) bgmPlaying = true; }

            document.addEventListener('keydown', () => getAudioCtx(), { once: true });
            document.addEventListener('click', () => getAudioCtx(), { once: true });

            // ============================================================
            // CANVAS GRAPHICS & SPRITES PRESERVATION
            // ============================================================
            const canvas = document.getElementById('gameCanvas');
            const ctx = canvas.getContext('2d');
            const scoreSpan = document.getElementById('scoreDisplay'), tokenSpan = document.getElementById('tokenDisplay');
            const livesSpan = document.getElementById('livesDisplay'), levelSpan = document.getElementById('levelDisplay');
            const fragmentSpan = document.getElementById('fragmentDisplay'), timerSpan = document.getElementById('timerDisplay');

            const WORLD_WIDTH = 3200, CANVAS_W = 800, CANVAS_H = 400, GROUND_Y = 370;

            const bgImage = new Image(); bgImage.src = '../ba(1).jpg';
            const playerJumpEast = new Image(); playerJumpEast.src = '../image/Character/C_Jumping/A_full-body_cartoon-style_illustration_of_a_small_jumping-1_east.gif';
            const playerJumpWest = new Image(); playerJumpWest.src = '../image/Character/C_Jumping/A_full-body_cartoon-style_illustration_of_a_small_jumping-1_west.gif';
            const playerRunEast = new Image(); playerRunEast.src = '../image/Character/C_Running/A_full-body_cartoon-style_illustration_of_a_small_running-4-frames_east.gif';
            const playerRunWest = new Image(); playerRunWest.src = '../image/Character/C_Running/A_full-body_cartoon-style_illustration_of_a_small_running-4-frames_west.gif';
            const playerWalkEast = new Image(); playerWalkEast.src = '../image/Character/C_Walking/A_full-body_cartoon-style_illustration_of_a_small_walking_east.gif';
            const playerWalkWest = new Image(); playerWalkWest.src = '../image/Character/C_Walking/A_full-body_cartoon-style_illustration_of_a_small_walking_west.gif';
            let playerFacing = 'east';

            const bugSpriteEast = new Image(); bugSpriteEast.src = '../image/Character/Bugs_Character/A_pixel_art_TYPE_OF_BUG_side_profile_2D_platformer_attack-right_east.gif';
            const bugSpriteWest = new Image(); bugSpriteWest.src = '../image/Character/Bugs_Character/A_pixel_art_TYPE_OF_BUG_side_profile_2D_platformer_attack-right_west.gif';

            let player = { x: 150, y: 280, w: 22, h: 22, vx: 0, vy: 0, grounded: false, color: '#E52525' };

            let platforms = [], questionBlocks = [], goombas = [], tokens = [], cameraX = 0;
            let lockedPortal = { x: 2800, y: GROUND_Y - 65, w: 45, h: 65, active: false, glowing: false };

            let timerInterval = null, levelTimeLeft = 60;

            function updateHUD() {
                livesSpan.innerText = lives;
                scoreSpan.innerText = debugScore;
                tokenSpan.innerText = String(codeTokens).padStart(3, '0');
                fragmentSpan.innerText = `${syntaxFragments}/4`;
                levelSpan.innerText = `L${currentLevel}: ${levelNames[currentLevel].split('—')[1] || levelNames[currentLevel]}`;
            }

            function startTimer(seconds) {
                if (timerInterval) clearInterval(timerInterval);
                levelTimeLeft = seconds;
                timerSpan.innerText = `${levelTimeLeft}s`;
                timerInterval = setInterval(() => {
                    if (!gamePaused) {
                        levelTimeLeft--;
                        timerSpan.innerText = `${levelTimeLeft}s`;
                        const dbgBadge = document.getElementById('debugTimerBadge');
                        if (dbgBadge) dbgBadge.innerText = `⏱️ TIME: ${String(Math.floor(levelTimeLeft / 60)).padStart(2, '0')}:${String(levelTimeLeft % 60).padStart(2, '0')}`;
                        if (levelTimeLeft <= 0) {
                            clearInterval(timerInterval);
                            triggerTimeUpFailure();
                        }
                    }
                }, 1000);
            }

            function stopTimer() {
                if (timerInterval) { clearInterval(timerInterval); timerInterval = null; }
            }

            // ============================================================
            // MARIO-STYLE LEVEL GENERATOR (SUPER MARIO PLATFORM ADVENTURE)
            // ============================================================
            function loadLevel(lvlNum) {
                currentLevel = lvlNum;
                player.x = 150; player.y = 280; player.vx = 0; player.vy = 0;
                challengeCompletedForLevel = false;

                // 1. Medium & Small Accessible Floating Platforms (Not Very High)
                platforms = [
                    { x: 0, y: GROUND_Y, w: WORLD_WIDTH, h: 20, color: '#5b8c4e' },
                    // Medium platforms
                    { x: 240, y: 320, w: 120, h: 18, color: '#7aa862' },
                    { x: 480, y: 290, w: 140, h: 18, color: '#b57a4a' },
                    { x: 780, y: 260, w: 130, h: 18, color: '#b57a4a' },
                    { x: 1080, y: 310, w: 150, h: 18, color: '#9c7c5b' },
                    { x: 1380, y: 275, w: 140, h: 18, color: '#8b6b48' },
                    { x: 1720, y: 245, w: 160, h: 18, color: '#7aa862' },
                    { x: 2080, y: 295, w: 150, h: 18, color: '#9c7c5b' },
                    { x: 2420, y: 320, w: 140, h: 18, color: '#b57a4a' },
                    // Small Floating Stepping Platforms
                    { x: 390, y: 330, w: 55, h: 16, color: '#4aa3b5' },
                    { x: 660, y: 310, w: 55, h: 16, color: '#4aa3b5' },
                    { x: 950, y: 285, w: 55, h: 16, color: '#4aa3b5' },
                    { x: 1260, y: 325, w: 55, h: 16, color: '#4aa3b5' },
                    { x: 1560, y: 295, w: 55, h: 16, color: '#4aa3b5' },
                    { x: 1920, y: 270, w: 55, h: 16, color: '#4aa3b5' },
                    { x: 2280, y: 315, w: 55, h: 16, color: '#4aa3b5' }
                ];

                // 2. Small & Medium Interactive Code Blocks (?) - Placed at Head-Hit Heights
                questionBlocks = [
                    { x: 400, y: 270, w: 28, h: 28, active: true, type: 'token', hit: false },
                    { x: 520, y: 230, w: 32, h: 32, active: true, type: 'tip', tipText: "JAVA TIP 1: Java programs run on the JVM!", hit: false },
                    { x: 670, y: 250, w: 28, h: 28, active: true, type: 'token', hit: false },
                    { x: 820, y: 200, w: 32, h: 32, active: true, type: 'token', hit: false },
                    { x: 1270, y: 260, w: 28, h: 28, active: true, type: 'token', hit: false },
                    { x: 1420, y: 215, w: 32, h: 32, active: true, type: 'tip', tipText: "JAVA TIP 2: Every statement usually ends with ;", hit: false },
                    { x: 1760, y: 185, w: 32, h: 32, active: true, type: 'challenge', hit: false }
                ];

                // 3. Collectible Code Tokens (Accessible Paths)
                tokens = [];
                for (let i = 0; i < 22; i++) {
                    tokens.push({
                        x: 280 + i * 120,
                        y: 280 + Math.sin(i * 0.8) * 35,
                        w: 14, h: 14, collected: false
                    });
                }

                // 4. Digital Enemies (Buglings & Crawlers)
                goombas = [];
                for (let j = 0; j < lvlNum + 3; j++) {
                    goombas.push({
                        x: 600 + j * 260, y: GROUND_Y - 22, w: 22, h: 22,
                        vx: (j % 2 === 0 ? 1 : -1) * (0.9 + j * 0.1), vy: 0,
                        alive: true, minX: 100, maxX: WORLD_WIDTH - 100
                    });
                }

                // 5. Locked Portal at Level End
                lockedPortal = { x: 2750, y: GROUND_Y - 65, w: 45, h: 65, active: true, glowing: false };

                updateHUD();
                showLevelStoryIntro(lvlNum);
            }

            // ============================================================
            // STORY & CUTSCENE SYSTEM
            // ============================================================
            const storyOverlay = document.getElementById('storyOverlay');
            const storyHeaderTitle = document.getElementById('storyHeaderTitle');
            const storySubTitle = document.getElementById('storySubTitle');
            const storyBodyText = document.getElementById('storyBodyText');
            const storySkipBtn = document.getElementById('storySkipBtn');
            const storyPrevBtn = document.getElementById('storyPrevBtn');
            const storyNextBtn = document.getElementById('storyNextBtn');

            let storyPages = [], currentStoryIdx = 0, storyOnCompleteCallback = null;

            function showStorySequence(pages, onComplete) {
                storyPages = pages; currentStoryIdx = 0; storyOnCompleteCallback = onComplete;
                gamePaused = true; storyOverlay.style.display = 'flex'; renderStoryPage();
            }

            function renderStoryPage() {
                const page = storyPages[currentStoryIdx];
                storyHeaderTitle.innerText = page.title || "CODE GUARDIAN";
                storySubTitle.innerText = page.subtitle || `PART ${currentStoryIdx + 1} / ${storyPages.length}`;
                storyBodyText.innerHTML = page.text;
                storyPrevBtn.style.display = currentStoryIdx > 0 ? 'inline-block' : 'none';
                storyNextBtn.innerText = currentStoryIdx === storyPages.length - 1 ? '▶️ CONTINUE' : '▶️ NEXT';
            }

            storyNextBtn.onclick = () => {
                if (currentStoryIdx < storyPages.length - 1) { currentStoryIdx++; renderStoryPage(); }
                else { storyOverlay.style.display = 'none'; gamePaused = false; if (storyOnCompleteCallback) storyOnCompleteCallback(); }
            };
            storyPrevBtn.onclick = () => { if (currentStoryIdx > 0) { currentStoryIdx--; renderStoryPage(); } };
            storySkipBtn.onclick = () => { storyOverlay.style.display = 'none'; gamePaused = false; if (storyOnCompleteCallback) storyOnCompleteCallback(); };

            function showPrologue() {
                showStorySequence([
                    {
                        title: "BYTE & THE CODE REALM",
                        subtitle: "PROLOGUE • PART 1",
                        text: "Byte is working on a mysterious Java program when suddenly the screen glitches...<br><br><span class='glitch-text'>SYSTEM ERROR... CODE REALM CORRUPTED...</span><br><br>A digital storm pulls Byte into the computer realm."
                    },
                    {
                        title: "THE CODE GUARDIAN APPEARS",
                        subtitle: "PROLOGUE • PART 2",
                        text: "Byte wakes up inside a pixel-art world filled with bugs, corrupted code, monsters, and locked portals.<br><br>A mysterious AI guide named <b>CODE GUARDIAN</b> appears:<br><i>“Welcome to the Code Realm, Byte. Four Syntax Fragments were lost when the system collapsed. You must recover them by mastering Java and defeating corrupted code.”</i>"
                    },
                    {
                        title: "THE PORTAL PROGRESSION",
                        subtitle: "PROLOGUE • PART 3",
                        text: "The Code Guardian explains:<br><i>“Explore each world, collect Code Tokens, hit interactive Code Blocks, and reach the Locked Portal to complete the Java challenge!”</i><br><br><b>JUMP → EXPLORE → LEARN → SOLVE → DEBUG → COLLECT → UNLOCK → ESCAPE</b>"
                    }
                ], () => loadLevel(1));
            }

            function showLevelStoryIntro(lvl) {
                if (lvl === 1) {
                    showStorySequence([
                        {
                            title: "LEVEL 1 — JAVA ORIGIN FOREST",
                            subtitle: "WORLD 1",
                            text: "Byte arrives in a mysterious forest made of digital objects. Trees contain glowing lines of code.<br><br>Code Guardian: <i>“Explore the forest, hit Code Blocks for Java tips, and reach the Challenge Station!”</i><br><br><b>MISSION: LEARN THE BASICS OF JAVA.</b>"
                        }
                    ]);
                } else if (lvl === 2) {
                    showStorySequence([
                        {
                            title: "LEVEL 2 — THE BROKEN CODE CAVE",
                            subtitle: "WORLD 2",
                            text: "Byte arrives inside a dark cave. Walls are covered with broken Java code.<br><br>The <b>SYNTAX GUARDIAN</b> says:<br><i>“Prove that you understand Java syntax to claim the next Syntax Fragment!”</i>"
                        }
                    ]);
                } else if (lvl === 3) {
                    showStorySequence([
                        {
                            title: "LEVEL 3 — THE 60-SECOND DEBUGGING ARENA",
                            subtitle: "WORLD 3",
                            text: "Byte arrives in a giant digital arena with fast moving platforms!<br><br>Code Guardian: <i>“Reach the Debugging Station to repair 4 syntax errors before the 1-minute timer expires!”</i>"
                        }
                    ]);
                } else if (lvl === 4) {
                    showStorySequence([
                        {
                            title: "LEVEL 4 — THE CODE APOCALYPSE",
                            subtitle: "WORLD 4",
                            text: "The entire Code Realm is collapsing! Digital monsters are escaping.<br><br>Code Guardian: <i>“This is your final test, Byte. Repair 10 syntax errors in 2 minutes to restore the Code Realm!”</i>"
                        }
                    ]);
                }
            }

            // ============================================================
            // LEARNING MODALS & INTERACTIVE CODE BLOCKS MECHANIC
            // ============================================================
            const learningOverlay = document.getElementById('learningOverlay');
            const learningTitle = document.getElementById('learningTitle');
            const learningContent = document.getElementById('learningContent');
            const startChallengeBtn = document.getElementById('startChallengeBtn');

            function triggerCodeBlockTip(tipText) {
                gamePaused = true;
                learningOverlay.style.display = 'flex';
                learningTitle.innerText = "💡 JAVA CODE BLOCK TIP";
                learningContent.innerHTML = `<p style="font-size:18px; color:#a9dc76;">${tipText}</p>`;
                startChallengeBtn.innerText = "👍 CONTINUE EXPLORING";
                startChallengeBtn.onclick = () => { learningOverlay.style.display = 'none'; gamePaused = false; };
            }

            function triggerLevelChallenge() {
                if (currentLevel === 1) showLearningSection(1);
                else if (currentLevel === 2) showLearningSection(2);
                else if (currentLevel === 3) startLevel3Arena();
                else if (currentLevel === 4) startLevel4Apocalypse();
            }

            function showLearningSection(lvl) {
                gamePaused = true;
                learningOverlay.style.display = 'flex';

                if (lvl === 1) {
                    learningTitle.innerText = "🌲 LEVEL 1: JAVA BASICS LESSON";
                    learningContent.innerHTML = `
                        <h4 style="color:#7ab4ff;">TOPIC 1 — WHAT IS JAVA?</h4>
                        <p>Java is a high-level, object-oriented programming language used for apps, games, and web software running on the <b>JVM</b>.</p>
                        <br>
                        <h4 style="color:#7ab4ff;">TOPIC 2 — TYPES OF JAVA VARIABLES</h4>
                        <ul>
                            <li><b>int</b>: Whole numbers (<code>int score = 100;</code>)</li>
                            <li><b>double</b>: Decimals (<code>double price = 25.50;</code>)</li>
                            <li><b>float</b>: Decimals with 'f' (<code>float temp = 36.5f;</code>)</li>
                            <li><b>char</b>: Single char in single quotes (<code>char grade = 'A';</code>)</li>
                            <li><b>boolean</b>: <code>true</code> or <code>false</code></li>
                            <li><b>String</b>: Text in double quotes (<code>String name = "Byte";</code>)</li>
                        </ul>
                    `;
                    startChallengeBtn.innerText = "⚔️ START 3 QUESTIONS";
                    startChallengeBtn.onclick = () => { learningOverlay.style.display = 'none'; startLevel1Challenge(); };
                } else if (lvl === 2) {
                    learningTitle.innerText = "🦇 LEVEL 2: JAVA SYNTAX LESSON";
                    learningContent.innerHTML = `
                        <h4 style="color:#7ab4ff;">BASIC JAVA SYNTAX RULES</h4>
                        <p>Statements end with semicolons (<code>;</code>), methods use parentheses (<code>()</code>), and blocks use curly braces (<code>{}</code>).</p>
                        <br>
                        <h4 style="color:#ff6b6b;">BROKEN:</h4> <code>int score = 100</code><br>
                        <h4 style="color:#4dff91;">FIXED:</h4> <code>int score = 100;</code>
                    `;
                    startChallengeBtn.innerText = "⚔️ DEFEAT SYNTAX GUARDIAN";
                    startChallengeBtn.onclick = () => { learningOverlay.style.display = 'none'; startLevel2Challenge(); };
                }
            }

            // ============================================================
            // MCQ SYSTEM (LEVEL 1 & 2)
            // ============================================================
            const questionModalOverlay = document.getElementById('questionModalOverlay');
            const questionStepBadge = document.getElementById('questionStepBadge');
            const questionTopicTag = document.getElementById('questionTopicTag');
            const questionPrompt = document.getElementById('questionPrompt');
            const mcqContainer = document.getElementById('mcqContainer');
            const questionFeedback = document.getElementById('questionFeedback');

            let currentQuestionsList = [], currentQuestionIdx = 0, onMcqSuccessCallback = null;

            const level1Questions = [
                { prompt: "Question 1: What is Java?", options: ["A. A programming language", "B. A computer virus", "C. A web browser", "D. A keyboard"], correct: 0 },
                { prompt: "Question 2: Which of the following is a Java variable/data type?", options: ["A. int", "B. print", "C. output", "D. program"], correct: 0 },
                { prompt: "Question 3: Which Java command is used to display text on screen?", options: ["A. System.out.println()", "B. display.text()", "C. show.console()", "D. print.screen()"], correct: 0 }
            ];

            const level2Questions = [
                { prompt: "Question 1: What is missing?\nint score = 100", options: ["A. ;", "B. :", "C. ( )", "D. { }"], correct: 0 },
                { prompt: "Question 2: Which symbol usually ends a Java statement?", options: ["A. ;", "B. :", "C. ,", "D. ."], correct: 0 },
                { prompt: "Question 3: Which brackets are used for method parameters?", options: ["A. ( )", "B. { }", "C. [ ]", "D. < >"], correct: 0 },
                { prompt: "Question 4: Which code correctly displays \"Hello\"?", options: ["A. System.out.println(\"Hello\");", "B. System.println(\"Hello\");", "C. print.System(\"Hello\");", "D. System.out.println(\"Hello\""], correct: 0 }
            ];

            function startMcqQuiz(questions, topicTag, onSuccess) {
                currentQuestionsList = questions; currentQuestionIdx = 0; onMcqSuccessCallback = onSuccess;
                questionTopicTag.innerText = topicTag; gamePaused = true; questionModalOverlay.style.display = 'flex';
                renderMcqQuestion();
            }

            function renderMcqQuestion() {
                const q = currentQuestionsList[currentQuestionIdx];
                questionStepBadge.innerText = `QUESTION ${currentQuestionIdx + 1}/${currentQuestionsList.length}`;
                questionPrompt.innerText = q.prompt;
                questionFeedback.innerText = "";
                mcqContainer.innerHTML = "";

                q.options.forEach((optText, optIdx) => {
                    const btn = document.createElement('button');
                    btn.className = 'mcq-btn';
                    btn.innerText = optText;
                    btn.onclick = () => checkMcqAnswer(optIdx, btn);
                    mcqContainer.appendChild(btn);
                });
            }

            function checkMcqAnswer(selectedIdx, btnEl) {
                const q = currentQuestionsList[currentQuestionIdx];
                const allBtns = mcqContainer.querySelectorAll('.mcq-btn');
                allBtns.forEach(b => b.disabled = true);

                if (selectedIdx === q.correct) {
                    btnEl.classList.add('correct');
                    questionFeedback.innerHTML = "<span style='color:#4dff91;'>✅ CORRECT ANSWER!</span>";
                    soundCorrect();
                    totalQuestionsAnswered++;
                    debugScore += 100;
                    updateHUD();

                    setTimeout(() => {
                        currentQuestionIdx++;
                        if (currentQuestionIdx < currentQuestionsList.length) {
                            renderMcqQuestion();
                        } else {
                            questionModalOverlay.style.display = 'none';
                            gamePaused = false;
                            if (onMcqSuccessCallback) onMcqSuccessCallback();
                        }
                    }, 1000);
                } else {
                    btnEl.classList.add('wrong');
                    allBtns[q.correct].classList.add('correct');
                    questionFeedback.innerHTML = "<span style='color:#ff6b6b;'>❌ INCORRECT! Try again...</span>";
                    soundWrong();
                    setTimeout(() => {
                        allBtns.forEach(b => b.disabled = false);
                        btnEl.classList.remove('wrong');
                    }, 1200);
                }
            }

            function startLevel1Challenge() {
                startMcqQuiz(level1Questions, "JAVA ORIGIN FOREST", () => {
                    triggerPortalSequence(1, "JAVA BASICS MASTERED!", () => {
                        challengeCompletedForLevel = true;
                        lockedPortal.glowing = true;
                    });
                });
            }

            function startLevel2Challenge() {
                startMcqQuiz(level2Questions, "BROKEN CODE CAVE", () => {
                    triggerPortalSequence(2, "SYNTAX GUARDIAN DEFEATED!", () => {
                        challengeCompletedForLevel = true;
                        lockedPortal.glowing = true;
                    });
                });
            }

            // ============================================================
            // TIMED DEBUGGING ARENA (LEVEL 3 & 4)
            // ============================================================
            const debuggerModalOverlay = document.getElementById('debuggerModalOverlay');
            const debugProgressBadge = document.getElementById('debugProgressBadge');
            const debugTitle = document.getElementById('debugTitle');
            const debugInstructions = document.getElementById('debugInstructions');
            const codeEditorContainer = document.getElementById('codeEditorContainer');
            const debugFeedback = document.getElementById('debugFeedback');

            let level3FixedCount = 0, level3ErrorsState = [false, false, false, false];

            function startLevel3Arena() {
                gamePaused = true; level3FixedCount = 0; level3ErrorsState.fill(false); startTimer(60);
                debugTitle.innerText = "⚔️ LEVEL 3: 60-SECOND DEBUGGING ARENA";
                debugInstructions.innerText = "Find and fix EXACTLY 4 syntax errors before time runs out!";
                debuggerModalOverlay.style.display = 'flex'; renderLevel3Code();
            }

            function renderLevel3Code() {
                debugProgressBadge.innerText = `ERRORS FIXED: ${level3FixedCount}/4`;
                codeEditorContainer.innerHTML = `
                    <div class="code-line"><span class="line-num">1</span><span class="line-text">public class Main {</span></div>
                    <div class="code-line"><span class="line-num">2</span><span class="line-text">    public static void main(String[] args) {</span></div>
                    <div class="code-line ${level3ErrorsState[0] ? 'fixed' : 'has-error'}"><span class="line-num">3</span><span class="line-text">String player = "Byte"${level3ErrorsState[0] ? ';' : ''}</span>${!level3ErrorsState[0] ? '<button class="secondary-btn" style="font-size:11px; padding:2px 6px;" onclick="fixL3Error(0)">Fix ;</button>' : '✅'}</div>
                    <div class="code-line"><span class="line-num">4</span><span class="line-text">        int score = 100;</span></div>
                    <div class="code-line ${level3ErrorsState[1] ? 'fixed' : 'has-error'}"><span class="line-num">5</span><span class="line-text">System.out.println("Player: " + player)${level3ErrorsState[1] ? ';' : ''}</span>${!level3ErrorsState[1] ? '<button class="secondary-btn" style="font-size:11px; padding:2px 6px;" onclick="fixL3Error(1)">Fix ;</button>' : '✅'}</div>
                    <div class="code-line ${level3ErrorsState[2] ? 'fixed' : 'has-error'}"><span class="line-num">6</span><span class="line-text">System.out.println("Score: " + score${level3ErrorsState[2] ? ');' : ';'}</span>${!level3ErrorsState[2] ? '<button class="secondary-btn" style="font-size:11px; padding:2px 6px;" onclick="fixL3Error(2)">Fix )</button>' : '✅'}</div>
                    <div class="code-line ${level3ErrorsState[3] ? 'fixed' : 'has-error'}"><span class="line-num">7</span><span class="line-text">System.out.print("Arena Active"${level3ErrorsState[3] ? ');' : ''}</span>${!level3ErrorsState[3] ? '<button class="secondary-btn" style="font-size:11px; padding:2px 6px;" onclick="fixL3Error(3)">Fix );</button>' : '✅'}</div>
                    <div class="code-line"><span class="line-num">8</span><span class="line-text">    }</span></div>
                    <div class="code-line"><span class="line-num">9</span><span class="line-text">}</span></div>
                `;
            }

            window.fixL3Error = function (idx) {
                if (!level3ErrorsState[idx]) {
                    level3ErrorsState[idx] = true; level3FixedCount++; totalErrorsFixed++; soundCorrect(); renderLevel3Code();
                    if (level3FixedCount === 4) {
                        stopTimer();
                        setTimeout(() => {
                            debuggerModalOverlay.style.display = 'none';
                            triggerPortalSequence(3, "DEBUGGING SUCCESS! 4/4 ERRORS FIXED", () => {
                                challengeCompletedForLevel = true; lockedPortal.glowing = true;
                            });
                        }, 800);
                    }
                }
            };

            let level4FixedCount = 0, level4ErrorsState = new Array(10).fill(false);

            function startLevel4Apocalypse() {
                gamePaused = true; level4FixedCount = 0; level4ErrorsState.fill(false); startTimer(120);
                debugTitle.innerText = "🔥 LEVEL 4: THE CODE APOCALYPSE";
                debugInstructions.innerText = "10 syntax errors are destroying the Code Realm! Fix all 10 errors before 2 minutes expire!";
                debuggerModalOverlay.style.display = 'flex'; renderLevel4Code();
            }

            function renderLevel4Code() {
                debugProgressBadge.innerText = `ERRORS FIXED: ${level4FixedCount}/10`;
                codeEditorContainer.innerHTML = `
                    <div class="code-line"><span class="line-num">1</span><span class="line-text">public class Main {</span></div>
                    <div class="code-line"><span class="line-num">2</span><span class="line-text">    public static void main(String[] args) {</span></div>
                    <div class="code-line ${level4ErrorsState[0] ? 'fixed' : 'has-error'}"><span class="line-num">3</span><span class="line-text">String player = "Byte"${level4ErrorsState[0] ? ';' : ''}</span>${!level4ErrorsState[0] ? '<button class="secondary-btn" style="font-size:10px; padding:2px 4px;" onclick="fixL4Error(0)">Fix ;</button>' : '✅'}</div>
                    <div class="code-line ${level4ErrorsState[1] ? 'fixed' : 'has-error'}"><span class="line-num">4</span><span class="line-text">int health = 100${level4ErrorsState[1] ? ';' : ''}</span>${!level4ErrorsState[1] ? '<button class="secondary-btn" style="font-size:10px; padding:2px 4px;" onclick="fixL4Error(1)">Fix ;</button>' : '✅'}</div>
                    <div class="code-line"><span class="line-num">5</span><span class="line-text">        int score = 500;</span></div>
                    <div class="code-line ${level4ErrorsState[2] ? 'fixed' : 'has-error'}"><span class="line-num">6</span><span class="line-text">System.out.println("Health: " + health${level4ErrorsState[2] ? ');' : ''}</span>${!level4ErrorsState[2] ? '<button class="secondary-btn" style="font-size:10px; padding:2px 4px;" onclick="fixL4Error(2)">Fix );</button>' : '✅'}</div>
                    <div class="code-line ${level4ErrorsState[3] ? 'fixed' : 'has-error'}"><span class="line-num">7</span><span class="line-text">if (health > 0${level4ErrorsState[3] ? ')' : ''} {</span>${!level4ErrorsState[3] ? '<button class="secondary-btn" style="font-size:10px; padding:2px 4px;" onclick="fixL4Error(3)">Fix )</button>' : '✅'}</div>
                    <div class="code-line"><span class="line-num">8</span><span class="line-text">            System.out.println("Byte is alive!");</span></div>
                    <div class="code-line"><span class="line-num">9</span><span class="line-text">        }</span></div>
                    <div class="code-line ${level4ErrorsState[4] ? 'fixed' : 'has-error'}"><span class="line-num">10</span><span class="line-text">System.out.println("Keep debugging!"${level4ErrorsState[4] ? ');' : ''}</span>${!level4ErrorsState[4] ? '<button class="secondary-btn" style="font-size:10px; padding:2px 4px;" onclick="fixL4Error(4)">Fix );</button>' : '✅'}</div>
                    <div class="code-line ${level4ErrorsState[5] ? 'fixed' : 'has-error'}"><span class="line-num">11</span><span class="line-text">${level4ErrorsState[5] ? 'int' : 'Int'} extraPower = 50;</span>${!level4ErrorsState[5] ? '<button class="secondary-btn" style="font-size:10px; padding:2px 4px;" onclick="fixL4Error(5)">Fix int</button>' : '✅'}</div>
                    <div class="code-line ${level4ErrorsState[6] ? 'fixed' : 'has-error'}"><span class="line-num">12</span><span class="line-text">System.out.println("Power: " + extraPower${level4ErrorsState[6] ? ');' : ''}</span>${!level4ErrorsState[6] ? '<button class="secondary-btn" style="font-size:10px; padding:2px 4px;" onclick="fixL4Error(6)">Fix );</button>' : '✅'}</div>
                    <div class="code-line ${level4ErrorsState[7] ? 'fixed' : 'has-error'}"><span class="line-num">13</span><span class="line-text">System.out.println("Unterminated string${level4ErrorsState[7] ? '"' : ''});</span>${!level4ErrorsState[7] ? '<button class="secondary-btn" style="font-size:10px; padding:2px 4px;" onclick="fixL4Error(7)">Fix quote</button>' : '✅'}</div>
                    <div class="code-line ${level4ErrorsState[8] ? 'fixed' : 'has-error'}"><span class="line-num">14</span><span class="line-text">boolean active = ${level4ErrorsState[8] ? 'true' : 'True'};</span>${!level4ErrorsState[8] ? '<button class="secondary-btn" style="font-size:10px; padding:2px 4px;" onclick="fixL4Error(8)">Fix true</button>' : '✅'}</div>
                    <div class="code-line ${level4ErrorsState[9] ? 'fixed' : 'has-error'}"><span class="line-num">15</span><span class="line-text">System.out.println("Active: " + active${level4ErrorsState[9] ? ');' : ''}</span>${!level4ErrorsState[9] ? '<button class="secondary-btn" style="font-size:10px; padding:2px 4px;" onclick="fixL4Error(9)">Fix );</button>' : '✅'}</div>
                    <div class="code-line"><span class="line-num">16</span><span class="line-text">    }</span></div>
                    <div class="code-line"><span class="line-num">17</span><span class="line-text">}</span></div>
                `;
            }

            window.fixL4Error = function (idx) {
                if (!level4ErrorsState[idx]) {
                    level4ErrorsState[idx] = true; level4FixedCount++; totalErrorsFixed++; soundCorrect(); renderLevel4Code();
                    if (level4FixedCount === 10) {
                        stopTimer();
                        setTimeout(() => {
                            debuggerModalOverlay.style.display = 'none';
                            triggerPortalSequence(4, "THE CODE APOCALYPSE HAS BEEN DEFEATED!", () => {
                                challengeCompletedForLevel = true; lockedPortal.glowing = true;
                            });
                        }, 800);
                    }
                }
            };

            document.getElementById('debugCheckBtn').onclick = () => { soundWrong(); };
            document.getElementById('debugResetBtn').onclick = () => { if (currentLevel === 3) startLevel3Arena(); else startLevel4Apocalypse(); };
            document.getElementById('debugHintBtn').onclick = () => { debugFeedback.innerHTML = "<span style='color:#ffd700;'>💡 HINT: Check for missing semicolons (;) or parentheses ).</span>"; };

function triggerTimeUpFailure() {
                debuggerModalOverlay.style.display = 'none'; gamePaused = true; soundDie();
                saveGameStats(false, '');
                document.getElementById('gameoverOverlay').style.display = 'flex';
            }

            document.getElementById('playAgainBtn').onclick = () => { document.getElementById('gameoverOverlay').style.display = 'none'; loadLevel(currentLevel); };
            document.getElementById('menuAgainBtn').onclick = () => { window.location.href = 'buttons.php'; };

            // ============================================================
            // PORTAL ACTIVATION ANIMATION
            // ============================================================
            const portalOverlay = document.getElementById('portalOverlay');
            const portalStatusTitle = document.getElementById('portalStatusTitle');
            const portalStepText = document.getElementById('portalStepText');
            const enterPortalBtn = document.getElementById('enterPortalBtn');

            function triggerPortalSequence(fragmentNum, titleMsg, onComplete) {
                syntaxFragments = fragmentNum; updateHUD(); gamePaused = true; portalOverlay.style.display = 'flex'; enterPortalBtn.style.display = 'none';
                portalStatusTitle.innerText = titleMsg; portalStepText.innerText = "PORTAL LOCKED";

                setTimeout(() => { portalStepText.innerText = "⚡ POWER RESTORED..."; soundCoin(); }, 800);
                setTimeout(() => { portalStepText.innerText = `✨ SYNTAX FRAGMENT ${fragmentNum}/4 INSERTED!`; soundCorrect(); }, 1600);
                setTimeout(() => { portalStepText.innerText = "🌀 PORTAL ACTIVATING..."; }, 2400);
                setTimeout(() => {
                    portalStatusTitle.innerText = `PORTAL ${fragmentNum} UNLOCKED!`;
                    portalStepText.innerText = "Approach the Glowing Portal at the end of the level to enter!";
                    enterPortalBtn.style.display = 'inline-block';
                    enterPortalBtn.innerText = "👍 CONTINUE PLAYING";
                    enterPortalBtn.onclick = () => { portalOverlay.style.display = 'none'; gamePaused = false; if (onComplete) onComplete(); };
                }, 3200);
            }

            // ============================================================
            // FINAL VICTORY SCREEN
            // ============================================================
            // ============================================================
            // SAVE SCORE TO DATABASE
            // ============================================================
            function saveGameStats(won, rankLabel) {
                try {
                    fetch('save_score.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            score: debugScore,
                            tokens: codeTokens,
                            fragments: syntaxFragments,
                            questions: totalQuestionsAnswered,
                            errors: totalErrorsFixed,
                            level: currentLevel,
                            won: won,
                            rank: rankLabel || ''
                        })
                    }).catch(function () { /* ignore network errors */ });
                } catch (e) { /* ignore */ }
            }

            function triggerFinalVictorySequence() {
                gamePaused = true; stopBGM(); soundLevelUp();
                const winOverlay = document.getElementById('winOverlay');
                const elapsedSec = Math.floor((Date.now() - startTimeMs) / 1000);

                document.getElementById('statLevels').innerText = "4/4";
                document.getElementById('statFragments').innerText = "4/4";
                document.getElementById('statTokens').innerText = codeTokens;
                document.getElementById('statQuestions').innerText = `${totalQuestionsAnswered}/7`;
                document.getElementById('statErrors').innerText = `${totalErrorsFixed}/14`;
                document.getElementById('statTime').innerText = `${String(Math.floor(elapsedSec / 60)).padStart(2, '0')}:${String(elapsedSec % 60).padStart(2, '0')}`;

                let rank = "JAVA MASTER";
                if (elapsedSec > 240) rank = "JAVA ROOKIE";
                else if (elapsedSec > 180) rank = "CODE EXPLORER";
                else if (elapsedSec > 120) rank = "DEBUGGING HERO";

                document.getElementById('finalRankSpan').innerText = rank;
                saveGameStats(true, rank);
                winOverlay.style.display = 'flex';
            }

            document.getElementById('playAgainWinBtn').onclick = () => location.reload();
            document.getElementById('menuWinBtn').onclick = () => window.location.href = 'buttons.php';

            // ============================================================
            // PLATFORM ENGINE (SUPER MARIO MOVEMENT + STOMP + HEAD BUMP)
            // ============================================================
            const keys = { left: false, right: false, up: false, upPressed: false };

            window.addEventListener('keydown', (e) => {
                if (['ArrowLeft', 'ArrowRight', 'ArrowUp', ' ', 'ArrowDown'].includes(e.key)) e.preventDefault();
                if (e.key === 'ArrowLeft') keys.left = true;
                if (e.key === 'ArrowRight') keys.right = true;
                if (e.key === 'ArrowUp' || e.key === ' ') { if (!keys.up) { keys.up = true; keys.upPressed = true; } }
            });

            window.addEventListener('keyup', (e) => {
                if (e.key === 'ArrowLeft') keys.left = false;
                if (e.key === 'ArrowRight') keys.right = false;
                if (e.key === 'ArrowUp' || e.key === ' ') { keys.up = false; keys.upPressed = false; }
            });

            function rectCollide(r1, r2) {
                return !(r2.x >= r1.x + r1.w || r2.x + r2.w <= r1.x || r2.y >= r1.y + r1.h || r2.y + r2.h <= r1.y);
            }

            function handlePlatformCollisions() {
                player.grounded = false;

                // 1. Platforms
                for (let pl of platforms) {
                    if (rectCollide(player, pl)) {
                        let oL = player.x + player.w - pl.x, oR = pl.x + pl.w - player.x, oT = player.y + player.h - pl.y, oB = pl.y + pl.h - player.y;
                        if (Math.min(oL, oR) < Math.min(oT, oB)) {
                            if (oL < oR) player.x = pl.x - player.w; else player.x = pl.x + pl.w;
                            player.vx = 0;
                        } else {
                            if (oT < oB) { player.y = pl.y - player.h; player.vy = 0; player.grounded = true; }
                            else { player.y = pl.y + pl.h; player.vy = 0; }
                        }
                    }
                }

                // 2. Interactive Code Blocks (?) — Mario Bump Mechanics
                for (let q of questionBlocks) {
                    if (rectCollide(player, q)) {
                        let oT = player.y + player.h - q.y, oB = q.y + q.h - player.y;
                        if (player.vy <= 0 && oB < 15) {
                            // Head bump from below!
                            player.y = q.y + q.h;
                            player.vy = 0;
                            if (!q.hit) {
                                q.hit = true;
                                soundBlockHit();
                                if (q.type === 'token') {
                                    codeTokens += 5; debugScore += 50; updateHUD(); soundCoin();
                                } else if (q.type === 'tip') {
                                    triggerCodeBlockTip(q.tipText);
                                } else if (q.type === 'challenge') {
                                    triggerLevelChallenge();
                                }
                            }
                        } else if (player.vy > 0 && oT < 15) {
                            player.y = q.y - player.h; player.vy = 0; player.grounded = true;
                        }
                    }
                }

                if (player.x < 0) player.x = 0;
                if (player.x + player.w > WORLD_WIDTH) player.x = WORLD_WIDTH - player.w;
                if (player.y + player.h > CANVAS_H + 50) die();
            }

            function updateTokens() {
                for (let t of tokens) {
                    if (!t.collected && rectCollide(player, t)) {
                        t.collected = true;
                        codeTokens++;
                        debugScore += 10;
                        updateHUD();
                        soundCoin();
                    }
                }
            }

            function updateEnemies() {
                for (let g of goombas) {
                    if (!g.alive) continue;
                    g.x += g.vx;
                    if (g.x <= g.minX || g.x + g.w >= g.maxX) g.vx *= -1;

                    if (rectCollide(player, g)) {
                        // Mario Stomp mechanics
                        if (player.vy > 0 && player.y + player.h <= g.y + 12) {
                            g.alive = false; debugScore += 50; updateHUD();
                            player.vy = -7.5; player.grounded = false; soundStomp();
                        } else {
                            die();
                        }
                    }
                }
            }

            function updatePortal() {
                if (lockedPortal.active && rectCollide(player, lockedPortal)) {
                    if (challengeCompletedForLevel) {
                        if (currentLevel < 4) {
                            soundLevelUp();
                            loadLevel(currentLevel + 1);
                        } else {
                            triggerFinalVictorySequence();
                        }
                    }
                }
            }

            function die() {
                lives--; updateHUD(); soundDie();
if (lives <= 0) {
                    gamePaused = true;
                    saveGameStats(false, '');
                    document.getElementById('gameoverTitle').innerText = "GAME OVER";
                    document.getElementById('gameoverMsg').innerText = "Byte ran out of lives!";
                    document.getElementById('gameoverOverlay').style.display = 'flex';
                } else {
                    player.x = 150; player.y = 280; player.vx = 0; player.vy = 0;
                }
            }

            function updateCamera() {
                let t = player.x + player.w / 2 - CANVAS_W / 2;
                cameraX = Math.max(0, Math.min(t, WORLD_WIDTH - CANVAS_W));
            }

            function update() {
                if (gamePaused) return;

                const ACC = 0.75;
                if (keys.left) player.vx -= ACC;
                else if (keys.right) player.vx += ACC;
                else player.vx *= 0.7;

                const MAX = 5.5;
                if (player.vx > MAX) player.vx = MAX;
                if (player.vx < -MAX) player.vx = -MAX;

                if (keys.upPressed && player.grounded) {
                    player.vy = -9.6; player.grounded = false; keys.upPressed = false; soundJump();
                }

                player.vy += 0.58;
                if (player.vy > 12) player.vy = 12;

                player.x += player.vx;
                player.y += player.vy;

                handlePlatformCollisions();
                updateTokens();
                updateEnemies();
                updatePortal();
                updateCamera();
            }

            function draw() {
                ctx.clearRect(0, 0, CANVAS_W, CANVAS_H);

                if (bgImage.complete && bgImage.naturalWidth > 0) {
                    ctx.drawImage(bgImage, 0, 0, CANVAS_W, CANVAS_H);
                } else {
                    ctx.fillStyle = '#0a2f44'; ctx.fillRect(0, 0, CANVAS_W, CANVAS_H);
                }

                ctx.save();
                ctx.translate(-cameraX, 0);

                // Platforms
                platforms.forEach(pl => {
                    ctx.fillStyle = pl.color || '#5b8c4e';
                    ctx.fillRect(pl.x, pl.y, pl.w, pl.h);
                });

                // Collectible Code Tokens (Mario Coins)
                tokens.forEach(t => {
                    if (t.collected) return;
                    ctx.fillStyle = '#ffd700'; ctx.beginPath();
                    ctx.arc(t.x + t.w / 2, t.y + t.h / 2, t.w / 2, 0, Math.PI * 2); ctx.fill();
                    ctx.fillStyle = '#b8860b'; ctx.font = 'bold 10px monospace';
                    ctx.fillText('🪙', t.x - 1, t.y + 11);
                });

                // Interactive Code Blocks (?)
                questionBlocks.forEach(q => {
                    if (q.hit) {
                        ctx.fillStyle = '#6c757d'; ctx.fillRect(q.x, q.y, q.w, q.h);
                        ctx.fillStyle = '#ffffff'; ctx.font = 'bold 16px monospace';
                        ctx.fillText('✓', q.x + 8, q.y + 22);
                    } else {
                        ctx.fillStyle = q.type === 'challenge' ? '#ffc107' : '#28a745';
                        ctx.fillRect(q.x, q.y, q.w, q.h);
                        ctx.fillStyle = '#ffffff'; ctx.font = 'bold 18px monospace';
                        ctx.fillText('?', q.x + 10, q.y + 23);
                    }
                });

                // Enemies (Buglings & Crawlers - Sprites Preserved)
                goombas.forEach(g => {
                    if (!g.alive) return;
                    const bSprite = g.vx >= 0 ? bugSpriteEast : bugSpriteWest;
                    const drawW = g.w * 2.2, drawH = g.h * 2.2;
                    if (bSprite.complete && bSprite.naturalWidth > 0) {
                        ctx.drawImage(bSprite, g.x - (drawW - g.w) / 2, g.y - (drawH - g.h), drawW, drawH);
                    } else {
                        ctx.fillStyle = '#e03a3a'; ctx.fillRect(g.x, g.y, g.w, g.h);
                    }
                });

                // Enemies (Buglings & Crawlers - Sprites Preserved)
                goombas.forEach(g => {
                    if (!g.alive) return;
                    const bSprite = g.vx >= 0 ? bugSpriteEast : bugSpriteWest;
                    const drawW = g.w * 2.2, drawH = g.h * 2.2;
                    if (bSprite.complete && bSprite.naturalWidth > 0) {
                        ctx.drawImage(bSprite, g.x - (drawW - g.w) / 2, g.y - (drawH - g.h), drawW, drawH);
                    } else {
                        ctx.fillStyle = '#e03a3a'; ctx.fillRect(g.x, g.y, g.w, g.h);
                    }
                });

                // Locked / Unlocked Portal
                if (lockedPortal.active) {
                    ctx.fillStyle = challengeCompletedForLevel ? '#4dff91' : '#17a2b8';
                    ctx.fillRect(lockedPortal.x, lockedPortal.y, lockedPortal.w, lockedPortal.h);
                    ctx.fillStyle = '#ffffff'; ctx.font = 'bold 24px monospace';
                    ctx.fillText(challengeCompletedForLevel ? '🌀' : '🔒', lockedPortal.x + 10, lockedPortal.y + 40);
                }

                // Byte Character (Sprites & Facing Direction Preserved)
                if (player.vx > 0.1) playerFacing = 'east';
                else if (player.vx < -0.1) playerFacing = 'west';

                let chosenSprite;
                if (!player.grounded) {
                    chosenSprite = playerFacing === 'east' ? playerJumpEast : playerJumpWest;
                } else if (Math.abs(player.vx) > 2) {
                    chosenSprite = playerFacing === 'east' ? playerRunEast : playerRunWest;
                } else {
                    chosenSprite = playerFacing === 'east' ? playerWalkEast : playerWalkWest;
                }

                const pW = player.w * 2.5, pH = player.h * 2.5;
                if (chosenSprite.complete && chosenSprite.naturalWidth > 0) {
                    ctx.drawImage(chosenSprite, player.x - (pW - player.w) / 2, player.y - (pH - player.h), pW, pH);
                } else {
                    ctx.fillStyle = player.color; ctx.fillRect(player.x, player.y, player.w, player.h);
                }

                ctx.restore();
            }

            function gameLoop() {
                update(); draw(); requestAnimationFrame(gameLoop);
            }

            // Settings buttons handlers
            document.getElementById('settingsBtn').onclick = () => { gamePaused = true; document.getElementById('settingsOverlay').style.display = 'flex'; };
            document.getElementById('settingsCloseBtn').onclick = () => { document.getElementById('settingsOverlay').style.display = 'none'; gamePaused = false; };
            document.getElementById('exitBtn').onclick = () => { gamePaused = true; document.getElementById('exitOverlay').style.display = 'flex'; };
            document.getElementById('exitCancelBtn').onclick = () => { document.getElementById('exitOverlay').style.display = 'none'; gamePaused = false; };
            document.getElementById('exitConfirmBtn').onclick = () => { window.location.href = 'buttons.php'; };

            // Launch Game Prologue & Engine Loop
            showPrologue();
            gameLoop();
        })();
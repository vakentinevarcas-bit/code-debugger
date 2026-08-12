<?php
session_start();
require_once __DIR__ . '/../db.php';

// ── Logout (destroy session) ──
if (isset($_GET['logout']) && $_GET['logout'] === '1') {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}

// ── Already logged in? Send to the game hub ──
if (isset($_SESSION['user_id'])) {
    header('Location: buttons.php');
    exit;
}

$error = '';
$success = '';

// ── Handle LOGIN ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            // Record online activity timestamp
            try {
                $pdo->prepare("UPDATE users SET last_seen = NOW() WHERE id = ?")->execute([$user['id']]);
            } catch (PDOException $e) {
                // last_seen column may not exist yet — ignore
            }

header('Location: buttons.php');
            exit;
        } elseif ($user) {
            $error = 'Incorrect password. Please try again.';
        } else {
            $error = 'No account found with this email. Please sign up.';
        }
    }
}

// ── Handle SIGNUP ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'signup') {
    $first = trim($_POST['first_name'] ?? '');
    $last = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if ($first === '' || $last === '' || $email === '' || $password === '' || $confirm === '') {
        $error = 'Please fill in all fields.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = 'This email is already registered. Please log in.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, 'user')");
            $stmt->execute([$first, $last, $email, password_hash($password, PASSWORD_DEFAULT)]);
            $success = 'Account created successfully! Please log in.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Code Quest · Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/user_log.css">
</head>
<body>

    <div class="mario-bg">
        <div class="cloud cloud-1"></div>
        <div class="cloud cloud-2"></div>
        <div class="cloud cloud-3"></div>
        <div class="hill hill-1"></div>
        <div class="hill hill-2"></div>
        <div class="hill hill-3"></div>
        <div class="pipe pipe-1"></div>
        <div class="pipe pipe-2"></div>
        <div class="qblock qblock-1"></div>
        <div class="qblock qblock-2"></div>
        <div class="qblock qblock-3"></div>
        <div class="bush bush-1"></div>
        <div class="bush bush-2"></div>
        <div class="bush bush-3"></div>
        <div class="coin-sparkle coin-1">⭐</div>
        <div class="coin-sparkle coin-2">⭐</div>
        <div class="coin-sparkle coin-3">⭐</div>
        <span class="pixel-star" style="top:12%;left:15%;">✦</span>
        <span class="pixel-star" style="top:8%;right:25%;">✦</span>
        <span class="pixel-star" style="top:18%;left:50%;">✦</span>
        <span class="pixel-star" style="top:6%;left:75%;">✦</span>
        <span class="pixel-star" style="top:22%;right:10%;">✦</span>
        <div class="ground">
            <div class="grass" style="left:10%;"></div>
            <div class="grass" style="left:25%;"></div>
            <div class="grass" style="left:45%;"></div>
            <div class="grass" style="left:65%;"></div>
            <div class="grass" style="left:85%;"></div>
        </div>
        <div class="mario-runner">🍄</div>
        <div class="luigi-runner">🐢</div>
    </div>

    <div class="form-container" id="app">
        <div class="logo">
            <span>🎮</span>
            <div>
                <h1>Code Quest</h1>
                <small>Debugging Survival</small>
            </div>
        </div>

<!-- LOGIN FORM -->
        <div class="login-form-wrapper" id="loginWrapper">
            <?php if ($error): ?>
                <div class="msg error" style="display:block;margin-bottom:1rem;"><?php echo htmlspecialchars($error); ?></div>
            <?php elseif ($success): ?>
                <div class="msg success" style="display:block;margin-bottom:1rem;"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <form class="form" method="POST" action="">
                <input type="hidden" name="form" value="login" />
                <div class="field">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="your@email.com" required />
                </div>
                <div class="field">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="••••••••" required />
                </div>
                <button type="submit" class="btn-submit">🚀 Log In</button>
                <div class="extra-link">
                    Don't have an account? <a onclick="showSignup()">Sign Up</a>
                </div>
            </form>
        </div>

        <!-- SIGNUP FORM (hidden by default) -->
        <div class="signup-form-wrapper" id="signupWrapper">
            <form class="form" method="POST" action="">
                <input type="hidden" name="form" value="signup" />
                <div class="field-row">
                    <div class="field">
                        <label>First Name</label>
                        <input type="text" name="first_name" placeholder="John" required />
                    </div>
                    <div class="field">
                        <label>Last Name</label>
                        <input type="text" name="last_name" placeholder="Doe" required />
                    </div>
                </div>
                <div class="field">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="your@email.com" required />
                </div>
                <div class="field">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Min 6 characters" required minlength="6" />
                </div>
                <div class="field">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm" placeholder="Confirm password" required />
                </div>
                <button type="submit" class="btn-submit">🎮 Create Account</button>
                <div class="back-link">
                    <a onclick="showLogin()">← Back to Login</a>
                </div>
            </form>
        </div>
    </div>

    <div class="toast-container" id="toastContainer"></div>

    <script src="../js/user_log.js"></script>

</body>
</html>
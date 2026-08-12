<?php
session_start();
require_once __DIR__ . '/../db.php';

if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'admin') {
    header('Location: admin.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
        $error = 'Please fill in both fields.';
    } else {
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, password, role FROM admin WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            header('Location: admin.php');
            exit;
        } elseif ($user) {
            $error = 'Incorrect password. Please try again.';
        } else {
            $error = 'No admin account found with this email.';
        }
    }
}

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
            $error = 'This email is already registered.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, 'user')");
            $stmt->execute([$first, $last, $email, password_hash($password, PASSWORD_DEFAULT)]);
            $successMsg = 'Account created successfully! Please sign in.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin · Sign In / Sign Up</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link rel="stylesheet" href="../css/admin_login.css">
</head>
<body>

    <div class="auth-container">

        <div class="auth-header">
            <div class="icon-wrapper">
                <i class="fas fa-user-cog"></i>
            </div>
            <h1 id="formTitle">Welcome Back</h1>
            <p class="subtitle" id="formSubtitle">Sign in to your admin account</p>
        </div>

        <div class="form-wrapper">

            <form class="auth-form" id="signinForm" method="POST" action="" novalidate>
                <input type="hidden" name="form" value="login" />
                <div class="input-group">
                    <i class="fas fa-envelope input-icon"></i>
                    <input
                    type="email"
                    id="signinEmail"
                    name="email"
                    placeholder="admin@codequest.com"
                    autocomplete="email"
                    required
                    />
                </div>
                <div class="input-group">
                    <i class="fas fa-lock input-icon"></i>
                    <input
                    type="password"
                    id="signinPassword"
                    name="password"
                    placeholder="••••••••"
                    autocomplete="current-password"
                    required
                    />
                    <button
                    type="button"
                    class="toggle-password"
                    data-target="signinPassword"
                    aria-label="Toggle password visibility"
                    >
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            <div class="error-msg" id="signinError">
                <i class="fas fa-exclamation-circle"></i>
                <span id="signinErrorText">Invalid credentials.</span>
            </div>

            <div class="options-row">
                <label class="remember-me">
                    <input type="checkbox" id="rememberMe" />
                    Remember me
                </label>
                <a href="#" class="forgot-link">Forgot password?</a>
            </div>

            <button type="submit" class="btn-submit">
                <span>Sign In</span>
                <i class="fas fa-arrow-right"></i>
            </button>
        </form>
        <form class="auth-form hidden" id="signupForm" method="POST" action="" novalidate>
            <input type="hidden" name="form" value="signup" />
            <div class="input-group">
                <i class="fas fa-user input-icon"></i>
                <input
                type="text"
                id="signupName"
                name="first_name"
                placeholder="First name"
                autocomplete="name"
                required
                />
            </div>

            <div class="input-group">
                <i class="fas fa-user input-icon"></i>
                <input
                type="text"
                id="signupLastName"
                name="last_name"
                placeholder="Last name"
                autocomplete="family-name"
                required
                />
            </div>

            <div class="input-group">
                <i class="fas fa-envelope input-icon"></i>
                <input
                type="email"
                id="signupEmail"
                name="email"
                placeholder="Your email"
                autocomplete="email"
                required
                />
            </div>

            <div class="input-group">
                <i class="fas fa-lock input-icon"></i>
                <input
                type="password"
                id="signupPassword"
                name="password"
                placeholder="Min. 6 characters"
                autocomplete="new-password"
                required
                />
                <button
                type="button"
                class="toggle-password"
                data-target="signupPassword"
                aria-label="Toggle password visibility"
                >
                <i class="fas fa-eye"></i>
            </button>
        </div>

        <div class="input-group">
            <i class="fas fa-check-circle input-icon"></i>
            <input
            type="password"
            id="signupConfirm"
            name="confirm"
            placeholder="Confirm password"
            autocomplete="new-password"
            required
            />
        </div>

        <div class="terms-row">
            <input type="checkbox" id="termsCheck" required />
            <label for="termsCheck">
                I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>.
            </label>
        </div>

        <button type="submit" class="btn-submit">
            <span>Create Account</span>
            <i class="fas fa-user-plus"></i>
        </button>
    </form>

</div>

<?php if ($error): ?>
    <div class="server-msg" style="margin-top:1.2rem;"><?php echo htmlspecialchars($error); ?></div>
<?php elseif (isset($successMsg)): ?>
    <div class="server-msg success" style="margin-top:1.2rem;"><?php echo htmlspecialchars($successMsg); ?></div>
<?php endif; ?>

<div class="auth-switch" id="authSwitch">
    <span id="switchText">Don't have an account? <a id="switchLink">Sign Up</a></span>
</div>

<div class="auth-footer">
    <span>Secure · Admin Panel v3.0</span>
</div>

</div>

<script src="../js/admin_login.js">
   
</script>

</body>
</html>


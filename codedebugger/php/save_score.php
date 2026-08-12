<?php
session_start();
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    $data = $_POST;
}

$userId = (int) $_SESSION['user_id'];

$score     = max(0, (int) ($data['score'] ?? 0));
$tokens    = max(0, (int) ($data['tokens'] ?? 0));
$fragments = max(0, (int) ($data['fragments'] ?? 0));
$questions = max(0, (int) ($data['questions'] ?? 0));
$errors    = max(0, (int) ($data['errors'] ?? 0));
$level     = max(0, (int) ($data['level'] ?? 0));
$won       = !empty($data['won']) ? 1 : 0;
$rank      = trim((string) ($data['rank'] ?? ''));
if ($rank === '') {
    $rank = 'PLAYER';
}

try {
    $stmt = $pdo->prepare(
        "INSERT INTO game_scores (user_id, score, tokens, fragments, questions, errors, level, won, rank_label)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([$userId, $score, $tokens, $fragments, $questions, $errors, $level, $won, $rank]);

    $best = $pdo->prepare("SELECT MAX(score) AS best FROM game_scores WHERE user_id = ?");
    $best->execute([$userId]);
    $bestScore = (int) $best->fetchColumn();

    $upd = $pdo->prepare("UPDATE users SET score = ? WHERE id = ?");
    $upd->execute([$bestScore, $userId]);

    echo json_encode(['success' => true, 'score' => $score, 'best_score' => $bestScore]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Could not save score']);
}

<?php
session_start();
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$payload = null;
$token = $_POST['csrfToken'] ?? null;

if ($token === null) {
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true);
    if (!is_array($payload)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request payload']);
        exit();
    }
    $token = $payload['csrfToken'] ?? null;
}

if (!is_string($token) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit();
}

$pdfBytes = null;
if (isset($_FILES['report_pdf']) && is_array($_FILES['report_pdf'])) {
    if (($_FILES['report_pdf']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'PDF upload failed']);
        exit();
    }

    $tmpPath = $_FILES['report_pdf']['tmp_name'] ?? '';
    if (!is_string($tmpPath) || $tmpPath === '' || !is_uploaded_file($tmpPath)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid uploaded PDF file']);
        exit();
    }

    $pdfBytes = file_get_contents($tmpPath);
} elseif (is_array($payload)) {
    $pdfDataUri = $payload['pdfDataUri'] ?? '';
    if (!is_string($pdfDataUri) || strpos($pdfDataUri, 'data:application/pdf;base64,') !== 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Expected a PDF payload']);
        exit();
    }

    $base64 = substr($pdfDataUri, strlen('data:application/pdf;base64,'));
    $pdfBytes = base64_decode($base64, true);
}

if (!is_string($pdfBytes) || $pdfBytes === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid PDF payload']);
    exit();
}

$exportsDir = __DIR__ . '/exports';
if (!is_dir($exportsDir) && !@mkdir($exportsDir, 0775, true)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to create export directory']);
    exit();
}

if (!is_writable($exportsDir)) {
    http_response_code(500);
    echo json_encode(['error' => 'Export directory is not writable by the web server']);
    exit();
}

$username = preg_replace('/[^a-zA-Z0-9_-]/', '', $_SESSION['username'] ?? 'user');
$filename = sprintf('report_%s_%s.pdf', $username, date('Ymd_His'));
$filePath = $exportsDir . '/' . $filename;

if (file_put_contents($filePath, $pdfBytes) === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save PDF']);
    exit();
}

$url = '/exports/' . $filename;
echo json_encode(['url' => $url]);

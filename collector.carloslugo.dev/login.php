<?php
session_start();

$host = 'localhost';
$db   = 'cse135_analytics';
$user = 'analytics_user';
$pass = 'cse135pw';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("SELECT id, username, password_hash, role, permissions FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($userData && password_verify($password, $userData['password_hash'])) {
            $allowedRoles = ['superadmin', 'analyst', 'viewer'];
            if (!in_array($userData['role'], $allowedRoles, true)) {
                $error = "Account role is invalid. Contact a superadmin.";
            } else {
                session_regenerate_id(true);
                $_SESSION['logged_in'] = true;
                $_SESSION['user_id'] = (int)$userData['id'];
                $_SESSION['username'] = $userData['username'];
                $_SESSION['role'] = $userData['role'];
                if ($userData['role'] === 'superadmin') {
                    $_SESSION['permissions'] = 'all';
                } elseif ($userData['role'] === 'viewer') {
                    $_SESSION['permissions'] = 'saved_reports';
                } else {
                    $_SESSION['permissions'] = $userData['permissions'] ?: 'none';
                }
                header("Location: /dashboard.php");
                exit();
            }
        } else {
            $error = "Invalid username or password.";
        }
    } catch (PDOException $e) {
        $error = "System error. Please try again later.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Wrecked Tech | Secure Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-md w-96">
        <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">Analytics Portal</h2>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 text-sm">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/login.php">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="username">Username</label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="username" type="text" name="username" required>
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="password">Password</label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline" id="password" type="password" name="password" required>
            </div>
            <div class="flex items-center justify-between">
                <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full" type="submit">
                    Sign In
                </button>
            </div>
        </form>
    </div>
</body>
</html>

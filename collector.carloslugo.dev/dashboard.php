<?php
session_start();

// 1. Forceful Browsing & Canonical Redirect
if (!empty($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] !== '/') {
    header('Location: /dashboard.php', true, 302);
    exit();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: /login.php");
    exit();
}

// 2. Database Configuration
$host = 'localhost';
$db = 'cse135_analytics';
$user = 'analytics_user';
$pass = 'cse135pw';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed.");
}

// 3. User Context & Permissions
$role = $_SESSION['role'] ?? 'viewer';
$username = $_SESSION['username'] ?? 'guest';
$permissionsRaw = $_SESSION['permissions'] ?? 'none';
$userId = (int)($_SESSION['user_id'] ?? 0);
$allowedRoles = ['superadmin', 'analyst', 'viewer'];

// 4. CSRF Token Management
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

if (!in_array($role, $allowedRoles, true)) {
    http_response_code(403);
    is_file(__DIR__ . '/403.html') ? readfile(__DIR__ . '/403.html') : die('Forbidden');
    exit();
}

// Helper Functions
function isValidCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}

$isSuperadmin = $role === 'superadmin';
$isAnalyst = $role === 'analyst';
$isViewer = $role === 'viewer';
$canSubmitComments = $isSuperadmin || $isAnalyst;

// System Metrics Access: Superadmin OR specific analyst accounts (Elijah/Damian)
$systemMetricsAnalysts = ['elijah', 'damian'];
$canViewSystemMetrics = $isSuperadmin || ($isAnalyst && in_array(strtolower($username), $systemMetricsAnalysts, true));

$flashMessage = '';
$flashError = '';

// 5. POST Request Handling (Comments & User Management)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
        $flashError = 'Session expired or invalid request token. Refresh and retry.';
    } else {
        // Handle Comments
        if (isset($_POST['new_comment']) && $canSubmitComments) {
            $category = in_array($_POST['category'], ['General', 'Performance', 'Security']) ? $_POST['category'] : 'General';
            $comment = trim($_POST['comment_text'] ?? '');
            if ($comment !== '') {
                $stmt = $pdo->prepare('INSERT INTO reports (author_id, category, analyst_comment) VALUES (?, ?, ?)');
                $stmt->execute([$userId, $category, $comment]);
                $flashMessage = 'Comment saved.';
            }
        }
        // Handle User Creation (Superadmin only)
        if (isset($_POST['create_user']) && $isSuperadmin) {
            $newUsername = trim($_POST['new_username'] ?? '');
            $newPassword = (string)($_POST['new_password'] ?? '');
            $newRole = $_POST['new_role'] ?? 'viewer';
            $newPerms = trim($_POST['new_permissions'] ?? 'none');
            
            if ($newUsername === '' || strlen($newPassword) < 8) {
                $flashError = 'Username required and password must be 8+ chars.';
            } else {
                try {
                    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                    $insert = $pdo->prepare('INSERT INTO users (username, password_hash, role, permissions) VALUES (?, ?, ?, ?)');
                    $insert->execute([$newUsername, $hash, $newRole, $newPerms]);
                    $flashMessage = "User '$newUsername' created.";
                } catch (PDOException $e) {
                    $flashError = 'Username may already exist.';
                }
            }
        }
    }
}

// 6. Data Fetching
// Traffic Breakdown
$chartQuery = $pdo->query('SELECT event_type, COUNT(*) AS count FROM tracking_data GROUP BY event_type ORDER BY count DESC');
$eventData = $chartQuery->fetchAll(PDO::FETCH_ASSOC);

// Trend Report (30 Day Window)
$trendQuery = $pdo->query(
    "SELECT DATE(FROM_UNIXTIME(CASE WHEN event_timestamp > 2000000000 THEN event_timestamp / 1000 ELSE event_timestamp END)) AS day, COUNT(*) AS count
     FROM tracking_data GROUP BY day ORDER BY day ASC LIMIT 30"
);
$trendData = $trendQuery->fetchAll(PDO::FETCH_ASSOC);

// Analyst Comments
$commentsQuery = $pdo->query('SELECT r.category, r.analyst_comment, r.created_at, u.username FROM reports r JOIN users u ON r.author_id = u.id ORDER BY r.created_at DESC');
$comments = $commentsQuery->fetchAll(PDO::FETCH_ASSOC);

// User List (Superadmin only)
$allUsers = $isSuperadmin ? $pdo->query('SELECT username, role, permissions FROM users ORDER BY role, username')->fetchAll(PDO::FETCH_ASSOC) : [];

// Data Processing for JS
$chartLabels = []; $chartCounts = [];
foreach ($eventData as $row) { $chartLabels[] = ucfirst($row['event_type']); $chartCounts[] = (int)$row['count']; }

$trendLabels = []; $trendCounts = [];
foreach ($trendData as $row) { $trendLabels[] = $row['day']; $trendCounts[] = (int)$row['count']; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Wrecked Tech | Executive Analytics</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</head>
<body class="bg-white text-black antialiased p-6 md:p-8">
    <noscript><div class="max-w-5xl mx-auto mb-4 border border-red-500 bg-red-50 p-3 text-sm">JavaScript Disabled: Charts/Export unavailable.</div></noscript>

    <div id="report-content" class="max-w-5xl mx-auto">
        <header class="border-b-2 border-black pb-4 mb-6 flex flex-col md:flex-row md:justify-between md:items-end">
            <div>
                <h1 class="text-3xl font-bold tracking-tighter">WRECKED TECH ANALYTICS</h1>
                <p class="text-sm mt-1">Session: <strong><?php echo htmlspecialchars($username); ?></strong> (<?php echo htmlspecialchars($role); ?>)</p>
            </div>
            <div class="flex gap-2 mt-4 md:mt-0">
                <button onclick="exportPDF()" class="border border-black px-4 py-2 hover:bg-gray-100 font-bold text-xs uppercase">Download PDF</button>
                <button onclick="exportAndSavePDF()" class="border border-black px-4 py-2 hover:bg-gray-100 font-bold text-xs uppercase">Save to Server</button>
                <a href="/logout.php" class="border border-black px-4 py-2 hover:bg-gray-100 font-bold text-xs uppercase">Logout</a>
            </div>
        </header>

        <div id="saved-report-link" class="mb-4 text-sm font-bold text-blue-600"></div>

        <?php if ($flashMessage): ?><div class="mb-4 border border-black bg-gray-50 p-3 text-sm"><?php echo htmlspecialchars($flashMessage); ?></div><?php endif; ?>
        <?php if ($flashError): ?><div class="mb-4 border border-red-500 bg-red-50 p-3 text-sm text-red-600"><?php echo htmlspecialchars($flashError); ?></div><?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <section class="border border-black p-4">
                <h2 class="text-lg font-bold uppercase mb-4 border-b border-black">Report 1: Traffic Breakdown</h2>
                <div class="h-64"><canvas id="trafficChart"></canvas></div>
            </section>

            <section class="border border-black p-4">
                <h2 class="text-lg font-bold uppercase mb-4 border-b border-black">Report 2: System Metrics</h2>
                <?php if ($canViewSystemMetrics): ?>
                    <div class="overflow-auto max-h-64 border border-gray-200">
                        <table class="w-full text-left text-sm tabular-nums">
                            <thead class="bg-gray-50 sticky top-0"><tr><th class="p-2 border-b border-black">Type</th><th class="p-2 border-b border-black">Total</th></tr></thead>
                            <tbody>
                                <?php foreach ($eventData as $row): ?>
                                    <tr class="border-b">
                                        <td class="p-2 uppercase"><?php echo htmlspecialchars($row['event_type']); ?></td>
                                        <td class="p-2"><?php echo htmlspecialchars($row['count']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="h-64 flex items-center justify-center text-gray-400 italic">Access Restricted to Designated Analysts</div>
                <?php endif; ?>
            </section>
        </div>

        <section class="border border-black p-4 mb-6">
            <h2 class="text-lg font-bold uppercase mb-4 border-b border-black">Report 3: Analyst Intelligence</h2>
            <?php if ($canSubmitComments): ?>
                <form method="POST" class="mb-4 flex flex-col md:flex-row gap-2 pb-4 border-b border-gray-200">
                    <input type="hidden" name="new_comment" value="1">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <select name="category" class="border border-black p-2 text-xs">
                        <option value="General">General</option><option value="Performance">Performance</option><option value="Security">Security</option>
                    </select>
                    <input type="text" name="comment_text" placeholder="Analyze this data..." class="border border-black p-2 text-xs flex-grow" required>
                    <button type="submit" class="bg-black text-white px-4 py-2 text-xs font-bold uppercase hover:bg-gray-800">Post</button>
                </form>
            <?php endif; ?>
            <div class="max-h-60 overflow-auto space-y-3">
                <?php foreach ($comments as $c): ?>
                    <div class="border-l-2 border-black pl-3 py-1">
                        <div class="text-[10px] uppercase text-gray-500 font-bold"><?php echo htmlspecialchars($c['username']); ?> | <?php echo htmlspecialchars($c['category']); ?> | <?php echo $c['created_at']; ?></div>
                        <p class="text-sm"><?php echo htmlspecialchars($c['analyst_comment']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <?php if (!$isViewer): ?>
            <section class="border border-black p-4 mb-6">
                <h2 class="text-lg font-bold uppercase mb-4 border-b border-black">Report 4: 30-Day Event Volume</h2>
                <div class="h-72"><canvas id="trendChart"></canvas></div>
            </section>
        <?php endif; ?>

        <?php if ($isSuperadmin): ?>
            <section class="border border-black p-4">
                <h2 class="text-lg font-bold uppercase mb-4 border-b border-black">Control: User Management</h2>
                <form method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-2 mb-4">
                    <input type="hidden" name="create_user" value="1">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input name="new_username" type="text" placeholder="User" class="border border-black p-2 text-xs" required>
                    <input name="new_password" type="password" placeholder="Pass (8+)" class="border border-black p-2 text-xs" required>
                    <select name="new_role" class="border border-black p-2 text-xs">
                        <option value="viewer">Viewer</option><option value="analyst">Analyst</option><option value="superadmin">Superadmin</option>
                    </select>
                    <button type="submit" class="bg-black text-white px-2 py-2 text-xs font-bold uppercase">Add User</button>
                </form>
            </section>
        <?php endif; ?>
    </div>

    <script>
        const canRenderChart = <?php echo $isViewer ? 'false' : 'true'; ?>;
        const chartLabels = <?php echo json_encode($chartLabels); ?>;
        const chartCounts = <?php echo json_encode($chartCounts); ?>;
        const trendLabels = <?php echo json_encode($trendLabels); ?>;
        const trendCounts = <?php echo json_encode($trendCounts); ?>;
        const csrfToken = <?php echo json_encode($csrfToken); ?>;

        // Chart Initialization Functions (Standardized for Re-rendering)
        function initTrafficChart(target) {
            const canvas = typeof target === 'string' ? document.getElementById(target) : target;
            if (!canvas) return;
            new Chart(canvas.getContext('2d'), {
                type: 'doughnut',
                data: { labels: chartLabels, datasets: [{ data: chartCounts, backgroundColor: ['#000','#333','#666','#999','#ccc'] }] },
                options: { responsive: true, maintainAspectRatio: false, animation: false, plugins: { legend: { position: 'right' } } }
            });
        }

        function initTrendChart(target) {
            const canvas = typeof target === 'string' ? document.getElementById(target) : target;
            if (!canvas) return;
            new Chart(canvas.getContext('2d'), {
                type: 'line',
                data: { labels: trendLabels, datasets: [{ label: 'Volume', data: trendCounts, borderColor: '#000', backgroundColor: 'rgba(0,0,0,0.05)', fill: true, tension: 0.1 }] },
                options: { responsive: true, maintainAspectRatio: false, animation: false, scales: { y: { beginAtZero: true } } }
            });
        }

        // Export Cloning Logic (Fixes Text Overlap & Chart Blackout)
        function buildExportClone() {
            const source = document.getElementById('report-content');
            const wrapper = document.createElement('div');
            const clone = source.cloneNode(true);

            wrapper.style.position = 'absolute'; wrapper.style.left = '-9999px'; wrapper.style.width = '800px'; wrapper.style.background = '#fff';
            document.body.appendChild(wrapper); wrapper.appendChild(clone);

            clone.style.width = '800px'; clone.style.display = 'block';
            clone.querySelectorAll('div, section, header').forEach(el => {
                el.style.display = 'block'; el.style.float = 'none'; el.style.width = '100%'; el.style.margin = '15px 0';
            });
            clone.querySelectorAll('p, h1, h2, h3, td, th, span').forEach(el => { el.style.lineHeight = '1.6'; el.style.letterSpacing = 'normal'; });

            if (canRenderChart) {
                const c1 = clone.querySelector('#trafficChart'); if (c1) { c1.style.height = '300px'; initTrafficChart(c1); }
                const c2 = clone.querySelector('#trendChart'); if (c2) { c2.style.height = '300px'; initTrendChart(c2); }
            }
            clone.querySelectorAll('button, a, form').forEach(el => el.remove());
            return { wrapper, clone };
        }

        async function exportPDF() {
            const { wrapper, clone } = buildExportClone();
            await new Promise(r => setTimeout(r, 200));
            try { await html2pdf().set({ margin: 0.5, filename: 'Report.pdf', image: { type: 'jpeg', quality: 1 }, html2canvas: { scale: 2, letterRendering: true }, jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' } }).from(clone).save(); } 
            finally { wrapper.remove(); }
        }

        async function exportAndSavePDF() {
            const status = document.getElementById('saved-report-link'); status.textContent = 'Processing...';
            const { wrapper, clone } = buildExportClone();
            await new Promise(r => setTimeout(r, 200));
            try {
                const worker = html2pdf().set({ margin: 0.5, image: { type: 'jpeg', quality: 0.9 }, html2canvas: { scale: 1.5 }, jsPDF: { format: 'letter' } }).from(clone).toPdf();
                const pdfBlob = await worker.get('pdf').then(pdf => pdf.output('blob'));
                const fd = new FormData(); fd.append('csrfToken', csrfToken); fd.append('report_pdf', pdfBlob);
                const res = await fetch('/save_report.php', { method: 'POST', body: fd });
                const json = await res.json();
                status.innerHTML = json.url ? `URL: <a class="underline" href="${json.url}" target="_blank">${json.url}</a>` : `Error: ${json.error}`;
            } catch (e) { status.textContent = 'Failed: ' + e.message; }
            finally { wrapper.remove(); }
        }

        window.onload = () => { if (canRenderChart) { initTrafficChart('trafficChart'); initTrendChart('trendChart'); } };
    </script>
</body>
</html>
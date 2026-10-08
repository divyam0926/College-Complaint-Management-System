<?php
include '../config/db.php';
session_start();


if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}


$category = isset($_GET['cat']) ? mysqli_real_escape_string($conn, $_GET['cat']) : '';


$query = "SELECT complaints.*, users.name, users.register_no, users.program 
          FROM complaints 
          JOIN users ON complaints.user_id = users.id";

if ($category != '') { $query .= " WHERE complaints.category = '$category'"; }
$query .= " ORDER BY complaints.created_at DESC";
$result = mysqli_query($conn, $query);


$stats_res = mysqli_query($conn, "SELECT COUNT(*) as total, 
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) as resolved FROM complaints");
$stats = mysqli_fetch_assoc($stats_res);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | College Grievance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');

        :root {
            --bg-main: #020617;
            --glass-bg: rgba(15,23,42,0.92);
            --glass-card: rgba(15,23,42,0.88);
            --accent: #6366f1;
            --accent-soft: rgba(99,102,241,0.25);
            --success: #22c55e;
            --warning: #facc15;
            --border-soft: rgba(148,163,184,0.45);
            --text-main: #e5e7eb;
            --text-muted: #9ca3af;
            --shadow-strong: 0 32px 100px rgba(15,23,42,0.95);
            --radius-xl: 32px;
        }

        * { box-sizing: border-box; }

        body {
            min-height: 100vh;
            font-family: 'Outfit', sans-serif;
            color: var(--text-main);
            background: radial-gradient(circle at top left, #1d4ed8 0, #020617 40%, #000 100%);
            overflow-x: hidden;
            position: relative;
        }

        /* Animated background */
        .bg-orbit {
            position: fixed;
            inset: -40%;
            background: conic-gradient(from 180deg at 10% 20%, #22d3ee, #6366f1, #a855f7, #f97316, #22d3ee);
            opacity: 0.38;
            filter: blur(55px);
            animation: orbit 22s linear infinite;
            z-index: -2;
        }

        @keyframes orbit {
            0% { transform: rotate(0deg) scale(1); }
            50% { transform: rotate(180deg) scale(1.06); }
            100% { transform: rotate(360deg) scale(1); }
        }

        .noise-overlay {
            position: fixed;
            inset: 0;
            background:
                radial-gradient(circle at 0 0, rgba(15,23,42,0.85), transparent 52%),
                radial-gradient(circle at 100% 100%, rgba(15,23,42,0.9), transparent 48%);
            mix-blend-mode: multiply;
            z-index: -1;
        }

        /* Sidebar */
        .sidebar {
            height: 100vh;
            background: var(--glass-bg);
            backdrop-filter: blur(25px);
            border-right: 1px solid var(--border-soft);
            position: fixed;
            width: 280px;
            padding: 32px 0;
            z-index: 1000;
            box-shadow: var(--shadow-strong);
            display: flex;
            flex-direction: column;
        }

        /* Logo Styling (updated to KJCMS style) */
        .logo-box {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding-bottom: 5px;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            background:
                radial-gradient(circle at 0 0, #22d3ee, transparent 65%),
                radial-gradient(circle at 100% 100%, #a855f7, #6366f1);
            color: #f9fafb;
            font-weight: 800;
            font-size: 1.15rem;
            box-shadow: 0 10px 26px rgba(79,70,229,0.9);
            position: relative;
            overflow: hidden;
        }

        .logo-icon::after {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 0 0, rgba(248,250,252,0.35), transparent 60%);
            mix-blend-mode: soft-light;
            opacity: 0.9;
        }

        .sidebar-brand {
            text-align: center;
            padding: 0 24px 32px;
            border-bottom: 1px solid var(--border-soft);
            margin-bottom: 24px;
        }

        .sidebar-brand h4 {
            font-weight: 800;
            font-size: 1.3rem;
            letter-spacing: -0.02em;
            background: linear-gradient(135deg, var(--accent), #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0;
        }

        .sidebar-nav {
            flex-grow: 1;
        }

        .sidebar-nav a {
            color: var(--text-muted);
            text-decoration: none;
            padding: 14px 28px;
            display: flex;
            align-items: center;
            gap: 16px;
            border-radius: 0 24px 24px 0;
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.22s ease-out;
            margin: 4px 8px;
            position: relative;
        }

        .sidebar-nav a:hover, .sidebar-nav a.active {
            color: var(--text-main);
            background: rgba(99,102,241,0.2);
            border-right: 3px solid var(--accent);
            transform: translateX(4px);
        }

        .sidebar-nav a i {
            width: 20px;
            font-size: 1.1rem;
        }

        .sidebar-footer {
            padding: 24px;
            border-top: 1px solid var(--border-soft);
            margin-top: auto;
        }

        .sidebar-logout {
            color: #f87171 !important;
            text-decoration: none;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            border-radius: 16px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: rgba(239, 68, 68, 0.05);
            border: 1px solid rgba(239, 68, 68, 0.1);
        }

        .sidebar-logout:hover {
            background: rgba(239, 68, 68, 0.15);
            border-color: rgba(239, 68, 68, 0.3);
            transform: translateY(-2px);
            color: #ef4444 !important;
        }

        .main-content {
            margin-left: 280px;
            padding: 40px;
            min-height: 100vh;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 36px;
        }

        .page-title {
            font-size: 2.2rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            margin: 0;
        }

        .page-date {
            font-size: 1rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .stats-grid {
            margin-bottom: 40px;
        }

        .stat-card {
            background: var(--glass-card);
            backdrop-filter: blur(20px);
            border-radius: var(--radius-xl);
            border: 1px solid var(--border-soft);
            padding: 28px;
            transition: all 0.3s cubic-bezier(0.19, 1, 0.22, 1);
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 32px 100px rgba(15,23,42,0.98);
            border-color: rgba(129,140,248,0.8);
        }

        .stat-icon {
            width: 64px;
            height: 64px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            margin-right: 20px;
            flex-shrink: 0;
        }

        .stat-primary .stat-icon { background: rgba(99,102,241,0.2); color: var(--accent); }
        .stat-warning .stat-icon { background: rgba(250,204,21,0.2); color: var(--warning); }
        .stat-success .stat-icon { background: rgba(34,197,94,0.2); color: var(--success); }

        .stat-label {
            font-size: 0.8rem;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: 600;
            margin-bottom: 4px;
        }

        .stat-value {
            font-size: 2.2rem;
            font-weight: 800;
            margin: 0;
            letter-spacing: -0.02em;
        }

        .table-container {
            background: var(--glass-bg);
            backdrop-filter: blur(25px);
            border-radius: var(--radius-xl);
            border: 1px solid var(--border-soft);
            padding: 32px;
            box-shadow: var(--shadow-strong);
            margin-bottom: 40px;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
        }

        .table-title {
            font-size: 1.4rem;
            font-weight: 700;
            margin: 0;
        }

        .table-filter {
            min-width: 160px;
        }

        .table-custom {
            background: rgba(15,23,42,0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid var(--border-soft);
            margin: 0;
        }

        .table-custom thead th {
            background: rgba(15,23,42,0.98);
            border: none;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.85rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 20px 16px;
            border-bottom: 1px solid var(--border-soft);
        }

        .table-custom tbody td {
            padding: 20px 16px;
            border-color: var(--border-soft);
            vertical-align: middle;
            font-size: 0.9rem;
        }

        .table-custom tbody tr {
            transition: background 0.2s ease-out;
        }

        .table-custom tbody tr:hover {
            background: rgba(99,102,241,0.1);
        }

        .category-badge {
            font-size: 0.75rem;
            font-weight: 700;
            padding: 8px 16px;
            border-radius: 999px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .badge-academic { background: rgba(99,102,241,0.2); color: var(--accent); border: 1px solid rgba(99,102,241,0.3); }
        .badge-hostel { background: rgba(139,92,246,0.2); color: #8b5cf6; border: 1px solid rgba(139,92,246,0.3); }
        .badge-transport { background: rgba(250,204,21,0.2); color: var(--warning); border: 1px solid rgba(250,204,21,0.3); }
        .badge-infrastructure { background: rgba(34,197,94,0.2); color: var(--success); border: 1px solid rgba(34,197,94,0.3); }
        .badge-others { background: rgba(148,163,184,0.2); color: var(--text-muted); border: 1px solid var(--border-soft); }

        .status-badge {
            font-size: 0.75rem;
            font-weight: 700;
            padding: 8px 16px;
            border-radius: 999px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .status-resolved { background: rgba(34,197,94,0.2); color: var(--success); border: 1px solid rgba(34,197,94,0.3); }
        .status-progress { background: rgba(250,204,21,0.2); color: var(--warning); border: 1px solid rgba(250,204,21,0.3); }
        .status-pending { background: rgba(148,163,184,0.2); color: var(--text-muted); border: 1px solid var(--border-soft); }

        .btn-manage {
            background: linear-gradient(135deg, var(--accent), #4f46e5);
            border: none;
            padding: 10px 20px;
            border-radius: 999px;
            font-weight: 600;
            font-size: 0.85rem;
            color: white;
            transition: all 0.22s ease-out;
            box-shadow: 0 8px 24px rgba(99,102,241,0.4);
        }

        .btn-manage:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(99,102,241,0.6);
            color: white;
        }
    </style>
</head>
<body>

<div class="bg-orbit"></div>
<div class="noise-overlay"></div>

<div class="sidebar">
    <div class="sidebar-brand">
        <div class="logo-box">
            <div class="logo-icon">
                <span>K</span>
            </div>
            <h4>KJCMS Admin</h4>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="<?php echo ($category == '') ? 'active' : ''; ?>">
            <i class="fas fa-home"></i> Dashboard
        </a>
        <a href="dashboard.php?cat=Academic" class="<?php echo ($category == 'Academic') ? 'active' : ''; ?>">
            <i class="fas fa-book"></i> Academic
        </a>
        <a href="dashboard.php?cat=Hostel" class="<?php echo ($category == 'Hostel') ? 'active' : ''; ?>">
            <i class="fas fa-bed"></i> Hostel
        </a>
        <a href="dashboard.php?cat=Transport" class="<?php echo ($category == 'Transport') ? 'active' : ''; ?>">
            <i class="fas fa-bus"></i> Transport
        </a>
        <a href="dashboard.php?cat=Infrastructure" class="<?php echo ($category == 'Infrastructure') ? 'active' : ''; ?>">
            <i class="fas fa-tools"></i> Infrastructure
        </a>
        <a href="dashboard.php?cat=Others" class="<?php echo ($category == 'Others') ? 'active' : ''; ?>">
            <i class="fas fa-question-circle"></i> Others
        </a>
    </nav>
    
    <div class="sidebar-footer">
        <a href="../logout.php" class="sidebar-logout">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<div class="main-content">
    <div class="page-header">
        <h1 class="page-title">Welcome, Administrator</h1>
        <div class="page-date"><?php echo date('D, d M Y'); ?></div>
    </div>

    <div class="row stats-grid g-4 mb-5">
        <div class="col-md-4">
            <div class="stat-card stat-primary">
                <div class="d-flex align-items-center">
                    <div class="stat-icon"><i class="fas fa-clipboard-list"></i></div>
                    <div>
                        <div class="stat-label">Total Grievances</div>
                        <h2 class="stat-value"><?php echo $stats['total']; ?></h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card stat-warning">
                <div class="d-flex align-items-center">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <div>
                        <div class="stat-label">Pending Review</div>
                        <h2 class="stat-value"><?php echo $stats['pending'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card stat-success">
                <div class="d-flex align-items-center">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div>
                        <div class="stat-label">Resolved</div>
                        <h2 class="stat-value"><?php echo $stats['resolved'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="table-container">
        <div class="table-header">
            <h3 class="table-title">Recent Submissions (<?php echo $category ?: 'All'; ?>)</h3>
            <form method="GET" class="d-flex">
                <select name="cat" class="form-select bg-dark text-white border-secondary shadow-none" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    <option value="Academic" <?php if($category == 'Academic') echo 'selected'; ?>>Academic</option>
                    <option value="Hostel" <?php if($category == 'Hostel') echo 'selected'; ?>>Hostel</option>
                    <option value="Transport" <?php if($category == 'Transport') echo 'selected'; ?>>Transport</option>
                    <option value="Infrastructure" <?php if($category == 'Infrastructure') echo 'selected'; ?>>Infrastructure</option>
                    <option value="Others" <?php if($category == 'Others') echo 'selected'; ?>>Others</option>
                </select>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-custom text-white">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Register No</th>
                        <th>Program</th>
                        <th>Category</th>
                        <th>Complaint Detail</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><strong><?php echo $row['name']; ?></strong></td>
                        <td><code><?php echo $row['register_no']; ?></code></td>
                        <td><small><?php echo $row['program']; ?></small></td>
                        <td>
                            <span class="category-badge badge-<?php echo strtolower($row['category']); ?>">
                                <?php echo $row['category']; ?>
                            </span>
                        </td>
                        <td><small class="text-muted"><?php echo substr($row['description'], 0, 50); ?>...</small></td>
                        <td>
                            <span class="status-badge 
                                <?php echo $row['status'] == 'Resolved' ? 'status-resolved' : 
                                    ($row['status'] == 'In Progress' ? 'status-progress' : 'status-pending'); ?>">
                                <?php echo $row['status']; ?>
                            </span>
                        </td>
                        <td>
                            <a href="reply_complaint.php?id=<?php echo $row['id']; ?>" class="btn btn-manage">
                                <i class="fas fa-cog me-1"></i> Manage
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>

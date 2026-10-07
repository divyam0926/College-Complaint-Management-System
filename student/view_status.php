<?php
include '../config/db.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

$uid = $_SESSION['user_id'];

$stats_res = mysqli_query($conn, "SELECT 
    COUNT(*) as total, 
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) as resolved
    FROM complaints WHERE user_id = '$uid'");
$st = mysqli_fetch_assoc($stats_res);

$query = "SELECT * FROM complaints WHERE user_id = '$uid' ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Grievance Board | CCMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');

        :root {
            --bg-main: #020617;
            --glass-bg: rgba(15,23,42,0.85);
            --glass-card: rgba(15,23,42,0.92);
            --accent: #6366f1;
            --accent-soft: rgba(99,102,241,0.25);
            --success: #22c55e;
            --warning: #facc15;
            --border-soft: rgba(148,163,184,0.45);
            --text-main: #e5e7eb;
            --text-muted: #9ca3af;
            --shadow-strong: 0 20px 50px rgba(15,23,42,0.9);
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

        /* Hero section */
        .hero-section {
            background: radial-gradient(circle at top left, rgba(99,102,241,0.45), rgba(15,23,42,0.98));
            border-radius: 0 0 var(--radius-xl) var(--radius-xl);
            padding: 48px 0 80px;
            color: var(--text-main);
            position: relative;
            border-bottom: 1px solid var(--border-soft);
            backdrop-filter: blur(20px);
        }

        .hero-section::before {
            content: "";
            position: absolute;
            inset: -30%;
            background: radial-gradient(circle at 0 0, rgba(248,250,252,0.25), transparent 60%);
            mix-blend-mode: soft-light;
            opacity: 0.7;
        }

        .btn-back-header {
            position: absolute;
            top: 24px;
            left: 24px;
            background: rgba(15,23,42,0.9);
            color: var(--text-main);
            border: 1px solid var(--border-soft);
            padding: 10px 18px;
            border-radius: 20px;
            backdrop-filter: blur(15px);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.88rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.22s ease-out;
            box-shadow: 0 12px 32px rgba(15,23,42,0.8);
            z-index: 10;
        }

        .btn-back-header:hover {
            background: rgba(99,102,241,0.2);
            border-color: var(--accent);
            transform: translateY(-2px);
            box-shadow: 0 16px 40px rgba(99,102,241,0.4);
        }

        .hero-content {
            position: relative;
            z-index: 1;
            text-align: center;
        }

        .hero-title {
            font-size: 2.2rem;
            font-weight: 800;
            margin-bottom: 8px;
            letter-spacing: -0.02em;
        }

        .hero-subtitle {
            font-size: 1rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        /* Stats bar */
        .stats-bar {
            background: var(--glass-bg);
            backdrop-filter: blur(25px);
            border-radius: var(--radius-xl);
            margin-top: -48px;
            padding: 24px;
            border: 1px solid var(--border-soft);
            box-shadow: var(--shadow-strong);
            position: relative;
            z-index: 2;
        }

        .stats-item {
            position: relative;
            z-index: 1;
            text-align: center;
        }

        .stats-label {
            font-size: 0.7rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: 600;
        }

        .stats-value {
            font-size: 1.8rem;
            font-weight: 800;
            margin: 4px 0 0;
        }

        .stats-value.warning { color: var(--warning); }
        .stats-value.success { color: var(--success); }

        /* Cards grid */
        .vibrant-card-link {
            text-decoration: none !important;
            display: flex;
            height: 100%;
            width: 100%;
        }

        .vibrant-card {
            background: var(--glass-card);
            backdrop-filter: blur(20px);
            border-radius: var(--radius-xl);
            border: 1px solid var(--border-soft);
            height: 100%;
            width: 100%;
            transition: all 0.3s cubic-bezier(0.19, 1, 0.22, 1);
            overflow: hidden;
            box-shadow: var(--shadow-strong);
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .vibrant-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 24px 70px rgba(15,23,42,0.95);
            border-color: rgba(129,140,248,0.8);
        }

        .card-accent {
            height: 4px;
            border-radius: var(--radius-xl) var(--radius-xl) 0 0;
            width: 100%;
        }

        .accent-pending { background: linear-gradient(90deg, #6b7280, #9ca3af); }
        .accent-progress { background: linear-gradient(90deg, #facc15, #fbbf24); }
        .accent-resolved { background: linear-gradient(90deg, var(--success), #16a34a); }

        .card-body {
            padding: 18px 22px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .status-pill {
            font-size: 0.75rem;
            font-weight: 700;
            padding: 6px 16px;
            border-radius: 999px;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            display: inline-block;
            box-shadow: 0 8px 24px rgba(0,0,0,0.3);
        }

        .icon-box {
            width: 56px;
            height: 56px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            background: rgba(15,23,42,0.9);
            border: 1px solid var(--border-soft);
            backdrop-filter: blur(10px);
            flex-shrink: 0;
        }

        .category-label {
            font-size: 0.72rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: 600;
        }

        .complaint-id {
            font-size: 1rem;
            font-weight: 700;
            margin: 0;
            color: var(--text-main);
        }

        .complaint-preview {
            color: #d1d5db;
            font-size: 0.88rem;
            line-height: 1.5;
            margin: 12px 0;
            flex-grow: 1;
        }

        .btn-view-file {
            background: linear-gradient(135deg, var(--accent), #4f46e5);
            color: white;
            border-radius: 12px;
            padding: 8px 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: 600;
            font-size: 0.82rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(99,102,241,0.4);
            transition: all 0.2s ease-out;
            box-shadow: 0 8px 24px rgba(99,102,241,0.4);
        }

        .remark-area {
            background: rgba(239,68,68,0.15);
            border-radius: 16px;
            padding: 12px;
            border-left: 4px solid #ef4444;
            backdrop-filter: blur(10px);
            margin-bottom: 10px;
        }

        .remark-label { font-weight: 700; color: #fca5a5; font-size: 0.8rem; margin-bottom: 4px; display: block; }
        .remark-text { font-size: 0.82rem; color: #f87171; }

        .card-footer {
            padding-top: 16px;
            border-top: 1px solid rgba(148,163,184,0.3);
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .date-label { font-size: 0.78rem; color: var(--text-muted); }
        .view-detail { font-size: 0.8rem; font-weight: 600; color: var(--accent); }
    </style>
</head>
<body>

<div class="bg-orbit"></div>
<div class="noise-overlay"></div>

<div class="hero-section">
    <a href="dashboard.php" class="btn-back-header">
        <i class="fas fa-chevron-left"></i> Dashboard
    </a>
    <div class="container">
        <div class="hero-content">
            <h1 class="hero-title">COMPLAINTS HISTORY LOG</h1>
            <p class="hero-subtitle">Click to view detailed status and updates</p>
        </div>
    </div>
</div>

<div class="container">
    <div class="stats-bar mb-5">
        <div class="row">
            <div class="col-4 stats-item">
                <div class="stats-label">Total</div>
                <div class="stats-value"><?php echo $st['total']; ?></div>
            </div>
            <div class="col-4 stats-item">
                <div class="stats-label">Pending</div>
                <div class="stats-value warning"><?php echo $st['pending'] ?? 0; ?></div>
            </div>
            <div class="col-4 stats-item">
                <div class="stats-label">Resolved</div>
                <div class="stats-value success"><?php echo $st['resolved'] ?? 0; ?></div>
            </div>
        </div>
    </div>

    <div class="row g-4 pb-5">
        <?php if(mysqli_num_rows($result) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($result)): 
                $status = $row['status'];
                $accent = "accent-pending";
                $badge = "bg-secondary text-white";

                if($status == 'Resolved') {
                    $accent = "accent-resolved"; $badge = "bg-success text-white";
                } else if($status == 'In Progress') {
                    $accent = "accent-progress"; $badge = "bg-warning text-dark";
                }
            ?>
            <div class="col-12 col-md-6 col-lg-4 d-flex">
                <a href="view_complaint.php?id=<?php echo $row['id']; ?>" class="vibrant-card-link">
                    <div class="vibrant-card">
                        <div class="card-accent <?php echo $accent; ?>"></div>
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <span class="status-pill <?php echo $badge; ?>">
                                    <?php echo $status; ?>
                                </span>
                            </div>

                            <div class="d-flex align-items-center mb-3">
                                <div class="icon-box me-3">
                                    <i class="fas <?php echo $row['category'] == 'Academic' ? 'fa-book' : ($row['category'] == 'Hostel' ? 'fa-home' : 'fa-info-circle'); ?>"></i>
                                </div>
                                <div>
                                    <div class="category-label"><?php echo $row['category']; ?></div>
                                    <h6 class="complaint-id">Complaint #<?php echo $row['id']; ?></h6>
                                </div>
                            </div>

                            <div class="complaint-preview">
                                <?php echo $row['description']; ?>
                            </div>

                            <?php if(!empty($row['admin_remark'])): ?>
                                <div class="remark-area">
                                    <div class="remark-label">ADMIN REMARK:</div>
                                    <div class="remark-text"><?php echo $row['admin_remark']; ?></div>
                                </div>
                            <?php endif; ?>

                            <div class="card-footer">
                                <div class="date-label">
                                    <i class="far fa-clock me-1"></i>
                                    <?php echo date('d M, Y', strtotime($row['created_at'])); ?>
                                </div>
                                <div class="view-detail">
                                    View Detail <i class="fas fa-arrow-right ms-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <h4>No grievances found</h4>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
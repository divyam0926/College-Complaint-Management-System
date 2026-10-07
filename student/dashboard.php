<?php
include '../config/db.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}
$uid  = $_SESSION['user_id'];
$name = $_SESSION['user_name'];

$user_query  = "SELECT profile_pic, register_no, program FROM users WHERE id = '$uid'";
$user_result = mysqli_query($conn, $user_query);
$user_data   = mysqli_fetch_assoc($user_result);
$profile_pic = !empty($user_data['profile_pic']) ? $user_data['profile_pic'] : 'default_user.png';

$stats_query = "SELECT 
    COUNT(*) as total, 
    SUM(CASE WHEN status = 'Pending'  THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) as resolved
    FROM complaints WHERE user_id = '$uid'";
$stats_result = mysqli_query($conn, $stats_query);
$stats        = mysqli_fetch_assoc($stats_result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | Student Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;500;800&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg-main: #020617;
            --bg-elevated: rgba(15,23,42,0.9);
            --accent: #6366f1;
            --accent-soft: rgba(99,102,241,0.2);
            --accent-strong: #4f46e5;
            --success: #22c55e;
            --warning: #facc15;
            --danger: #ef4444;
            --text-main: #e5e7eb;
            --text-muted: #9ca3af;
            --border-soft: rgba(148,163,184,0.35);
            --shadow-soft: 0 24px 60px rgba(15,23,42,0.95);
            --radius-xl: 32px;
        }

        body {
            background: radial-gradient(circle at top left, #1d2333 0, #020617 45%, #000 100%);
            font-family: 'Outfit', sans-serif;
            color: var(--text-main);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Ambient blobs */
        .ambient-glow {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: -2;
        }

        .ambient-glow::before,
        .ambient-glow::after {
            content: "";
            position: absolute;
            width: 520px;
            height: 520px;
            border-radius: 999px;
            filter: blur(120px);
            opacity: 0.9;
        }

        .ambient-glow::before {
            top: -160px;
            left: -40px;
            background: radial-gradient(circle, rgba(96,165,250,0.7), transparent 70%);
        }

        .ambient-glow::after {
            bottom: -180px;
            right: -40px;
            background: radial-gradient(circle, rgba(244,114,182,0.7), transparent 70%);
        }

        .container-fluid {
            padding: 32px 48px 40px;
            max-width: 1320px;
        }

        /* Shell */
        .shell {
            background: linear-gradient(135deg, rgba(15,23,42,0.92), rgba(15,23,42,0.94));
            border-radius: 40px;
            border: 1px solid rgba(148,163,184,0.5);
            box-shadow: var(--shadow-soft);
            padding: 20px 22px 28px;
            backdrop-filter: blur(26px);
        }

        /* Top nav */
        .glass-nav {
            background: linear-gradient(135deg, rgba(15,23,42,0.85), rgba(15,23,42,0.96));
            border-radius: 26px;
            padding: 14px 22px;
            border: 1px solid rgba(148,163,184,0.45);
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 18px 40px rgba(15,23,42,0.9);
        }

        .brand-lockup {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-logo-pill {
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

        .brand-logo-pill::after {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 0 0, rgba(248,250,252,0.35), transparent 60%);
            mix-blend-mode: soft-light;
            opacity: 0.9;
        }

        .brand-title {
            font-weight: 800;
            font-size: 1.15rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            background: linear-gradient(120deg,#e5e7eb,#bfdbfe);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .chip {
            border-radius: 999px;
            padding: 6px 14px;
            font-size: 0.7rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            border: 1px solid rgba(129,140,248,0.9);
            color: #c7d2fe;
            background:
                radial-gradient(circle at 0 0, rgba(129,140,248,0.45), transparent 60%),
                radial-gradient(circle at 100% 100%, rgba(15,23,42,0.96), rgba(15,23,42,0.96));
            box-shadow: 0 10px 26px rgba(79,70,229,0.85);
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .user-meta {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            line-height: 1.1;
        }

        .user-meta span:first-child {
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            color: var(--text-muted);
        }

        .user-meta span:last-child {
            font-size: 0.85rem;
            color: #e5e7eb;
        }

        .btn-ghost-danger {
            border-radius: 999px;
            border: 1px solid rgba(248,113,113,0.7);
            color: #fecaca;
            font-size: 0.8rem;
            padding-inline: 16px;
            background: radial-gradient(circle at top left, rgba(248,113,113,0.18), rgba(15,23,42,0.85));
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.18s ease-out;
        }

        .btn-ghost-danger:hover {
            background: rgba(248,113,113,0.2);
            border-color: rgba(248,113,113,0.9);
            transform: translateY(-1px);
            color: #fee2e2;
        }

        /* Hero + stats layout */
        .hero-row {
            display: grid;
            grid-template-columns: minmax(0,3.1fr) minmax(0,2.3fr);
            gap: 20px;
            margin-top: 22px;
            margin-bottom: 16px;
        }

        /* Profile hero */
        .profile-hub {
            background: radial-gradient(circle at top left, rgba(56,189,248,0.16), rgba(15,23,42,0.96));
            border-radius: var(--radius-xl);
            padding: 28px 26px;
            border: 1px solid rgba(148,163,184,0.45);
            display: flex;
            align-items: center;
            gap: 18px;
            position: relative;
            overflow: hidden;
        }

        .profile-hub::after {
            content: "";
            position: absolute;
            inset: -12%;
            background-image: radial-gradient(circle at 0 0, rgba(248,250,252,0.16), transparent 55%);
            mix-blend-mode: soft-light;
            opacity: 0.65;
            pointer-events: none;
        }

        .profile-hub a {
            position: relative;
            display: inline-block;
            flex-shrink: 0;
            z-index: 1;
        }

        .profile-hub img {
            width: 90px;
            height: 90px;
            border-radius: 26px;
            object-fit: cover;
            border: 2px solid rgba(248,250,252,0.16);
            box-shadow: 0 18px 40px rgba(15,23,42,0.9);
            transition: transform 0.35s cubic-bezier(0.19, 1, 0.22, 1), box-shadow 0.35s ease-out, border-color 0.35s ease-out;
        }

        .profile-hub a:hover img {
            transform: translateY(-4px) scale(1.04);
            border-color: rgba(96,165,250,0.85);
            box-shadow: 0 24px 60px rgba(37,99,235,0.65);
        }

        .hero-copy {
            position: relative;
            z-index: 1;
            width: 100%;
        }

        .greeting-label {
            font-size: 0.75rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--accent);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .greeting-label::before {
            content: "";
            width: 6px;
            height: 6px;
            border-radius: 999px;
            background: var(--accent);
            box-shadow: 0 0 0 6px rgba(96,165,250,0.35);
        }

        .hero-title {
            margin: 6px 0 2px;
            font-size: 1.6rem;
            font-weight: 800;
            letter-spacing: 0.03em;
        }

        .hero-subtitle {
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .meta-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 14px;
        }

        .meta-pill {
            font-size: 0.75rem;
            padding: 6px 10px;
            border-radius: 999px;
            border: 1px solid rgba(148,163,184,0.5);
            color: #e5e7eb;
            background: radial-gradient(circle at top left, rgba(148,163,184,0.16), rgba(15,23,42,0.94));
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .meta-pill i {
            font-size: 0.8rem;
            opacity: 0.8;
        }

        /* Stats column */
        .stats-panel {
            background: radial-gradient(circle at top right, rgba(96,165,250,0.12), rgba(15,23,42,0.96));
            border-radius: var(--radius-xl);
            border: 1px solid rgba(148,163,184,0.5);
            padding: 20px 20px 16px;
            position: relative;
            overflow: hidden;
        }

        .stats-panel::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(79,70,229,0.22), transparent 55%);
            mix-blend-mode: soft-light;
            opacity: 0.7;
            pointer-events: none;
        }

        .stats-panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 1;
            margin-bottom: 14px;
        }

        .stats-title {
            font-size: 0.8rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--text-muted);
        }

        .stats-chip {
            font-size: 0.7rem;
            padding: 4px 10px;
            border-radius: 999px;
            background: rgba(15,23,42,0.92);
            border: 1px solid rgba(148,163,184,0.6);
            color: #e5e7eb;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .stats-chip i {
            color: var(--accent);
        }

        .stats-grid {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: repeat(3, minmax(0,1fr));
            gap: 10px;
        }

        .glass-card {
            background: rgba(15,23,42,0.88);
            border-radius: 22px;
            padding: 14px 14px 12px;
            border: 1px solid rgba(148,163,184,0.6);
            box-shadow: 0 18px 40px rgba(15,23,42,0.95);
            display: flex;
            flex-direction: column;
            gap: 4px;
            position: relative;
            overflow: hidden;
            transition: transform 0.22s ease-out, box-shadow 0.22s ease-out, border-color 0.22s ease-out, background 0.22s ease-out;
        }

        .glass-card::before {
            content: "";
            position: absolute;
            inset: -60%;
            background: radial-gradient(circle at 0 0, rgba(248,250,252,0.16), transparent 55%);
            opacity: 0;
            mix-blend-mode: soft-light;
            transition: opacity 0.25s ease-out, transform 0.25s ease-out;
        }

        .glass-card:hover {
            transform: translateY(-4px);
            border-color: rgba(129,140,248,0.95);
            background: rgba(15,23,42,0.96);
            box-shadow: 0 22px 56px rgba(15,23,42,0.98);
        }

        .glass-card:hover::before {
            opacity: 1;
            transform: translate3d(6px,-8px,0);
        }

        .glass-card span {
            font-size: 0.7rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--text-muted);
        }

        .glass-card h2 {
            font-weight: 800;
            font-size: 1.6rem;
            margin: 0;
        }

        .glass-card span.text-warning {
            color: #facc15 !important;
        }

        .glass-card span.text-success {
            color: #22c55e !important;
        }

        /* Controls row */
        .main-controls {
            display: grid;
            grid-template-columns: minmax(0,2.4fr) minmax(0,1.6fr);
            gap: 16px;
            margin-top: 12px;
        }

        .action-hub {
            background: radial-gradient(circle at top left, rgba(129,140,248,0.45), rgba(79,70,229,0.9));
            border-radius: var(--radius-xl);
            padding: 26px 26px 22px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            text-decoration: none;
            color: #f9fafb;
            border: 1px solid rgba(191,219,254,0.7);
            box-shadow: 0 28px 70px rgba(79,70,229,0.9);
            position: relative;
            overflow: hidden;
            transition: transform 0.22s ease-out, box-shadow 0.22s ease-out, border-color 0.22s ease-out, filter 0.22s ease-out;
        }

        .action-hub::before {
            content: "";
            position: absolute;
            inset: -40%;
            background: radial-gradient(circle at 0 0, rgba(248,250,252,0.26), transparent 60%);
            opacity: 0.8;
            mix-blend-mode: soft-light;
            pointer-events: none;
        }

        .action-hub:hover {
            transform: translateY(-4px) scale(1.01);
            filter: brightness(1.06);
            border-color: #e0f2fe;
            box-shadow: 0 32px 80px rgba(79,70,229,1);
        }

        .action-hub h2 {
            font-weight: 800;
            font-size: 1.35rem;
            margin-bottom: 4px;
        }

        .action-hub p {
            margin-bottom: 0;
            font-size: 0.9rem;
            color: #e0e7ff;
        }

        .action-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 18px;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.18em;
        }

        .action-footer .hint {
            opacity: 0.85;
        }

        .action-footer i {
            padding: 10px 11px;
            border-radius: 999px;
            background: rgba(15,23,42,0.22);
            border: 1px solid rgba(191,219,254,0.9);
            box-shadow: 0 18px 40px rgba(15,23,42,0.6);
        }

        .btn-history {
            background: radial-gradient(circle at top right, rgba(15,23,42,0.96), rgba(15,23,42,1));
            border-radius: var(--radius-xl);
            padding: 24px 22px 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            text-decoration: none;
            border: 1px solid rgba(148,163,184,0.7);
            box-shadow: 0 24px 60px rgba(15,23,42,0.95);
            color: var(--text-main);
            position: relative;
            overflow: hidden;
            transition: transform 0.2s ease-out, box-shadow 0.2s ease-out, border-color 0.2s ease-out, background 0.2s ease-out;
        }

        .btn-history::before {
            content: "";
            position: absolute;
            inset: -45%;
            background: radial-gradient(circle at 0 0, rgba(148,163,184,0.22), transparent 60%);
            opacity: 0.15;
            mix-blend-mode: soft-light;
            pointer-events: none;
        }

        .btn-history:hover {
            transform: translateY(-3px);
            border-color: rgba(129,140,248,0.95);
            background: radial-gradient(circle at top right, rgba(30,64,175,0.55), rgba(15,23,42,0.98));
            box-shadow: 0 28px 70px rgba(15,23,42,1);
        }

        .btn-history h2 {
            font-weight: 800;
            font-size: 1.25rem;
            margin-bottom: 4px;
            color: #e5e7eb;
        }

        .btn-history p {
            margin-bottom: 0;
            font-size: 0.88rem;
            color: var(--text-muted);
        }

        .btn-history-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 16px;
        }

        .btn-history-footer span {
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.16em;
            color: var(--text-muted);
        }

        .btn-history-footer i {
            color: #60a5fa;
            padding: 8px 9px;
            border-radius: 999px;
            background: rgba(15,23,42,0.8);
            border: 1px solid rgba(37,99,235,0.9);
            box-shadow: 0 16px 38px rgba(15,23,42,0.9);
        }

        /* Responsive tweaks */
        @media (max-width: 1199.98px) {
            .container-fluid {
                padding-inline: 24px;
            }
            .hero-row {
                grid-template-columns: minmax(0,1.5fr) minmax(0,1.2fr);
            }
        }

        @media (max-width: 991.98px) {
            .shell {
                padding-inline: 18px;
            }
            .glass-nav {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }
            .nav-right {
                justify-content: space-between;
            }
            .hero-row {
                grid-template-columns: minmax(0,1fr);
            }
            .stats-panel {
                order: -1;
            }
            .main-controls {
                grid-template-columns: minmax(0,1fr);
            }
        }

        @media (max-width: 575.98px) {
            .container-fluid {
                padding-inline: 16px;
                padding-top: 22px;
            }
            .shell {
                border-radius: 26px;
                padding-inline: 14px;
            }
            .glass-nav {
                border-radius: 18px;
            }
            .profile-hub {
                border-radius: 22px;
                padding: 18px 16px;
                flex-direction: column;
                align-items: flex-start;
            }
            .profile-hub img {
                width: 80px;
                height: 80px;
            }
            .stats-panel {
                border-radius: 22px;
            }
            .main-controls {
                gap: 12px;
            }
        }
    </style>
</head>
<body>

<div class="ambient-glow"></div>

<div class="container-fluid">
    <div class="shell">

        <!-- Top Navigation -->
        <nav class="glass-nav mb-3">
            <div class="brand-lockup">
                <div class="brand-logo-pill">
                    <span>K</span>
                </div>
                <div>
                    <div class="brand-title">KJCMS</div>
                    <div class="chip">KRISTU JAYANTI COMPLAINT MANAGEMENT</div>
                </div>
            </div>
            <div class="nav-right">
                <div class="user-meta">
                    <span>Student ID</span>
                    <span><?php echo $user_data['register_no']; ?></span>
                </div>
                <a href="../logout.php" class="btn btn-ghost btn-ghost-danger">
                    <i class="fa-solid fa-power-off"></i>
                    <span>Logout</span>
                </a>
            </div>
        </nav>

        <!-- Hero + Stats -->
        <div class="hero-row">

            <!-- Profile / Greeting -->
            <div class="profile-hub">
                <a href="profile.php">
                    <img src="../uploads/<?php echo $profile_pic; ?>" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo $name; ?>&background=random'">
                </a>
                <div class="hero-copy">
                    <div class="greeting-label">Welcome back</div>
                    <h1 class="hero-title">
                        Hello, <?php echo explode(' ', $name)[0]; ?>
                    </h1>
                    <p class="hero-subtitle">
                        <?php echo $user_data['program']; ?>
                    </p>
                    <div class="meta-pills">
                        <div class="meta-pill">
                            <i class="fa-regular fa-id-badge"></i>
                            <span><?php echo $user_data['register_no']; ?></span>
                        </div>
                        <div class="meta-pill">
                            <i class="fa-solid fa-shield-heart"></i>
                            <span>Campus grievance center</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-panel">
                <div class="stats-panel-header">
                    <div class="stats-title">Complaint overview</div>
                    <div class="stats-chip">
                        <i class="fa-regular fa-clock"></i>
                        <span>Real-time status</span>
                    </div>
                </div>
                <div class="stats-grid">
                    <div class="glass-card">
                        <span>Total cases</span>
                        <h2><?php echo $stats['total']; ?></h2>
                    </div>
                    <div class="glass-card">
                        <span class="text-warning">Pending</span>
                        <h2><?php echo $stats['pending'] ?? 0; ?></h2>
                    </div>
                    <div class="glass-card">
                        <span class="text-success">Resolved</span>
                        <h2><?php echo $stats['resolved'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Controls -->
        <div class="main-controls">
            <a href="submit_complaint.php" class="action-hub">
                <div>
                    <h2>REGISTER COMPLAINT</h2>
                    <p>Raise a campus issue and route it to the appropriate authority.</p>
                </div>
                <div class="action-footer">
                    <span class="hint">Takes less than a minute</span>
                    <i class="fas fa-arrow-right fa-lg"></i>
                </div>
            </a>
            <a href="view_status.php" class="btn-history">
                <div>
                    <h2>COMPLAINT HISTORY</h2>
                    <p>Review every complaints, track progress, and see how each recent updates.</p>
                </div>
                <div class="btn-history-footer">
                    <span>View activity log</span>
                    <i class="fas fa-stream"></i>
                </div>
            </a>
        </div>

    </div>
</div>

</body>
</html>

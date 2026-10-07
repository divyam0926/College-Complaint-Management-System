<?php
include '../config/db.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

$id = mysqli_real_escape_string($conn, $_GET['id']);
$uid = $_SESSION['user_id'];

$query = "SELECT * FROM complaints WHERE id = '$id' AND user_id = '$uid'";
$res = mysqli_query($conn, $query);
$complaint = mysqli_fetch_assoc($res);

if (!$complaint) {
    header("Location: view_status.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Complaint Details | CCMS</title>
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
            --shadow-strong: 0 32px 100px rgba(15,23,42,0.95);
            --radius-xl: 36px;
        }

        * { box-sizing: border-box; }

        body {
            min-height: 100vh;
            font-family: 'Outfit', sans-serif;
            color: var(--text-main);
            background: radial-gradient(circle at top left, #1d4ed8 0, #020617 40%, #000 100%);
            overflow-x: hidden;
            position: relative;
            padding: 40px 0;
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

        .container {
            max-width: 800px;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: rgba(15,23,42,0.9);
            color: var(--text-main);
            border: 1px solid var(--border-soft);
            padding: 12px 20px;
            border-radius: 24px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            backdrop-filter: blur(15px);
            box-shadow: 0 16px 40px rgba(15,23,42,0.8);
            transition: all 0.22s ease-out;
            margin-bottom: 32px;
        }

        .back-btn:hover {
            background: rgba(99,102,241,0.2);
            border-color: var(--accent);
            transform: translateY(-2px);
            box-shadow: 0 20px 50px rgba(99,102,241,0.4);
            color: var(--text-main);
        }

        .detail-card {
            background: var(--glass-card);
            backdrop-filter: blur(25px);
            border-radius: var(--radius-xl);
            border: 1px solid var(--border-soft);
            overflow: hidden;
            box-shadow: var(--shadow-strong);
            position: relative;
        }

        .detail-card::before {
            content: "";
            position: absolute;
            inset: -30%;
            background: radial-gradient(circle at top left, rgba(99,102,241,0.25), transparent 60%);
            mix-blend-mode: soft-light;
            opacity: 0.6;
        }

        .header-bar {
            height: 8px;
            background: linear-gradient(90deg, 
                <?php 
                    $status = $complaint['status'];
                    if($status == 'Resolved') echo '#22c55e, #16a34a';
                    elseif($status == 'In Progress') echo '#facc15, #fbbf24';
                    else echo '#6b7280, #9ca3af';
                ?>
            );
        }

        .card-content {
            padding: 36px;
            position: relative;
            z-index: 1;
        }

        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 32px;
            gap: 20px;
        }

        .complaint-header {
            font-size: 1.8rem;
            font-weight: 800;
            margin: 0;
            letter-spacing: -0.02em;
        }

        .status-tag {
            font-size: 0.78rem;
            font-weight: 700;
            padding: 10px 20px;
            border-radius: 999px;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            backdrop-filter: blur(15px);
            box-shadow: 0 12px 32px rgba(0,0,0,0.4);
            border: 1px solid rgba(255,255,255,0.2);
        }

        .status-resolved { background: rgba(34,197,94,0.2); color: var(--success); border-color: var(--success); }
        .status-progress { background: rgba(250,204,21,0.2); color: var(--warning); border-color: var(--warning); }
        .status-pending { background: rgba(107,114,128,0.2); color: #9ca3af; border-color: #9ca3af; }

        .section {
            margin-bottom: 28px;
        }

        .section-label {
            font-size: 0.75rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: 600;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-main);
            margin: 0;
        }

        .description-box {
            background: rgba(15,23,42,0.95);
            border-radius: 20px;
            padding: 24px;
            border: 1px solid var(--border-soft);
            backdrop-filter: blur(15px);
            line-height: 1.6;
            font-size: 0.95rem;
        }

        .evidence-section {
            text-align: center;
            background: rgba(15,23,42,0.95);
            border-radius: 24px;
            padding: 28px;
            border: 1px solid var(--border-soft);
            backdrop-filter: blur(15px);
            margin-top: 24px;
        }

        .evidence-image {
            max-width: 100%;
            max-height: 400px;
            border-radius: 20px;
            box-shadow: 0 24px 60px rgba(0,0,0,0.6);
            object-fit: cover;
        }

        .admin-remark {
            background: rgba(239,68,68,0.15);
            border-radius: 20px;
            padding: 24px;
            border-left: 5px solid #ef4444;
            backdrop-filter: blur(15px);
            margin-top: 24px;
        }

        .remark-header {
            font-weight: 700;
            color: #fca5a5;
            font-size: 1rem;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .remark-content {
            font-style: italic;
            color: #f87171;
            font-size: 0.95rem;
            line-height: 1.6;
            margin: 0;
        }

        .meta-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 24px;
            border-top: 1px solid var(--border-soft);
            margin-top: 32px;
            font-size: 0.85rem;
        }

        .date-info {
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container { padding: 0 20px; }
            .card-content { padding: 24px 20px; }
            .header-row { flex-direction: column; align-items: stretch; gap: 16px; }
            .complaint-header { font-size: 1.5rem; }
        }

        @media (max-width: 576px) {
            body { padding: 20px 0; }
            .detail-card { border-radius: 28px; }
            .evidence-image { max-height: 300px; }
        }
    </style>
</head>
<body>

<div class="bg-orbit"></div>
<div class="noise-overlay"></div>

<div class="container">
    <a href="view_status.php" class="back-btn">
        <i class="fas fa-arrow-left"></i>
        Back to Board
    </a>

    <div class="detail-card">
        <div class="header-bar"></div>
        <div class="card-content">
            <div class="header-row">
                <h1 class="complaint-header">Complaint #<?php echo $id; ?></h1>
                <span class="status-tag 
                    <?php 
                        echo $complaint['status'] == 'Resolved' ? 'status-resolved' : 
                            ($complaint['status'] == 'In Progress' ? 'status-progress' : 'status-pending'); 
                    ?>">
                    <?php echo $complaint['status']; ?>
                </span>
            </div>

            <div class="section">
                <label class="section-label">
                    <i class="fas <?php 
                        echo $complaint['category'] == 'Academic' ? 'fa-book' : 
                            ($complaint['category'] == 'Hostel' ? 'fa-home' : 
                            ($complaint['category'] == 'Transport' ? 'fa-bus' : 
                            ($complaint['category'] == 'Infrastructure' ? 'fa-building' : 'fa-info-circle')));
                    ?>"></i>
                    Category
                </label>
                <h3 class="section-value"><?php echo $complaint['category']; ?></h3>
            </div>

            <div class="section">
                <label class="section-label">
                    <i class="fas fa-align-left"></i>
                    Description
                </label>
                <div class="description-box"><?php echo nl2br($complaint['description']); ?></div>
            </div>

            <?php if(!empty($complaint['evidence_file'])): ?>
            <div class="evidence-section">
                <label class="section-label">
                    <i class="fas fa-paperclip"></i>
                    Evidence Provided
                </label>
                <a href="../uploads/<?php echo $complaint['evidence_file']; ?>" target="_blank">
                    <img src="../uploads/<?php echo $complaint['evidence_file']; ?>" 
                         class="evidence-image" 
                         alt="Evidence" 
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                    <div style="display:none; color:var(--text-muted); font-size:0.9rem; margin-top:20px;">
                        <i class="fas fa-file me-2"></i>
                        <?php echo pathinfo($complaint['evidence_file'], PATHINFO_FILENAME); ?>
                    </div>
                </a>
            </div>
            <?php endif; ?>

            <?php if(!empty($complaint['admin_remark'])): ?>
            <div class="admin-remark">
                <div class="remark-header">
                    <i class="fas fa-comment-dots"></i>
                    Admin Remark
                </div>
                <p class="remark-content">"<?php echo nl2br($complaint['admin_remark']); ?>"</p>
            </div>
            <?php endif; ?>

            <div class="meta-row">
                <div class="date-info">
                    <i class="far fa-clock"></i>
                    <?php echo date('d M, Y \a\t g:i A', strtotime($complaint['created_at'])); ?>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>

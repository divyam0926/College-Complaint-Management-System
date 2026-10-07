<?php
include '../config/db.php';
session_start();

$update_status = "";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

$res = mysqli_query($conn, "SELECT complaints.*, users.name, users.email, users.register_no, users.program 
                            FROM complaints 
                            JOIN users ON complaints.user_id = users.id 
                            WHERE complaints.id = '$id'");
$complaint = mysqli_fetch_assoc($res);

if (isset($_POST['update_status'])) {
    $status = $_POST['status'];
    $remark = mysqli_real_escape_string($conn, $_POST['admin_remark']);
    
    $update = "UPDATE complaints SET status='$status', admin_remark='$remark' WHERE id='$id'";
    if (mysqli_query($conn, $update)) {
        $update_status = "success";
    } else {
        $update_status = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Complaint | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --glass-bg: rgba(255, 255, 255, 0.25);
            --glass-border: rgba(255, 255, 255, 0.18);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #0f0f23 0%, #1a1a2e 50%, #16213e 100%);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(120, 219, 255, 0.2) 0%, transparent 50%);
            z-index: -1;
            animation: float 20s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            33% { transform: translateY(-20px) rotate(120deg); }
            66% { transform: translateY(10px) rotate(240deg); }
        }

        .main-container {
            padding: 2rem 0;
            backdrop-filter: blur(10px);
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 50px;
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.2);
            color: white;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(40px);
            border-radius: 30px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 
                0 25px 45px rgba(0,0,0,0.1),
                0 0 0 1px rgba(255, 255, 255, 0.05);
            overflow: hidden;
            position: relative;
        }

        .glass-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: var(--primary-gradient);
        }

        .details-section {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            padding: 3rem;
            position: relative;
            overflow: hidden;
        }

        .details-section::before {
            content: '';
            position: absolute;
            right: 0;
            top: 0;
            height: 100%;
            width: 1px;
            background: linear-gradient(to bottom, transparent, rgba(255,255,255,0.3), transparent);
        }

        .action-section {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(20px);
            padding: 3rem;
        }

        .category-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            background: var(--primary-gradient);
            color: white;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            backdrop-filter: blur(10px);
        }

        .complaint-header h3 {
            font-size: 2.5rem;
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
            letter-spacing: -1px;
        }

        .complaint-meta {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.1rem;
            font-weight: 400;
        }

        .section-label {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 1.5rem;
            display: block;
        }

        .student-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .student-info-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.25rem;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            transition: all 0.3s ease;
        }

        .student-info-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            background: rgba(255, 255, 255, 0.15);
        }

        .student-info-item i {
            width: 45px;
            height: 45px;
            background: var(--primary-gradient);
            color: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .issue-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            padding: 2.5rem;
            border: 1px solid rgba(255, 255, 255, 0.15);
            position: relative;
            overflow: hidden;
        }

        .issue-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--secondary-gradient);
        }

        .issue-content {
            color: rgba(255, 255, 255, 0.95);
            line-height: 1.7;
            font-size: 1.05rem;
        }

        .evidence-section {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.15);
        }

        .evidence-btn {
            background: var(--secondary-gradient);
            border: none;
            padding: 1rem 2rem;
            border-radius: 50px;
            color: white;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 10px 30px rgba(245, 87, 108, 0.4);
        }

        .evidence-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 40px rgba(245, 87, 108, 0.5);
            color: white;
        }

        .status-badge {
            background: var(--success-gradient);
            color: white;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: 0 10px 30px rgba(79, 172, 254, 0.4);
            backdrop-filter: blur(10px);
        }

        .action-header {
            color: white;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 2.5rem;
            position: relative;
        }

        .action-header::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 60px;
            height: 4px;
            background: var(--primary-gradient);
            border-radius: 2px;
        }

        .form-control, .form-select {
            background: rgba(255, 255, 255, 0.1) !important;
            backdrop-filter: blur(20px) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            border-radius: 20px !important;
            color: white !important;
            padding: 1.25rem !important;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.6) !important;
        }

        .form-control:focus, .form-select:focus {
            background: rgba(255, 255, 255, 0.15) !important;
            border-color: rgba(102, 126, 234, 0.5) !important;
            box-shadow: 0 0 0 0.5rem rgba(102, 126, 234, 0.1) !important;
            color: white !important;
            transform: translateY(-2px);
        }

        .form-label {
            color: rgba(255, 255, 255, 0.95);
            font-weight: 600;
            font-size: 1.05rem;
            margin-bottom: 1rem;
        }

        .btn-update {
            background: var(--primary-gradient);
            border: none;
            padding: 1.5rem;
            border-radius: 25px;
            font-weight: 700;
            font-size: 1.1rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-update:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 50px rgba(102, 126, 234, 0.6);
        }

        .btn-update:active {
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .student-info-grid {
                grid-template-columns: 1fr;
            }
            
            .details-section, .action-section {
                padding: 2rem 1.5rem;
            }
            
            .complaint-header h3 {
                font-size: 2rem;
            }
        }

        .floating-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }

        .shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            animation: float 15s infinite linear;
        }

        .shape:nth-child(1) { width: 80px; height: 80px; top: 20%; right: 10%; animation-delay: 0s; }
        .shape:nth-child(2) { width: 50px; height: 50px; top: 60%; left: 15%; animation-delay: 5s; }
        .shape:nth-child(3) { width: 120px; height: 120px; top: 80%; right: 20%; animation-delay: 10s; }
    </style>
</head>
<body>
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <div class="container py-5 main-container">
        <div class="mb-5">
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Back to Centralized Dashboard
            </a>
        </div>

        <div class="card glass-card">
            <div class="row g-0">
                <div class="col-md-6 details-section">
                    <div class="mb-4">
                        <span class="category-pill">
                            <i class="fas fa-tag"></i>
                            <?php echo $complaint['category']; ?>
                        </span>
                    </div>
                    
                    <div class="complaint-header mb-4">
                        <h3>Complaint #<?php echo $id; ?></h3>
                        <p class="complaint-meta mb-0">Submitted on <?php echo date('M d, Y', strtotime($complaint['created_at'])); ?></p>
                    </div>
                    
                    <span class="section-label">Student Profile</span>
                    <div class="student-info-grid">
                        <div class="student-info-item">
                            <i class="fas fa-user"></i>
                            <div>
                                <strong><?php echo $complaint['name']; ?></strong>
                            </div>
                        </div>
                        <div class="student-info-item">
                            <i class="fas fa-id-card"></i>
                            <div>Reg No: <code><?php echo $complaint['register_no']; ?></code></div>
                        </div>
                        <div class="student-info-item">
                            <i class="fas fa-graduation-cap"></i>
                            <div>Program: <?php echo $complaint['program']; ?></div>
                        </div>
                        <div class="student-info-item">
                            <i class="fas fa-envelope"></i>
                            <div class="text-muted"><?php echo $complaint['email']; ?></div>
                        </div>
                    </div>

                    <div class="issue-card">
                        <span class="section-label">Issue Description</span>
                        <div class="issue-content"><?php echo $complaint['description']; ?></div>

                        <?php if(!empty($complaint['evidence_file'])): ?>
                            <div class="evidence-section">
                                <span class="section-label">
                                    <i class="fas fa-paperclip"></i>
                                    Attached Evidence
                                </span>
                                <a href="../uploads/<?php echo $complaint['evidence_file']; ?>" target="_blank" class="evidence-btn">
                                    <i class="fas fa-eye"></i>
                                    View Attachment
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mt-4">
                        <span class="status-badge">
                            <i class="fas fa-info-circle"></i>
                            Current Status: <strong><?php echo $complaint['status']; ?></strong>
                        </span>
                    </div>
                </div>

                <div class="col-md-6 action-section">
                    <h4 class="action-header">Administrative Action</h4>
                    
                    <form method="POST" id="replyForm">
                        <div class="mb-5">
                            <label class="form-label">
                                <i class="fas fa-tasks"></i>
                                Set New Status
                            </label>
                            <select name="status" class="form-select form-select-lg">
                                <option value="Pending" <?php if($complaint['status'] == 'Pending') echo 'selected'; ?>>Pending (Under Review)</option>
                                <option value="In Progress" <?php if($complaint['status'] == 'In Progress') echo 'selected'; ?>>In Progress (Action Taken)</option>
                                <option value="Resolved" <?php if($complaint['status'] == 'Resolved') echo 'selected'; ?>>Resolved (Issue Closed)</option>
                            </select>
                        </div>

                        <div class="mb-5">
                            <label class="form-label">
                                <i class="fas fa-comment-dots"></i>
                                Official Remarks
                            </label>
                            <textarea name="admin_remark" class="form-control" rows="6" placeholder="Type the resolution details or remarks here..."><?php echo $complaint['admin_remark']; ?></textarea>
                        </div>

                        <button name="update_status" class="btn btn-primary btn-update w-100">
                            <i class="fas fa-save"></i>
                            Update Resolution
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php if($update_status == "success"): ?>
            Swal.fire({
                icon: 'success',
                title: 'Status Updated',
                text: 'Resolution has been successfully recorded.',
                confirmButtonColor: '#667eea',
                customClass: {
                    confirmButton: 'btn btn-lg px-5 py-3 rounded-25 shadow-lg'
                },
                buttonsStyling: false
            }).then(() => { window.location = 'dashboard.php'; });
        <?php elseif($update_status == "error"): ?>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Something went wrong. Please try again.',
                confirmButtonColor: '#f5576c',
                customClass: {
                    confirmButton: 'btn btn-lg px-5 py-3 rounded-25 shadow-lg'
                },
                buttonsStyling: false
            });
        <?php endif; ?>
    </script>
</body>
</html>

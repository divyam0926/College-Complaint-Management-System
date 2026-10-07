<?php
include '../config/db.php';
session_start();

$sub_status = "";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

if (isset($_POST['submit_complaint'])) {
    $uid = $_SESSION['user_id'];
    $cat = mysqli_real_escape_string($conn, $_POST['category']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    
    $file_name = "";
    if (!empty($_FILES['evidence']['name'])) {
        $file_extension = pathinfo($_FILES['evidence']['name'], PATHINFO_EXTENSION);
        $file_name = "EVIDENCE_" . time() . "_" . uniqid() . "." . $file_extension;
        $target_dir = "../uploads/";
        
        if (!is_dir($target_dir)) { 
            mkdir($target_dir, 0777, true); 
        }
        
        if (move_uploaded_file($_FILES['evidence']['tmp_name'], $target_dir . $file_name)) {
        } else {
            $sub_status = "error"; 
        }
    }
  

  
    $sql = "INSERT INTO complaints (user_id, category, description, evidence_file, status, created_at) 
            VALUES ('$uid', '$cat', '$desc', '$file_name', 'Pending', NOW())";
    
    if (mysqli_query($conn, $sql)) {
        $sub_status = "success";
    } else {
        $sub_status = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submit Grievance | Student Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        :root {
            --primary: #6366f1;
            --primary-soft: rgba(99,102,241,0.18);
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #8b5cf6 35%, #ec4899 100%);
            --bg-main: #020617;
            --glass-bg: rgba(15,23,42,0.85);
            --border-soft: rgba(148,163,184,0.45);
            --text-main: #e5e7eb;
            --text-muted: #9ca3af;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            background: radial-gradient(circle at top left, #1e293b 0, #020617 40%, #000 100%);
            position: relative;
            overflow: hidden;
        }

        /* Animated gradient background layer */
        .bg-orbit {
            position: fixed;
            inset: -40%;
            background: conic-gradient(from 180deg at 10% 20%, #22d3ee, #6366f1, #a855f7, #f97316, #22d3ee);
            opacity: 0.37;
            filter: blur(55px);
            animation: orbit 22s linear infinite;
            z-index: -2;
        }

        @keyframes orbit {
            0% { transform: rotate(0deg) scale(1); }
            50% { transform: rotate(180deg) scale(1.06); }
            100% { transform: rotate(360deg) scale(1); }
        }

        /* Subtle dark overlay + vignette */
        .noise-overlay {
            position: fixed;
            inset: 0;
            background:
                radial-gradient(circle at 0 0, rgba(15,23,42,0.85), transparent 52%),
                radial-gradient(circle at 100% 100%, rgba(15,23,42,0.9), transparent 48%);
            mix-blend-mode: multiply;
            z-index: -1;
        }

        /* Container & card shell */
        .page-wrapper {
            width: 100%;
            max-width: 1180px;
            padding: 24px 20px;
        }

        .submit-card {
            border-radius: 32px;
            border: 1px solid rgba(148,163,184,0.55);
            background: radial-gradient(circle at top left, rgba(148,163,184,0.22), rgba(15,23,42,0.96));
            backdrop-filter: blur(28px);
            -webkit-backdrop-filter: blur(28px);
            box-shadow: 0 28px 80px rgba(15,23,42,0.95);
            overflow: hidden;
        }

        .row-equal {
            display: flex;
            flex-wrap: wrap;
        }

        .info-side {
            background: transparent;
            color: var(--text-main);
            padding: 32px 32px 32px 36px;
            border-right: 1px solid rgba(148,163,184,0.5);
            position: relative;
        }

        .info-side::before {
            content: "";
            position: absolute;
            inset: -35%;
            background:
                radial-gradient(circle at 0 0, rgba(248,250,252,0.22), transparent 58%),
                radial-gradient(circle at 100% 100%, rgba(96,165,250,0.3), transparent 55%);
            opacity: 0.7;
            pointer-events: none;
            mix-blend-mode: soft-light;
        }

        .info-inner {
            position: relative;
            z-index: 1;
        }

        .badge-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 999px;
            background: rgba(15,23,42,0.85);
            border: 1px solid rgba(148,163,184,0.7);
            font-size: 0.74rem;
            letter-spacing: 0.17em;
            text-transform: uppercase;
            color: #cbd5f5;
            margin-bottom: 14px;
        }

        .badge-pill i {
            color: #22c55e;
        }

        .info-side h2 {
            font-weight: 700;
            font-size: 1.7rem;
            margin-bottom: 10px;
        }

        .info-side p {
            font-size: 0.95rem;
            color: var(--text-muted);
            margin-bottom: 24px;
        }

        .step-item {
            display: flex;
            align-items: center;
            margin-bottom: 14px;
            font-size: 0.9rem;
        }

        .step-icon {
            width: 26px;
            height: 26px;
            border-radius: 999px;
            background: rgba(15,23,42,0.9);
            border: 1px solid rgba(148,163,184,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            color: #a5b4fc;
            font-size: 0.7rem;
        }

        .info-note {
            margin-top: 30px;
            padding: 10px 12px;
            border-radius: 16px;
            background: rgba(15,23,42,0.92);
            border: 1px solid rgba(148,163,184,0.7);
            font-size: 0.82rem;
            color: var(--text-muted);
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }

        .info-note i {
            margin-top: 2px;
            color: #38bdf8;
        }

        .form-side {
            background: rgba(15,23,42,0.94);
            padding: 30px 32px;
            color: var(--text-main);
        }

        .form-side h4 {
            font-weight: 600;
            margin-bottom: 8px;
        }

        .form-side small {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .form-label {
            font-weight: 600;
            font-size: 0.88rem;
            color: #e5e7eb;
        }

        .form-section-title {
            margin-bottom: 5px;
        }

        .form-control,
        .form-select {
            border-radius: 12px;
            padding: 11px 12px;
            border: 1px solid rgba(148,163,184,0.7);
            background-color: rgba(15,23,42,0.9);
            color: var(--text-main);
            font-size: 0.9rem;
        }

        .form-control::placeholder,
        .form-select option[disabled] {
            color: #6b7280;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #818cf8;
            box-shadow: 0 0 0 1px rgba(129,140,248,0.45);
            background-color: rgba(15,23,42,0.98);
            color: var(--text-main);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 140px;
        }

        .form-text {
            font-size: 0.78rem;
        }

        .btn-submit {
            border: none;
            padding: 12px 16px;
            border-radius: 14px;
            font-weight: 600;
            font-size: 0.95rem;
            color: #f9fafb;
            background-image: var(--primary-gradient);
            box-shadow: 0 18px 40px rgba(79,70,229,0.85);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: transform 0.18s ease-out, box-shadow 0.18s ease-out, filter 0.18s ease-out;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            filter: brightness(1.05);
            box-shadow: 0 22px 60px rgba(79,70,229,1);
            color: #f9fafb;
        }

        .btn-submit:active {
            transform: translateY(0);
            box-shadow: 0 10px 28px rgba(79,70,229,0.85);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            font-size: 0.86rem;
            font-weight: 500;
            color: #cbd5f5;
            padding: 6px 12px;
            border-radius: 999px;
            background-color: rgba(15,23,42,0.88);
            border: 1px solid rgba(148,163,184,0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            margin-bottom: 16px;
        }

        .back-link i {
            font-size: 0.8rem;
        }

        .back-link:hover {
            color: #e5e7eb;
            border-color: #818cf8;
        }

        /* Responsive */
        @media (max-width: 991.98px) {
            body {
                align-items: flex-start;
            }
            .page-wrapper {
                padding-top: 32px;
            }
            .submit-card {
                border-radius: 26px;
            }
            .info-side {
                border-right: none;
                border-bottom: 1px solid rgba(148,163,184,0.5);
            }
        }

        @media (max-width: 767.98px) {
            .info-side {
                padding: 24px;
            }
            .form-side {
                padding: 24px;
            }
            .submit-card {
                box-shadow: 0 18px 50px rgba(15,23,42,0.9);
            }
        }
    </style>
</head>
<body>

<div class="bg-orbit"></div>
<div class="noise-overlay"></div>

<div class="page-wrapper mx-auto">
    <a href="dashboard.php" class="back-link">
        <i class="fas fa-chevron-left"></i>
        Back to dashboard
    </a>

    <div class="submit-card">
        <div class="row-equal">
            <!-- Left info side -->
            <div class="col-lg-4 d-none d-lg-block info-side">
                <div class="info-inner">
                    <div class="badge-pill">
                        <i class="fas fa-circle"></i>
                        GRIEVANCE TICKET
                    </div>

                    <h2>Submit your grievance</h2>
                    <p>Share clear details and, if possible, attach evidence so your issue can be resolved efficiently.</p>

                    <div class="step-item">
                        <div class="step-icon">
                            <span>1</span>
                        </div>
                        <div><strong>Select category.</strong> Choose where your issue belongs.</div>
                    </div>
                    <div class="step-item">
                        <div class="step-icon">
                            <span>2</span>
                        </div>
                        <div><strong>Describe problem.</strong> Add all important details.</div>
                    </div>
                    <div class="step-item">
                        <div class="step-icon">
                            <span>3</span>
                        </div>
                        <div><strong>Upload evidence.</strong> Screenshots, photos, or PDFs.</div>
                    </div>
                    <div class="step-item">
                        <div class="step-icon">
                            <span>4</span>
                        </div>
                        <div><strong>Track status.</strong> Monitor progress in your dashboard.</div>
                    </div>

                    <div class="info-note">
                        <i class="fas fa-shield-alt"></i>
                        <span>All submissions are securely stored and visible only to authorized college administrators.</span>
                    </div>
                </div>
            </div>

            <!-- Right form side -->
            <div class="col-lg-8 form-side">
                <div class="mb-3">
                    <h4 class="form-section-title">Complaint details</h4>
                    <small>Fill out the fields below to raise a new grievance.</small>
                </div>

                <form method="POST" enctype="multipart/form-data" id="complaintForm">
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-list-ul me-2 text-primary"></i>
                            Select category
                        </label>
                        <select name="category" class="form-select shadow-none" required>
                            <option value="" disabled selected>Choose the relevant category...</option>
                            <option value="Academic">Academic (Lectures, Exams, Results)</option>
                            <option value="Hostel">Hostel (Rooms, Mess, Cleaning)</option>
                            <option value="Transport">Transport (Bus timings, Routes)</option>
                            <option value="Infrastructure">Infrastructure (Water, Electricity, Lab)</option>
                            <option value="Others">Others</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-paperclip me-2 text-primary"></i>
                            Attach evidence (optional)
                        </label>
                        <input type="file" name="evidence" class="form-control shadow-none" accept=".jpg,.jpeg,.png,.pdf">
                        <div class="form-text mt-1 text-muted">
                            Max size: 2MB. Formats: JPG, PNG, PDF.
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-pen-nib me-2 text-primary"></i>
                            Describe the problem
                        </label>
                        <textarea name="description" class="form-control shadow-none" rows="5" placeholder="Be as specific as possible..." required></textarea>
                    </div>

                    <div class="d-grid pt-1">
                        <button type="submit" name="submit_complaint" class="btn btn-submit">
                            <i class="fas fa-paper-plane"></i>
                            Submit complaint to admin
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    <?php if($sub_status == "success"): ?>
        Swal.fire({
            icon: 'success',
            title: 'Submitted!',
            text: 'Grievance Submitted Successfully!',
            confirmButtonColor: '#6366f1',
            timer: 3000,
            timerProgressBar: true
        }).then((result) => {
            if (result.isConfirmed || result.dismiss === Swal.DismissReason.timer) {
                window.location = 'view_status.php';
            }
        });
    <?php elseif($sub_status == "error"): ?>
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: 'Something went wrong. Please try again.',
            confirmButtonColor: '#6366f1'
        });
    <?php endif; ?>
</script>

</body>
</html>

<?php
include '../config/db.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

$uid = $_SESSION['user_id'];
$msg = "";

if (isset($_POST['upload_pic'])) {
    if (!empty($_FILES['profile_img']['name'])) {
        $ext = pathinfo($_FILES['profile_img']['name'], PATHINFO_EXTENSION);
        $new_name = "PROFILE_" . $uid . "_" . time() . "." . $ext;
        $target = "../uploads/" . $new_name;

        if (move_uploaded_file($_FILES['profile_img']['tmp_name'], $target)) {
            mysqli_query($conn, "UPDATE users SET profile_pic = '$new_name' WHERE id = '$uid'");
            $msg = "success";
        }
    }
}

$user_res = mysqli_query($conn, "SELECT * FROM users WHERE id = '$uid'");
$user = mysqli_fetch_assoc($user_res);
$pic = !empty($user['profile_pic']) ? $user['profile_pic'] : 'default_user.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | Student Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #10b981;
            --accent: #f59e0b;
            --dark: #1e293b;
            --light: #f8fafc;
            --glass: rgba(248, 250, 252, 0.95);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100vh;
            overflow-x: hidden;
        }

        body {
            background: linear-gradient(145deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            font-family: 'Poppins', sans-serif;
            position: relative;
        }

        .particles-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }

        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            animation: rise 6s infinite linear;
        }

        .particle:nth-child(1) { width: 8px; height: 8px; left: 10%; animation-delay: 0s; }
        .particle:nth-child(2) { width: 12px; height: 12px; left: 20%; animation-delay: 1s; }
        .particle:nth-child(3) { width: 6px; height: 6px; left: 30%; animation-delay: 2s; }
        .particle:nth-child(4) { width: 10px; height: 10px; left: 40%; animation-delay: 3s; }
        .particle:nth-child(5) { width: 7px; height: 7px; left: 50%; animation-delay: 4s; }

        @keyframes rise {
            0% { opacity: 0; transform: translateY(100vh) scale(0); }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { opacity: 0; transform: translateY(-10vh) scale(1); }
        }

        .main-container {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            position: relative;
            z-index: 1;
        }

        .profile-container {
            max-width: 380px;
            width: 100%;
        }

        .profile-card {
            background: var(--glass);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 2rem 1.8rem;
            position: relative;
            overflow: hidden;
        }

        .profile-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary), var(--accent));
            background-size: 200% 100%;
            animation: shimmer 3s infinite;
        }

        @keyframes shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #64748b;
            font-weight: 500;
            font-size: 0.9rem;
            text-decoration: none;
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
            padding: 0.5rem 0;
        }

        .back-btn:hover {
            color: var(--primary);
            transform: translateX(-5px);
        }

        .profile-avatar-section {
            text-align: center;
            margin-bottom: 1.8rem;
        }

        .avatar-container {
            position: relative;
            display: inline-block;
            margin-bottom: 1rem;
        }

        .profile-avatar {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            border: 4px solid white;
            object-fit: cover;
            background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.2);
            transition: all 0.3s ease;
        }

        .upload-camera {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 32px;
            height: 32px;
            background: var(--primary);
            border-radius: 50%;
            border: 3px solid white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            color: white;
            font-size: 0.8rem;
        }

        .upload-camera:hover {
            background: var(--primary-dark);
            transform: scale(1.1);
        }

        .profile-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.25rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .profile-role {
            color: #64748b;
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-section {
            margin-top: 1.5rem;
        }

        .info-item {
            display: flex;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #e2e8f0;
            gap: 0.8rem;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .info-content h6 {
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.2rem;
        }

        .info-content p {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
        }

        .info-content code {
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            padding: 0.15rem 0.4rem;
            border-radius: 4px;
            font-size: 0.85rem;
        }

        .upload-form {
            position: absolute;
            opacity: 0;
            visibility: hidden;
        }

        @media (max-width: 480px) {
            .main-container {
                padding: 0.5rem;
            }
            
            .profile-card {
                padding: 1.5rem 1.2rem;
                border-radius: 20px;
            }
            
            .profile-avatar {
                width: 80px;
                height: 80px;
            }
            
            .profile-name {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>
    <div class="particles-bg">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <div class="main-container">
        <div class="profile-container">
            <div class="profile-card">
                <a href="dashboard.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Dashboard
                </a>

                <div class="profile-avatar-section">
                    <div class="avatar-container">
                        <img src="../uploads/<?php echo $pic; ?>" class="profile-avatar" id="preview" 
                             onerror="this.src='https://cdn-icons-png.flaticon.com/512/149/149071.png'">
                        <label for="file-input" class="upload-camera">
                            <i class="fas fa-camera"></i>
                        </label>
                    </div>
                    <h1 class="profile-name"><?php echo $user['name']; ?></h1>
                    <div class="profile-role"><?php echo ucfirst($user['role']); ?></div>
                </div>

                <form method="POST" enctype="multipart/form-data" class="upload-form" id="profileForm">
                    <input type="file" name="profile_img" id="file-input" accept="image/*">
                    <input type="hidden" name="upload_pic">
                </form>

                <div class="info-section">
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-id-card"></i>
                        </div>
                        <div class="info-content">
                            <h6>Register Number</h6>
                            <p><code><?php echo $user['register_no']; ?></code></p>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div class="info-content">
                            <h6>Program</h6>
                            <p><?php echo $user['program']; ?></p>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="info-content">
                            <h6>Email Address</h6>
                            <p><?php echo $user['email']; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Profile picture upload
        document.getElementById('file-input').addEventListener('change', function() {
            document.getElementById('profileForm').submit();
        });

        <?php if($msg == "success"): ?>
            Swal.fire({
                icon: 'success',
                title: 'Profile Updated!',
                text: 'Your profile picture has been updated successfully.',
                confirmButtonColor: '#6366f1',
                timer: 2000,
                timerProgressBar: true
            });
        <?php endif; ?>
    </script>
</body>
</html>

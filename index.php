<?php
include 'config/db.php';
session_start();

$login_status = "";

if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $pass = $_POST['password'];

  
    if (!str_ends_with($email, '@kristujayanti.com')) {
        $login_status = "invalid_domain";
    } else {
        $res = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
        $user = mysqli_fetch_assoc($res);

        if ($user && password_verify($pass, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['user_name'] = $user['name'];
            $login_status = "success";
        } else { 
            $login_status = "error"; 
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login | CCMS Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --glass-bg: rgba(255, 255, 255, 0.15);
            --glass-border: rgba(255, 255, 255, 0.2);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #0f0f23 0%, #1a1a2e 50%, #16213e 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.4) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.4) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(120, 219, 255, 0.3) 0%, transparent 50%);
            z-index: -1;
            animation: ambientFloat 25s ease-in-out infinite;
        }

        @keyframes ambientFloat {
            0%, 100% { transform: scale(1) rotate(0deg); opacity: 0.8; }
            50% { transform: scale(1.1) rotate(180deg); opacity: 0.5; }
        }

        .floating-shapes {
            position: fixed;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }

        .shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            animation: floatShapes 20s infinite linear;
        }

        .shape:nth-child(1) { width: 100px; height: 100px; top: 20%; right: 10%; animation-delay: 0s; }
        .shape:nth-child(2) { width: 60px; height: 60px; top: 70%; left: 20%; animation-delay: 7s; }
        .shape:nth-child(3) { width: 80px; height: 80px; top: 40%; right: 30%; animation-delay: 14s; }

        @keyframes floatShapes {
            0% { transform: translateY(0px) rotate(0deg); opacity: 0.3; }
            50% { transform: translateY(-30px) rotate(180deg); opacity: 0.6; }
            100% { transform: translateY(0px) rotate(360deg) opacity: 0.3; }
        }

        .login-container {
            max-width: 950px;
            width: 95%;
            z-index: 1;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(50px);
            border-radius: 30px;
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: 
                0 32px 64px rgba(0,0,0,0.15),
                0 0 0 1px rgba(255, 255, 255, 0.08);
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
            z-index: 2;
        }

        .welcome-panel {
            background: var(--primary-gradient);
            padding: 4rem 3.5rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .welcome-panel::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 400px;
            height: 400px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: pulseGlow 4s ease-in-out infinite;
        }

        @keyframes pulseGlow {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.2; }
        }

        .welcome-title {
            font-size: 3.2rem;
            font-weight: 800;
            background: rgba(255, 255, 255, 0.95);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1.5rem;
            letter-spacing: -2px;
            line-height: 1.2;
        }

        .welcome-subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.25rem;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .welcome-icon {
            font-size: 6rem;
            opacity: 0.15;
            margin: 0 auto;
            filter: drop-shadow(0 0 30px rgba(255, 255, 255, 0.3));
            animation: iconFloat 6s ease-in-out infinite;
        }

        @keyframes iconFloat {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-15px) rotate(5deg); }
        }

        .form-panel {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(30px);
            padding: 4rem 4rem;
            position: relative;
        }

        .form-title {
            color: white;
            font-size: 2.5rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
        }

        .form-title::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: var(--secondary-gradient);
            border-radius: 2px;
        }

        .form-floating {
            position: relative;
            margin-bottom: 2rem;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.1) !important;
            backdrop-filter: blur(20px) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            border-radius: 20px !important;
            color: white !important;
            padding: 1.5rem 1.25rem 1rem !important;
            font-size: 1.05rem;
            height: auto;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.5) !important;
        }

        .form-floating > label {
            color: rgba(255, 255, 255, 0.7) !important;
            font-weight: 600;
            font-size: 1rem;
            padding-left: 1.25rem;
            transform-origin: 0 0;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.15) !important;
            border-color: rgba(102, 126, 234, 0.6) !important;
            box-shadow: 0 0 0 0.5rem rgba(102, 126, 234, 0.15), 0 20px 40px rgba(0,0,0,0.2) !important;
            transform: translateY(-3px);
            color: white !important;
        }

        .form-control:focus + label,
        .form-control:not(:placeholder-shown) + label {
            color: rgba(102, 126, 234, 1) !important;
            font-size: 0.85rem;
            transform: translateY(-1.5rem) scale(0.85);
        }

        .btn-login {
            background: var(--primary-gradient);
            border: none;
            padding: 1.75rem;
            border-radius: 25px;
            font-weight: 700;
            font-size: 1.15rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 20px 40px rgba(102, 126, 234, 0.4);
            position: relative;
            overflow: hidden;
            width: 100%;
            margin-bottom: 2rem;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .btn-login:hover {
            transform: translateY(-8px);
            box-shadow: 0 30px 60px rgba(102, 126, 234, 0.6);
        }

        .register-link {
            color: rgba(255, 255, 255, 0.8);
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .register-link:hover {
            color: white;
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
        }

        @media (max-width: 768px) {
            .welcome-panel, .form-panel {
                padding: 3rem 2.5rem;
            }
            
            .welcome-title {
                font-size: 2.5rem;
            }
            
            .form-title {
                font-size: 2rem;
            }
        }

        .input-icon {
            position: absolute;
            right: 1.25rem;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.5);
            font-size: 1.2rem;
            z-index: 2;
        }
    </style>
</head>
<body>
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <div class="container d-flex justify-content-center">
        <div class="login-container">
            <div class="card glass-card">
                <div class="row g-0">
                    <div class="col-md-5 welcome-panel d-none d-md-flex">
                        <div>
                            <h1 class="welcome-title">Welcome Back!</h1>
                            <p class="welcome-subtitle">
                                Access the KRISTU JAYANTI Complaint Management System to resolve your issues effectively.
                            </p>
                        </div>
                        <div class="welcome-icon">
                            <i class="fas fa-university"></i>
                        </div>
                    </div>
                    
                    <div class="col-md-7 form-panel">
                        <h2 class="form-title">Login to KJCMS</h2>
                        
                        <form method="POST" id="loginForm">
                            <div class="form-floating">
                                <input type="email" class="form-control" name="email" id="email" placeholder="" required>
                                <label for="email">Username</label>
                                <i class="fas fa-envelope input-icon"></i>
                            </div>
                            
                            <div class="form-floating">
                                <input type="password" class="form-control" name="password" id="password" placeholder="" required>
                                <label for="password">Password</label>
                                <i class="fas fa-lock input-icon"></i>
                            </div>
                            
                            <button type="submit" name="login" class="btn btn-login">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Sign In
                            </button>
                        </form>
                        
                        <div class="text-center">
                            <p class="mb-0">
                                Don't have an account? 
                                <a href="register.php" class="register-link fw-bold">
                                    <i class="fas fa-user-plus me-1"></i>Create Account
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php if($login_status == "success"): ?>
            Swal.fire({
                icon: 'success',
                title: 'Login Successful!',
                text: 'Welcome back, <?php echo $_SESSION['user_name']; ?>',
                showConfirmButton: false,
                timer: 2200,
                timerProgressBar: true,
                customClass: {
                    popup: 'animate__animated animate__fadeIn',
                    timerProgressBar: 'bg-gradient-primary'
                }
            }).then(() => {
                window.location = "<?php echo ($_SESSION['role'] == 'admin') ? 'admin/dashboard.php' : 'student/dashboard.php'; ?>";
            });
        <?php elseif($login_status == "invalid_domain"): ?>
            Swal.fire({
                icon: 'warning',
                title: 'Access Denied',
                text: 'Only @kristujayanti.com emails are authorized to access this system.',
                confirmButtonColor: '#764ba2',
                customClass: {
                    popup: 'animate__animated animate__shakeX'
                }
            });
        <?php elseif($login_status == "error"): ?>
            Swal.fire({
                icon: 'error',
                title: 'Login Failed',
                text: 'Invalid email or password. Please try again.',
                confirmButtonColor: 'transparent',
                customClass: {
                    confirmButton: 'btn btn-lg px-5 py-3 rounded-25 shadow-lg border border-light text-white',
                    popup: 'animate__animated animate__shakeX'
                },
                buttonsStyling: false
            });
        <?php endif; ?>
    </script>
</body>
</html>
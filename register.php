<?php
include 'config/db.php';


$reg_status = "";


$email = isset($_POST['email']) ? mysqli_real_escape_string($conn, $_POST['email']) : '';


if (isset($_POST['register'])) {
    $role = $_POST['role'];
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
   
    if ($role == 'admin') {
        $name = "Administrator";
        $reg_no = "";
        $program = "";
    } else {
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $reg_no = mysqli_real_escape_string($conn, $_POST['register_no']);
        $program = mysqli_real_escape_string($conn, $_POST['program']);
    }
   
    if (!str_ends_with($email, '@kristujayanti.com')) {
        $reg_status = "invalid_domain";
    } else {
        $check_email = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
       
        if (mysqli_num_rows($check_email) > 0) {
            $reg_status = "exists";
        } else {
            $sql = "INSERT INTO users (name, email, register_no, program, password, role)
                    VALUES ('$name', '$email', '$reg_no', '$program', '$pass', '$role')";
           
            if (mysqli_query($conn, $sql)) {
                $reg_status = "success";
            } else {
                $reg_status = "error";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join CCMS | Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --bg-primary: #1e1b4b;
            --bg-secondary: #2d1b69;
            --accent-primary: #a855f7;
            --accent-secondary: #ec4899;
            --text-primary: #f8fafc;
            --text-secondary: #cbd5e1;
            --border-light: rgba(255, 255, 255, 0.1);
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
            background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 50%, #3b0764 100%);
            font-family: 'Manrope', sans-serif;
            position: relative;
        }


        .gradient-mesh {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background:
                radial-gradient(ellipse at top left, rgba(168, 85, 247, 0.3) 0%, transparent 50%),
                radial-gradient(ellipse at bottom right, rgba(236, 72, 153, 0.3) 0%, transparent 50%);
        }


        .main-container {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }


        .auth-container {
            width: 100%;
            max-width: 380px;
        }


        .auth-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(40px);
            border-radius: 24px;
            border: 1px solid var(--border-light);
            padding: 2rem 1.8rem;
            position: relative;
            overflow: hidden;
        }


        .auth-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--accent-primary), var(--accent-secondary), transparent);
        }


        .brand-section {
            text-align: center;
            margin-bottom: 1.5rem;
        }


        .brand-logo {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.8rem;
            font-size: 1.3rem;
            color: white;
            position: relative;
        }


        .brand-logo::before {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            right: 2px;
            bottom: 2px;
            background: rgba(30, 27, 75, 0.9);
            border-radius: 12px;
        }


        .brand-title {
            font-size: 1.6rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--text-primary), var(--text-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.2rem;
            line-height: 1.1;
        }


        .brand-tagline {
            color: var(--text-secondary);
            font-size: 0.8rem;
            font-weight: 400;
            opacity: 0.8;
            margin: 0;
        }


        .form-field {
            position: relative;
            margin-bottom: 1rem;
        }


        .form-field input,
        .form-field select {
            width: 100%;
            padding: 0.9rem 0.9rem 0.7rem 2.6rem;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-light);
            border-radius: 14px;
            color: var(--text-primary);
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }


        .form-field input::placeholder {
            color: var(--text-secondary);
            opacity: 0.7;
        }


        .form-field input:focus,
        .form-field select:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.18);
            border-color: var(--accent-primary);
            transform: translateY(-1px);
        }


        .field-icon {
            position: absolute;
            left: 0.9rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }


        .form-field:focus-within .field-icon {
            color: var(--accent-primary);
        }


        .dual-fields {
            display: flex;
            gap: 0.6rem;
            margin-bottom: 1rem;
        }


        .dual-fields .form-field {
            flex: 1;
        }


        .role-field {
            margin-bottom: 1.2rem;
        }


        .role-label {
            display: block;
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 0.75rem;
            margin-bottom: 0.5rem;
        }


        .cta-button {
            width: 100%;
            padding: 0.95rem 1.2rem;
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            border: none;
            border-radius: 16px;
            color: white;
            font-size: 0.95rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.2px;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }


        .cta-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s ease;
        }


        .cta-button:hover::before {
            left: 100%;
        }


        .cta-button:hover {
            transform: translateY(-2px);
        }


        .auth-link {
            text-align: center;
        }


        .auth-text {
            color: var(--text-secondary);
            font-size: 0.8rem;
            margin: 0;
        }


        .auth-link a {
            color: var(--accent-primary);
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }


        .auth-link a:hover {
            color: var(--accent-secondary);
        }


        .d-none { display: none !important; }


        @media (max-width: 480px) {
            .main-container {
                padding: 0.5rem;
            }
           
            .auth-card {
                padding: 1.5rem 1.2rem;
                border-radius: 20px;
            }
           
            .dual-fields {
                flex-direction: column;
                gap: 0.6rem;
            }
           
            .brand-title {
                font-size: 1.4rem;
            }
        }
    </style>
</head>
<body>
    <div class="gradient-mesh"></div>


    <div class="main-container">
        <div class="auth-container">
            <div class="auth-card">
                <div class="brand-section">
                    <div class="brand-logo">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h1 class="brand-title">KJCMS Registration</h1>
                    <p class="brand-tagline">Join the complaint system</p>
                </div>


                <form method="POST" id="regForm">
                    <div class="role-field">
                        <label class="role-label">Register as:</label>
                        <div class="form-field">
                            <i class="fas fa-users field-icon"></i>
                            <select name="role" id="roleSelect" onchange="toggleStudentFields()">
                                <option value="student">Student</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>


                    <div id="studentOnlyFields">
                        <div class="form-field">
                            <i class="fas fa-user field-icon"></i>
                            <input type="text" name="name" id="nameInput" placeholder="Full Name" required>
                        </div>


                        <div class="dual-fields">
                            <div class="form-field">
                                <i class="fas fa-id-card field-icon"></i>
                                <input type="text" name="register_no" id="regNoInput" placeholder="Reg Number" required>
                            </div>
                            <div class="form-field">
                                <i class="fas fa-graduation-cap field-icon"></i>
                                <select name="program" id="programInput" required>
                                    <option value="" disabled selected>Program</option>
                                    <option value="B.Tech">B.Tech</option>
                                    <option value="M.Tech">M.Tech</option>
                                    <option value="BCA">BCA</option>
                                    <option value="MCA">MCA</option>
                                    <option value="B.Sc">B.Sc</option>
                                    <option value="MBA">MBA</option>
                                </select>
                            </div>
                        </div>
                    </div>


                    <div class="form-field">
                        <i class="fas fa-envelope field-icon"></i>
                        <input type="email" name="email" placeholder="Email" required>
                    </div>


                    <div class="form-field">
                        <i class="fas fa-lock field-icon"></i>
                        <input type="password" name="password" placeholder="Password" required>
                    </div>


                    <button type="submit" name="register" class="cta-button">
                        <i class="fas fa-rocket me-1"></i>Create
                    </button>
                </form>


                <div class="auth-link">
                    <p class="auth-text">
                        Have account? <a href="index.php">Log In</a>
                    </p>
                </div>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleStudentFields() {
            const role = document.getElementById('roleSelect').value;
            const studentSection = document.getElementById('studentOnlyFields');
            const inputs = studentSection.querySelectorAll('input, select');


            if (role === 'admin') {
                studentSection.classList.add('d-none');
                inputs.forEach(el => el.required = false);
            } else {
                studentSection.classList.remove('d-none');
                inputs.forEach(el => el.required = true);
            }
        }


        <?php if($reg_status == "success"): ?>
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Account created!',
                confirmButtonColor: '#a855f7',
                timer: 1500,
                timerProgressBar: true
            }).then(() => {
                window.location = "index.php";
            });
        <?php elseif($reg_status == "exists"): ?>
            Swal.fire({
                icon: 'warning',
                title: 'Email Exists',
                text: 'Try different email.',
                confirmButtonColor: '#a855f7'
            });
        <?php elseif($reg_status == "invalid_domain"): ?>
            Swal.fire({
                icon: 'error',
                title: 'Invalid Domain',
                text: 'Please use your official @kristujayanti.com email address.',
                confirmButtonColor: '#a855f7'
            });
        <?php elseif($reg_status == "error"): ?>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Try again.',
                confirmButtonColor: '#a855f7'
            });
        <?php endif; ?>
    </script>
</body>
</html>
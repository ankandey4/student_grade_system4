<?php
session_start(); 
include 'db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $selected_role = $_POST['role'] ?? "";

    // Basic validation
    if(empty($username) || empty($password) || empty($selected_role)) {
        $error = "Please fill in all fields!";
    } else {
        // Using prepared statement with status check
        $sql = "SELECT * FROM users WHERE username = ? AND password = ? AND role = ? AND status = 'approved'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $username, $password, $selected_role);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['username'] = $user['username'];

            // Redirect based on role
            $redirect = [
                'admin' => 'admin.php',
                'teacher' => 'teacher.php',
                'parent' => 'parent.php'
            ][$selected_role] ?? 'login.php';
            
            header("Location: $redirect");
            exit();
        } else {
            // Check if user exists but not approved
            $check_sql = "SELECT status FROM users WHERE username = ? AND role = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("ss", $username, $selected_role);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if($check_result->num_rows == 1) {
                $status = $check_result->fetch_assoc()['status'];
                if($status == 'pending') {
                    $error = "⚠️ Your account is pending admin approval. Please wait!";
                } else {
                    $error = "❌ Incorrect username or password!";
                }
            } else {
                $error = "❌ Incorrect username, password, or user type!";
            }
        }
    }
}
?>                              
<!DOCTYPE html>
<html>
<head>
    <title>Student Grade Tracking System</title>
    <style> 
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex; 
            justify-content: center; 
            align-items: center; 
            padding: 20px;
        }
        .login-box { 
            background: white; 
            padding: 40px; 
            border-radius: 16px; 
            box-shadow: 0 20px 60px rgba(0,0,0,0.3); 
            width: 400px; 
            max-width: 100%;
        }
        .login-box .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-box .logo h2 { 
            color: #2c3e50; 
            font-size: 24px;
        }
        .login-box .logo h2 span { color: #667eea; }
        .login-box .logo p {
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .error-box {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border: 1px solid #f5c6cb;
        }
        
        input, select { 
            width: 100%; 
            padding: 12px 14px; 
            margin: 8px 0; 
            box-sizing: border-box; 
            border: 2px solid #e0e0e0; 
            border-radius: 8px; 
            font-size: 14px;
            transition: 0.3s;
        }
        input:focus, select:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        }
        select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23333' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            padding-right: 40px;
        }
        
        button { 
            width: 100%; 
            padding: 14px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; 
            border: none; 
            cursor: pointer; 
            font-size: 16px; 
            border-radius: 8px; 
            margin-top: 12px; 
            font-weight: 600;
            transition: 0.3s;
        }
        button:hover { 
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .reg-links { 
            margin-top: 20px; 
            text-align: center; 
            font-size: 14px; 
            line-height: 1.8;
            color: #7f8c8d;
        }
        .reg-links a { 
            color: #667eea; 
            text-decoration: none; 
            font-weight: 600;
        }
        .reg-links a:hover { text-decoration: underline; }
        .reg-links .separator {
            color: #ddd;
            margin: 0 8px;
        }
    </style>
</head>
<body>
<div class="login-box">
    <div class="logo">
        <h2>🎓 <span>Grade</span>Tracker</h2>
        <p>Student Grade Tracking System</p>
    </div>
    
    <?php if($error != ""): ?>
        <div class="error-box"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <select name="role" required>
            <option value="" disabled selected>👤 Select User Type</option>
            <option value="admin">🛡️ Admin</option>
            <option value="teacher">👨‍🏫 Teacher</option>
            <option value="parent">👨‍👩‍👦 Parent</option>
        </select>

        <input type="text" name="username" placeholder="👤 Username" required>
        <input type="password" name="password" placeholder="🔒 Password" required>
        
        <button type="submit">🔑 Login</button>
    </form>
    
    <div class="reg-links">
        New User? 
        <a href="register_teacher.php">Register as Teacher</a>
        <span class="separator">|</span>
        <a href="register_parent.php">Register as Parent</a>
    </div>
</div>
</body>
</html>
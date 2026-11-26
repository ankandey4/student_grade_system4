<?php
include 'db.php';
$msg = ""; 
$msg_class = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username     = trim($_POST['username']);
    $password     = trim($_POST['password']); 
    $parent_name  = trim($_POST['parent_name']);
    $email        = trim($_POST['email']);
    $phone        = trim($_POST['phone']);
    $student_roll = trim($_POST['student_roll']);

    // Validation
    $errors = [];
    if(strlen($username) < 4) $errors[] = "Username must be at least 4 characters";
    if(strlen($password) < 6) $errors[] = "Password must be at least 6 characters";
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email address";
    if(!preg_match('/^01\d{9}$/', $phone)) $errors[] = "Invalid phone number (must be 01XXXXXXXXX)";
    if(empty($student_roll)) $errors[] = "Student roll number is required";

    if(!empty($errors)) {
        $msg = implode("<br>", $errors);
        $msg_class = "error";
    } else {
        // Check if student exists
        $check_student = $conn->prepare("SELECT student_id, student_name FROM students WHERE roll = ?");
        $check_student->bind_param("s", $student_roll);
        $check_student->execute();
        $res_student = $check_student->get_result();

        if ($res_student->num_rows == 0) {
            $msg = "❌ Student Roll Number not found! Please contact admin.";
            $msg_class = "error";
        } else {
            $student_data = $res_student->fetch_assoc();
            $student_id = $student_data['student_id'];
            $student_name = $student_data['student_name'];

            // Check if student already has a parent
            $check_parent = $conn->query("SELECT parent_id FROM students WHERE student_id = $student_id AND parent_id IS NOT NULL");
            if($check_parent->num_rows > 0) {
                $msg = "⚠️ This student already has a parent linked! Please contact admin.";
                $msg_class = "error";
            } else {
                // Check username duplicate
                $check_user = $conn->prepare("SELECT username FROM users WHERE username = ?");
                $check_user->bind_param("s", $username);
                $check_user->execute();
                $check_user->store_result();

                if ($check_user->num_rows > 0) {
                    $msg = "❌ Username already exists! Please choose a different one.";
                    $msg_class = "error";
                } else {
                    // Status is 'pending' - Admin will approve
                    $sql_user = "INSERT INTO users (username, password, role, status) VALUES (?, ?, 'parent', 'pending')";
                    $stmt_user = $conn->prepare($sql_user);
                    $stmt_user->bind_param("ss", $username, $password);

                    if ($stmt_user->execute()) {
                        $user_id = $conn->insert_id;
                        
                        $sql_parent = "INSERT INTO parents (user_id, parent_name, email, phone) VALUES (?, ?, ?, ?)";
                        $stmt_parent = $conn->prepare($sql_parent);
                        $stmt_parent->bind_param("isss", $user_id, $parent_name, $email, $phone);
                        
                        if($stmt_parent->execute()) {
                            $parent_id = $conn->insert_id;

                            $update_student = $conn->prepare("UPDATE students SET parent_id = ? WHERE student_id = ?");
                            $update_student->bind_param("ii", $parent_id, $student_id);
                            $update_student->execute();

                            $msg = "✅ Parent Registered successfully for <strong>$student_name</strong>! Please wait for admin approval. <a href='login.php'>Login Here</a>";
                            $msg_class = "success";
                        } else {
                            // Rollback user if parent insert fails
                            $conn->query("DELETE FROM users WHERE user_id = $user_id");
                            $msg = "❌ Error occurred! Please try again.";
                            $msg_class = "error";
                        }
                    } else {
                        $msg = "❌ Error occurred! Please try again.";
                        $msg_class = "error";
                    }
                }
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
    <title>Parent Registration</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 440px;
            padding: 40px;
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo h2 {
            font-size: 28px;
            color: #2c3e50;
        }
        .logo h2 span { color: #11998e; }
        .logo p {
            color: #7f8c8d;
            font-size: 14px;
            margin-top: 5px;
        }
        .msg {
            padding: 12px 16px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 14px;
            line-height: 1.6;
        }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            color: #333;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .form-group label .required { color: #e74c3c; }
        .form-group input {
            width: 100%;
            padding: 12px 14px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: 0.3s;
        }
        .form-group input:focus {
            border-color: #11998e;
            outline: none;
            box-shadow: 0 0 0 3px rgba(17, 153, 142, 0.2);
        }
        .form-group input::placeholder {
            color: #aaa;
        }
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 5px;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(17, 153, 142, 0.4);
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #7f8c8d;
        }
        .footer a {
            color: #11998e;
            text-decoration: none;
            font-weight: 600;
        }
        .footer a:hover { text-decoration: underline; }
        .hint {
            font-size: 12px;
            color: #95a5a6;
            margin-top: 4px;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            background: #ffc107;
            color: #333;
        }
        .info-box {
            background: #f8f9fa;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 18px;
            border-left: 4px solid #11998e;
            font-size: 14px;
            color: #555;
        }
        .info-box strong { color: #11998e; }
    </style>
</head>
<body>
<div class="container">
    <div class="logo">
        <h2>👨‍👩‍👦 <span>Parent</span> Registration</h2>
        <p>Link your child's account and track their progress</p>
    </div>

    <?php if($msg != ""): ?>
        <div class="msg <?php echo $msg_class; ?>"><?php echo $msg; ?></div>
    <?php endif; ?>

    <div class="info-box">
        💡 Need your child's roll number? Ask your child or contact the school office.
    </div>

    <form method="POST">
        <div class="form-group">
            <label>Parent Full Name <span class="required">*</span></label>
            <input type="text" name="parent_name" placeholder="Enter your full name" required>
        </div>

        <div class="form-group">
            <label>Your Child's Roll Number <span class="required">*</span></label>
            <input type="text" name="student_roll" placeholder="Enter student's roll number" required>
            <div class="hint">This is the unique roll number assigned to your child</div>
        </div>

        <div class="form-group">
            <label>Username <span class="required">*</span></label>
            <input type="text" name="username" placeholder="Choose a username (min 4 chars)" required minlength="4">
            <div class="hint">Must be at least 4 characters</div>
        </div>

        <div class="form-group">
            <label>Password <span class="required">*</span></label>
            <input type="password" name="password" placeholder="Choose a password (min 6 chars)" required minlength="6">
            <div class="hint">Must be at least 6 characters</div>
        </div>

        <div class="form-group">
            <label>Email <span class="required">*</span></label>
            <input type="email" name="email" placeholder="your@email.com" required>
        </div>

        <div class="form-group">
            <label>Phone <span class="required">*</span></label>
            <input type="tel" name="phone" placeholder="01XXXXXXXXX" required pattern="01\d{9}">
            <div class="hint">Must be 11 digits starting with 01</div>
        </div>

        <button type="submit" class="btn">Register as Parent</button>
    </form>

    <div class="footer">
        Already have an account? <a href="login.php">Login Here</a>
    </div>
    <div class="footer" style="margin-top: 10px; font-size: 13px;">
        ⏳ Your account will be <span class="status-badge">pending</span> until admin approval
    </div>
</div>
</body>
</html>
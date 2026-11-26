<?php
include 'db.php';

// Check admin session
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { 
    header("Location: login.php"); 
    exit(); 
}

$msg = "";
$msg_type = "";

// ========== Pending User Approve/Reject ==========
if (isset($_GET['approve_user'])) {
    $user_id = intval($_GET['approve_user']);
    $conn->query("UPDATE users SET status = 'approved' WHERE user_id = $user_id");
    $msg = "✅ User approved successfully!";
    $msg_type = "success";
    header("Location: admin.php?msg=" . urlencode($msg) . "&type=success");
    exit();
}

if (isset($_GET['reject_user'])) {
    $user_id = intval($_GET['reject_user']);
    $user_check = $conn->query("SELECT role FROM users WHERE user_id = $user_id");
    $user_data = $user_check->fetch_assoc();
    
    if ($user_data) {
        if ($user_data['role'] == 'teacher') {
            $conn->query("DELETE FROM teachers WHERE user_id = $user_id");
        } elseif ($user_data['role'] == 'parent') {
            $parent = $conn->query("SELECT parent_id FROM parents WHERE user_id = $user_id")->fetch_assoc();
            if ($parent) {
                $conn->query("UPDATE students SET parent_id = NULL WHERE parent_id = {$parent['parent_id']}");
                $conn->query("DELETE FROM parents WHERE user_id = $user_id");
            }
        }
        $conn->query("DELETE FROM users WHERE user_id = $user_id");
        $msg = "❌ User rejected and removed!";
        $msg_type = "danger";
    } else {
        $msg = "❌ User not found!";
        $msg_type = "danger";
    }
    header("Location: admin.php?msg=" . urlencode($msg) . "&type=danger");
    exit();
}

// Get message from URL
if (isset($_GET['msg'])) {
    $msg = urldecode($_GET['msg']);
    $msg_type = $_GET['type'] ?? 'info';
}

// 1. Add Student
if (isset($_POST['add_student'])) {
    $sname = trim($_POST['student_name']); 
    $roll = trim($_POST['roll']); 
    $phone = trim($_POST['phone']); 
    $parent_name = trim($_POST['parent_name']); 
    $class_id = intval($_POST['class_id']);
    
    // Validate phone
    if(!preg_match('/^01\d{9}$/', $phone)) {
        $msg = "❌ Invalid phone number! Must be 01XXXXXXXXX";
        $msg_type = "danger";
    } else {
        $check_roll = $conn->prepare("SELECT roll FROM students WHERE roll = ?");
        $check_roll->bind_param("s", $roll);
        $check_roll->execute();
        $check_roll->store_result();
        
        if($check_roll->num_rows > 0) {
            $msg = "❌ Roll Number already exists!";
            $msg_type = "danger";
        } else {
            $q = "INSERT INTO students (student_name, roll, phone, parent_name, class_id) VALUES (?, ?, ?, ?, ?)";
            $st = $conn->prepare($q); 
            $st->bind_param("ssssi", $sname, $roll, $phone, $parent_name, $class_id);
            if($st->execute()) {
                $msg = "✅ Student Registered Successfully!";
                $msg_type = "success";
            } else {
                $msg = "❌ Error creating student.";
                $msg_type = "danger";
            }
        }
    }
}

// 2. Class Subject Mapping
if (isset($_POST['map_subject'])) {
    $class_id = intval($_POST['class_id']); 
    $subject_id = intval($_POST['subject_id']);
    
    $check = $conn->query("SELECT * FROM class_subjects WHERE class_id = $class_id AND subject_id = $subject_id");
    if($check->num_rows > 0) {
        $msg = "⚠️ This subject is already mapped to this class!";
        $msg_type = "warning";
    } else {
        $q = "INSERT INTO class_subjects (class_id, subject_id) VALUES (?, ?)";
        $st = $conn->prepare($q); 
        $st->bind_param("ii", $class_id, $subject_id);
        if($st->execute()) {
            $msg = "✅ Subject mapped to Class successfully!";
            $msg_type = "success";
        }
    }
}

// 3. Assign Teacher
if (isset($_POST['assign_teacher'])) {
    $teacher_id = intval($_POST['teacher_id']); 
    $class_subject_id = intval($_POST['class_subject_id']);
    
    $check = $conn->query("SELECT * FROM teacher_assignment WHERE teacher_id = $teacher_id AND class_subject_id = $class_subject_id");
    if($check->num_rows > 0) {
        $msg = "⚠️ This teacher is already assigned to this subject!";
        $msg_type = "warning";
    } else {
        $q = "INSERT INTO teacher_assignment (teacher_id, class_subject_id) VALUES (?, ?)";
        $st = $conn->prepare($q); 
        $st->bind_param("ii", $teacher_id, $class_subject_id);
        if($st->execute()) {
            $msg = "✅ Teacher assigned successfully!";
            $msg_type = "success";
        }
    }
}

// 4. Add Notice
if (isset($_POST['add_notice'])) {
    $title = trim($_POST['title']); 
    $desc = trim($_POST['description']); 
    $date = date('Y-m-d');
    
    if(empty($title) || empty($desc)) {
        $msg = "❌ Title and description are required!";
        $msg_type = "danger";
    } else {
        $q = "INSERT INTO notices (title, description, publish_date) VALUES (?, ?, ?)";
        $st = $conn->prepare($q); 
        $st->bind_param("sss", $title, $desc, $date);
        if($st->execute()) {
            $msg = "✅ Notice Published!";
            $msg_type = "success";
        }
    }
}
?>
<div class="navbar">
    <div>
        <a href="promotion.php" style="background:#27ae60; color:white; border-radius:5px; padding:8px 16px;">
            🎯 Promotion
        </a>
    </div>
</div>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; }
        
        .navbar {
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            padding: 18px 30px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
            flex-wrap: wrap;
            gap: 10px;
        }
        .navbar h1 { font-size: 24px; }
        .navbar h1 span { color: #ffd700; }
        .navbar a { 
            color: #ffd700; 
            text-decoration: none; 
            padding: 8px 18px; 
            border-radius: 5px; 
            transition: 0.3s;
            background: rgba(255,215,0,0.1);
        }
        .navbar a:hover { background: #ffd700; color: #1a1a2e; }

        .container { max-width: 1400px; margin: 30px auto; padding: 0 20px; }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-warning { background: #fff3cd; color: #856404; border: 1px solid #ffc107; }
        .alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }

        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 25px; margin-bottom: 30px; }

        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.12); }
        .card h3 {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 12px;
            margin-bottom: 18px;
            font-size: 18px;
        }
        .card h3 .icon { margin-right: 8px; }

        .card label { font-weight: 600; color: #333; display: block; margin-top: 10px; font-size: 14px; }
        .card input, .card select, .card textarea {
            width: 100%;
            padding: 10px 12px;
            margin: 5px 0 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            transition: 0.3s;
        }
        .card input:focus, .card select:focus, .card textarea:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52,152,219,0.2);
        }
        .card textarea { resize: vertical; min-height: 80px; }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: 0.3s;
            width: 100%;
            margin-top: 5px;
        }
        .btn-primary { background: #3498db; color: white; }
        .btn-primary:hover { background: #2980b9; }
        .btn-success { background: #27ae60; color: white; }
        .btn-success:hover { background: #229954; }
        .btn-warning { background: #f39c12; color: white; }
        .btn-warning:hover { background: #d68910; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-danger:hover { background: #c0392b; }

        .table-container {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-top: 25px;
            overflow-x: auto;
        }
        .table-container h3 {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th { background: #2c3e50; color: white; padding: 12px; text-align: left; }
        td { padding: 10px 12px; border-bottom: 1px solid #ecf0f1; }
        tr:hover td { background: #f8f9fa; }

        .pending-badge {
            background: #ffc107;
            color: #333;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .approved-badge {
            background: #27ae60;
            color: white;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .action-btn {
            padding: 4px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            margin: 2px;
        }
        .action-btn.approve { background: #27ae60; color: white; }
        .action-btn.reject { background: #e74c3c; color: white; }
        .action-btn.approve:hover { background: #1e8449; }
        .action-btn.reject:hover { background: #c0392b; }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        }
        .stat-card .number { font-size: 32px; font-weight: 700; color: #2c3e50; }
        .stat-card .label { color: #7f8c8d; font-size: 14px; }

        .pending-card {
            background: #fff3cd;
            border: 2px solid #ffc107;
        }
        .pending-card h3 { border-color: #ffc107; }

        @media (max-width: 768px) {
            .navbar { flex-direction: column; text-align: center; }
            .grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="navbar">
    <h1>🎓 <span>Admin</span> Dashboard</h1>
    <div>
        <a href="logout.php">🚪 Logout</a>
    </div>
</div>

<div class="container">
    <?php if($msg): ?>
        <div class="alert alert-<?php echo $msg_type; ?>"><?php echo $msg; ?></div>
    <?php endif; ?>

    <!-- Statistics -->
    <div class="stats">
        <div class="stat-card">
            <div class="number"><?php echo $conn->query("SELECT COUNT(*) as c FROM students")->fetch_assoc()['c']; ?></div>
            <div class="label">👨‍🎓 Students</div>
        </div>
        <div class="stat-card">
            <div class="number"><?php echo $conn->query("SELECT COUNT(*) as c FROM teachers")->fetch_assoc()['c']; ?></div>
            <div class="label">👨‍🏫 Teachers</div>
        </div>
        <div class="stat-card">
            <div class="number"><?php echo $conn->query("SELECT COUNT(*) as c FROM parents")->fetch_assoc()['c']; ?></div>
            <div class="label">👨‍👩‍👦 Parents</div>
        </div>
        <div class="stat-card">
            <div class="number"><?php echo $conn->query("SELECT COUNT(*) as c FROM users WHERE status = 'pending'")->fetch_assoc()['c']; ?></div>
            <div class="label">⏳ Pending</div>
        </div>
    </div>

    <!-- Pending Users Section -->
    <?php
    $pending_users = $conn->query("SELECT u.*, 
        COALESCE(t.teacher_name, p.parent_name) as full_name 
        FROM users u 
        LEFT JOIN teachers t ON u.user_id = t.user_id 
        LEFT JOIN parents p ON u.user_id = p.user_id 
        WHERE u.status = 'pending'");
    if($pending_users->num_rows > 0): 
    ?>
    <div class="card pending-card">
        <h3 style="border-color: #ffc107;">⏳ Pending User Approvals (<?php echo $pending_users->num_rows; ?>)</h3>
        <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Full Name</th>
                    <th>Role</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($user = $pending_users->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['full_name'] ?? 'N/A'); ?></td>
                        <td><span class="pending-badge"><?php echo $user['role']; ?></span></td>
                        <td>
                            <a href="?approve_user=<?php echo $user['user_id']; ?>" class="action-btn approve" onclick="return confirm('Approve this user?')">✅ Approve</a>
                            <a href="?reject_user=<?php echo $user['user_id']; ?>" class="action-btn reject" onclick="return confirm('Reject and delete this user?')">❌ Reject</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Forms Grid -->
    <div class="grid">
        <!-- Add Student -->
        <div class="card">
            <h3>📝 Register Student</h3>
            <form method="POST" onsubmit="return validateStudentForm()">
                <label>Student Name *</label>
                <input type="text" name="student_name" placeholder="Full name" required>
                <label>Roll Number *</label>
                <input type="text" id="roll" name="roll" placeholder="Unique roll no" required>
                <label>Phone *</label>
                <input type="text" id="phone" name="phone" placeholder="01XXXXXXXXX" required>
                <label>Parent Name *</label>
                <input type="text" name="parent_name" placeholder="Parent/Guardian" required>
                <label>Class *</label>
                <select name="class_id" required>
                    <option value="">-- Select --</option>
                    <?php 
                    $res = $conn->query("SELECT * FROM classes"); 
                    while($r = $res->fetch_assoc()) 
                        echo "<option value='{$r['class_id']}'>{$r['class_name']}</option>"; 
                    ?>
                </select>
                <button type="submit" name="add_student" class="btn btn-primary">Add Student</button>
            </form>
        </div>

        <!-- Map Subject -->
        <div class="card">
            <h3>🔗 Class → Subject Mapping</h3>
            <form method="POST">
                <label>Class *</label>
                <select name="class_id" required>
                    <option value="">-- Select Class --</option>
                    <?php $res = $conn->query("SELECT * FROM classes"); 
                    while($r = $res->fetch_assoc()) echo "<option value='{$r['class_id']}'>{$r['class_name']}</option>"; ?>
                </select>
                <label>Subject *</label>
                <select name="subject_id" required>
                    <option value="">-- Select Subject --</option>
                    <?php $res = $conn->query("SELECT * FROM subjects ORDER BY subject_name"); 
                    while($r = $res->fetch_assoc()) echo "<option value='{$r['subject_id']}'>{$r['subject_name']}</option>"; ?>
                </select>
                <button type="submit" name="map_subject" class="btn btn-success">Map Subject</button>
            </form>
        </div>

        <!-- Assign Teacher -->
        <div class="card">
            <h3>👨‍🏫 Assign Teacher</h3>
            <form method="POST">
                <label>Teacher *</label>
                <select name="teacher_id" required>
                    <option value="">-- Select Teacher --</option>
                    <?php $res = $conn->query("SELECT * FROM teachers"); 
                    while($r = $res->fetch_assoc()) echo "<option value='{$r['teacher_id']}'>{$r['teacher_name']}</option>"; ?>
                </select>
                <label>Class → Subject *</label>
                <select name="class_subject_id" required>
                    <option value="">-- Select Class-Subject --</option>
                    <?php 
                    $res = $conn->query("SELECT cs.class_subject_id, c.class_name, s.subject_name 
                        FROM class_subjects cs 
                        JOIN classes c ON cs.class_id = c.class_id 
                        JOIN subjects s ON cs.subject_id = s.subject_id"); 
                    while($r = $res->fetch_assoc()) 
                        echo "<option value='{$r['class_subject_id']}'>{$r['class_name']} → {$r['subject_name']}</option>"; 
                    ?>
                </select>
                <button type="submit" name="assign_teacher" class="btn btn-warning">Assign Teacher</button>
            </form>
        </div>

        <!-- Add Notice -->
        <div class="card">
            <h3>📢 Publish Notice</h3>
            <form method="POST">
                <label>Title *</label>
                <input type="text" name="title" placeholder="Notice title" required>
                <label>Description *</label>
                <textarea name="description" placeholder="Notice details..." required></textarea>
                <button type="submit" name="add_notice" class="btn btn-primary">Publish Notice</button>
            </form>
        </div>
    </div>

    <!-- Students Table -->
    <div class="table-container">
        <h3>📋 All Students</h3>
        <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Roll</th>
                    <th>Phone</th>
                    <th>Parent</th>
                    <th>Class</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $students = $conn->query("SELECT s.*, c.class_name FROM students s JOIN classes c ON s.class_id = c.class_id ORDER BY s.student_id DESC");
                if($students->num_rows == 0) echo "<tr><td colspan='6' style='text-align:center; color:#999; padding:20px;'>No students found</td></tr>";
                while($st = $students->fetch_assoc()) {
                    echo "<tr>
                            <td>{$st['student_id']}</td>
                            <td>" . htmlspecialchars($st['student_name']) . "</td>
                            <td>" . htmlspecialchars($st['roll']) . "</td>
                            <td>" . htmlspecialchars($st['phone']) . "</td>
                            <td>" . htmlspecialchars($st['parent_name']) . "</td>
                            <td>" . htmlspecialchars($st['class_name']) . "</td>
                          </tr>";
                }
                ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<script>
function validateStudentForm() {
    let phone = document.getElementById("phone").value.trim();
    let roll = document.getElementById("roll").value.trim();
    
    if (!/^\d+$/.test(phone)) {
        alert("❌ Phone number must contain only numbers!");
        return false;
    }
    if (phone.length < 11) {
        alert("❌ Please enter a valid phone number (at least 11 digits)!");
        return false;
    }
    if (!/^01/.test(phone)) {
        alert("❌ Phone number must start with '01'!");
        return false;
    }
    if (roll.length < 3) {
        alert("❌ Roll number must be at least 3 characters!");
        return false;
    }
    return true;
}
</script>
</body>
</html>
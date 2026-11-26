<?php
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') { 
    header("Location: login.php"); 
    exit(); 
}

$uid = $_SESSION['user_id'];
$t_res = $conn->query("SELECT teacher_id, teacher_name FROM teachers WHERE user_id = $uid");
$t_data = $t_res->fetch_assoc();
$teacher_id = $t_data['teacher_id'] ?? 0;
$teacher_name = $t_data['teacher_name'] ?? 'Teacher';

// If teacher not found, logout
if($teacher_id == 0) {
    session_destroy();
    header("Location: login.php?error=Teacher account not found");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f7f6; }
        
        .nav { 
            background: linear-gradient(135deg, #2c3e50, #34495e);
            padding: 15px 30px; 
            color: white; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            flex-wrap: wrap;
            gap: 10px;
        }
        .nav .user-info {
            font-size: 16px;
        }
        .nav .user-info strong { color: #ffd700; }
        .nav a { 
            color: white; 
            text-decoration: none; 
            font-weight: bold; 
            background: #e74c3c; 
            padding: 8px 18px; 
            border-radius: 6px; 
            transition: 0.3s;
        }
        .nav a:hover { background: #c0392b; }
        
        .container { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
        .header-section {
            margin-bottom: 25px;
        }
        .header-section h2 { color: #2c3e50; font-size: 28px; }
        .header-section p { color: #7f8c8d; font-size: 16px; }
        
        .grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); 
            gap: 20px; 
            margin-top: 20px; 
        }
        
        .class-card { 
            background: white; 
            padding: 25px; 
            border-radius: 12px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.06); 
            border-left: 5px solid #3498db; 
            transition: transform 0.3s, box-shadow 0.3s;
            text-decoration: none; 
            color: inherit; 
            display: block; 
        }
        .class-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 8px 25px rgba(0,0,0,0.12); 
        }
        .class-card h3 { 
            margin: 0 0 10px 0; 
            color: #2c3e50;
            font-size: 20px;
        }
        .class-card .subject-name {
            color: #3498db;
            font-weight: 600;
            font-size: 16px;
        }
        .class-card p { 
            margin: 5px 0; 
            color: #7f8c8d; 
            font-size: 14px; 
        }
        .class-card .arrow {
            color: #3498db;
            margin-top: 15px;
            font-weight: bold;
            display: inline-block;
        }
        
        .no-data { 
            background: #fff; 
            padding: 40px; 
            border-radius: 12px; 
            text-align: center; 
            color: #95a5a6;
            grid-column: 1/-1;
        }
        .no-data .icon { font-size: 48px; display: block; margin-bottom: 15px; }
        
        @media (max-width: 768px) {
            .nav { flex-direction: column; text-align: center; }
            .grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="nav">
    <div class="user-info">
        👋 Welcome, <strong><?php echo htmlspecialchars($teacher_name); ?></strong> 👨‍🏫
    </div>
    <a href="logout.php">🚪 Logout</a>
</div>

<div class="container">
    <div class="header-section">
        <h2>📚 Your Assigned Classes & Subjects</h2>
        <p>Click on any class below to manage daily <strong>Attendance</strong> and <strong>Student Assessments</strong>.</p>
    </div>
    
    <div class="grid">
        <?php 
        $asg_query = "SELECT ta.assignment_id, c.class_name, s.subject_name 
                      FROM teacher_assignment ta 
                      JOIN class_subjects cs ON ta.class_subject_id = cs.class_subject_id 
                      JOIN classes c ON cs.class_id = c.class_id 
                      JOIN subjects s ON cs.subject_id = s.subject_id 
                      WHERE ta.teacher_id = ?";
        $stmt = $conn->prepare($asg_query);
        $stmt->bind_param("i", $teacher_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo "<a href='teacher_action.php?assignment_id={$row['assignment_id']}' class='class-card'>";
                echo "<h3>" . htmlspecialchars($row['class_name']) . "</h3>";
                echo "<p><span class='subject-name'>📖 " . htmlspecialchars($row['subject_name']) . "</span></p>";
                echo "<p class='arrow'>→ Open Class Panel</p>";
                echo "</a>";
            }
        } else {
            echo "<div class='no-data'>";
            echo "<span class='icon'>📭</span>";
            echo "<p>No classes assigned to you yet by Admin.</p>";
            echo "<p style='font-size:13px; color:#bdc3c7;'>Please contact the admin to get assigned.</p>";
            echo "</div>";
        }
        ?>
    </div>
</div>

</body>
</html>
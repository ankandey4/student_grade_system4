<?php
include 'db.php';

// Session validation
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') { 
    header("Location: login.php"); 
    exit(); 
}

$assignment_id = $_GET['assignment_id'] ?? 0;
if($assignment_id == 0) {
    die("❌ Invalid Assignment ID!");
}

// Get class and subject info
$info_q = "SELECT c.class_id, c.class_name, s.subject_name FROM teacher_assignment ta 
           JOIN class_subjects cs ON ta.class_subject_id = cs.class_subject_id
           JOIN classes c ON cs.class_id = c.class_id 
           JOIN subjects s ON cs.subject_id = s.subject_id 
           WHERE ta.assignment_id = ?";
$stmt_info = $conn->prepare($info_q); 
$stmt_info->bind_param("i", $assignment_id); 
$stmt_info->execute();
$info_res = $stmt_info->get_result()->fetch_assoc();

if (!$info_res) { 
    die("❌ Invalid Access or Assignment Not Found!"); 
}

$class_id = $info_res['class_id']; 
$class_name = $info_res['class_name']; 
$subject_name = $info_res['subject_name'];
$msg = ""; 
$msg_type = "";

// Auto-create Final Exam if not exists
$check_final = $conn->query("SELECT exam_config_id FROM exam_configurations WHERE assignment_id = $assignment_id AND exam_type = 'final'");
if($check_final->num_rows == 0) {
    $conn->query("INSERT INTO exam_configurations (assignment_id, exam_title, exam_type, total_marks) VALUES ($assignment_id, 'Final Exam', 'final', 70)");
}

// Calculate assessment totals
$total_allocated_res = $conn->query("SELECT SUM(total_marks) as total_allocated FROM exam_configurations WHERE assignment_id = $assignment_id AND exam_type = 'assessment'");
$total_allocated_row = $total_allocated_res->fetch_assoc();
$current_assessment_total = $total_allocated_row['total_allocated'] ?? 0;
$remaining_marks = 30 - $current_assessment_total;

// 1. Save Attendance
if (isset($_POST['save_attendance'])) {
    $attendance_date = $_POST['attendance_date'];
    $present_students = $_POST['attendance'] ?? [];
    $all_stud_res = $conn->query("SELECT student_id FROM students WHERE class_id = $class_id");
    
    while ($st = $all_stud_res->fetch_assoc()) {
        $sid = $st['student_id'];
        $status = in_array($sid, $present_students) ? 'Present' : 'Absent';
        $conn->query("INSERT INTO attendance (student_id, assignment_id, attendance_date, status) 
                      VALUES ($sid, $assignment_id, '$attendance_date', '$status') 
                      ON DUPLICATE KEY UPDATE status = '$status'");
    }
    $msg = "✅ Attendance updated successfully!"; 
    $msg_type = "success";
}

// 2. Create Assessment
if (isset($_POST['create_assessment'])) {
    $exam_title = trim($_POST['exam_title']);
    $input_marks = floatval($_POST['total_marks']);
    
    if ($input_marks <= 0) {
        $msg = "❌ Exam marks must be greater than 0.";
        $msg_type = "danger";
    } else if (($current_assessment_total + $input_marks) > 30) {
        $msg = "❌ Total Assessment marks cannot exceed 30. You have already used $current_assessment_total Marks. Maximum allowed is $remaining_marks Marks.";
        $msg_type = "danger";
    } else {
        $check_dup = $conn->prepare("SELECT exam_config_id FROM exam_configurations WHERE assignment_id = ? AND exam_title = ?");
        $check_dup->bind_param("is", $assignment_id, $exam_title);
        $check_dup->execute();
        
        if($check_dup->get_result()->num_rows > 0) {
            $msg = "❌ An assessment with this name already exists for this subject!";
            $msg_type = "danger";
        } else {
            $ins = $conn->prepare("INSERT INTO exam_configurations (assignment_id, exam_title, exam_type, total_marks) VALUES (?, ?, 'assessment', ?)");
            $ins->bind_param("isd", $assignment_id, $exam_title, $input_marks);
            if($ins->execute()) {
                header("Location: teacher_action.php?assignment_id=$assignment_id&active_exam=" . $conn->insert_id . "&msg=created"); 
                exit();
            }
        }
    }
}

if(isset($_GET['msg']) && $_GET['msg'] == 'created') {
    $msg = "✅ New Assessment created successfully!";
    $msg_type = "success";
}

// 3. Save Marks
if (isset($_POST['save_marks'])) {
    $exam_config_id = intval($_POST['exam_config_id']);
    $max_limit = floatval($_POST['max_marks']);
    $student_marks = $_POST['marks'] ?? [];
    
    $error_flag = false;
    foreach ($student_marks as $sid => $mark) {
        if ($mark !== "" && floatval($mark) > $max_limit) {
            $error_flag = true;
            break;
        }
    }
    
    if($error_flag) {
        $msg = "❌ Marks obtained cannot be greater than maximum exam marks ($max_limit).";
        $msg_type = "danger";
    } else {
        foreach ($student_marks as $sid => $mark) {
            if ($mark !== "") {
                $conn->query("INSERT INTO student_marks (student_id, exam_config_id, marks_obtained) 
                              VALUES ($sid, $exam_config_id, $mark) 
                              ON DUPLICATE KEY UPDATE marks_obtained = $mark");
            }
        }
        $msg = "✅ Student marks successfully updated!"; 
        $msg_type = "success";
    }
}

$selected_date = $_GET['attendance_date'] ?? date('Y-m-d');
$active_exam = $_GET['active_exam'] ?? 0;

// Recalculate after any updates
$total_allocated_res = $conn->query("SELECT SUM(total_marks) as total_allocated FROM exam_configurations WHERE assignment_id = $assignment_id AND exam_type = 'assessment'");
$current_assessment_total = $total_allocated_res->fetch_assoc()['total_allocated'] ?? 0;
$remaining_marks = 30 - $current_assessment_total;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Action Panel</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f6f9; padding: 20px; color: #333; }
        
        .back-btn { 
            display: inline-block; 
            margin-bottom: 15px; 
            color: #3498db; 
            text-decoration: none; 
            font-weight: bold;
            padding: 8px 16px;
            background: white;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .back-btn:hover { background: #e8f4fd; }
        
        .header-panel { 
            background: white; 
            padding: 20px 25px; 
            border-radius: 10px; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.06); 
            margin-bottom: 20px; 
            border-left: 5px solid #3498db; 
        }
        .header-panel h2 { 
            margin: 0; 
            font-size: 22px; 
            color: #2c3e50; 
        }
        .header-panel .sub-info {
            color: #7f8c8d;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .grid-sections { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 20px; 
        }
        
        .card { 
            background: white; 
            padding: 25px; 
            border-radius: 10px; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.06); 
            height: fit-content; 
        }
        .card h3 { 
            margin-top: 0; 
            color: #2c3e50; 
            border-bottom: 2px solid #ecf0f1; 
            padding-bottom: 12px; 
            font-size: 18px; 
        }
        
        .alert { 
            padding: 12px 18px; 
            border-radius: 6px; 
            margin-bottom: 20px; 
            font-weight: 500; 
            font-size: 14px; 
        }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .info-box { 
            background: #e8f0fe; 
            padding: 12px 16px; 
            border-radius: 6px; 
            font-size: 14px; 
            color: #1a73e8; 
            font-weight: 600; 
            margin-bottom: 15px; 
        }
        
        input[type="text"], input[type="number"], input[type="date"], select { 
            padding: 10px 12px; 
            width: 100%; 
            box-sizing: border-box; 
            border: 2px solid #e0e0e0; 
            border-radius: 6px; 
            margin-top: 5px; 
            font-size: 14px;
            transition: 0.3s;
        }
        input:focus, select:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52,152,219,0.2);
        }
        
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 12px; 
            font-size: 14px;
        }
        table, th, td { 
            border: 1px solid #ecf0f1; 
            padding: 8px 10px; 
            text-align: left; 
        }
        th { 
            background: #f8f9fc; 
            color: #2c3e50; 
            font-weight: 600; 
        }
        
        .btn { 
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white; 
            border: none; 
            padding: 12px; 
            border-radius: 6px; 
            cursor: pointer; 
            width: 100%; 
            font-size: 15px; 
            margin-top: 15px; 
            font-weight: 600;
            transition: 0.3s;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(39,174,96,0.3); }
        
        .btn-blue { 
            background: linear-gradient(135deg, #3498db, #2980b9);
        }
        .btn-blue:hover { box-shadow: 0 4px 15px rgba(52,152,219,0.3); }
        
        .btn-purple { 
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
        }
        .btn-purple:hover { box-shadow: 0 4px 15px rgba(155,89,182,0.3); }
        
        .warning-box {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #ffc107;
            margin: 10px 0;
        }
        
        .exam-select {
            margin: 10px 0;
        }
        
        @media (max-width: 768px) {
            .grid-sections { grid-template-columns: 1fr; }
            .header-panel h2 { font-size: 18px; }
        }
    </style>
</head>
<body>

<a href="teacher.php" class="back-btn">← Back to Dashboard</a>

<div class="header-panel">
    <h2>📚 <?php echo htmlspecialchars($class_name); ?> — <?php echo htmlspecialchars($subject_name); ?></h2>
    <div class="sub-info">Assignment ID: #<?php echo $assignment_id; ?></div>
</div>

<?php if($msg != ""): ?>
    <div class="alert alert-<?php echo $msg_type; ?>"><?php echo $msg; ?></div>
<?php endif; ?>

<div class="grid-sections">
    <!-- Attendance Section -->
    <div class="card">
        <h3>📊 Daily Attendance</h3>
        
        <form method="GET" action="teacher_action.php" style="margin-bottom: 15px;">
            <input type="hidden" name="assignment_id" value="<?php echo $assignment_id; ?>">
            <label style="font-weight: 600;">Select Date:</label>
            <input type="date" name="attendance_date" value="<?php echo $selected_date; ?>" onchange="this.form.submit()">
        </form>
        
        <form method="POST">
            <input type="hidden" name="attendance_date" value="<?php echo $selected_date; ?>">
            <div style="overflow-x: auto;">
            <table>
                <tr>
                    <th>Roll</th>
                    <th>Student Name</th>
                    <th style="text-align:center; width:70px;">Present</th>
                </tr>
                <?php
                $stud_res = $conn->query("SELECT student_id, student_name, roll FROM students WHERE class_id = $class_id ORDER BY roll ASC");
                if($stud_res->num_rows == 0) {
                    echo "<tr><td colspan='3' style='text-align:center; color:#999;'>No students in this class</td></tr>";
                }
                while ($st = $stud_res->fetch_assoc()) {
                    $check_att = $conn->query("SELECT status FROM attendance WHERE student_id = {$st['student_id']} AND assignment_id = $assignment_id AND attendance_date = '$selected_date'");
                    $att_row = $check_att->fetch_assoc();
                    $is_present = ($att_row && $att_row['status'] == 'Present') ? 'checked' : '';
                    echo "<tr>
                            <td>{$st['roll']}</td>
                            <td>" . htmlspecialchars($st['student_name']) . "</td>
                            <td style='text-align:center;'>
                                <input type='checkbox' name='attendance[]' value='{$st['student_id']}' $is_present style='width:20px;height:20px;cursor:pointer;'>
                            </td>
                          </tr>";
                }
                ?>
            </table>
            </div>
            <button type="submit" name="save_attendance" class="btn btn-blue">💾 Save Attendance</button>
        </form>
    </div>

    <!-- Marks Section -->
    <div class="card">
        <h3>📝 Marks Management</h3>
        
        <div class="info-box">
            📊 Assessment Used: <strong><?php echo $current_assessment_total; ?></strong> / 30 Marks | 
            Remaining: <strong><?php echo $remaining_marks; ?></strong> Marks
        </div>

        <?php if($remaining_marks > 0): ?>
            <form method="POST" style="background:#f8f9fc; padding:15px; border-radius:8px; border: 1px solid #ecf0f1; margin-bottom:20px;">
                <label style="font-weight: 600; color: #2c3e50;">➕ Create New Assessment:</label>
                <input type="text" name="exam_title" placeholder="e.g., Class Test 1, Quiz 1" required>
                
                <label style="font-weight: 600; margin-top:10px; display:block;">Assessment Marks (Max: <?php echo $remaining_marks; ?>):</label>
                <input type="number" name="total_marks" min="1" max="<?php echo $remaining_marks; ?>" placeholder="Enter marks" required>
                
                <button type="submit" name="create_assessment" class="btn">✨ Create Assessment</button>
            </form>
        <?php else: ?>
            <div class="warning-box">
                ⚠️ 30 Marks limit reached for Continuous Assessments! You cannot create more assessments.
            </div>
        <?php endif; ?>

        <label style="font-weight: 600; display:block; margin-top:10px;">📋 Select Exam:</label>
        <select class="exam-select" onchange="window.location.href='teacher_action.php?assignment_id=<?php echo $assignment_id; ?>&attendance_date=<?php echo $selected_date; ?>&active_exam='+this.value">
            <option value="">-- Choose Exam --</option>
            <?php 
            $exams = $conn->query("SELECT * FROM exam_configurations WHERE assignment_id = $assignment_id ORDER BY exam_type DESC, created_at DESC");
            while($e = $exams->fetch_assoc()) {
                $sel = ($active_exam == $e['exam_config_id']) ? "selected" : "";
                $type_label = ($e['exam_type'] == 'final') ? '🎯 Final' : '📝 Assessment';
                echo "<option value='{$e['exam_config_id']}' $sel>{$type_label} - {$e['exam_title']} (Max: {$e['total_marks']})</option>";
            }
            ?>
        </select>

        <?php if($active_exam > 0): 
            $exam_info = $conn->query("SELECT * FROM exam_configurations WHERE exam_config_id = $active_exam")->fetch_assoc();
            if($exam_info):
        ?>
            <form method="POST" style="margin-top: 15px;">
                <input type="hidden" name="exam_config_id" value="<?php echo $active_exam; ?>">
                <input type="hidden" name="max_marks" value="<?php echo $exam_info['total_marks']; ?>">
                
                <div style="background:#e8f8f5; padding:10px 15px; border-radius:6px; margin-bottom:10px;">
                    <strong>📌 <?php echo htmlspecialchars($exam_info['exam_title']); ?></strong>
                    <span style="color:#666; font-size:13px;">(Max: <?php echo $exam_info['total_marks']; ?> marks)</span>
                </div>
                
                <div style="overflow-x: auto;">
                <table>
                    <tr>
                        <th>Roll</th>
                        <th>Student</th>
                        <th>Marks</th>
                    </tr>
                    <?php
                    $stud_res_m = $conn->query("SELECT student_id, student_name, roll FROM students WHERE class_id = $class_id ORDER BY roll ASC");
                    if($stud_res_m->num_rows == 0) {
                        echo "<tr><td colspan='3' style='text-align:center; color:#999;'>No students</td></tr>";
                    }
                    while ($st = $stud_res_m->fetch_assoc()) {
                        $check_mark = $conn->query("SELECT marks_obtained FROM student_marks WHERE student_id = {$st['student_id']} AND exam_config_id = $active_exam");
                        $mark_row = $check_mark->fetch_assoc();
                        $current_mark = $mark_row ? $mark_row['marks_obtained'] : '';
                        echo "<tr>
                                <td>{$st['roll']}</td>
                                <td>" . htmlspecialchars($st['student_name']) . "</td>
                                <td><input type='number' step='0.01' min='0' max='{$exam_info['total_marks']}' 
                                    name='marks[{$st['student_id']}]' value='$current_mark' 
                                    placeholder='0-{$exam_info['total_marks']}' style='width:80px;'></td>
                              </tr>";
                    }
                    ?>
                </table>
                </div>
                <button type="submit" name="save_marks" class="btn btn-purple">💾 Save Marks</button>
            </form>
        <?php 
            endif;
        endif; 
        ?>
    </div>
</div>

</body>
</html>
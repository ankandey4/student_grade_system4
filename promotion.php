<?php
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { 
    header("Location: login.php"); 
    exit(); 
}

$msg = "";
$msg_type = "";
$selected_class = $_GET['class_id'] ?? 0;
$academic_year = date('Y') . '-' . (date('Y') + 1);

// ========== GET NEXT CLASS ID FUNCTION ==========
function getNextClassId($current_class_id) {
    // Class ID Mapping: 1→2, 2→3, 3→4, 4→5, 5→0 (No promotion)
    $mapping = [
        1 => 2,  // Class 6 → Class 7
        2 => 3,  // Class 7 → Class 8
        3 => 4,  // Class 8 → Class 9
        4 => 5,  // Class 9 → Class 10
        5 => 0   // Class 10 → No promotion
    ];
    return $mapping[$current_class_id] ?? 0;
}

// ========== GET CLASS NAME BY ID ==========
function getClassName($conn, $class_id) {
    $q = $conn->query("SELECT class_name FROM classes WHERE class_id = $class_id");
    $data = $q->fetch_assoc();
    return $data['class_name'] ?? 'Unknown';
}

// ========== GET STUDENT RESULT FUNCTION ==========
function getStudentResult($conn, $student_id) {
    $result = [
        'total' => 0,
        'grade' => 'Incomplete',
        'assessment_total' => 0,
        'final_total' => 0,
        'subject_results' => []
    ];
    
    // Get student's class
    $class_q = $conn->query("SELECT class_id FROM students WHERE student_id = $student_id");
    $class_data = $class_q->fetch_assoc();
    $class_id = $class_data['class_id'] ?? 0;
    
    if($class_id > 0) {
        // Get all subjects for this class
        $subjects = $conn->query("SELECT cs.class_subject_id, s.subject_name, ta.assignment_id 
                                  FROM class_subjects cs 
                                  JOIN subjects s ON cs.subject_id = s.subject_id
                                  JOIN teacher_assignment ta ON cs.class_subject_id = ta.class_subject_id
                                  WHERE cs.class_id = $class_id");
        
        $total_marks = 0;
        $subject_count = 0;
        $has_final = false;
        $failed = false;
        
        while($sub = $subjects->fetch_assoc()) {
            $asg_id = $sub['assignment_id'];
            
            // Get assessment marks
            $assess = $conn->query("SELECT SUM(sm.marks_obtained) as obtained 
                                   FROM student_marks sm 
                                   JOIN exam_configurations ec ON sm.exam_config_id = ec.exam_config_id 
                                   WHERE sm.student_id = $student_id AND ec.assignment_id = $asg_id AND ec.exam_type = 'assessment'");
            $assess_data = $assess->fetch_assoc();
            $assessment_mark = $assess_data['obtained'] ?? 0;
            $result['assessment_total'] += $assessment_mark;
            
            // Get final exam marks
            $final = $conn->query("SELECT sm.marks_obtained as fnm FROM student_marks sm 
                                  JOIN exam_configurations ec ON sm.exam_config_id = ec.exam_config_id 
                                  WHERE sm.student_id = $student_id AND ec.assignment_id = $asg_id AND ec.exam_type = 'final'");
            $final_data = $final->fetch_assoc();
            $final_mark = $final_data['fnm'] ?? null;
            
            if($final_mark !== null) {
                $has_final = true;
                $result['final_total'] += $final_mark;
                $subject_total = $assessment_mark + $final_mark;
                $total_marks += $subject_total;
                $subject_count++;
                
                $result['subject_results'][$sub['subject_name']] = [
                    'assessment' => $assessment_mark,
                    'final' => $final_mark,
                    'total' => $subject_total
                ];
                
                // Check if passed (minimum 40%)
                if($subject_total < 40) {
                    $failed = true;
                }
            }
        }
        
        if($has_final && $subject_count > 0) {
            $result['total'] = round($total_marks / $subject_count, 2);
            
            if($failed) {
                $result['grade'] = 'F';
            } else {
                $avg = $result['total'];
                if($avg >= 80) $result['grade'] = 'A+';
                elseif($avg >= 70) $result['grade'] = 'A';
                elseif($avg >= 60) $result['grade'] = 'A-';
                elseif($avg >= 50) $result['grade'] = 'B';
                elseif($avg >= 40) $result['grade'] = 'C';
                else $result['grade'] = 'F';
            }
        }
    }
    
    return $result;
}

// ========== PROCESS PROMOTION ==========
if (isset($_POST['promote_students'])) {
    $class_id = intval($_POST['class_id']);
    $students = $_POST['students'] ?? [];
    $action = $_POST['action'] ?? 'promote';
    
    if(empty($students)) {
        $msg = "❌ No students selected for promotion!";
        $msg_type = "danger";
    } else {
        $next_class_id = getNextClassId($class_id);
        
        if($next_class_id == 0) {
            $msg = "❌ This is the last class (Class 10)! No promotion possible.";
            $msg_type = "danger";
        } else {
            // Check if next class exists
            $check_next = $conn->query("SELECT class_id FROM classes WHERE class_id = $next_class_id");
            if($check_next->num_rows == 0) {
                $msg = "❌ Next class not found!";
                $msg_type = "danger";
            } else {
                $success_count = 0;
                $fail_count = 0;
                $promoted_students_list = [];
                $failed_students_list = [];
                
                foreach($students as $student_id) {
                    // Get student details
                    $stud_q = $conn->query("SELECT student_name, roll FROM students WHERE student_id = $student_id");
                    $student = $stud_q->fetch_assoc();
                    
                    // Get student's result
                    $result = getStudentResult($conn, $student_id);
                    $total = $result['total'] ?? 0;
                    $grade = $result['grade'] ?? 'F';
                    $passed = ($grade != 'F' && $grade != 'Incomplete');
                    
                    if($passed) {
                        // Update student's class
                        $update = $conn->query("UPDATE students SET class_id = $next_class_id WHERE student_id = $student_id");
                        
                        if($update) {
                            // Insert promotion history
                            $conn->query("INSERT INTO promotion_history 
                                         (student_id, from_class_id, to_class_id, academic_year, status, total_marks, grade, promoted_by) 
                                         VALUES ($student_id, $class_id, $next_class_id, '$academic_year', 'approved', $total, '$grade', {$_SESSION['user_id']})");
                            $success_count++;
                            $promoted_students_list[] = $student['student_name'];
                        }
                    } else {
                        // Failed students - keep in same class
                        $conn->query("INSERT INTO promotion_history 
                                     (student_id, from_class_id, to_class_id, academic_year, status, total_marks, grade, promoted_by) 
                                     VALUES ($student_id, $class_id, $class_id, '$academic_year', 'rejected', $total, '$grade', {$_SESSION['user_id']})");
                        $fail_count++;
                        $failed_students_list[] = $student['student_name'];
                    }
                }
                
                $msg = "✅ Promotion completed!<br>";
                $msg .= "📈 $success_count students promoted from " . getClassName($conn, $class_id) . " to " . getClassName($conn, $next_class_id) . "<br>";
                if(!empty($promoted_students_list)) {
                    $msg .= "✅ Promoted: " . implode(", ", $promoted_students_list) . "<br>";
                }
                if(!empty($failed_students_list)) {
                    $msg .= "❌ Retained (Failed): " . implode(", ", $failed_students_list);
                }
                $msg_type = "success";
            }
        }
    }
}

// ========== GENERATE RESULT SHEET ==========
if (isset($_POST['generate_result'])) {
    $class_id = intval($_POST['class_id']);
    $exam_type = $_POST['exam_type'] ?? 'final';
    
    // Generate CSV result
    $filename = "result_class_" . getClassName($conn, $class_id) . "_" . date('Y-m-d') . ".csv";
    $filepath = "results/" . $filename;
    
    // Create results directory if not exists
    if(!is_dir("results")) {
        mkdir("results", 0777, true);
    }
    
    $students = $conn->query("SELECT s.*, c.class_name FROM students s 
                              JOIN classes c ON s.class_id = c.class_id 
                              WHERE s.class_id = $class_id 
                              ORDER BY s.roll ASC");
    
    $fp = fopen($filepath, 'w');
    fputcsv($fp, ['Roll', 'Student Name', 'Subject', 'Assessment (Max 30)', 'Final (Max 70)', 'Total (100)', 'Grade', 'Status']);
    
    while($st = $students->fetch_assoc()) {
        $result = getStudentResult($conn, $st['student_id']);
        
        if(!empty($result['subject_results'])) {
            foreach($result['subject_results'] as $subject => $marks) {
                $grade = getGrade($marks['total']);
                $status = ($grade != 'F' && $grade != 'Incomplete') ? 'Pass' : 'Fail';
                fputcsv($fp, [
                    $st['roll'],
                    $st['student_name'],
                    $subject,
                    $marks['assessment'],
                    $marks['final'],
                    $marks['total'],
                    $grade,
                    $status
                ]);
            }
        } else {
            fputcsv($fp, [
                $st['roll'],
                $st['student_name'],
                'No marks available',
                'N/A',
                'N/A',
                'N/A',
                'Incomplete',
                'Pending'
            ]);
        }
    }
    
    fclose($fp);
    
    // Save to database
    $conn->query("INSERT INTO result_sheets (class_id, academic_year, exam_type, generated_by, file_path) 
                  VALUES ($class_id, '$academic_year', '$exam_type', {$_SESSION['user_id']}, '$filepath')");
    
    $msg = "✅ Result sheet generated! <a href='$filepath' target='_blank'>📄 Download CSV</a>";
    $msg_type = "success";
}

// ========== GET GRADE FUNCTION ==========
function getGrade($marks) {
    if($marks >= 80) return 'A+';
    elseif($marks >= 70) return 'A';
    elseif($marks >= 60) return 'A-';
    elseif($marks >= 50) return 'B';
    elseif($marks >= 40) return 'C';
    else return 'F';
}

// ========== GET PROMOTION HISTORY ==========
$promotion_history = $conn->query("SELECT ph.*, 
                                   s.student_name, s.roll,
                                   fc.class_name as from_class,
                                   tc.class_name as to_class
                                   FROM promotion_history ph
                                   JOIN students s ON ph.student_id = s.student_id
                                   JOIN classes fc ON ph.from_class_id = fc.class_id
                                   JOIN classes tc ON ph.to_class_id = tc.class_id
                                   ORDER BY ph.promotion_date DESC
                                   LIMIT 50");

// ========== GET CLASSES FOR DROPDOWN ==========
$classes = $conn->query("SELECT * FROM classes ORDER BY class_id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Promotion System</title>
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
            flex-wrap: wrap;
            gap: 10px;
        }
        .navbar h1 { font-size: 24px; }
        .navbar h1 span { color: #ffd700; }
        .navbar .nav-links {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .navbar a {
            color: white;
            text-decoration: none;
            padding: 8px 18px;
            border-radius: 5px;
            transition: 0.3s;
        }
        .navbar a.dashboard { background: #3498db; }
        .navbar a.promotion { background: #27ae60; }
        .navbar a.logout { background: #e74c3c; }
        .navbar a:hover { opacity: 0.8; transform: translateY(-2px); }
        
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
        
        .grid-2 {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
        }
        
        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }
        .card h3 {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 12px;
            margin-bottom: 18px;
            font-size: 18px;
        }
        .card h3 .icon { margin-right: 8px; }
        .card label { font-weight: 600; color: #333; display: block; margin-top: 10px; font-size: 14px; }
        .card select, .card input {
            width: 100%;
            padding: 10px 12px;
            margin: 5px 0 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
        }
        .card select:focus, .card input:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52,152,219,0.2);
        }
        
        .btn {
            padding: 10px 24px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn-primary { background: #3498db; color: white; }
        .btn-primary:hover { background: #2980b9; }
        .btn-success { background: #27ae60; color: white; }
        .btn-success:hover { background: #229954; }
        .btn-warning { background: #f39c12; color: white; }
        .btn-warning:hover { background: #d68910; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-danger:hover { background: #c0392b; }
        .btn-info { background: #17a2b8; color: white; }
        .btn-info:hover { background: #138496; }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        th {
            background: #2c3e50;
            color: white;
            padding: 10px 12px;
            text-align: left;
        }
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #ecf0f1;
        }
        tr:hover td { background: #f8f9fa; }
        
        .badge-pass { background: #27ae60; color: white; padding: 3px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .badge-fail { background: #e74c3c; color: white; padding: 3px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .badge-pending { background: #f39c12; color: white; padding: 3px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        
        .grade-Aplus { color: #27ae60; font-weight: 700; }
        .grade-A { color: #2ecc71; font-weight: 700; }
        .grade-Aminus { color: #f1c40f; font-weight: 700; }
        .grade-B { color: #f39c12; font-weight: 700; }
        .grade-C { color: #e67e22; font-weight: 700; }
        .grade-F { color: #e74c3c; font-weight: 700; }
        .grade-Incomplete { color: #95a5a6; font-weight: 700; }
        
        .scroll-table {
            max-height: 400px;
            overflow-y: auto;
        }
        .scroll-table::-webkit-scrollbar {
            width: 6px;
        }
        .scroll-table::-webkit-scrollbar-thumb {
            background: #3498db;
            border-radius: 3px;
        }
        
        .promotion-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .class-info {
            background: #e8f0fe;
            padding: 10px 15px;
            border-radius: 6px;
            margin: 10px 0;
            border-left: 4px solid #3498db;
        }
        .class-info strong { color: #2c3e50; }
        
        @media (max-width: 992px) {
            .grid-2 { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .navbar { flex-direction: column; text-align: center; }
            .navbar .nav-links { justify-content: center; }
        }
    </style>
</head>
<body>
<div class="navbar">
    <h1>🎓 <span>Promotion</span> System</h1>
    <div class="nav-links">
        <a href="admin.php" class="dashboard">📊 Dashboard</a>
        <a href="promotion.php" class="promotion">🎯 Promotion</a>
        <a href="logout.php" class="logout">🚪 Logout</a>
    </div>
</div>

<div class="container">
    <?php if($msg): ?>
        <div class="alert alert-<?php echo $msg_type; ?>"><?php echo $msg; ?></div>
    <?php endif; ?>

    <div class="grid-2">
        <!-- LEFT: Promotion Management -->
        <div>
            <div class="card">
                <h3>🎯 Student Promotion</h3>
                
                <div class="class-info">
                    <strong>📌 Class Mapping:</strong> 
                    Class 6 (ID:1) → Class 7 (ID:2) → Class 8 (ID:3) → Class 9 (ID:4) → Class 10 (ID:5)
                </div>
                
                <form method="GET" action="promotion.php">
                    <label>Select Class for Promotion:</label>
                    <select name="class_id" onchange="this.form.submit()">
                        <option value="">-- Select Class --</option>
                        <?php 
                        $classes->data_seek(0);
                        while($cls = $classes->fetch_assoc()): 
                            $sel = ($selected_class == $cls['class_id']) ? "selected" : "";
                            $next_id = getNextClassId($cls['class_id']);
                            $next_name = ($next_id > 0) ? getClassName($conn, $next_id) : "No Promotion";
                        ?>
                            <option value="<?php echo $cls['class_id']; ?>" <?php echo $sel; ?>>
                                <?php echo $cls['class_name']; ?> (ID: <?php echo $cls['class_id']; ?>) 
                                → <?php echo $next_name; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </form>

                <?php if($selected_class > 0): 
                    $next_class_id = getNextClassId($selected_class);
                    
                    if($next_class_id == 0):
                ?>
                    <div class="alert alert-warning">
                        ⚠️ This is the last class (Class 10). No promotion possible.
                    </div>
                <?php else: 
                    // Get students with results
                    $students_q = $conn->query("SELECT s.* 
                                                FROM students s
                                                WHERE s.class_id = $selected_class
                                                ORDER BY s.roll ASC");
                ?>
                    <form method="POST">
                        <input type="hidden" name="class_id" value="<?php echo $selected_class; ?>">
                        
                        <div style="overflow-x: auto;">
                            <table>
                                <thead>
                                    <tr>
                                        <th style="width:40px;"><input type="checkbox" id="selectAll" onchange="toggleAll(this)"></th>
                                        <th>Roll</th>
                                        <th>Student Name</th>
                                        <th>Total Marks</th>
                                        <th>Grade</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $student_count = 0;
                                    while($st = $students_q->fetch_assoc()): 
                                        $student_count++;
                                        $result = getStudentResult($conn, $st['student_id']);
                                        $total = $result['total'] ?? 0;
                                        $grade = $result['grade'] ?? 'Incomplete';
                                        $passed = ($grade != 'F' && $grade != 'Incomplete');
                                        $grade_class = 'grade-' . str_replace('+', 'plus', $grade);
                                        $grade_class = str_replace('-', 'minus', $grade_class);
                                    ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="students[]" value="<?php echo $st['student_id']; ?>" 
                                                       <?php echo $passed ? 'checked' : 'disabled'; ?>>
                                            </td>
                                            <td><?php echo $st['roll']; ?></td>
                                            <td><?php echo htmlspecialchars($st['student_name']); ?></td>
                                            <td><strong><?php echo $total; ?></strong></td>
                                            <td class="<?php echo $grade_class; ?>"><strong><?php echo $grade; ?></strong></td>
                                            <td>
                                                <?php if($passed): ?>
                                                    <span class="badge-pass">✅ Pass</span>
                                                <?php elseif($grade == 'Incomplete'): ?>
                                                    <span class="badge-pending">⏳ Pending</span>
                                                <?php else: ?>
                                                    <span class="badge-fail">❌ Fail</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                    <?php if($student_count == 0): ?>
                                        <tr><td colspan="6" style="text-align:center; color:#999; padding:20px;">No students found in this class</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php if($student_count > 0): ?>
                            <div class="promotion-actions">
                                <button type="submit" name="promote_students" class="btn btn-success" onclick="return confirm('Promote selected students to <?php echo getClassName($conn, $next_class_id); ?>?')">
                                    🎯 Promote to <?php echo getClassName($conn, $next_class_id); ?>
                                </button>
                                <button type="submit" name="generate_result" class="btn btn-info" formnovalidate>
                                    📄 Generate Result Sheet
                                </button>
                                <input type="hidden" name="exam_type" value="final">
                            </div>
                            <p style="font-size:13px; color:#95a5a6; margin-top:10px;">
                                * Only passed students (Grade A+ to C) can be promoted. 
                                Failed students will be retained in same class.
                            </p>
                        <?php endif; ?>
                    </form>
                <?php endif; endif; ?>
            </div>
            
            <!-- Promotion History -->
            <div class="card">
                <h3>📜 Promotion History</h3>
                <div class="scroll-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Grade</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($promotion_history->num_rows > 0): ?>
                                <?php while($ph = $promotion_history->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($ph['student_name']); ?> (<?php echo $ph['roll']; ?>)</td>
                                        <td><?php echo $ph['from_class']; ?></td>
                                        <td><?php echo $ph['to_class']; ?></td>
                                        <td><?php echo $ph['grade'] ?? 'N/A'; ?></td>
                                        <td>
                                            <?php if($ph['status'] == 'approved'): ?>
                                                <span class="badge-pass">✅ Approved</span>
                                            <?php elseif($ph['status'] == 'pending'): ?>
                                                <span class="badge-pending">⏳ Pending</span>
                                            <?php else: ?>
                                                <span class="badge-fail">❌ Rejected</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d M Y', strtotime($ph['promotion_date'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" style="text-align:center; color:#999; padding:20px;">No promotion history yet</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- RIGHT: Quick Stats & Info -->
        <div>
            <div class="card">
                <h3>📊 Quick Stats</h3>
                <?php
                $total_students = $conn->query("SELECT COUNT(*) as c FROM students")->fetch_assoc()['c'];
                $total_promoted = $conn->query("SELECT COUNT(*) as c FROM promotion_history WHERE status = 'approved'")->fetch_assoc()['c'];
                $pending_promotions = $conn->query("SELECT COUNT(*) as c FROM promotion_history WHERE status = 'pending'")->fetch_assoc()['c'];
                ?>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                    <div style="background:#f8f9fa; padding:15px; border-radius:8px; text-align:center;">
                        <div style="font-size:28px; font-weight:700; color:#3498db;"><?php echo $total_students; ?></div>
                        <div style="font-size:13px; color:#95a5a6;">Total Students</div>
                    </div>
                    <div style="background:#f8f9fa; padding:15px; border-radius:8px; text-align:center;">
                        <div style="font-size:28px; font-weight:700; color:#27ae60;"><?php echo $total_promoted; ?></div>
                        <div style="font-size:13px; color:#95a5a6;">Promoted</div>
                    </div>
                    <div style="background:#f8f9fa; padding:15px; border-radius:8px; text-align:center;">
                        <div style="font-size:28px; font-weight:700; color:#f39c12;"><?php echo $pending_promotions; ?></div>
                        <div style="font-size:13px; color:#95a5a6;">Pending</div>
                    </div>
                    <div style="background:#f8f9fa; padding:15px; border-radius:8px; text-align:center;">
                        <div style="font-size:28px; font-weight:700; color:#e74c3c;"><?php echo $total_students - $total_promoted - $pending_promotions; ?></div>
                        <div style="font-size:13px; color:#95a5a6;">Not Promoted</div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <h3>📌 Grading System</h3>
                <table style="font-size:13px;">
                    <tr><td>80% - 100%</td><td><span class="grade-Aplus">A+</span></td><td>Outstanding</td></tr>
                    <tr><td>70% - 79%</td><td><span class="grade-A">A</span></td><td>Excellent</td></tr>
                    <tr><td>60% - 69%</td><td><span class="grade-Aminus">A-</span></td><td>Very Good</td></tr>
                    <tr><td>50% - 59%</td><td><span class="grade-B">B</span></td><td>Good</td></tr>
                    <tr><td>40% - 49%</td><td><span class="grade-C">C</span></td><td>Satisfactory</td></tr>
                    <tr><td>Below 40%</td><td><span class="grade-F">F</span></td><td>Fail</td></tr>
                </table>
                <p style="font-size:12px; color:#95a5a6; margin-top:10px;">
                    * Students with grade C or above are eligible for promotion.
                </p>
            </div>
            
            <div class="card">
                <h3>📁 Result Sheets</h3>
                <?php
                $result_sheets = $conn->query("SELECT * FROM result_sheets ORDER BY generated_at DESC LIMIT 10");
                if($result_sheets->num_rows > 0):
                ?>
                    <ul style="list-style:none; padding:0;">
                    <?php while($rs = $result_sheets->fetch_assoc()): ?>
                        <li style="padding:8px 0; border-bottom:1px solid #ecf0f1;">
                            <a href="<?php echo $rs['file_path']; ?>" target="_blank" style="color:#3498db; text-decoration:none;">
                                📄 <?php echo getClassName($conn, $rs['class_id']); ?> - <?php echo date('d M Y', strtotime($rs['generated_at'])); ?>
                            </a>
                            <span style="font-size:12px; color:#95a5a6; float:right;"><?php echo $rs['exam_type']; ?></span>
                        </li>
                    <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p style="color:#95a5a6;">No result sheets generated yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function toggleAll(source) {
    let checkboxes = document.querySelectorAll('input[name="students[]"]');
    for(let i = 0; i < checkboxes.length; i++) {
        if(!checkboxes[i].disabled) {
            checkboxes[i].checked = source.checked;
        }
    }
}
</script>
</body>
</html>
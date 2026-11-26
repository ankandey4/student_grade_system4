<?php
include 'db.php';

// Session check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'parent') { 
    header("Location: login.php"); 
    exit(); 
}

$uid = $_SESSION['user_id'];

// Get parent_id
$parent_res = $conn->query("SELECT parent_id FROM parents WHERE user_id = $uid");
$parent_data = $parent_res->fetch_assoc();
$parent_id = $parent_data['parent_id'] ?? 0;

// Get student data
$stud_q = $conn->query("SELECT s.*, c.class_name FROM students s JOIN classes c ON s.class_id = c.class_id WHERE s.parent_id = $parent_id");
$student = $stud_q->fetch_assoc();
$student_id = $student['student_id'] ?? 0;

$report_card = [];

if($student_id > 0) {
    // Get all subjects for this class
    $subjects_q = $conn->query("SELECT cs.class_subject_id, sub.subject_name, ta.assignment_id 
                                FROM class_subjects cs 
                                JOIN subjects sub ON cs.subject_id = sub.subject_id
                                JOIN teacher_assignment ta ON cs.class_subject_id = ta.class_subject_id
                                WHERE cs.class_id = {$student['class_id']}");
    
    while($sub = $subjects_q->fetch_assoc()) {
        $asg_id = $sub['assignment_id'];
        
        // Get assessment marks sum
        $assess_res = $conn->query("SELECT SUM(sm.marks_obtained) as obtained 
                                    FROM student_marks sm 
                                    JOIN exam_configurations ec ON sm.exam_config_id = ec.exam_config_id 
                                    WHERE sm.student_id = $student_id AND ec.assignment_id = $asg_id AND ec.exam_type = 'assessment'");
        $assess_data = $assess_res->fetch_assoc();
        $final_assessment_mark = $assess_data['obtained'] ?? 0;
        
        // Get final exam marks
        $final_res = $conn->query("SELECT sm.marks_obtained as fnm FROM student_marks sm 
                                   JOIN exam_configurations ec ON sm.exam_config_id = ec.exam_config_id 
                                   WHERE sm.student_id = $student_id AND ec.assignment_id = $asg_id AND ec.exam_type = 'final'");
        $final_data = $final_res->fetch_assoc();
        $final_mark = ($final_data !== null) ? $final_data['fnm'] : null;
        
        // Calculate total
        $total_mark = ($final_mark !== null) ? ($final_assessment_mark + $final_mark) : $final_assessment_mark;
        $total_mark = round($total_mark, 2);
        
        // Calculate grade
        $grade = "Incomplete";
        if($final_mark !== null) {
            if($total_mark >= 80) $grade = "A+";
            elseif($total_mark >= 70) $grade = "A";
            elseif($total_mark >= 60) $grade = "A-";
            elseif($total_mark >= 50) $grade = "B";
            elseif($total_mark >= 40) $grade = "C";
            else $grade = "F";
        }
        
        $report_card[] = [
            'subject' => $sub['subject_name'],
            'assessment' => $final_assessment_mark,
            'final' => ($final_mark !== null) ? $final_mark : 'Not Published',
            'total' => $total_mark,
            'grade' => $grade
        ];
    }
}

// ========== SUBJECT-WISE ATTENDANCE ONLY ==========
$subject_attendance = [];
if($student_id > 0) {
    $sub_att_q = $conn->query("
        SELECT 
            s.subject_name,
            COUNT(a.attendance_id) as total,
            SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as present
        FROM attendance a
        JOIN teacher_assignment ta ON a.assignment_id = ta.assignment_id
        JOIN class_subjects cs ON ta.class_subject_id = cs.class_subject_id
        JOIN subjects s ON cs.subject_id = s.subject_id
        WHERE a.student_id = $student_id
        GROUP BY s.subject_name
        ORDER BY s.subject_name ASC
    ");
    while($row = $sub_att_q->fetch_assoc()) {
        $subject_attendance[] = $row;
    }
}

// ========== GET ALL NOTICES ==========
$notices = $conn->query("SELECT * FROM notices ORDER BY publish_date DESC, notice_id DESC");
$notice_count = $notices->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Portal</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; color: #333; }
        
        .header { 
            background: linear-gradient(135deg, #6f42c1, #9b59b6);
            padding: 18px 30px; 
            color: white; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            flex-wrap: wrap;
            gap: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header h2 { margin: 0; font-size: 24px; }
        .header .user-info { font-size: 14px; opacity: 0.9; }
        .header a { 
            color: white; 
            text-decoration: none; 
            font-weight: bold; 
            background: #e74c3c; 
            padding: 8px 18px; 
            border-radius: 6px; 
            transition: 0.3s;
        }
        .header a:hover { background: #c0392b; }
        
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        
        /* Two Column Layout */
        .two-column {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            margin-bottom: 25px;
        }
        
        .section { 
            background: white; 
            padding: 25px; 
            border-radius: 12px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.06); 
        }
        .section h3 { 
            margin-top: 0; 
            color: #6f42c1; 
            border-bottom: 2px solid #ecf0f1; 
            padding-bottom: 12px; 
            font-size: 20px; 
        }
        .section h3 .badge-count {
            background: #6f42c1;
            color: white;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 14px;
            margin-left: 8px;
        }
        
        .profile-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .profile-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }
        .profile-item .label { font-size: 12px; color: #95a5a6; text-transform: uppercase; letter-spacing: 0.5px; }
        .profile-item .value { font-size: 18px; font-weight: 600; color: #2c3e50; }
        
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 15px; 
            font-size: 14px;
        }
        table, th, td { 
            border: 1px solid #ecf0f1; 
            padding: 12px; 
            text-align: left; 
        }
        th { 
            background: #f8f9fc; 
            color: #6f42c1; 
            font-weight: 600; 
        }
        tr:hover td { background: #f8f9fa; }
        
        .badge { 
            padding: 4px 12px; 
            border-radius: 20px; 
            font-weight: 600; 
            color: white; 
            font-size: 13px; 
            display: inline-block; 
        }
        .badge-Aplus { background: #27ae60; }
        .badge-A { background: #2ecc71; }
        .badge-Aminus { background: #f1c40f; color: #333; }
        .badge-B { background: #f39c12; }
        .badge-C { background: #e67e22; }
        .badge-F { background: #e74c3c; }
        .badge-Incomplete { background: #95a5a6; }
        
        /* ===== SUBJECT-WISE ATTENDANCE TABLE ===== */
        .att-subject-table {
            font-size: 14px;
            margin-top: 10px;
        }
        .att-subject-table th {
            background: #6f42c1;
            color: white;
        }
        .att-subject-table th:first-child { border-radius: 4px 0 0 0; }
        .att-subject-table th:last-child { border-radius: 0 4px 0 0; }
        
        .progress-bar {
            width: 100%;
            height: 10px;
            background: #ecf0f1;
            border-radius: 5px;
            overflow: hidden;
            margin-top: 4px;
            min-width: 80px;
        }
        .progress-bar .fill {
            height: 100%;
            border-radius: 5px;
            transition: width 0.8s ease;
        }
        .progress-bar .fill.green { background: linear-gradient(90deg, #27ae60, #2ecc71); }
        .progress-bar .fill.orange { background: linear-gradient(90deg, #f39c12, #f1c40f); }
        .progress-bar .fill.red { background: linear-gradient(90deg, #e74c3c, #c0392b); }
        
        .att-rate-high { color: #27ae60; font-weight: 700; }
        .att-rate-medium { color: #f39c12; font-weight: 700; }
        .att-rate-low { color: #e74c3c; font-weight: 700; }
        
        .no-attendance {
            text-align: center;
            color: #95a5a6;
            padding: 30px 0;
        }
        .no-attendance .icon { font-size: 36px; display: block; margin-bottom: 10px; }
        
        /* ===== NOTICE STYLES ===== */
        .notice-list {
            max-height: 500px;
            overflow-y: auto;
        }
        .notice-list::-webkit-scrollbar {
            width: 6px;
        }
        .notice-list::-webkit-scrollbar-thumb {
            background: #6f42c1;
            border-radius: 3px;
        }
        .notice-list::-webkit-scrollbar-track {
            background: #f0f0f0;
            border-radius: 3px;
        }
        
        .notice-item {
            padding: 15px;
            border-bottom: 1px solid #ecf0f1;
            transition: 0.3s;
            border-left: 4px solid transparent;
        }
        .notice-item:hover {
            background: #f8f9fa;
            border-left-color: #6f42c1;
        }
        .notice-item:last-child {
            border-bottom: none;
        }
        .notice-item .notice-title {
            font-weight: 600;
            color: #2c3e50;
            font-size: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .notice-item .notice-title .date {
            font-weight: normal;
            font-size: 12px;
            color: #95a5a6;
            background: #f0f0f0;
            padding: 2px 10px;
            border-radius: 12px;
        }
        .notice-item .notice-desc {
            color: #555;
            font-size: 14px;
            margin-top: 6px;
            line-height: 1.6;
        }
        .no-notice {
            text-align: center;
            color: #95a5a6;
            padding: 30px 0;
        }
        .no-notice .icon { font-size: 36px; display: block; margin-bottom: 10px; }
        
        .no-student {
            text-align: center;
            padding: 40px 20px;
        }
        .no-student .icon { font-size: 48px; display: block; margin-bottom: 15px; }
        .no-student h4 { color: #e74c3c; font-size: 20px; }
        .no-student p { color: #95a5a6; }
        
        @media (max-width: 992px) {
            .two-column {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .header { flex-direction: column; text-align: center; }
            .profile-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="header">
    <div>
        <h2>👨‍👩‍👦 Parent Portal</h2>
        <div class="user-info">Welcome! Track your child's academic progress</div>
    </div>
    <a href="logout.php">🚪 Logout</a>
</div>

<div class="container">

<?php if($student_id > 0): ?>
    <!-- TWO COLUMN LAYOUT -->
    <div class="two-column">
        
        <!-- LEFT COLUMN -->
        <div>
            <!-- Student Profile -->
            <div class="section">
                <h3>👤 Student Profile</h3>
                <div class="profile-grid">
                    <div class="profile-item">
                        <div class="label">Student Name</div>
                        <div class="value"><?php echo htmlspecialchars($student['student_name']); ?></div>
                    </div>
                    <div class="profile-item">
                        <div class="label">Roll Number</div>
                        <div class="value"><?php echo htmlspecialchars($student['roll']); ?></div>
                    </div>
                    <div class="profile-item">
                        <div class="label">Class</div>
                        <div class="value"><?php echo htmlspecialchars($student['class_name']); ?></div>
                    </div>
                    <div class="profile-item">
                        <div class="label">Phone</div>
                        <div class="value"><?php echo htmlspecialchars($student['phone'] ?? 'N/A'); ?></div>
                    </div>
                </div>
            </div>

            <!-- Report Card -->
            <div class="section">
                <h3>📊 Academic Progress Report</h3>
                <p style="font-size: 13px; color: #95a5a6; margin-top: -5px;">
                    * Assessment: Total marks from all class tests (Max 30) | Final: Final exam (Max 70)
                </p>
                <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Assessment (Max 30)</th>
                            <th>Final (Max 70)</th>
                            <th>Total (100)</th>
                            <th>Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($report_card)): ?>
                            <tr><td colspan="5" style="text-align:center; color:#999; padding:20px;">No marks available yet</td></tr>
                        <?php else: ?>
                            <?php foreach($report_card as $rc): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($rc['subject']); ?></strong></td>
                                <td style="color: #3498db; font-weight: 600;"><?php echo $rc['assessment']; ?></td>
                                <td style="color: #27ae60; font-weight: 600;"><?php echo $rc['final']; ?></td>
                                <td><strong><?php echo $rc['total']; ?></strong></td>
                                <td>
                                    <?php 
                                    $grade_class = $rc['grade'];
                                    if($grade_class == 'A+') $cls = 'Aplus';
                                    elseif($grade_class == 'A') $cls = 'A';
                                    elseif($grade_class == 'A-') $cls = 'Aminus';
                                    elseif($grade_class == 'B') $cls = 'B';
                                    elseif($grade_class == 'C') $cls = 'C';
                                    elseif($grade_class == 'F') $cls = 'F';
                                    else $cls = 'Incomplete';
                                    ?>
                                    <span class="badge badge-<?php echo $cls; ?>"><?php echo $rc['grade']; ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>

            <!-- ===== SUBJECT-WISE ATTENDANCE ONLY ===== -->
            <div class="section">
                <h3>📅 Subject-wise Attendance</h3>
                
                <?php if(!empty($subject_attendance)): ?>
                    <div style="overflow-x: auto;">
                        <table class="att-subject-table">
                            <thead>
                                <tr>
                                    <th style="width:30%;">Subject</th>
                                    <th style="text-align:center; width:20%;">Present</th>
                                    <th style="text-align:center; width:20%;">Total</th>
                                    <th style="text-align:center; width:30%;">Attendance Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($subject_attendance as $sub_att): 
                                    $rate = $sub_att['total'] > 0 ? round(($sub_att['present'] / $sub_att['total']) * 100, 1) : 0;
                                    if($rate >= 80) {
                                        $color = 'green';
                                        $class = 'att-rate-high';
                                    } elseif($rate >= 60) {
                                        $color = 'orange';
                                        $class = 'att-rate-medium';
                                    } else {
                                        $color = 'red';
                                        $class = 'att-rate-low';
                                    }
                                ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($sub_att['subject_name']); ?></strong></td>
                                        <td style="text-align:center;"><?php echo $sub_att['present']; ?></td>
                                        <td style="text-align:center;"><?php echo $sub_att['total']; ?></td>
                                        <td>
                                            <span class="<?php echo $class; ?>"><?php echo $rate; ?>%</span>
                                            <div class="progress-bar">
                                                <div class="fill <?php echo $color; ?>" style="width: <?php echo $rate; ?>%;"></div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-attendance">
                        <span class="icon">📭</span>
                        <p>No attendance records found yet.</p>
                        <p style="font-size:13px; color:#bdc3c7;">Attendance will appear here once teachers mark attendance.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- RIGHT COLUMN - NOTICES -->
        <div class="section" style="position: sticky; top: 20px; align-self: start;">
            <h3>
                📢 Notice Board
                <?php if($notice_count > 0): ?>
                    <span class="badge-count"><?php echo $notice_count; ?></span>
                <?php endif; ?>
            </h3>
            
            <div class="notice-list">
                <?php if($notice_count > 0): ?>
                    <?php while($notice = $notices->fetch_assoc()): ?>
                        <div class="notice-item">
                            <div class="notice-title">
                                <span><?php echo htmlspecialchars($notice['title']); ?></span>
                                <span class="date">📅 <?php echo date('d M Y', strtotime($notice['publish_date'])); ?></span>
                            </div>
                            <div class="notice-desc">
                                <?php echo nl2br(htmlspecialchars($notice['description'])); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-notice">
                        <span class="icon">📭</span>
                        <p>No notices published yet.</p>
                        <p style="font-size:13px; color:#bdc3c7;">Check back later for updates.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
    </div>

<?php else: ?>
    <!-- NO STUDENT -->
    <div class="two-column" style="grid-template-columns: 1fr;">
        <div class="section no-student">
            <span class="icon">👤</span>
            <h4>No Student Linked!</h4>
            <p>This parent account is not connected to any student.</p>
            <p style="font-size:13px; color:#95a5a6; margin-top:10px;">Please contact the school administration to link your child.</p>
        </div>
        
        <div class="section">
            <h3>
                📢 Notice Board
                <?php if($notice_count > 0): ?>
                    <span class="badge-count"><?php echo $notice_count; ?></span>
                <?php endif; ?>
            </h3>
            <div class="notice-list">
                <?php if($notice_count > 0): ?>
                    <?php while($notice = $notices->fetch_assoc()): ?>
                        <div class="notice-item">
                            <div class="notice-title">
                                <span><?php echo htmlspecialchars($notice['title']); ?></span>
                                <span class="date">📅 <?php echo date('d M Y', strtotime($notice['publish_date'])); ?></span>
                            </div>
                            <div class="notice-desc">
                                <?php echo nl2br(htmlspecialchars($notice['description'])); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-notice">
                        <span class="icon">📭</span>
                        <p>No notices published yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

</div>

</body>
</html>
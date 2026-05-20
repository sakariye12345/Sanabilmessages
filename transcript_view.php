<?php
/**
 * VIEW FILE: transcript_view.php
 * 
 * Sida xogta Controller-ka looga soo dirayo (Example Data Structure):
 * 
 * $data['student'] = [
 *     'name' => 'إبراهيم عبد الله إسماعيل',
 *     'id'   => 'M076'
 * ];
 * 
 * $data['levels'] = [
 *     [
 *         'level_name' => 'المستوى الأول',
 *         'subjects'   => [
 *             ['id' => 1, 'name' => 'مهارة الحوار', 'grade' => 61],
 *             ['id' => 2, 'name' => 'مهارة التعبير', 'grade' => 56],
 *             // ...
 *         ],
 *         'total'      => 253,
 *         'average'    => 63.25,
 *         'result'     => 'ناجح',
 *         'grade_text' => 'متوسط'
 *     ],
 *     // Levels-ka kale halkan ayaad ku dari...
 * ];
 * 
 * $this->load->view('transcript_view', $data);
 */
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نموذج الكشف - Transcript</title>
    
    <style>
        body {
            background-color: #333333; 
            margin: 0;
            padding: 40px;
            display: flex;
            justify-content: center;
            font-family: 'Times New Roman', 'Traditional Arabic', serif; 
        }

        .a4-container {
            background-color: white;
            width: 210mm;
            min-height: 297mm;
            padding: 15mm;
            box-shadow: 0 0 15px rgba(0,0,0,0.5);
            box-sizing: border-box;
        }

        .header-title {
            text-align: center;
            color: #006064; 
            font-size: 26px;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .student-info-container {
            display: flex;
            justify-content: space-between;
            color: #006064; 
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        table.main-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px; 
            border: 1px solid black;
        }

        .main-table th, .main-table td {
            border: 1px solid black;
            padding: 5px;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
        }

        .level-header {
            background-color: #005eb8; 
            color: white;
        }

        .col-header {
            background-color: #8c8c8c; 
            color: white;
        }

        .col-num { width: 15%; }
        .col-subject { width: 60%; }
        .col-grade { width: 25%; }

        .data-row td {
            color: #0000cc; 
        }

        .footer-cell {
            padding: 0 !important;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
            margin: 0;
        }

        .footer-table td {
            width: 25%;
            border: none;
            border-left: 1px solid black; 
            padding: 5px;
            color: #0000cc;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
        }

        .footer-table td:last-child {
            border-left: none; 
        }

        @media print {
            body {
                background-color: white;
                padding: 0;
            }
            .a4-container {
                box-shadow: none;
                width: 100%;
                padding: 10mm;
            }
            .level-header, .col-header {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

    <div class="a4-container">
        
        <!-- Document Title -->
        <div class="header-title">كشف الدّرجات لمستويات دبلوم اللغة العربية</div>
        
        <!-- Student Info (Dynamic) -->
        <div class="student-info-container">
            <div>اسم الطالب/ـة : <?= isset($student['name']) ? htmlspecialchars($student['name']) : 'إبراهيم عبد الله إسماعيل' ?></div>
            <div>رقم الطالب/ـة : <?= isset($student['id']) ? htmlspecialchars($student['id']) : 'M076' ?></div>
        </div>

        <!-- Levels Loop (Dynamic) -->
        <?php if(isset($levels) && is_array($levels)): ?>
            <?php foreach($levels as $level): ?>
                
                <table class="main-table">
                    <thead>
                        <tr>
                            <th colspan="3" class="level-header"><?= htmlspecialchars($level['level_name']) ?></th>
                        </tr>
                        <tr>
                            <th class="col-header col-num">الرقم</th>
                            <th class="col-header col-subject">المواد الدّراسيّة</th>
                            <th class="col-header col-grade">الدرجات</th>
                        </tr>
                    </thead>
                    <tbody>
                        
                        <!-- Subjects Loop (Dynamic) -->
                        <?php if(isset($level['subjects']) && is_array($level['subjects'])): ?>
                            <?php foreach($level['subjects'] as $index => $subject): ?>
                                <tr class="data-row">
                                    <!-- Use dynamic index or id -->
                                    <td><?= (isset($subject['id']) ? $subject['id'] : ($index + 1)) ?></td>
                                    <td><?= htmlspecialchars($subject['name']) ?></td>
                                    <td><?= htmlspecialchars($subject['grade']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- Fallback if no subjects -->
                            <tr class="data-row"><td colspan="3">لا توجد مواد</td></tr>
                        <?php endif; ?>
                        
                        <!-- Level Footer (Dynamic) -->
                        <tr>
                            <td colspan="3" class="footer-cell">
                                <table class="footer-table">
                                    <tr>
                                        <td>المجموع: <?= isset($level['total']) ? htmlspecialchars($level['total']) : '-' ?></td>
                                        <td>المعدل: <?= isset($level['average']) ? htmlspecialchars($level['average']) : '-' ?></td>
                                        <td>النتيجة: <?= isset($level['result']) ? htmlspecialchars($level['result']) : '-' ?></td>
                                        <td>التقدير: <?= isset($level['grade_text']) ? htmlspecialchars($level['grade_text']) : '-' ?></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                    </tbody>
                </table>

            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align: center; color: red;">Fadlan soo dir xogta (Array-ga) $levels</p>
        <?php endif; ?>

    </div>

</body>
</html>

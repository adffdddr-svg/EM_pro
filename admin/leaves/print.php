<?php
/**
 * Employee Management System
 * طباعة نموذج الإجازة
 */

define('ACCESS_ALLOWED', true);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

$db = getDB();

// الحصول على معرف الإجازة
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    redirect(SITE_URL . '/admin/leaves/index.php');
}

// الحصول على بيانات الإجازة
$stmt = $db->prepare("SELECT l.*, 
                             e.first_name, e.last_name, e.employee_code, e.position, e.email, e.phone,
                             d.name as department_name,
                             se.first_name as substitute_first_name, se.last_name as substitute_last_name, 
                             se.position as substitute_position, se.employee_code as substitute_code
                      FROM employee_leaves l 
                      JOIN employees e ON l.employee_id = e.id 
                      LEFT JOIN departments d ON e.department_id = d.id
                      LEFT JOIN employees se ON l.substitute_employee_id = se.id
                      WHERE l.id = ?");
$stmt->execute([$id]);
$leave = $stmt->fetch();

if (!$leave) {
    redirect(SITE_URL . '/admin/leaves/index.php');
}

// الحصول على الموافقات
$stmt = $db->prepare("SELECT * FROM leave_approvals WHERE leave_id = ? ORDER BY id ASC");
$stmt->execute([$id]);
$approvals = $stmt->fetchAll();

$leave_types = getLeaveTypes();
$leave_type_labels = [
    'ordinary' => 'إجازة اعتيادية',
    'time' => 'إجازة زمنية',
    'medical' => 'فحص طبي',
    'emergency' => 'إجازة طارئة',
    'unpaid' => 'إجازة بدون راتب'
];

$approver_types = [
    'leave_unit' => 'مسؤول وحدة الإجازات',
    'direct_supervisor' => 'المسؤول المباشر',
    'assistant_dean' => 'معاون العميد الإداري'
];

// توليد رقم الإجازة
$leave_number = str_pad($id, 4, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نموذج الإجازة - <?php echo $leave_number; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', 'Tahoma', sans-serif;
            direction: rtl;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .print-container {
            max-width: 210mm;
            margin: 0 auto;
            background: white;
            padding: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #333;
        }
        
        .header-left {
            text-align: right;
        }
        
        .header-right {
            text-align: left;
        }
        
        .header h1 {
            font-size: 16px;
            margin-bottom: 5px;
            color: #000;
        }
        
        .header p {
            font-size: 12px;
            color: #333;
        }
        
        .logo {
            text-align: center;
            margin: 20px 0;
        }
        
        .document-number {
            text-align: right;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .leave-type {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin: 20px 0;
            padding: 10px;
            background: #f0f0f0;
        }
        
        .request-text {
            text-align: right;
            margin: 20px 0;
            line-height: 2;
            font-size: 14px;
        }
        
        .form-section {
            margin: 30px 0;
            padding: 15px;
            border: 1px solid #ddd;
        }
        
        .form-section h3 {
            font-size: 14px;
            margin-bottom: 15px;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 15px;
            margin-bottom: 10px;
            align-items: center;
        }
        
        .form-row label {
            font-weight: bold;
            font-size: 13px;
        }
        
        .form-row span {
            font-size: 13px;
            border-bottom: 1px dotted #333;
            padding-bottom: 2px;
            min-height: 20px;
        }
        
        .signature-section {
            margin-top: 30px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .signature-box {
            text-align: center;
            padding: 15px;
            border: 1px solid #ddd;
        }
        
        .signature-box h4 {
            font-size: 13px;
            margin-bottom: 40px;
        }
        
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 40px;
            padding-top: 5px;
        }
        
        .copy-section {
            margin-top: 30px;
            padding: 15px;
            background: #f9f9f9;
            border: 1px solid #ddd;
        }
        
        .copy-section h4 {
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .copy-section ul {
            list-style: none;
            padding: 0;
        }
        
        .copy-section li {
            padding: 5px 0;
            font-size: 13px;
        }
        
        .balance-info {
            margin-top: 15px;
            padding: 10px;
            background: #fff;
            border: 1px solid #ddd;
        }
        
        .balance-info p {
            margin: 5px 0;
            font-size: 13px;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .print-container {
                box-shadow: none;
                padding: 20px;
            }
            
            .no-print {
                display: none;
            }
        }
        
        .print-buttons {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .print-buttons button {
            padding: 10px 20px;
            margin: 0 10px;
            font-size: 16px;
            cursor: pointer;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
        }
        
        .print-buttons button:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="print-buttons no-print">
        <button onclick="window.print()">
            <i class="fas fa-print"></i> طباعة
        </button>
        <button onclick="window.close()">
            <i class="fas fa-times"></i> إغلاق
        </button>
    </div>
    
    <div class="print-container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <h1>جمهورية العراق</h1>
                <p>وزارة التعليم العالي والبحث العلمي</p>
                <p>جامعة البصرة</p>
                <p>كلية علوم الحاسوب وتكنولوجيا المعلومات</p>
            </div>
            <div class="header-right">
                <h1>Republic of Iraq</h1>
                <p>Ministry of Higher Education and Scientific Research</p>
                <p>University of Basrah</p>
                <p>College of Computer Science & Information</p>
            </div>
        </div>
        
        <!-- Document Number -->
        <div class="document-number">
            <strong>العدد:</strong> <?php echo $leave_number; ?>
        </div>
        
        <!-- Leave Type -->
        <div class="leave-type">
            م / <?php echo $leave_type_labels[$leave['leave_type']] ?? $leave['leave_type']; ?>
        </div>
        
        <!-- Request Text -->
        <div class="request-text">
            <?php if ($leave['leave_type'] === 'time'): ?>
                يرجى التفضل بالموافقة على منحي <?php echo $leave_type_labels[$leave['leave_type']]; ?> ابتداءاً من الساعة 
                <strong>( <?php echo $leave['start_time'] ? date('H', strtotime($leave['start_time'])) : '12'; ?> )</strong> 
                ولغاية الساعة 
                <strong>( <?php echo $leave['end_time'] ? date('H', strtotime($leave['end_time'])) : '21'; ?> )</strong> 
                بتاريخ 
                <strong>( <?php echo date('d/m/Y', strtotime($leave['start_date'])); ?> )</strong> 
                لغرض 
                <strong><?php echo htmlspecialchars($leave['purpose'] ?: '...................'); ?></strong>
            <?php else: ?>
                يرجى التفضل بالموافقة على منحي <?php echo $leave_type_labels[$leave['leave_type']]; ?> لمدة 
                <strong>( <?php echo (int)$leave['days']; ?> )</strong> يوم ابتداءاً من تاريخ 
                <strong>( <?php echo date('d/m/Y', strtotime($leave['start_date'])); ?> )</strong> 
                ولغاية 
                <strong>( <?php echo date('d/m/Y', strtotime($leave['end_date'])); ?> )</strong> 
                وذلك لغرض 
                <strong><?php echo htmlspecialchars($leave['purpose'] ?: '...................'); ?></strong>
            <?php endif; ?>
        </div>
        
        <!-- Applicant Information -->
        <div class="form-section">
            <h3>اسم طالب الإجازة</h3>
            <div class="form-row">
                <label>الاسم:</label>
                <span><?php echo htmlspecialchars($leave['first_name'] . ' ' . $leave['last_name']); ?></span>
            </div>
            <div class="form-row">
                <label>العنوان الوظيفي:</label>
                <span><?php echo htmlspecialchars($leave['position']); ?></span>
            </div>
            <div class="form-row">
                <label>مكان العمل:</label>
                <span><?php echo htmlspecialchars($leave['department_name'] ?? 'الموارد البشرية'); ?></span>
            </div>
            <div class="form-row">
                <label>التوقيع:</label>
                <span class="signature-line"></span>
            </div>
        </div>
        
        <!-- Substitute Employee -->
        <?php if ($leave['substitute_first_name']): ?>
            <div class="form-section">
                <h3>اسم الموظف البديل</h3>
                <div class="form-row">
                    <label>الاسم:</label>
                    <span><?php echo htmlspecialchars($leave['substitute_first_name'] . ' ' . $leave['substitute_last_name']); ?></span>
                </div>
                <div class="form-row">
                    <label>العنوان الوظيفي:</label>
                    <span><?php echo htmlspecialchars($leave['substitute_position']); ?></span>
                </div>
                <div class="form-row">
                    <label>مكان العمل:</label>
                    <span>الموارد البشرية</span>
                </div>
                <div class="form-row">
                    <label>التوقيع:</label>
                    <span class="signature-line"></span>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Approvals -->
        <div class="signature-section">
            <?php foreach ($approvals as $approval): ?>
                <div class="signature-box">
                    <h4><?php echo $approver_types[$approval['approver_type']] ?? $approval['approver_type']; ?></h4>
                    <?php if ($approval['approver_name']): ?>
                        <p><strong>الاسم:</strong> <?php echo htmlspecialchars($approval['approver_name']); ?></p>
                    <?php endif; ?>
                    <?php if ($approval['approved_at']): ?>
                        <p><strong>التاريخ:</strong> <?php echo date('d/m/Y', strtotime($approval['approved_at'])); ?></p>
                    <?php endif; ?>
                    <div class="signature-line">
                        <small>التوقيع</small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Copy Section -->
        <div class="copy-section">
            <h4>نسخة منه إلى:-</h4>
            <ul>
                <li>القسم المعني</li>
                <li>شعبة الموارد البشرية/وحدة الإجازات لغرض التأشير</li>
                <li>
                    <div class="balance-info">
                        <?php 
                        $balance = getLeaveBalance($leave['employee_id']);
                        if ($balance):
                        ?>
                            <p>الرصيد الكلي ( <?php echo $balance['total_balance']; ?> ) مدة الإجازة ( <?php echo (int)$leave['days']; ?> ) ( المتبقي من الرصيد ( <?php echo $balance['remaining_balance']; ?> )</p>
                            <p>الرصيد الشهري المتوفر ( <?php echo $balance['monthly_balance']; ?> ) يوم ، الرصيد المتبقي ( <?php echo $balance['remaining_balance']; ?> ) يوم</p>
                        <?php else: ?>
                            <p>الرصيد الكلي ( ) مدة الإجازة ( <?php echo (int)$leave['days']; ?> ) ( المتبقي من الرصيد ( )</p>
                            <p>الرصيد الشهري المتوفر ( ) يوم ، الرصيد المتبقي ( ) يوم</p>
                        <?php endif; ?>
                    </div>
                </li>
                <li>الملف الشخصي لغرض الحفظ</li>
            </ul>
        </div>
    </div>
    
    <script>
        // طباعة تلقائية عند فتح الصفحة (اختياري)
        // window.onload = function() {
        //     window.print();
        // };
    </script>
</body>
</html>


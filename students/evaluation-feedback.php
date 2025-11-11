<?php
define('APP_LOADED', true);
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
checkUserType(['student']);

$pageTitle = "التقييم والتغذية الراجعة";

$studentId = $_SESSION['user_id'];

// الحصول على جميع التقييمات
$evaluationsStmt = $pdo->prepare("
    SELECT se.*, i.company_name, i.start_date, i.end_date,
           u.first_name as supervisor_first_name, u.last_name as supervisor_last_name
    FROM site_evaluations se
    JOIN internships i ON se.internship_id = i.internship_id
    JOIN site_supervisors ss ON se.supervisor_id = ss.supervisor_id
    JOIN users u ON ss.supervisor_id = u.user_id
    WHERE i.student_id = ?
    ORDER BY se.evaluation_date DESC
");
$evaluationsStmt->execute([$studentId]);
$evaluations = $evaluationsStmt->fetchAll(PDO::FETCH_ASSOC);

// حساب متوسط التقييم العام
$avgScore = 0;
if (!empty($evaluations)) {
    $totalScore = 0;
    $count = 0;
    foreach ($evaluations as $eval) {
        if ($eval['overall_score']) {
            $totalScore += $eval['overall_score'];
            $count++;
        }
    }
    $avgScore = $count > 0 ? round($totalScore / $count, 1) : 0;
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="page-header">
    <h1>التقييم والتغذية الراجعة</h1>
    <div class="breadcrumbs">
        <a href="dashboard.php">الرئيسية</a> / التقييم والتغذية الراجعة
    </div>
</div>

<!-- نظرة عامة على التقييم -->
<div class="card mb-4">
    <div class="card-body text-center">
        <h2 class="mb-4">نظرة عامة على تقييماتك</h2>
        <div class="d-flex justify-content-center align-items-center gap-5">
            <div class="text-center">
                <div class="display-3 fw-bold text-primary"><?php echo $avgScore; ?>/10</div>
                <div class="text-muted mt-2">متوسط التقييم</div>
            </div>
            <div class="text-center">
                <div class="display-3 fw-bold text-success"><?php echo count($evaluations); ?></div>
                <div class="text-muted mt-2">عدد التقييمات</div>
            </div>
        </div>
        
        <div class="mt-4">
            <div class="progress" style="height: 25px;">
                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $avgScore * 10; ?>%">
                    <?php echo $avgScore; ?>/10
                </div>
            </div>
        </div>
        
        <div class="mt-4">
            <span class="badge bg-<?php echo $avgScore >= 8 ? 'success' : ($avgScore >= 6 ? 'warning' : 'danger'); ?> fs-5">
                <?php 
                if ($avgScore >= 9) echo "ممتاز";
                elseif ($avgScore >= 8) echo "جيد جداً";
                elseif ($avgScore >= 7) echo "جيد";
                elseif ($avgScore >= 6) echo "مقبول";
                else echo "بحاجة لتحسين";
                ?>
            </span>
        </div>
    </div>
</div>

<!-- التقييمات التفصيلية -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="mb-0">التقييمات التفصيلية</h3>
        <span class="badge bg-primary"><?php echo count($evaluations); ?> تقييمات</span>
    </div>
    <div class="card-body">
        <?php if (empty($evaluations)): ?>
            <div class="text-center py-5">
                <i class="fas fa-star fa-3x text-muted mb-3"></i>
                <p class="text-muted fs-4">لم يتم تقييمك حتى الآن</p>
                <p class="text-muted">ستظهر هنا تقييمات مشرفيك الميدانيين والأكاديميين بعد إكمال فترات التدريب</p>
            </div>
        <?php else: ?>
            <?php foreach ($evaluations as $index => $eval): ?>
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0"><?php echo $eval['company_name']; ?></h4>
                            <span class="badge bg-<?php echo $eval['is_final'] ? 'success' : 'info'; ?>">
                                <?php echo $eval['is_final'] ? 'تقييم نهائي' : 'تقييم دوري'; ?>
                            </span>
                        </div>
                        <div class="mt-2 text-muted">
                            <i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($eval['evaluation_date'])); ?> |
                            <i class="fas fa-user"></i> <?php echo $eval['supervisor_first_name'] . ' ' . $eval['supervisor_last_name']; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5>مجالات التقييم</h5>
                                <div class="list-group mt-3">
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>الاحترافية</span>
                                        <span class="badge bg-primary"><?php echo $eval['professionalism_score'] ?? 0; ?>/10</span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>المهارات التقنية</span>
                                        <span class="badge bg-primary"><?php echo $eval['technical_skills_score'] ?? 0; ?>/10</span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>التواصل</span>
                                        <span class="badge bg-primary"><?php echo $eval['communication_score'] ?? 0; ?>/10</span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>العمل الجماعي</span>
                                        <span class="badge bg-primary"><?php echo $eval['teamwork_score'] ?? 0; ?>/10</span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>حل المشكلات</span>
                                        <span class="badge bg-primary"><?php echo $eval['problem_solving_score'] ?? 0; ?>/10</span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>الحضور والانضباط</span>
                                        <span class="badge bg-primary"><?php echo $eval['attendance_score'] ?? 0; ?>/10</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h5>التقييم العام</h5>
                                <div class="text-center mt-3">
                                    <div class="display-4 fw-bold text-primary"><?php echo $eval['overall_score']; ?>/10</div>
                                    <div class="mt-2">
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-<?php 
                                                echo $eval['overall_score'] >= 9 ? 'success' : 
                                                     ($eval['overall_score'] >= 7 ? 'info' : 'warning');
                                            ?>" role="progressbar" style="width: <?php echo $eval['overall_score'] * 10; ?>%">
                                                <?php echo $eval['overall_score']; ?>/10
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <span class="badge bg-<?php 
                                            echo $eval['recommendation'] === 'excellent' ? 'success' : 
                                                 ($eval['recommendation'] === 'good' ? 'primary' : 
                                                 ($eval['recommendation'] === 'satisfactory' ? 'warning' : 'danger'));
                                        ?> fs-5">
                                            <?php 
                                            $recommendations = [
                                                'excellent' => 'ممتاز',
                                                'good' => 'جيد',
                                                'satisfactory' => 'مقبول',
                                                'needs_improvement' => 'بحاجة لتحسين'
                                            ];
                                            echo $recommendations[$eval['recommendation']] ?? $eval['recommendation'];
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <h5>نقاط القوة</h5>
                                <div class="p-3 bg-light rounded">
                                    <?php echo nl2br($eval['strengths'] ?? 'لا توجد نقاط قوة محددة'); ?>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <h5>مجالات التحسين</h5>
                                <div class="p-3 bg-light rounded">
                                    <?php echo nl2br($eval['areas_for_improvement'] ?? 'لا توجد مجالات تحسين محددة'); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <h5>ملاحظات عامة</h5>
                            <div class="p-3 bg-light rounded">
                                <?php echo nl2br($eval['final_comments'] ?? 'لا توجد ملاحظات إضافية'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // يمكنك إضافة أي وظائف JavaScript تحتاجها هنا
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
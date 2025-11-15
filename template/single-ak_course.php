<?php
/**
 * Single Course Template
 * 
 * @package Template Academy
 */

get_header();

$course_id = get_the_ID();
$user_id = get_current_user_id();
$progress = ak_get_course_progress($user_id, $course_id);
$modules = ak_get_course_modules($course_id);
?>

<div class="ak-container">
    <div class="ak-course-header">
        <h1><?php the_title(); ?></h1>
        <div class="ak-course-progress-summary">
            <div class="ak-stat-item">
                <i data-lucide="book-open"></i>
                <span><?php echo $progress['completed_lessons']; ?>/<?php echo $progress['total_lessons']; ?> уроков</span>
            </div>
            <div class="ak-stat-item">
                <i data-lucide="clipboard-check"></i>
                <span><?php echo $progress['completed_quizzes']; ?> заданий</span>
            </div>
            <div class="ak-stat-item">
                <i data-lucide="trending-up"></i>
                <span><?php echo $progress['percentage']; ?>%</span>
            </div>
        </div>
        <?php ak_progress_bar($progress['percentage']); ?>
    </div>
    
    <div class="ak-access-section">
        <h3>Доступы</h3>
        <div class="ak-access-grid">
            <div class="ak-access-item">
                <i data-lucide="book"></i>
                <span>Доступ к учебным материалам</span>
                <span class="ak-access-status">(только достигнутые)</span>
            </div>
            <div class="ak-access-item">
                <i data-lucide="clipboard-list"></i>
                <span>Доступ к заданиям</span>
                <span class="ak-access-status">(только достигнутые)</span>
            </div>
            <div class="ak-access-item ak-access-available">
                <i data-lucide="message-circle"></i>
                <span>Обратная связь</span>
                <span class="ak-access-status">(доступна)</span>
            </div>
        </div>
    </div>
    
    <div class="ak-modules-grid">
        <?php foreach ($modules as $index => $module): 
            $module_progress = ak_get_module_progress($user_id, $module->ID);
            $is_module_locked = $user_id ? ak_is_lesson_locked($user_id, $module->ID) : false;
        ?>
            <div class="ak-module-card <?php echo $is_module_locked ? 'locked' : ''; ?>">
                <div class="ak-module-number">Модуль <?php echo $index + 1; ?></div>
                
                <?php if ($is_module_locked): ?>
                    <div class="ak-locked-badge">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                        Заблокирован
                    </div>
                <?php endif; ?>
                
                <h3 class="ak-module-title">
                    <?php if ($is_module_locked): ?>
                        <span style="opacity: 0.5;"><?php echo esc_html($module->post_title); ?></span>
                    <?php else: ?>
                        <a href="<?php echo get_permalink($module->ID); ?>"><?php echo esc_html($module->post_title); ?></a>
                    <?php endif; ?>
                </h3>
                
                <?php if (!$is_module_locked): ?>
                    <div class="ak-module-progress">
                        <span><?php echo $module_progress['completed_lessons']; ?>/<?php echo $module_progress['total_lessons']; ?> уроков</span>
                    </div>
                    <?php ak_progress_bar($module_progress['percentage']); ?>
                    <div class="ak-module-stats">
                        <span><i data-lucide="book-open"></i> <?php echo $module_progress['total_lessons']; ?> уроков</span>
                        <span><i data-lucide="trending-up"></i> <?php echo $module_progress['percentage']; ?>%</span>
                    </div>
                <?php else: ?>
                    <p style="color: #666; font-size: 14px; margin: 10px 0;">Доступ к модулю ограничен</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php get_footer(); ?>

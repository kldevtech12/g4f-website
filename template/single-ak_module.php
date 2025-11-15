<?php
/**
 * Single Module Template
 * 
 * @package Template Academy
 */

get_header();

$module_id = get_the_ID();
$user_id = get_current_user_id();
$course_id = get_post_meta($module_id, '_ak_course_id', true);
$course = get_post($course_id);

// Check if module is locked for this user
$is_module_locked = false;
if ($user_id) {
    $is_module_locked = ak_is_lesson_locked($user_id, $module_id);
}

$lessons = ak_get_module_lessons($module_id);
$module_progress = ak_get_module_progress($user_id, $module_id);
?>

<div class="ak-container">
    <?php if ($course): ?>
        <div class="ak-breadcrumbs">
            <a href="<?php echo get_permalink($course_id); ?>"><?php echo esc_html($course->post_title); ?></a>
            <span class="separator">&gt;</span>
            <span><?php the_title(); ?></span>
        </div>
    <?php endif; ?>
    
    <?php if ($is_module_locked): ?>
        <div class="ak-lesson-locked">
            <div class="ak-locked-content">
                <div class="ak-locked-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                </div>
                <h1>Модуль заблокирован</h1>
                <p>Доступ к этому модулю ограничен администратором.</p>
                <p>Пожалуйста, свяжитесь с преподавателем для получения доступа.</p>
                <a href="<?php echo get_permalink($course_id); ?>" class="ak-btn">Вернуться к курсу</a>
            </div>
        </div>
    <?php else: ?>
    
    <div class="ak-module-header">
        <h1><?php the_title(); ?></h1>
        <?php ak_progress_bar($module_progress['percentage']); ?>
    </div>
    
    <div class="ak-content">
        <?php the_content(); ?>
    </div>
    
    <div class="ak-lessons-list">
        <h2>Уроки модуля</h2>
        <?php 
        foreach ($lessons as $lesson):
            $is_completed = ak_is_lesson_completed($user_id, $lesson->ID);
            $is_locked = $user_id ? ak_is_lesson_locked($user_id, $lesson->ID) : false;
        ?>
            <div class="ak-lesson-item <?php echo $is_locked ? 'locked' : ($is_completed ? 'completed' : 'available'); ?>">
                <div class="ak-lesson-status">
                    <?php if ($is_locked): ?>
                        <i data-lucide="lock"></i>
                        <span class="ak-status-text">Заблокирован</span>
                    <?php elseif ($is_completed): ?>
                        <i data-lucide="check-circle"></i>
                        <span class="ak-status-text">Пройден</span>
                    <?php else: ?>
                        <i data-lucide="circle"></i>
                        <span class="ak-status-text">Доступен</span>
                    <?php endif; ?>
                </div>
                <div class="ak-lesson-info">
                    <h3>
                        <?php if ($is_locked): ?>
                            <span style="opacity: 0.5;"><?php echo esc_html($lesson->post_title); ?></span>
                        <?php else: ?>
                            <a href="<?php echo get_permalink($lesson->ID); ?>"><?php echo esc_html($lesson->post_title); ?></a>
                        <?php endif; ?>
                    </h3>
                </div>
                <div class="ak-lesson-action">
                    <?php if ($is_locked): ?>
                        <button class="ak-btn ak-btn-locked" disabled>
                            <i data-lucide="lock"></i>
                            <span>Заблокирован</span>
                        </button>
                    <?php else: ?>
                        <a href="<?php echo get_permalink($lesson->ID); ?>" class="ak-btn">
                            <?php echo $is_completed ? 'Повторить' : 'Начать'; ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <?php endif; // End is_module_locked check ?>
</div>

<?php get_footer(); ?>

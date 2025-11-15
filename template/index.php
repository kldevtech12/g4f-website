<?php
/**
 * Template Name: Courses List
 * 
 * @package Template Academy
 */

get_header();

$user_id = get_current_user_id();
$courses = get_posts(array(
    'post_type' => 'ak_course',
    'numberposts' => -1,
));
?>

<div class="ak-container">
    <div class="ak-courses-grid">
        <?php foreach ($courses as $course): 
            $progress = ak_get_course_progress($user_id, $course->ID);
            $thumbnail = get_the_post_thumbnail_url($course->ID, 'medium');
        ?>
            <div class="ak-course-card">
                <div class="ak-course-thumbnail">
                    <?php if ($thumbnail): ?>
                        <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr($course->post_title); ?>">
                    <?php else: ?>
                        <div class="ak-course-placeholder"></div>
                    <?php endif; ?>
                </div>
                <div class="ak-course-content">
                    <div class="ak-course-date">30 июля</div>
                    <h2 class="ak-course-title">
                        <a href="<?php echo get_permalink($course->ID); ?>"><?php echo esc_html($course->post_title); ?></a>
                    </h2>
                    <div class="ak-course-stats">
                        <div class="ak-stat">
                            <i data-lucide="book-open"></i>
                            <span><?php echo $progress['completed_lessons']; ?>/<?php echo $progress['total_lessons']; ?> уроков</span>
                        </div>
                        <div class="ak-stat">
                            <i data-lucide="clipboard-check"></i>
                            <span><?php echo $progress['completed_quizzes']; ?> заданий</span>
                        </div>
                        <div class="ak-stat">
                            <i data-lucide="trending-up"></i>
                            <span><?php echo $progress['percentage']; ?>%</span>
                        </div>
                    </div>
                    <?php ak_progress_bar($progress['percentage']); ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php get_footer(); ?>

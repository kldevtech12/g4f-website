<?php
/**
 * User Progress Tracking Functions
 *
 * @package Template Academy
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Mark lesson as completed for user
 *
 * @param int $user_id User ID
 * @param int $lesson_id Lesson ID
 * @return bool
 */
function ak_mark_lesson_completed($user_id, $lesson_id) {
    $progress = get_user_meta($user_id, 'ak_user_progress', true);
    if (!is_array($progress)) {
        $progress = array();
    }
    
    $module_id = get_post_meta($lesson_id, '_ak_module_id', true);
    $course_id = get_post_meta($module_id, '_ak_course_id', true);
    
    if (!isset($progress[$course_id])) {
        $progress[$course_id] = array(
            'completed_lessons' => array(),
            'completed_quizzes' => array(),
            'last_visited_lesson' => 0,
        );
    }
    
    if (!in_array($lesson_id, $progress[$course_id]['completed_lessons'])) {
        $progress[$course_id]['completed_lessons'][] = $lesson_id;
    }
    
    $progress[$course_id]['last_visited_lesson'] = $lesson_id;
    
    return update_user_meta($user_id, 'ak_user_progress', $progress);
}

/**
 * Check if lesson is completed
 *
 * @param int $user_id User ID
 * @param int $lesson_id Lesson ID
 * @return bool
 */
function ak_is_lesson_completed($user_id, $lesson_id) {
    $progress = get_user_meta($user_id, 'ak_user_progress', true);
    if (!is_array($progress)) {
        return false;
    }
    
    $module_id = get_post_meta($lesson_id, '_ak_module_id', true);
    $course_id = get_post_meta($module_id, '_ak_course_id', true);
    
    if (!isset($progress[$course_id]['completed_lessons'])) {
        return false;
    }
    
    return in_array($lesson_id, $progress[$course_id]['completed_lessons']);
}

/**
 * Get course progress for user
 *
 * @param int $user_id User ID
 * @param int $course_id Course ID
 * @return array
 */
function ak_get_course_progress($user_id, $course_id) {
    $progress = get_user_meta($user_id, 'ak_user_progress', true);
    
    $total_lessons = ak_get_course_total_lessons($course_id);
    $total_quizzes = ak_get_course_total_quizzes($course_id);
    
    $completed_lessons = 0;
    $completed_quizzes = 0;
    
    if (is_array($progress) && isset($progress[$course_id])) {
        $completed_lessons = count($progress[$course_id]['completed_lessons']);
        $completed_quizzes = isset($progress[$course_id]['completed_quizzes']) ? count($progress[$course_id]['completed_quizzes']) : 0;
    }
    
    $percentage = $total_lessons > 0 ? round(($completed_lessons / $total_lessons) * 100) : 0;
    
    return array(
        'completed_lessons' => $completed_lessons,
        'total_lessons' => $total_lessons,
        'completed_quizzes' => $completed_quizzes,
        'total_quizzes' => $total_quizzes,
        'percentage' => $percentage,
    );
}

/**
 * Get module progress for user
 *
 * @param int $user_id User ID
 * @param int $module_id Module ID
 * @return array
 */
function ak_get_module_progress($user_id, $module_id) {
    $lessons = get_posts(array(
        'post_type' => 'ak_lesson',
        'meta_key' => '_ak_module_id',
        'meta_value' => $module_id,
        'numberposts' => -1,
    ));
    
    $total_lessons = count($lessons);
    $completed_lessons = 0;
    
    foreach ($lessons as $lesson) {
        if (ak_is_lesson_completed($user_id, $lesson->ID)) {
            $completed_lessons++;
        }
    }
    
    $percentage = $total_lessons > 0 ? round(($completed_lessons / $total_lessons) * 100) : 0;
    
    return array(
        'completed_lessons' => $completed_lessons,
        'total_lessons' => $total_lessons,
        'percentage' => $percentage,
    );
}

/**
 * Get total lessons in course
 *
 * @param int $course_id Course ID
 * @return int
 */
function ak_get_course_total_lessons($course_id) {
    $modules = get_posts(array(
        'post_type' => 'ak_module',
        'meta_key' => '_ak_course_id',
        'meta_value' => $course_id,
        'numberposts' => -1,
    ));
    
    $total = 0;
    foreach ($modules as $module) {
        $lessons = get_posts(array(
            'post_type' => 'ak_lesson',
            'meta_key' => '_ak_module_id',
            'meta_value' => $module->ID,
            'numberposts' => -1,
        ));
        $total += count($lessons);
    }
    
    return $total;
}

/**
 * Get total quizzes in course
 *
 * @param int $course_id Course ID
 * @return int
 */
function ak_get_course_total_quizzes($course_id) {
    $modules = get_posts(array(
        'post_type' => 'ak_module',
        'meta_key' => '_ak_course_id',
        'meta_value' => $course_id,
        'numberposts' => -1,
    ));
    
    $total = 0;
    foreach ($modules as $module) {
        $lessons = get_posts(array(
            'post_type' => 'ak_lesson',
            'meta_key' => '_ak_module_id',
            'meta_value' => $module->ID,
            'numberposts' => -1,
        ));
        foreach ($lessons as $lesson) {
            $quizzes = get_post_meta($lesson->ID, '_ak_quiz_questions', true);
            if (is_array($quizzes)) {
                $total += count($quizzes);
            }
        }
    }
    
    return $total;
}

/**
 * Get lessons for module
 *
 * @param int $module_id Module ID
 * @return array
 */
function ak_get_module_lessons($module_id) {
    return get_posts(array(
        'post_type' => 'ak_lesson',
        'meta_key' => '_ak_module_id',
        'meta_value' => $module_id,
        'numberposts' => -1,
        'orderby' => 'menu_order',
        'order' => 'ASC',
    ));
}

/**
 * Get modules for course
 *
 * @param int $course_id Course ID
 * @return array
 */
function ak_get_course_modules($course_id) {
    return get_posts(array(
        'post_type' => 'ak_module',
        'meta_key' => '_ak_course_id',
        'meta_value' => $course_id,
        'numberposts' => -1,
        'orderby' => 'menu_order',
        'order' => 'ASC',
    ));
}

<?php
/**
 * Helper Functions
 *
 * @package Template Academy
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Display progress bar
 *
 * @param int $percentage Percentage value
 * @return void
 */
function ak_progress_bar($percentage) {
    ?>
    <div class="ak-progress-bar">
        <div class="ak-progress-fill" style="width: <?php echo intval($percentage); ?>%"></div>
    </div>
    <?php
}

/**
 * Get video HTML
 *
 * @param int $lesson_id Lesson ID
 * @return string
 */
function ak_get_video_html($lesson_id) {
    $video_type = get_post_meta($lesson_id, '_ak_video_type', true);
    $video_url = get_post_meta($lesson_id, '_ak_video_url', true);
    $video_file = get_post_meta($lesson_id, '_ak_video_file', true);
    
    if (empty($video_type)) {
        return '';
    }
    
    $html = '<div class="ak-video-container">';
    
    switch ($video_type) {
        case 'youtube':
            $video_id = ak_extract_youtube_id($video_url);
            if ($video_id) {
                $html .= '<iframe src="https://www.youtube.com/embed/' . esc_attr($video_id) . '" frameborder="0" allowfullscreen></iframe>';
            }
            break;
            
        case 'vimeo':
            $video_id = ak_extract_vimeo_id($video_url);
            if ($video_id) {
                $html .= '<iframe src="https://player.vimeo.com/video/' . esc_attr($video_id) . '" frameborder="0" allowfullscreen></iframe>';
            }
            break;
            
        case 'mp4':
            if ($video_file) {
                $html .= '<video controls><source src="' . esc_url($video_file) . '" type="video/mp4"></video>';
            }
            break;
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Extract YouTube video ID from URL
 *
 * @param string $url YouTube URL
 * @return string|null
 */
function ak_extract_youtube_id($url) {
    preg_match('/[?&]v=([^&]+)/', $url, $matches);
    return isset($matches[1]) ? $matches[1] : null;
}

/**
 * Extract Vimeo video ID from URL
 *
 * @param string $url Vimeo URL
 * @return string|null
 */
function ak_extract_vimeo_id($url) {
    preg_match('/vimeo\.com\/(\d+)/', $url, $matches);
    return isset($matches[1]) ? $matches[1] : null;
}

/**
 * Display breadcrumbs
 *
 * @param int $lesson_id Lesson ID
 * @return void
 */
function ak_breadcrumbs($lesson_id = null) {
    if (!$lesson_id) {
        return;
    }
    
    $module_id = get_post_meta($lesson_id, '_ak_module_id', true);
    $course_id = get_post_meta($module_id, '_ak_course_id', true);
    
    $course = get_post($course_id);
    $module = get_post($module_id);
    $lesson = get_post($lesson_id);
    
    ?>
    <div class="ak-breadcrumbs">
        <a href="<?php echo get_permalink($course_id); ?>"><?php echo esc_html($course->post_title); ?></a>
        <span class="separator">&gt;</span>
        <a href="<?php echo get_permalink($module_id); ?>"><?php echo esc_html($module->post_title); ?></a>
        <span class="separator">&gt;</span>
        <span><?php echo esc_html($lesson->post_title); ?></span>
    </div>
    <?php
}

<?php
/**
 * Custom Post Types Registration
 *
 * @package Template Academy
*/

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Course Post Type
 */
function ak_register_course_post_type() {
    $labels = array(
        'name' => 'Курсы',
        'singular_name' => 'Курс',
        'add_new' => 'Добавить курс',
        'add_new_item' => 'Добавить новый курс',
        'edit_item' => 'Редактировать курс',
        'new_item' => 'Новый курс',
        'view_item' => 'Просмотреть курс',
        'search_items' => 'Искать курсы',
        'not_found' => 'Курсы не найдены',
        'not_found_in_trash' => 'В корзине курсов не найдено',
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'show_in_rest' => true,
        'supports' => array('title', 'editor', 'thumbnail'),
        'menu_icon' => 'dashicons-book',
        'rewrite' => array('slug' => 'course'),
    );

    register_post_type('ak_course', $args);
}
add_action('init', 'ak_register_course_post_type');

/**
 * Register Module Post Type
 */
function ak_register_module_post_type() {
    $labels = array(
        'name' => 'Модули',
        'singular_name' => 'Модуль',
        'add_new' => 'Добавить модуль',
        'add_new_item' => 'Добавить новый модуль',
        'edit_item' => 'Редактировать модуль',
        'new_item' => 'Новый модуль',
        'view_item' => 'Просмотреть модуль',
        'search_items' => 'Искать модули',
        'not_found' => 'Модули не найдены',
        'not_found_in_trash' => 'В корзине модулей не найдено',
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => false,
        'show_in_rest' => true,
        'supports' => array('title', 'editor'),
        'menu_icon' => 'dashicons-list-view',
        'rewrite' => array('slug' => 'module'),
    );

    register_post_type('ak_module', $args);
}
add_action('init', 'ak_register_module_post_type');

/**
 * Register Lesson Post Type
 */
function ak_register_lesson_post_type() {
    $labels = array(
        'name' => 'Уроки',
        'singular_name' => 'Урок',
        'add_new' => 'Добавить урок',
        'add_new_item' => 'Добавить новый урок',
        'edit_item' => 'Редактировать урок',
        'new_item' => 'Новый урок',
        'view_item' => 'Просмотреть урок',
        'search_items' => 'Искать уроки',
        'not_found' => 'Уроки не найдены',
        'not_found_in_trash' => 'В корзине уроков не найдено',
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => false,
        'show_in_rest' => true,
        'supports' => array('title', 'editor'),
        'menu_icon' => 'dashicons-media-document',
        'rewrite' => array('slug' => 'lesson'),
    );

    register_post_type('ak_lesson', $args);
}
add_action('init', 'ak_register_lesson_post_type');

/**
 * Add meta boxes for course/module/lesson relationships
 */
function ak_add_meta_boxes() {
    // Module meta box
    add_meta_box(
        'ak_module_course',
        'Привязка к курсу',
        'ak_module_course_callback',
        'ak_module',
        'side',
        'default'
    );
    
    // Lesson meta box
    add_meta_box(
        'ak_lesson_module',
        'Привязка к модулю',
        'ak_lesson_module_callback',
        'ak_lesson',
        'side',
        'default'
    );
    
    // Video meta box
    add_meta_box(
        'ak_lesson_video',
        'Видео урока',
        'ak_lesson_video_callback',
        'ak_lesson',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'ak_add_meta_boxes');

/**
 * Module course selection callback
 */
function ak_module_course_callback($post) {
    wp_nonce_field('ak_module_course_nonce', 'ak_module_course_nonce');
    $course_id = get_post_meta($post->ID, '_ak_course_id', true);
    $courses = get_posts(array('post_type' => 'ak_course', 'numberposts' => -1));
    
    echo '<select name="ak_course_id" style="width:100%;">';
    echo '<option value="">Выберите курс</option>';
    foreach ($courses as $course) {
        $selected = ($course_id == $course->ID) ? 'selected' : '';
        echo '<option value="' . esc_attr($course->ID) . '" ' . $selected . '>' . esc_html($course->post_title) . '</option>';
    }
    echo '</select>';
}

/**
 * Lesson module selection callback
 */
function ak_lesson_module_callback($post) {
    wp_nonce_field('ak_lesson_module_nonce', 'ak_lesson_module_nonce');
    $module_id = get_post_meta($post->ID, '_ak_module_id', true);
    $modules = get_posts(array('post_type' => 'ak_module', 'numberposts' => -1));
    
    echo '<select name="ak_module_id" style="width:100%;">';
    echo '<option value="">Выберите модуль</option>';
    foreach ($modules as $module) {
        $selected = ($module_id == $module->ID) ? 'selected' : '';
        echo '<option value="' . esc_attr($module->ID) . '" ' . $selected . '>' . esc_html($module->post_title) . '</option>';
    }
    echo '</select>';
}

/**
 * Lesson video callback
 */
function ak_lesson_video_callback($post) {
    wp_nonce_field('ak_lesson_video_nonce', 'ak_lesson_video_nonce');
    $video_type = get_post_meta($post->ID, '_ak_video_type', true);
    $video_url = get_post_meta($post->ID, '_ak_video_url', true);
    $video_file = get_post_meta($post->ID, '_ak_video_file', true);
    ?>
    <p>
        <label><strong>Тип видео:</strong></label><br>
        <select name="ak_video_type" id="ak_video_type" style="width:100%;">
            <option value="">Без видео</option>
            <option value="youtube" <?php selected($video_type, 'youtube'); ?>>YouTube</option>
            <option value="vimeo" <?php selected($video_type, 'vimeo'); ?>>Vimeo</option>
            <option value="mp4" <?php selected($video_type, 'mp4'); ?>>MP4 файл</option>
        </select>
    </p>
    <p id="video_url_field" style="display:<?php echo in_array($video_type, array('youtube', 'vimeo')) ? 'block' : 'none'; ?>;">
        <label><strong>URL видео:</strong></label><br>
        <input type="text" name="ak_video_url" value="<?php echo esc_attr($video_url); ?>" style="width:100%;" placeholder="https://youtube.com/watch?v=...">
    </p>
    <p id="video_file_field" style="display:<?php echo $video_type === 'mp4' ? 'block' : 'none'; ?>;">
        <label><strong>MP4 файл:</strong></label><br>
        <input type="text" name="ak_video_file" id="ak_video_file" value="<?php echo esc_attr($video_file); ?>" style="width:80%;" readonly>
        <button type="button" class="button" id="ak_upload_video">Загрузить</button>
    </p>
    <script>
    jQuery(document).ready(function($) {
        $('#ak_video_type').change(function() {
            var type = $(this).val();
            $('#video_url_field').hide();
            $('#video_file_field').hide();
            if (type === 'youtube' || type === 'vimeo') {
                $('#video_url_field').show();
            } else if (type === 'mp4') {
                $('#video_file_field').show();
            }
        });
        
        $('#ak_upload_video').click(function(e) {
            e.preventDefault();
            var mediaUploader = wp.media({
                title: 'Выберите MP4 файл',
                button: { text: 'Использовать это видео' },
                multiple: false,
                library: { type: 'video/mp4' }
            });
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#ak_video_file').val(attachment.url);
            });
            mediaUploader.open();
        });
    });
    </script>
    <?php
}

/**
 * Save meta data
 */
function ak_save_meta_data($post_id) {
    // Module course
    if (isset($_POST['ak_module_course_nonce']) && wp_verify_nonce($_POST['ak_module_course_nonce'], 'ak_module_course_nonce')) {
        if (isset($_POST['ak_course_id'])) {
            update_post_meta($post_id, '_ak_course_id', sanitize_text_field($_POST['ak_course_id']));
        }
    }
    
    // Lesson module
    if (isset($_POST['ak_lesson_module_nonce']) && wp_verify_nonce($_POST['ak_lesson_module_nonce'], 'ak_lesson_module_nonce')) {
        if (isset($_POST['ak_module_id'])) {
            update_post_meta($post_id, '_ak_module_id', sanitize_text_field($_POST['ak_module_id']));
        }
    }
    
    // Lesson video
    if (isset($_POST['ak_lesson_video_nonce']) && wp_verify_nonce($_POST['ak_lesson_video_nonce'], 'ak_lesson_video_nonce')) {
        if (isset($_POST['ak_video_type'])) {
            update_post_meta($post_id, '_ak_video_type', sanitize_text_field($_POST['ak_video_type']));
        }
        if (isset($_POST['ak_video_url'])) {
            update_post_meta($post_id, '_ak_video_url', esc_url_raw($_POST['ak_video_url']));
        }
        if (isset($_POST['ak_video_file'])) {
            update_post_meta($post_id, '_ak_video_file', esc_url_raw($_POST['ak_video_file']));
        }
    }
}
add_action('save_post', 'ak_save_meta_data');

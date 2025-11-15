<?php
/**
 * Template Academy Theme Functions
 *
 * @package Template Academy
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Theme constants
define('AK_THEME_VERSION', '1.0.0');
define('AK_THEME_DIR', get_template_directory());
define('AK_THEME_URI', get_template_directory_uri());

/**
 * Redirect non-logged-in users to login page
 */
function ak_force_login() {
    // Разрешить доступ к админке и страницам входа/регистрации
    if (is_admin() || is_user_logged_in()) {
        return;
    }
    
    // Разрешить доступ к wp-login.php, wp-register.php, AJAX запросам
    $allowed_urls = array('wp-login.php', 'wp-register.php', 'admin-ajax.php');
    foreach ($allowed_urls as $url) {
        if (strpos($_SERVER['REQUEST_URI'], $url) !== false) {
            return;
        }
    }
    
    // Перенаправить на страницу входа
    auth_redirect();
}
add_action('template_redirect', 'ak_force_login');

/**
 * Theme setup
 */
function ak_theme_setup() {
    // Add theme support
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption'));
    add_theme_support('custom-logo');
    
    // Register navigation menus
    register_nav_menus(array(
        'primary' => __('Primary Menu', 'akademiya-istochnik'),
    ));
}
add_action('after_setup_theme', 'ak_theme_setup');

/**
 * Enqueue scripts and styles
 */
function ak_enqueue_assets() {
    // Main CSS
    wp_enqueue_style('ak-main-style', AK_THEME_URI . '/assets/css/main.css', array(), AK_THEME_VERSION);
    
    // Lucide Icons
    wp_enqueue_script('lucide-icons', 'https://unpkg.com/lucide@latest/dist/umd/lucide.js', array(), null, true);
    
    // Main JS
    wp_enqueue_script('ak-main-js', AK_THEME_URI . '/assets/js/main.js', array(), AK_THEME_VERSION, true);
    
    // Progress Tracker
    wp_enqueue_script('ak-progress', AK_THEME_URI . '/assets/js/progress-tracker.js', array('jquery'), AK_THEME_VERSION, true);
    
    // Quiz System
    wp_enqueue_script('ak-quiz', AK_THEME_URI . '/assets/js/quiz.js', array('jquery'), AK_THEME_VERSION, true);
    
    // Calendar
    wp_enqueue_script('ak-calendar', AK_THEME_URI . '/assets/js/calendar.js', array(), AK_THEME_VERSION, true);
    
    // File Upload
    wp_enqueue_script('ak-file-upload', AK_THEME_URI . '/assets/js/file-upload.js', array('jquery'), AK_THEME_VERSION, true);
    
    // Localize script for AJAX
    wp_localize_script('ak-progress', 'akAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ak-ajax-nonce'),
    ));
}
add_action('wp_enqueue_scripts', 'ak_enqueue_assets');

/**
 * Include required files
 */
require_once AK_THEME_DIR . '/inc/post-types.php';
require_once AK_THEME_DIR . '/inc/user-progress.php';
require_once AK_THEME_DIR . '/inc/ajax-handlers.php';
require_once AK_THEME_DIR . '/inc/quiz-system.php';
require_once AK_THEME_DIR . '/inc/file-manager.php';
require_once AK_THEME_DIR . '/inc/helpers.php';
require_once AK_THEME_DIR . '/inc/admin-user-progress.php';
require_once AK_THEME_DIR . '/inc/lesson-attachments.php';

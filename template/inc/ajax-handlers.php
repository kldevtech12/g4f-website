<?php
/**
 * AJAX Handlers
 *
 * @package Template Academy
*/

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Mark lesson as completed via AJAX
 */
function ak_ajax_mark_lesson_completed() {
    check_ajax_referer('ak-ajax-nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'User not logged in'));
    }
    
    $lesson_id = intval($_POST['lesson_id']);
    $user_id = get_current_user_id();
    
    $result = ak_mark_lesson_completed($user_id, $lesson_id);
    
    if ($result) {
        wp_send_json_success(array('message' => 'Lesson marked as completed'));
    } else {
        wp_send_json_error(array('message' => 'Failed to mark lesson'));
    }
}
add_action('wp_ajax_ak_mark_lesson_completed', 'ak_ajax_mark_lesson_completed');

/**
 * Submit quiz answer via AJAX
 */
function ak_ajax_submit_quiz_answer() {
    check_ajax_referer('ak-ajax-nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'User not logged in'));
    }
    
    $lesson_id = intval($_POST['lesson_id']);
    $question_index = intval($_POST['question_index']);
    $answer_data = isset($_POST['answer_data']) ? $_POST['answer_data'] : null;
    $user_id = get_current_user_id();
    
    // Handle both numeric (choice) and text answers
    if (is_numeric($answer_data)) {
        $answer_data = intval($answer_data);
    } else {
        $answer_data = sanitize_text_field($answer_data);
    }
    
    $result = ak_save_quiz_answer($user_id, $lesson_id, $question_index, $answer_data);
    
    if ($result) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error(array('message' => 'Failed to save answer'));
    }
}
add_action('wp_ajax_ak_submit_quiz_answer', 'ak_ajax_submit_quiz_answer');

/**
 * Upload document via AJAX
 */
function ak_ajax_upload_document() {
    check_ajax_referer('ak-ajax-nonce', 'nonce');
    
    if (!is_user_logged_in() || !current_user_can('upload_files')) {
        wp_send_json_error(array('message' => 'Permission denied'));
    }
    
    if (empty($_FILES['file'])) {
        wp_send_json_error(array('message' => 'No file uploaded'));
    }
    
    $allowed_types = array('pdf', 'docx', 'pptx', 'zip');
    $file_extension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
    
    if (!in_array(strtolower($file_extension), $allowed_types)) {
        wp_send_json_error(array('message' => 'File type not allowed'));
    }
    
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    
    $attachment_id = media_handle_upload('file', 0);
    
    if (is_wp_error($attachment_id)) {
        wp_send_json_error(array('message' => $attachment_id->get_error_message()));
    }
    
    $user_id = get_current_user_id();
    $documents = get_user_meta($user_id, 'ak_user_documents', true);
    if (!is_array($documents)) {
        $documents = array();
    }
    
    $documents[] = array(
        'id' => $attachment_id,
        'url' => wp_get_attachment_url($attachment_id),
        'name' => get_the_title($attachment_id),
        'type' => $file_extension,
        'date' => current_time('mysql'),
    );
    
    update_user_meta($user_id, 'ak_user_documents', $documents);
    
    wp_send_json_success(array(
        'id' => $attachment_id,
        'url' => wp_get_attachment_url($attachment_id),
        'name' => get_the_title($attachment_id),
        'type' => $file_extension,
    ));
}
add_action('wp_ajax_ak_upload_document', 'ak_ajax_upload_document');

/**
 * Delete document via AJAX
 */
function ak_ajax_delete_document() {
    check_ajax_referer('ak-ajax-nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Permission denied'));
    }
    
    $attachment_id = intval($_POST['attachment_id']);
    $user_id = get_current_user_id();
    
    $documents = get_user_meta($user_id, 'ak_user_documents', true);
    if (!is_array($documents)) {
        wp_send_json_error(array('message' => 'No documents found'));
    }
    
    $updated_documents = array_filter($documents, function($doc) use ($attachment_id) {
        return $doc['id'] != $attachment_id;
    });
    
    update_user_meta($user_id, 'ak_user_documents', array_values($updated_documents));
    wp_delete_attachment($attachment_id, true);
    
    wp_send_json_success(array('message' => 'Document deleted'));
}
add_action('wp_ajax_ak_delete_document', 'ak_ajax_delete_document');

<?php
/**
 * File Manager Functions
 *
 * @package Template Academy
*/

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get user documents
 *
 * @param int $user_id User ID
 * @return array
 */
function ak_get_user_documents($user_id) {
    $documents = get_user_meta($user_id, 'ak_user_documents', true);
    return is_array($documents) ? $documents : array();
}

/**
 * Get document icon based on file type
 *
 * @param string $type File type
 * @return string Icon name for Lucide
 */
function ak_get_document_icon($type) {
    $icons = array(
        'pdf' => 'file-text',
        'docx' => 'file-text',
        'pptx' => 'presentation',
        'zip' => 'archive',
    );
    
    return isset($icons[$type]) ? $icons[$type] : 'file';
}

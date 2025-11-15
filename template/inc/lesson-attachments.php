<?php
/**
 * Lesson Attachments System
 *
 * @package Template Academy
*/

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add attachments meta box
 */
function ak_add_lesson_attachments_meta_box() {
    add_meta_box(
        'ak_lesson_attachments',
        'Прикрепленные файлы',
        'ak_lesson_attachments_callback',
        'ak_lesson',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'ak_add_lesson_attachments_meta_box');

/**
 * Attachments meta box callback
 */
function ak_lesson_attachments_callback($post) {
    wp_nonce_field('ak_lesson_attachments_nonce', 'ak_lesson_attachments_nonce');
    
    $attachments = get_post_meta($post->ID, '_ak_lesson_attachments', true);
    if (!is_array($attachments)) {
        $attachments = array();
    }
    ?>
    <div id="ak-attachments-container">
        <p><strong>Загрузите файлы для урока (PDF, PPTX, TXT, DOCX, ZIP)</strong></p>
        
        <div id="ak-attachments-list">
            <?php if (!empty($attachments)): ?>
                <?php foreach ($attachments as $index => $attachment): ?>
                    <div class="ak-attachment-item" data-index="<?php echo $index; ?>">
                        <div class="ak-attachment-info">
                            <span class="dashicons dashicons-media-document"></span>
                            <strong><?php echo esc_html($attachment['title']); ?></strong>
                            <small>(<?php echo esc_html($attachment['type']); ?>, <?php echo size_format($attachment['size']); ?>)</small>
                            <input type="hidden" name="ak_attachments[<?php echo $index; ?>][id]" value="<?php echo esc_attr($attachment['id']); ?>">
                            <input type="hidden" name="ak_attachments[<?php echo $index; ?>][url]" value="<?php echo esc_attr($attachment['url']); ?>">
                            <input type="hidden" name="ak_attachments[<?php echo $index; ?>][title]" value="<?php echo esc_attr($attachment['title']); ?>">
                            <input type="hidden" name="ak_attachments[<?php echo $index; ?>][type]" value="<?php echo esc_attr($attachment['type']); ?>">
                            <input type="hidden" name="ak_attachments[<?php echo $index; ?>][size]" value="<?php echo esc_attr($attachment['size']); ?>">
                        </div>
                        <button type="button" class="button ak-remove-attachment">Удалить</button>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="ak-no-attachments" style="color: #666; font-style: italic;">Нет прикрепленных файлов</p>
            <?php endif; ?>
        </div>
        
        <p style="margin-top: 15px;">
            <button type="button" class="button button-primary" id="ak-add-attachment">Добавить файл</button>
        </p>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        var attachmentIndex = <?php echo count($attachments); ?>;
        var frame;
        
        // Add attachment
        $('#ak-add-attachment').on('click', function(e) {
            e.preventDefault();
            
            // Create media frame
            if (frame) {
                frame.open();
                return;
            }
            
            frame = wp.media({
                title: 'Выберите файл',
                button: {
                    text: 'Прикрепить'
                },
                library: {
                    type: ['application/pdf', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'text/plain', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip']
                },
                multiple: false
            });
            
            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                
                // Validate file type
                var allowedTypes = ['pdf', 'pptx', 'ppt', 'txt', 'docx', 'doc', 'zip'];
                var fileExtension = attachment.filename.split('.').pop().toLowerCase();
                
                if (allowedTypes.indexOf(fileExtension) === -1) {
                    alert('Неподдерживаемый тип файла. Разрешены: PDF, PPTX, TXT, DOCX, ZIP');
                    return;
                }
                
                // Hide "no attachments" message
                $('.ak-no-attachments').remove();
                
                // Add attachment to list
                var html = '<div class="ak-attachment-item" data-index="' + attachmentIndex + '">' +
                    '<div class="ak-attachment-info">' +
                    '<span class="dashicons dashicons-media-document"></span>' +
                    '<strong>' + attachment.title + '</strong>' +
                    '<small>(' + fileExtension.toUpperCase() + ', ' + formatBytes(attachment.filesizeInBytes) + ')</small>' +
                    '<input type="hidden" name="ak_attachments[' + attachmentIndex + '][id]" value="' + attachment.id + '">' +
                    '<input type="hidden" name="ak_attachments[' + attachmentIndex + '][url]" value="' + attachment.url + '">' +
                    '<input type="hidden" name="ak_attachments[' + attachmentIndex + '][title]" value="' + attachment.title + '">' +
                    '<input type="hidden" name="ak_attachments[' + attachmentIndex + '][type]" value="' + fileExtension.toUpperCase() + '">' +
                    '<input type="hidden" name="ak_attachments[' + attachmentIndex + '][size]" value="' + attachment.filesizeInBytes + '">' +
                    '</div>' +
                    '<button type="button" class="button ak-remove-attachment">Удалить</button>' +
                    '</div>';
                
                $('#ak-attachments-list').append(html);
                attachmentIndex++;
            });
            
            frame.open();
        });
        
        // Remove attachment
        $(document).on('click', '.ak-remove-attachment', function() {
            if (confirm('Удалить этот файл?')) {
                $(this).closest('.ak-attachment-item').remove();
                
                // Show "no attachments" if empty
                if ($('#ak-attachments-list .ak-attachment-item').length === 0) {
                    $('#ak-attachments-list').html('<p class="ak-no-attachments" style="color: #666; font-style: italic;">Нет прикрепленных файлов</p>');
                }
            }
        });
        
        // Format bytes helper
        function formatBytes(bytes) {
            if (bytes === 0) return '0 Bytes';
            var k = 1024;
            var sizes = ['Bytes', 'KB', 'MB', 'GB'];
            var i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }
    });
    </script>
    
    <style>
        #ak-attachments-container {
            padding: 10px;
        }
        #ak-attachments-list {
            margin-bottom: 15px;
        }
        .ak-attachment-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .ak-attachment-info {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
        }
        .ak-attachment-info .dashicons {
            color: #2271b1;
            font-size: 24px;
            width: 24px;
            height: 24px;
        }
        .ak-attachment-info strong {
            color: #000;
        }
        .ak-attachment-info small {
            color: #666;
        }
    </style>
    <?php
}

/**
 * Save attachments data
 */
function ak_save_lesson_attachments($post_id) {
    if (!isset($_POST['ak_lesson_attachments_nonce']) || !wp_verify_nonce($_POST['ak_lesson_attachments_nonce'], 'ak_lesson_attachments_nonce')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    if (isset($_POST['ak_attachments']) && is_array($_POST['ak_attachments'])) {
        $attachments = array();
        foreach ($_POST['ak_attachments'] as $attachment) {
            if (!empty($attachment['id'])) {
                $attachments[] = array(
                    'id' => intval($attachment['id']),
                    'url' => esc_url_raw($attachment['url']),
                    'title' => sanitize_text_field($attachment['title']),
                    'type' => sanitize_text_field($attachment['type']),
                    'size' => intval($attachment['size']),
                );
            }
        }
        update_post_meta($post_id, '_ak_lesson_attachments', $attachments);
    } else {
        delete_post_meta($post_id, '_ak_lesson_attachments');
    }
}
add_action('save_post_ak_lesson', 'ak_save_lesson_attachments');

/**
 * Get lesson attachments
 */
function ak_get_lesson_attachments($lesson_id) {
    $attachments = get_post_meta($lesson_id, '_ak_lesson_attachments', true);
    if (!is_array($attachments)) {
        return array();
    }
    return $attachments;
}

/**
 * Get file icon based on type
 */
function ak_get_file_icon($type) {
    $type = strtolower($type);
    
    $icons = array(
        'pdf' => 'file-text',
        'pptx' => 'presentation',
        'ppt' => 'presentation',
        'docx' => 'file-text',
        'doc' => 'file-text',
        'txt' => 'file-text',
        'zip' => 'archive',
    );
    
    return isset($icons[$type]) ? $icons[$type] : 'file';
}

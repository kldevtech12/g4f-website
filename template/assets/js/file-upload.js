/**
 * File Upload JavaScript
 * 
 * @package Template Academy
 */

(function($) {
    'use strict';
    
    let uploadInProgress = false;
    
    $(document).ready(function() {
        const fileInput = $('#ak-file-input');
        const uploadBtn = $('#ak-upload-btn');
        
        // Open file dialog
        uploadBtn.on('click', function(e) {
            e.preventDefault();
            if (uploadInProgress) return;
            fileInput.click();
        });
        
        // Handle file selection
        fileInput.on('change', function() {
            const file = this.files[0];
            if (!file || uploadInProgress) return;
            
            // Validate file type
            const allowedTypes = ['pdf', 'docx', 'pptx', 'zip'];
            const fileExtension = file.name.split('.').pop().toLowerCase();
            
            if (!allowedTypes.includes(fileExtension)) {
                alert('Допустимые форматы: PDF, DOCX, PPTX, ZIP');
                fileInput.val('');
                return;
            }
            
            uploadInProgress = true;
            
            // Create FormData
            const formData = new FormData();
            formData.append('action', 'ak_upload_document');
            formData.append('nonce', akAjax.nonce);
            formData.append('file', file);
            
            // Show loading state
            uploadBtn.prop('disabled', true).text('Загрузка...');
            
            // Upload file
            $.ajax({
                url: akAjax.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                timeout: 30000,
                success: function(response) {
                    uploadBtn.prop('disabled', false).text('Выбрать файл');
                    fileInput.val('');
                    uploadInProgress = false;
                    
                    if (response.success) {
                        const doc = response.data;
                        addDocumentToList(doc);
                    } else {
                        alert('Ошибка загрузки: ' + (response.data.message || 'Неизвестная ошибка'));
                    }
                },
                error: function(xhr, status, error) {
                    uploadBtn.prop('disabled', false).text('Выбрать файл');
                    fileInput.val('');
                    uploadInProgress = false;
                    console.error('Upload error:', status, error);
                    alert('Произошла ошибка при загрузке файла. Попробуйте еще раз.');
                }
            });
        });
        
        // Delete document
        $(document).on('click', '.ak-delete-doc', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (!confirm('Вы уверены, что хотите удалить этот документ?')) {
                return;
            }
            
            const button = $(this);
            const documentItem = button.closest('.ak-document-item');
            const attachmentId = button.data('id');
            
            button.prop('disabled', true);
            
            $.ajax({
                url: akAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'ak_delete_document',
                    nonce: akAjax.nonce,
                    attachment_id: attachmentId
                },
                timeout: 10000,
                success: function(response) {
                    if (response.success) {
                        documentItem.fadeOut(300, function() {
                            $(this).remove();
                            checkEmptyState();
                        });
                    } else {
                        alert('Ошибка удаления документа');
                        button.prop('disabled', false);
                    }
                },
                error: function() {
                    alert('Произошла ошибка при удалении документа');
                    button.prop('disabled', false);
                }
            });
        });
        
        // Add document to list
        function addDocumentToList(doc) {
            const noDocuments = $('.ak-no-documents');
            if (noDocuments.length) {
                noDocuments.remove();
                if (!$('.ak-documents-grid').length) {
                    $('.ak-documents-list').append('<div class="ak-documents-grid"></div>');
                }
            }
            
            const iconName = getDocumentIcon(doc.type);
            const docName = escapeHtml(doc.name);
            const docType = escapeHtml(doc.type.toUpperCase());
            
            const newDoc = $(`
                <div class="ak-document-item" data-id="${doc.id}" style="display:none;">
                    <div class="ak-document-icon">
                        <i data-lucide="${iconName}"></i>
                    </div>
                    <div class="ak-document-info">
                        <h3>${docName}</h3>
                        <span class="ak-document-type">${docType}</span>
                    </div>
                    <div class="ak-document-actions">
                        <a href="${doc.url}" class="ak-btn-icon" download>
                            <i data-lucide="download"></i>
                        </a>
                        <button class="ak-btn-icon ak-delete-doc" data-id="${doc.id}">
                            <i data-lucide="trash-2"></i>
                        </button>
                    </div>
                </div>
            `);
            
            $('.ak-documents-grid').prepend(newDoc);
            
            // Reinitialize Lucide icons for new element
            setTimeout(function() {
                if (typeof window.initializeLucideIcons === 'function') {
                    window.initializeLucideIcons();
                }
                newDoc.fadeIn(300);
            }, 100);
        }
        
        // Escape HTML to prevent XSS
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
        
        // Check if documents list is empty
        function checkEmptyState() {
            if ($('.ak-document-item').length === 0) {
                $('.ak-documents-grid').remove();
                $('.ak-documents-list').append('<p class="ak-no-documents">Документы отсутствуют</p>');
            }
        }
        
        // Get document icon
        function getDocumentIcon(type) {
            const icons = {
                'pdf': 'file-text',
                'docx': 'file-text',
                'pptx': 'presentation',
                'zip': 'archive'
            };
            return icons[type] || 'file';
        }
    });
    
})(jQuery);

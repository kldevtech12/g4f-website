<?php
/**
 * Template Name: Documents
 * 
 * @package Template Academy
 */

get_header();

$user_id = get_current_user_id();
$documents = ak_get_user_documents($user_id);
?>

<div class="ak-container">
    <h1>Документы</h1>
    
    <div class="ak-documents-upload">
        <form id="ak-upload-form" enctype="multipart/form-data">
            <div class="ak-upload-area">
                <i data-lucide="upload"></i>
                <p>Загрузите документ (PDF, DOCX, PPTX, ZIP)</p>
                <input type="file" id="ak-file-input" accept=".pdf,.docx,.pptx,.zip">
                <button type="button" id="ak-upload-btn" class="ak-btn">Выбрать файл</button>
            </div>
        </form>
    </div>
    
    <div class="ak-documents-list">
        <h2>Мои документы</h2>
        <?php if (empty($documents)): ?>
            <p class="ak-no-documents">Документы отсутствуют</p>
        <?php else: ?>
            <div class="ak-documents-grid">
                <?php foreach ($documents as $doc): ?>
                    <div class="ak-document-item" data-id="<?php echo esc_attr($doc['id']); ?>">
                        <div class="ak-document-icon">
                            <i data-lucide="<?php echo ak_get_document_icon($doc['type']); ?>"></i>
                        </div>
                        <div class="ak-document-info">
                            <h3><?php echo esc_html($doc['name']); ?></h3>
                            <span class="ak-document-type"><?php echo strtoupper($doc['type']); ?></span>
                        </div>
                        <div class="ak-document-actions">
                            <a href="<?php echo esc_url($doc['url']); ?>" class="ak-btn-icon" download>
                                <i data-lucide="download"></i>
                            </a>
                            <button class="ak-btn-icon ak-delete-doc" data-id="<?php echo esc_attr($doc['id']); ?>">
                                <i data-lucide="trash-2"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>

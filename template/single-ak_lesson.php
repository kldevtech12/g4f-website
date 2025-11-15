<?php
/**
 * Single Lesson Template
 * 
 * @package Template Academy
 */

get_header();

$lesson_id = get_the_ID();
$user_id = get_current_user_id();
$module_id = get_post_meta($lesson_id, '_ak_module_id', true);
$course_id = get_post_meta($module_id, '_ak_course_id', true);
$questions = get_post_meta($lesson_id, '_ak_quiz_questions', true);

// Check if lesson is locked for this user
$is_locked = false;
if ($user_id) {
    $is_locked = ak_is_lesson_locked($user_id, $lesson_id);
}
?>

<div class="ak-container">
    <?php ak_breadcrumbs($lesson_id); ?>
    
    <?php if ($is_locked): ?>
        <div class="ak-lesson-locked">
            <div class="ak-locked-content">
                <div class="ak-locked-icon">
                    <i data-lucide="lock"></i>
                </div>
                <h1>Урок заблокирован</h1>
                <p>Доступ к этому уроку ограничен администратором.</p>
                <p>Пожалуйста, свяжитесь с преподавателем для получения доступа.</p>
                <a href="<?php echo get_permalink($course_id); ?>" class="ak-btn">Вернуться к курсу</a>
            </div>
        </div>
    <?php else: ?>
    
    <article class="ak-lesson">
        <h1><?php the_title(); ?></h1>
        
        <?php echo ak_get_video_html($lesson_id); ?>
        
        <div class="ak-content">
            <?php the_content(); ?>
        </div>
        
        <?php 
        // Display lesson attachments
        $attachments = ak_get_lesson_attachments($lesson_id);
        if (!empty($attachments)): 
        ?>
            <div class="ak-lesson-attachments">
                <h2>Материалы урока</h2>
                <div class="ak-attachments-grid">
                    <?php foreach ($attachments as $attachment): ?>
                        <a href="<?php echo esc_url($attachment['url']); ?>" class="ak-attachment-card" download target="_blank">
                            <div class="ak-attachment-icon">
                                <i data-lucide="<?php echo ak_get_file_icon($attachment['type']); ?>"></i>
                            </div>
                            <div class="ak-attachment-info">
                                <h4><?php echo esc_html($attachment['title']); ?></h4>
                                <div class="ak-attachment-meta">
                                    <span class="ak-file-type"><?php echo esc_html($attachment['type']); ?></span>
                                    <span class="ak-file-size"><?php echo size_format($attachment['size']); ?></span>
                                </div>
                            </div>
                            <div class="ak-download-icon">
                                <i data-lucide="download"></i>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (is_array($questions) && !empty($questions)): ?>
            <div class="ak-quiz-section">
                <h2>Тесты</h2>
                <div class="ak-quiz-card">
                    <div class="ak-quiz-info">
                        <h3>Тест по уроку</h3>
                        <p>Вопросов: <?php echo count($questions); ?></p>
                    </div>
                    <button class="ak-btn ak-open-quiz" data-lesson="<?php echo $lesson_id; ?>">Пройти тест</button>
                </div>
            </div>
            
            <!-- Quiz Modal -->
            <div id="ak-quiz-modal" class="ak-quiz-modal">
                <div class="ak-quiz-modal-content">
                    <button class="ak-quiz-close">&times;</button>
                    <div class="ak-quiz-header">
                        <h2>Тест по уроку</h2>
                        <div class="ak-quiz-progress">
                            <span class="ak-quiz-current">1</span> / <span class="ak-quiz-total"><?php echo count($questions); ?></span>
                        </div>
                    </div>
                    <div class="ak-quiz-body">
                        <?php foreach ($questions as $index => $question): ?>
                            <?php $question_type = $question['type'] ?? 'choice'; ?>
                            <div class="ak-quiz-question" data-question="<?php echo $index; ?>" style="<?php echo $index === 0 ? 'display: block;' : 'display: none;'; ?>">
                                <h3><?php echo esc_html($question['question']); ?></h3>
                                
                                <?php if ($question_type === 'text'): ?>
                                    <!-- Text answer -->
                                    <div class="ak-quiz-text-answer">
                                        <input type="text" class="ak-quiz-text-input" placeholder="Введите ваш ответ">
                                    </div>
                                <?php else: ?>
                                    <!-- Choice answers -->
                                    <div class="ak-quiz-answers">
                                        <?php foreach ($question['answers'] as $answer_index => $answer): ?>
                                            <label class="ak-quiz-answer">
                                                <input type="radio" name="quiz_<?php echo $index; ?>" value="<?php echo $answer_index; ?>" data-correct="<?php echo $question['correct']; ?>">
                                                <span><?php echo esc_html($answer); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="ak-quiz-feedback"></div>
                                <div class="ak-quiz-actions">
                                    <?php if ($index > 0): ?>
                                        <button class="ak-btn ak-btn-secondary ak-quiz-prev">Назад</button>
                                    <?php endif; ?>
                                    <button class="ak-btn ak-quiz-next" disabled>
                                        <?php echo $index === count($questions) - 1 ? 'Завершить тест' : 'Следующий вопрос'; ?>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="ak-quiz-results" style="display: none;">
                            <div class="ak-quiz-results-content">
                                <i data-lucide="award"></i>
                                <h3>Тест завершен!</h3>
                                <p class="ak-quiz-score"></p>
                                <button class="ak-btn ak-quiz-finish">Закрыть</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </article>
    
    <?php endif; // End is_locked check ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-mark lesson as completed
    if (typeof akAjax !== 'undefined') {
        fetch(akAjax.ajaxurl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'ak_mark_lesson_completed',
                nonce: akAjax.nonce,
                lesson_id: <?php echo $lesson_id; ?>
            })
        });
    }
});
</script>

<?php get_footer(); ?>

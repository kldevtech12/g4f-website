<?php
/**
 * Quiz System Functions
 *
 * @package Template Academy
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add quiz meta box
 */
function ak_add_quiz_meta_box() {
    add_meta_box(
        'ak_lesson_quiz',
        'Тесты урока',
        'ak_lesson_quiz_callback',
        'ak_lesson',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'ak_add_quiz_meta_box');

/**
 * Quiz meta box callback
 */
function ak_lesson_quiz_callback($post) {
    wp_nonce_field('ak_lesson_quiz_nonce', 'ak_lesson_quiz_nonce');
    $questions = get_post_meta($post->ID, '_ak_quiz_questions', true);
    if (!is_array($questions)) {
        $questions = array();
    }
    ?>
    <div id="ak-quiz-container">
        <div id="ak-quiz-questions">
            <?php foreach ($questions as $index => $question): ?>
                <div class="ak-quiz-question" data-index="<?php echo $index; ?>">
                    <h4>Вопрос <?php echo $index + 1; ?> <button type="button" class="button ak-remove-question">Удалить</button></h4>
                    <p>
                        <label><strong>Текст вопроса:</strong></label><br>
                        <input type="text" name="ak_quiz[<?php echo $index; ?>][question]" value="<?php echo esc_attr($question['question']); ?>" style="width:100%;" required>
                    </p>
                    <p>
                        <label><strong>Тип вопроса:</strong></label><br>
                        <select name="ak_quiz[<?php echo $index; ?>][type]" class="ak-question-type" data-question="<?php echo $index; ?>" style="width:100%;">
                            <option value="choice" <?php selected($question['type'] ?? 'choice', 'choice'); ?>>Выбор варианта</option>
                            <option value="text" <?php selected($question['type'] ?? 'choice', 'text'); ?>>Текстовый ответ</option>
                        </select>
                    </p>
                    
                    <div class="ak-choice-answers" data-question="<?php echo $index; ?>" style="<?php echo ($question['type'] ?? 'choice') === 'text' ? 'display:none;' : ''; ?>">
                        <p><strong>Варианты ответов:</strong> <button type="button" class="button button-small ak-add-answer" data-question="<?php echo $index; ?>">+ Добавить вариант</button></p>
                        <div class="ak-answers-list" data-question="<?php echo $index; ?>">
                            <?php 
                            $answers = isset($question['answers']) ? $question['answers'] : array();
                            if (!empty($answers)) {
                                foreach ($answers as $i => $answer): 
                            ?>
                                <div class="ak-answer-item" style="margin-left: 20px; margin-bottom: 10px; display: flex; align-items: center; gap: 10px;">
                                    <input type="radio" name="ak_quiz[<?php echo $index; ?>][correct]" value="<?php echo $i; ?>" <?php checked($question['correct'], $i); ?>>
                                    <input type="text" name="ak_quiz[<?php echo $index; ?>][answers][<?php echo $i; ?>]" value="<?php echo esc_attr($answer); ?>" style="flex: 1;" placeholder="Вариант <?php echo $i + 1; ?>">
                                    <?php if (count($answers) > 2): ?>
                                        <button type="button" class="button button-small ak-remove-answer">Удалить</button>
                                    <?php endif; ?>
                                </div>
                            <?php 
                                endforeach;
                            }
                            ?>
                        </div>
                    </div>
                    
                    <div class="ak-text-answer" data-question="<?php echo $index; ?>" style="<?php echo ($question['type'] ?? 'choice') === 'choice' ? 'display:none;' : ''; ?>">
                        <p>
                            <label><strong>Правильный ответ:</strong></label><br>
                            <textarea name="ak_quiz[<?php echo $index; ?>][text_answer]" style="width:100%; height:80px;" placeholder="Введите правильный ответ (можно несколько вариантов через запятую)"><?php echo esc_textarea($question['text_answer'] ?? ''); ?></textarea>
                            <small style="display:block; margin-top:5px; color:#666;">Можно указать несколько правильных вариантов через запятую. Регистр не учитывается.</small>
                        </p>
                    </div>
                    <hr>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="button button-primary" id="ak-add-question">Добавить вопрос</button>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        var questionIndex = <?php echo count($questions); ?>;
        
        // Toggle question type
        $(document).on('change', '.ak-question-type', function() {
            var questionIdx = $(this).data('question');
            var type = $(this).val();
            var question = $(this).closest('.ak-quiz-question');
            
            if (type === 'text') {
                question.find('.ak-choice-answers').hide();
                question.find('.ak-text-answer').show();
                // Remove required from choice inputs
                question.find('.ak-choice-answers input').removeAttr('required');
                question.find('.ak-text-answer textarea').attr('required', true);
            } else {
                question.find('.ak-choice-answers').show();
                question.find('.ak-text-answer').hide();
                // Add required to choice inputs
                question.find('.ak-choice-answers input[type="text"]').attr('required', true);
                question.find('.ak-choice-answers input[type="radio"]').attr('required', true);
                question.find('.ak-text-answer textarea').removeAttr('required');
            }
        });
        
        // Add new question
        $('#ak-add-question').click(function() {
            var html = '<div class="ak-quiz-question" data-index="' + questionIndex + '">' +
                '<h4>Вопрос ' + (questionIndex + 1) + ' <button type="button" class="button ak-remove-question">Удалить</button></h4>' +
                '<p><label><strong>Текст вопроса:</strong></label><br>' +
                '<input type="text" name="ak_quiz[' + questionIndex + '][question]" style="width:100%;" required></p>' +
                '<p><label><strong>Тип вопроса:</strong></label><br>' +
                '<select name="ak_quiz[' + questionIndex + '][type]" class="ak-question-type" data-question="' + questionIndex + '" style="width:100%;">' +
                '<option value="choice">Выбор варианта</option>' +
                '<option value="text">Текстовый ответ</option>' +
                '</select></p>' +
                '<div class="ak-choice-answers" data-question="' + questionIndex + '">' +
                '<p><strong>Варианты ответов:</strong> <button type="button" class="button button-small ak-add-answer" data-question="' + questionIndex + '">+ Добавить вариант</button></p>' +
                '<div class="ak-answers-list" data-question="' + questionIndex + '">';
            
            // Start with 2 default answers
            for (var i = 0; i < 2; i++) {
                html += '<div class="ak-answer-item" style="margin-left: 20px; margin-bottom: 10px; display: flex; align-items: center; gap: 10px;">' +
                    '<input type="radio" name="ak_quiz[' + questionIndex + '][correct]" value="' + i + '" required> ' +
                    '<input type="text" name="ak_quiz[' + questionIndex + '][answers][' + i + ']" style="flex: 1;" placeholder="Вариант ' + (i + 1) + '" required>' +
                    '</div>';
            }
            
            html += '</div></div>' +
                '<div class="ak-text-answer" data-question="' + questionIndex + '" style="display:none;">' +
                '<p><label><strong>Правильный ответ:</strong></label><br>' +
                '<textarea name="ak_quiz[' + questionIndex + '][text_answer]" style="width:100%; height:80px;" placeholder="Введите правильный ответ (можно несколько вариантов через запятую)"></textarea>' +
                '<small style="display:block; margin-top:5px; color:#666;">Можно указать несколько правильных вариантов через запятую. Регистр не учитывается.</small>' +
                '</p></div>' +
                '<hr></div>';
            $('#ak-quiz-questions').append(html);
            questionIndex++;
        });
        
        // Remove question
        $(document).on('click', '.ak-remove-question', function() {
            if (confirm('Удалить этот вопрос?')) {
                $(this).closest('.ak-quiz-question').remove();
            }
        });
        
        // Add answer to question
        $(document).on('click', '.ak-add-answer', function() {
            var questionIdx = $(this).data('question');
            var answersList = $('.ak-answers-list[data-question="' + questionIdx + '"]');
            var answerCount = answersList.find('.ak-answer-item').length;
            
            var newAnswer = '<div class="ak-answer-item" style="margin-left: 20px; margin-bottom: 10px; display: flex; align-items: center; gap: 10px;">' +
                '<input type="radio" name="ak_quiz[' + questionIdx + '][correct]" value="' + answerCount + '" required> ' +
                '<input type="text" name="ak_quiz[' + questionIdx + '][answers][' + answerCount + ']" style="flex: 1;" placeholder="Вариант ' + (answerCount + 1) + '" required>' +
                '<button type="button" class="button button-small ak-remove-answer">Удалить</button>' +
                '</div>';
            
            answersList.append(newAnswer);
            
            // Add delete button to existing answers if count >= 3
            if (answerCount >= 2) {
                answersList.find('.ak-answer-item').each(function() {
                    if ($(this).find('.ak-remove-answer').length === 0) {
                        $(this).append('<button type="button" class="button button-small ak-remove-answer">Удалить</button>');
                    }
                });
            }
        });
        
        // Remove answer
        $(document).on('click', '.ak-remove-answer', function() {
            var answerItem = $(this).closest('.ak-answer-item');
            var answersList = answerItem.closest('.ak-answers-list');
            var answerCount = answersList.find('.ak-answer-item').length;
            
            if (answerCount > 2) {
                answerItem.remove();
                
                // Reindex answers and radio buttons
                var questionIdx = answersList.data('question');
                answersList.find('.ak-answer-item').each(function(index) {
                    $(this).find('input[type="radio"]').attr('value', index);
                    $(this).find('input[type="text"]').attr('name', 'ak_quiz[' + questionIdx + '][answers][' + index + ']');
                    $(this).find('input[type="text"]').attr('placeholder', 'Вариант ' + (index + 1));
                });
                
                // Remove delete buttons if only 2 answers left
                if (answersList.find('.ak-answer-item').length === 2) {
                    answersList.find('.ak-remove-answer').remove();
                }
            } else {
                alert('Должно быть минимум 2 варианта ответа');
            }
        });
    });
    </script>
    
    <style>
        .ak-quiz-question { 
            padding: 15px; 
            background: #f9f9f9; 
            margin-bottom: 15px; 
            border-radius: 5px; 
        }
        .ak-quiz-question h4 { 
            margin-top: 0; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }
        .ak-answers-list {
            margin-bottom: 10px;
        }
        .ak-answer-item {
            background: white;
            padding: 8px;
            border-radius: 3px;
            border: 1px solid #ddd;
        }
    </style>
    <?php
}

/**
 * Save quiz data
 */
function ak_save_quiz_data($post_id) {
    if (!isset($_POST['ak_lesson_quiz_nonce']) || !wp_verify_nonce($_POST['ak_lesson_quiz_nonce'], 'ak_lesson_quiz_nonce')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    if (isset($_POST['ak_quiz'])) {
        $questions = array();
        foreach ($_POST['ak_quiz'] as $question_data) {
            if (!empty($question_data['question'])) {
                $type = isset($question_data['type']) ? $question_data['type'] : 'choice';
                
                $question = array(
                    'question' => sanitize_text_field($question_data['question']),
                    'type' => $type,
                );
                
                if ($type === 'text') {
                    // Text answer type
                    $question['text_answer'] = sanitize_textarea_field($question_data['text_answer']);
                } else {
                    // Choice type
                    $question['answers'] = array_map('sanitize_text_field', $question_data['answers']);
                    $question['correct'] = intval($question_data['correct']);
                }
                
                $questions[] = $question;
            }
        }
        update_post_meta($post_id, '_ak_quiz_questions', $questions);
    } else {
        delete_post_meta($post_id, '_ak_quiz_questions');
    }
}
add_action('save_post_ak_lesson', 'ak_save_quiz_data');

/**
 * Save user quiz answer
 *
 * @param int $user_id User ID
 * @param int $lesson_id Lesson ID
 * @param int $question_index Question index
 * @param mixed $answer_data Selected answer index or text answer
 * @return bool|array
 */
function ak_save_quiz_answer($user_id, $lesson_id, $question_index, $answer_data) {
    $questions = get_post_meta($lesson_id, '_ak_quiz_questions', true);
    if (!is_array($questions) || !isset($questions[$question_index])) {
        return false;
    }
    
    $question = $questions[$question_index];
    $type = $question['type'] ?? 'choice';
    
    // Check if answer is correct
    if ($type === 'text') {
        // Text answer - check against correct answers (case-insensitive, trim spaces)
        $user_answer = mb_strtolower(trim($answer_data), 'UTF-8');
        $correct_answers = array_map(function($ans) {
            return mb_strtolower(trim($ans), 'UTF-8');
        }, explode(',', $question['text_answer']));
        
        $correct = in_array($user_answer, $correct_answers);
        $correct_answer = $question['text_answer'];
    } else {
        // Choice answer
        $correct = ($question['correct'] == $answer_data);
        $correct_answer = $question['correct'];
    }
    
    $results = get_user_meta($user_id, 'ak_quiz_results', true);
    if (!is_array($results)) {
        $results = array();
    }
    
    if (!isset($results[$lesson_id])) {
        $results[$lesson_id] = array();
    }
    
    $results[$lesson_id]['question_' . $question_index] = array(
        'selected' => $answer_data,
        'correct' => $correct,
        'type' => $type,
    );
    
    // Calculate score
    $total_questions = count($questions);
    $correct_answers = 0;
    foreach ($results[$lesson_id] as $key => $result) {
        if ($key !== 'score' && isset($result['correct']) && $result['correct']) {
            $correct_answers++;
        }
    }
    $results[$lesson_id]['score'] = round(($correct_answers / $total_questions) * 100);
    
    update_user_meta($user_id, 'ak_quiz_results', $results);
    
    // Mark quiz as completed in progress
    $progress = get_user_meta($user_id, 'ak_user_progress', true);
    if (!is_array($progress)) {
        $progress = array();
    }
    
    $module_id = get_post_meta($lesson_id, '_ak_module_id', true);
    $course_id = get_post_meta($module_id, '_ak_course_id', true);
    
    if (!isset($progress[$course_id]['completed_quizzes'])) {
        $progress[$course_id]['completed_quizzes'] = array();
    }
    
    $quiz_key = $lesson_id . '_' . $question_index;
    if (!in_array($quiz_key, $progress[$course_id]['completed_quizzes'])) {
        $progress[$course_id]['completed_quizzes'][] = $quiz_key;
        update_user_meta($user_id, 'ak_user_progress', $progress);
    }
    
    return array(
        'correct' => $correct,
        'correct_answer' => $correct_answer,
        'type' => $type,
    );
}

/**
 * Get user quiz result
 *
 * @param int $user_id User ID
 * @param int $lesson_id Lesson ID
 * @param int $question_index Question index
 * @return array|null
 */
function ak_get_quiz_result($user_id, $lesson_id, $question_index) {
    $results = get_user_meta($user_id, 'ak_quiz_results', true);
    if (!is_array($results) || !isset($results[$lesson_id]['question_' . $question_index])) {
        return null;
    }
    
    return $results[$lesson_id]['question_' . $question_index];
}

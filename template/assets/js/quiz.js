/**
 * Quiz System JavaScript
 * 
 * @package Template Academy
 */

(function($) {
    'use strict';
    
    let quizInProgress = false;
    
    $(document).ready(function() {
        let currentQuestion = 0;
        let totalQuestions = 0;
        let correctAnswers = 0;
        let lessonId = 0;
        const userAnswers = [];
        
        // Open quiz modal
        $(document).on('click', '.ak-open-quiz', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (quizInProgress) return;
            
            lessonId = $(this).data('lesson');
            const modal = $('#ak-quiz-modal');
            totalQuestions = modal.find('.ak-quiz-question').length;
            currentQuestion = 0;
            correctAnswers = 0;
            userAnswers.length = 0;
            quizInProgress = true;
            
            // Reset all questions
            modal.find('.ak-quiz-question').hide().removeClass('active');
            modal.find('.ak-quiz-question').first().show().addClass('active');
            modal.find('.ak-quiz-answer').removeClass('selected correct incorrect disabled');
            modal.find('.ak-quiz-feedback').removeClass('show correct incorrect').hide();
            modal.find('.ak-quiz-results').removeClass('show').hide();
            modal.find('input[type="radio"]').prop('checked', false).prop('disabled', false);
            modal.find('.ak-quiz-text-input').val('').prop('disabled', false);
            modal.find('.ak-quiz-next').prop('disabled', true);
            
            updateProgress();
            modal.addClass('active');
            $('body').css('overflow', 'hidden');
            
            // Initialize icons once
            if (typeof window.initializeLucideIcons === 'function') {
                window.initializeLucideIcons();
            }
        });
        
        // Close quiz modal
        $(document).on('click', '.ak-quiz-close, .ak-quiz-finish', function(e) {
            e.preventDefault();
            e.stopPropagation();
            closeQuizModal();
        });
        
        // Close on overlay click
        $(document).on('click', '#ak-quiz-modal', function(e) {
            if ($(e.target).is('#ak-quiz-modal')) {
                closeQuizModal();
            }
        });
        
        function closeQuizModal() {
            $('#ak-quiz-modal').removeClass('active');
            $('body').css('overflow', '');
            quizInProgress = false;
        }
        
        // Answer selection for choice questions
        $(document).on('change', '.ak-quiz-question.active input[type="radio"]', function(e) {
            e.stopPropagation();
            
            const question = $(this).closest('.ak-quiz-question');
            const questionIndex = question.data('question');
            const selectedAnswer = parseInt($(this).val());
            const correctAnswer = parseInt($(this).data('correct'));
            
            // Remove previous selection
            question.find('.ak-quiz-answer').removeClass('selected');
            
            // Mark selected
            $(this).closest('.ak-quiz-answer').addClass('selected');
            
            // Enable next button
            question.find('.ak-quiz-next').prop('disabled', false);
            
            // Store answer
            userAnswers[questionIndex] = {
                selected: selectedAnswer,
                correct: correctAnswer,
                type: 'choice'
            };
        });
        
        // Text input for text questions
        $(document).on('input keyup', '.ak-quiz-question.active .ak-quiz-text-input', function(e) {
            const question = $(this).closest('.ak-quiz-question');
            const questionIndex = question.data('question');
            const textAnswer = $(this).val().trim();
            
            // Enable next button if text is not empty
            if (textAnswer.length > 0) {
                question.find('.ak-quiz-next').prop('disabled', false);
                
                // Store answer
                userAnswers[questionIndex] = {
                    text: textAnswer,
                    type: 'text'
                };
            } else {
                question.find('.ak-quiz-next').prop('disabled', true);
                // Clear stored answer if empty
                if (userAnswers[questionIndex]) {
                    delete userAnswers[questionIndex];
                }
            }
        });
        
        // Next button
        $(document).on('click', '.ak-quiz-next', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const button = $(this);
            if (button.prop('disabled')) return;
            
            const question = button.closest('.ak-quiz-question');
            const questionIndex = question.data('question');
            const userAnswer = userAnswers[questionIndex];
            
            if (!userAnswer) return;
            
            // Disable button to prevent double click
            button.prop('disabled', true);
            
            const answerType = userAnswer.type;
            
            if (answerType === 'text') {
                // Text answer - disable input
                question.find('.ak-quiz-text-input').prop('disabled', true);
                
                // Save and check answer via AJAX
                saveQuizAnswer(lessonId, questionIndex, userAnswer.text, function(response) {
                    if (response.success) {
                        const isCorrect = response.data.correct;
                        const feedback = question.find('.ak-quiz-feedback');
                        
                        if (isCorrect) {
                            correctAnswers++;
                            feedback.addClass('correct').html('<i data-lucide="check-circle"></i> Правильно!');
                        } else {
                            feedback.addClass('incorrect').html('<i data-lucide="x-circle"></i> Неправильно. Правильный ответ: ' + response.data.correct_answer);
                        }
                        
                        feedback.addClass('show').show();
                        
                        // Initialize icons for feedback
                        if (typeof window.initializeLucideIcons === 'function') {
                            window.initializeLucideIcons();
                        }
                        
                        // Move to next question or results
                        setTimeout(function() {
                            if (questionIndex < totalQuestions - 1) {
                                currentQuestion++;
                                showQuestion(currentQuestion);
                                updateProgress();
                            } else {
                                showResults();
                            }
                        }, 2000);
                    }
                });
                
            } else {
                // Choice answer
                const selectedAnswer = userAnswer.selected;
                const correctAnswer = userAnswer.correct;
                
                // Disable all answers in current question
                question.find('input[type="radio"]').prop('disabled', true);
                question.find('.ak-quiz-answer').addClass('disabled');
                
                // Show feedback
                const isCorrect = selectedAnswer === correctAnswer;
                const feedback = question.find('.ak-quiz-feedback');
                
                if (isCorrect) {
                    correctAnswers++;
                    feedback.addClass('correct').html('<i data-lucide="check-circle"></i> Правильно!');
                    question.find('input[value="' + selectedAnswer + '"]').closest('.ak-quiz-answer').addClass('correct');
                } else {
                    const correctAnswerText = question.find('input[value="' + correctAnswer + '"]').closest('.ak-quiz-answer').find('span').text();
                    feedback.addClass('incorrect').html('<i data-lucide="x-circle"></i> Неправильно. Правильный ответ: ' + correctAnswerText);
                    question.find('input[value="' + selectedAnswer + '"]').closest('.ak-quiz-answer').addClass('incorrect');
                    question.find('input[value="' + correctAnswer + '"]').closest('.ak-quiz-answer').addClass('correct');
                }
                
                feedback.addClass('show').show();
                
                // Initialize icons for feedback
                if (typeof window.initializeLucideIcons === 'function') {
                    window.initializeLucideIcons();
                }
                
                // Save answer to server
                saveQuizAnswer(lessonId, questionIndex, selectedAnswer);
                
                // Delay before moving to next question
                setTimeout(function() {
                    if (questionIndex < totalQuestions - 1) {
                        currentQuestion++;
                        showQuestion(currentQuestion);
                        updateProgress();
                    } else {
                        showResults();
                    }
                }, 1500);
            }
        });
        
        // Previous button
        $(document).on('click', '.ak-quiz-prev', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (currentQuestion > 0) {
                currentQuestion--;
                showQuestion(currentQuestion);
                updateProgress();
            }
        });
        
        function showQuestion(index) {
            $('.ak-quiz-question').hide().removeClass('active');
            $('.ak-quiz-question[data-question="' + index + '"]').show().addClass('active');
        }
        
        function updateProgress() {
            $('.ak-quiz-current').text(currentQuestion + 1);
            $('.ak-quiz-total').text(totalQuestions);
        }
        
        function showResults() {
            $('.ak-quiz-body .ak-quiz-question').hide();
            const percentage = Math.round((correctAnswers / totalQuestions) * 100);
            const resultsText = 'Вы правильно ответили на ' + correctAnswers + ' из ' + totalQuestions + ' вопросов (' + percentage + '%)';
            
            $('.ak-quiz-results .ak-quiz-score').text(resultsText);
            $('.ak-quiz-results').addClass('show').show();
            
            // Initialize icons for results
            if (typeof window.initializeLucideIcons === 'function') {
                window.initializeLucideIcons();
            }
        }
        
        function saveQuizAnswer(lessonId, questionIndex, answerData, callback) {
            $.ajax({
                url: akAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'ak_submit_quiz_answer',
                    nonce: akAjax.nonce,
                    lesson_id: lessonId,
                    question_index: questionIndex,
                    answer_data: answerData
                },
                success: function(response) {
                    if (callback) {
                        callback(response);
                    }
                },
                error: function() {
                    console.error('Failed to save answer');
                    if (callback) {
                        callback({success: false});
                    }
                }
            });
        }
    });
    
})(jQuery);

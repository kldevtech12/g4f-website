<?php
/**
 * Admin User Progress Management
 *
 * @package Template Academy
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add admin menu for user progress
 */
function ak_add_user_progress_menu() {
    add_users_page(
        'Прогресс пользователей',
        'Прогресс обучения',
        'edit_users',
        'ak-user-progress',
        'ak_user_progress_page'
    );
}
add_action('admin_menu', 'ak_add_user_progress_menu');

/**
 * User progress page
 */
function ak_user_progress_page() {
    // Check if viewing specific user
    $selected_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    
    // Handle bulk actions for items
    if (isset($_POST['action']) && isset($_POST['user_id']) && !empty($_POST['items'])) {
        check_admin_referer('ak_user_progress_action', 'ak_nonce');
        
        $user_id = intval($_POST['user_id']);
        $action = $_POST['action'];
        $item_ids = array_map('intval', $_POST['items']);
        
        if (!empty($item_ids)) {
            switch ($action) {
                case 'lock':
                    ak_lock_item_for_user($user_id, $item_ids);
                    echo '<div class="notice notice-success is-dismissible"><p>Элементы заблокированы.</p></div>';
                    break;
                    
                case 'unlock':
                    ak_unlock_item_for_user($user_id, $item_ids);
                    echo '<div class="notice notice-success is-dismissible"><p>Элементы разблокированы.</p></div>';
                    break;
                    
                case 'reset_progress':
                    ak_reset_user_item_progress($user_id, $item_ids);
                    echo '<div class="notice notice-success is-dismissible"><p>Прогресс сброшен.</p></div>';
                    break;
            }
        }
    }
    
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Прогресс обучения</h1>
        <hr class="wp-header-end">
        
        <?php if ($selected_user_id): ?>
            <?php ak_render_user_progress_detail($selected_user_id); ?>
        <?php else: ?>
            <?php ak_render_users_table(); ?>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Render users table (main view)
 */
function ak_render_users_table() {
    // Get search query
    $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    
    // Get users
    $args = array(
        'orderby' => 'display_name',
        'order' => 'ASC'
    );
    
    if (!empty($search)) {
        $args['search'] = '*' . $search . '*';
        $args['search_columns'] = array('user_login', 'user_email', 'display_name');
    }
    
    $users = get_users($args);
    $total_users = count($users);
    
    ?>
    <form method="get" class="search-form wp-clearfix">
        <input type="hidden" name="page" value="ak-user-progress">
        <p class="search-box">
            <label class="screen-reader-text" for="user-search-input">Поиск пользователей:</label>
            <input type="search" id="user-search-input" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Поиск пользователей...">
            <input type="submit" id="search-submit" class="button" value="Найти пользователей">
        </p>
    </form>
    
    <?php if (!empty($search)): ?>
        <div class="tablenav top">
            <div class="alignleft">
                <span class="displaying-num"><?php echo sprintf(_n('%s пользователь найден', '%s пользователей найдено', $total_users), number_format_i18n($total_users)); ?></span>
            </div>
        </div>
    <?php endif; ?>
    
    <table class="wp-list-table widefat fixed striped users">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-username column-primary">Пользователь</th>
                <th scope="col" class="manage-column">Email</th>
                <th scope="col" class="manage-column">Прогресс</th>
                <th scope="col" class="manage-column">Курсы</th>
                <th scope="col" class="manage-column">Заблокировано</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
                <tr>
                    <td colspan="5">
                        <p style="text-align: center; padding: 20px 0;">
                            <?php echo $search ? 'Пользователи не найдены.' : 'Нет пользователей.'; ?>
                        </p>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                    <?php
                    $progress = get_user_meta($user->ID, 'ak_user_progress', true);
                    $locked_items = get_user_meta($user->ID, 'ak_locked_items', true);
                    
                    if (!is_array($progress)) $progress = array();
                    if (!is_array($locked_items)) $locked_items = array();
                    
                    // Calculate total progress
                    $total_lessons = 0;
                    $completed_lessons = 0;
                    
                    $courses = get_posts(array(
                        'post_type' => 'ak_course',
                        'posts_per_page' => -1,
                        'post_status' => 'publish'
                    ));
                    
                    foreach ($courses as $course) {
                        $modules = get_posts(array(
                            'post_type' => 'ak_module',
                            'meta_key' => '_ak_course_id',
                            'meta_value' => $course->ID,
                            'posts_per_page' => -1,
                            'post_status' => 'publish'
                        ));
                        
                        foreach ($modules as $module) {
                            $lessons = get_posts(array(
                                'post_type' => 'ak_lesson',
                                'meta_key' => '_ak_module_id',
                                'meta_value' => $module->ID,
                                'posts_per_page' => -1,
                                'post_status' => 'publish'
                            ));
                            $total_lessons += count($lessons);
                        }
                    }
                    
                    // Count completed lessons (only published ones)
                    foreach ($progress as $course_id => $course_data) {
                        if (isset($course_data['completed_lessons'])) {
                            foreach ($course_data['completed_lessons'] as $lesson_id) {
                                $lesson_status = get_post_status($lesson_id);
                                if ($lesson_status === 'publish') {
                                    $completed_lessons++;
                                }
                            }
                        }
                    }
                    
                    $progress_percent = $total_lessons > 0 ? round(($completed_lessons / $total_lessons) * 100) : 0;
                    $locked_count = count($locked_items);
                    
                    // Count only active courses (published)
                    $active_courses = 0;
                    foreach ($progress as $course_id => $course_data) {
                        if (get_post_status($course_id) === 'publish') {
                            $active_courses++;
                        }
                    }
                    ?>
                    <tr>
                        <td class="username column-username has-row-actions column-primary" data-colname="Пользователь">
                            <strong>
                                <a href="<?php echo admin_url('users.php?page=ak-user-progress&user_id=' . $user->ID); ?>">
                                    <?php echo esc_html($user->display_name); ?>
                                </a>
                            </strong>
                            <br>
                            <div class="row-actions">
                                <span class="edit">
                                    <a href="<?php echo admin_url('users.php?page=ak-user-progress&user_id=' . $user->ID); ?>">Управление прогрессом</a> |
                                </span>
                                <span class="view">
                                    <a href="<?php echo get_author_posts_url($user->ID); ?>" target="_blank">Профиль</a>
                                </span>
                            </div>
                        </td>
                        <td data-colname="Email">
                            <?php echo esc_html($user->user_email); ?>
                        </td>
                        <td data-colname="Прогресс">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="flex: 1; background: #f0f0f1; border-radius: 3px; height: 20px; overflow: hidden;">
                                    <div style="background: #2271b1; height: 100%; width: <?php echo $progress_percent; ?>%;"></div>
                                </div>
                                <span style="min-width: 40px;"><strong><?php echo $progress_percent; ?>%</strong></span>
                            </div>
                            <small style="color: #646970;"><?php echo $completed_lessons; ?> / <?php echo $total_lessons; ?> уроков</small>
                        </td>
                        <td data-colname="Курсы">
                            <strong><?php echo $active_courses; ?></strong>
                            <?php echo _n('курс', 'курса', $active_courses); ?>
                        </td>
                        <td data-colname="Заблокировано">
                            <?php if ($locked_count > 0): ?>
                                <span class="ak-badge ak-badge-locked">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                    <?php echo $locked_count; ?>
                                </span>
                            <?php else: ?>
                                <span style="color: #646970;">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th scope="col" class="manage-column column-username column-primary">Пользователь</th>
                <th scope="col" class="manage-column">Email</th>
                <th scope="col" class="manage-column">Прогресс</th>
                <th scope="col" class="manage-column">Курсы</th>
                <th scope="col" class="manage-column">Заблокировано</th>
            </tr>
        </tfoot>
    </table>
    
    <style>
        .search-form.wp-clearfix {
            float: right;
            margin: 10px 0 20px;
        }
        .search-box {
            margin: 0;
        }
        .search-box input[type="search"] {
            width: 280px;
            margin-right: 5px;
        }
    </style>
    <?php
}

/**
 * Render user progress detail view
 */
function ak_render_user_progress_detail($user_id) {
    $user = get_user_by('id', $user_id);
    if (!$user) {
        echo '<p>Пользователь не найден.</p>';
        return;
    }
    
    // Get filter
    $filter = isset($_GET['filter']) ? sanitize_text_field($_GET['filter']) : 'all';
    
    // Get user data
    $progress = get_user_meta($user_id, 'ak_user_progress', true);
    $locked_items = get_user_meta($user_id, 'ak_locked_items', true);
    $quiz_results = get_user_meta($user_id, 'ak_quiz_results', true);
    
    if (!is_array($progress)) $progress = array();
    if (!is_array($locked_items)) $locked_items = array();
    if (!is_array($quiz_results)) $quiz_results = array();
    
    // Get all learning items
    $all_items = ak_get_all_learning_items();
    
    // Apply filter
    $items = array();
    foreach ($all_items as $item) {
        if ($filter === 'all' || $item['type'] === $filter) {
            $items[] = $item;
        }
    }
    
    // Count by type
    $modules_count = 0;
    $lessons_count = 0;
    foreach ($all_items as $item) {
        if ($item['type'] === 'module') $modules_count++;
        else $lessons_count++;
    }
    
    ?>
    <p>
        <a href="<?php echo admin_url('users.php?page=ak-user-progress'); ?>" class="button">
            ← Назад к списку пользователей
        </a>
    </p>
    
    <h2>
        Управление прогрессом: <?php echo esc_html($user->display_name); ?>
        <small style="font-weight: normal; color: #646970;">(<?php echo esc_html($user->user_email); ?>)</small>
    </h2>
    
    <?php if (empty($all_items)): ?>
        <p>Нет доступных уроков и модулей.</p>
        <?php return; ?>
    <?php endif; ?>
    
    <form method="post">
        <?php wp_nonce_field('ak_user_progress_action', 'ak_nonce'); ?>
        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
        
        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <label for="bulk-action-selector-top" class="screen-reader-text">Выберите массовое действие</label>
                <select name="action" id="bulk-action-selector-top">
                    <option value="-1">Действия</option>
                    <option value="lock">Заблокировать</option>
                    <option value="unlock">Разблокировать</option>
                    <option value="reset_progress">Сбросить прогресс</option>
                </select>
                <input type="submit" class="button action" value="Применить">
            </div>
            
            <div class="alignleft actions">
                <select name="filter" id="filter-by-type" onchange="this.form.method='get'; this.form.submit();">
                    <option value="all" <?php selected($filter, 'all'); ?>>Все типы (<?php echo count($all_items); ?>)</option>
                    <option value="module" <?php selected($filter, 'module'); ?>>Модули (<?php echo $modules_count; ?>)</option>
                    <option value="lesson" <?php selected($filter, 'lesson'); ?>>Уроки (<?php echo $lessons_count; ?>)</option>
                </select>
                <input type="hidden" name="page" value="ak-user-progress">
                <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
            </div>
        </div>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input id="cb-select-all-1" type="checkbox">
                    </td>
                    <th scope="col" class="manage-column column-primary">Название</th>
                    <th scope="col" class="manage-column">Тип</th>
                    <th scope="col" class="manage-column">Курс</th>
                    <th scope="col" class="manage-column">Статус</th>
                    <th scope="col" class="manage-column">Результат теста</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="6">
                            <p style="text-align: center; padding: 20px 0;">
                                Нет элементов для отображения.
                            </p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <?php
                        $is_locked = in_array($item['id'], $locked_items);
                        $is_completed = false;
                        $quiz_score = null;
                        
                        // Check completion
                        if ($item['type'] === 'lesson') {
                            $course_id = $item['course_id'];
                            if (isset($progress[$course_id]['completed_lessons'])) {
                                $is_completed = in_array($item['id'], $progress[$course_id]['completed_lessons']);
                            }
                            if (isset($quiz_results[$item['id']]['score'])) {
                                $quiz_score = $quiz_results[$item['id']]['score'];
                            }
                        }
                        ?>
                        <tr>
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="items[]" value="<?php echo $item['id']; ?>">
                            </th>
                            <td class="column-primary" data-colname="Название">
                                <strong><?php echo esc_html($item['title']); ?></strong>
                                <button type="button" class="toggle-row"><span class="screen-reader-text">Подробнее</span></button>
                            </td>
                            <td data-colname="Тип">
                                <?php if ($item['type'] === 'module'): ?>
                                    <span class="ak-type-badge module">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/></svg>
                                        Модуль
                                    </span>
                                <?php else: ?>
                                    <span class="ak-type-badge lesson">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                                        Урок
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td data-colname="Курс">
                                <small><?php echo esc_html($item['course_title']); ?></small>
                            </td>
                            <td data-colname="Статус">
                                <?php if ($is_locked): ?>
                                    <span class="ak-badge ak-badge-locked">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                        Заблокирован
                                    </span>
                                <?php elseif ($is_completed): ?>
                                    <span class="ak-badge ak-badge-completed">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>
                                        Завершен
                                    </span>
                                <?php else: ?>
                                    <span class="ak-badge ak-badge-available">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>
                                        Доступен
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td data-colname="Результат теста">
                                <?php if ($quiz_score !== null): ?>
                                    <strong><?php echo $quiz_score; ?>%</strong>
                                <?php else: ?>
                                    <span style="color: #999;">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input id="cb-select-all-2" type="checkbox">
                    </td>
                    <th scope="col" class="manage-column column-primary">Название</th>
                    <th scope="col" class="manage-column">Тип</th>
                    <th scope="col" class="manage-column">Курс</th>
                    <th scope="col" class="manage-column">Статус</th>
                    <th scope="col" class="manage-column">Результат теста</th>
                </tr>
            </tfoot>
        </table>
        
        <div class="tablenav bottom">
            <div class="alignleft actions bulkactions">
                <label for="bulk-action-selector-bottom" class="screen-reader-text">Выберите массовое действие</label>
                <select name="action" id="bulk-action-selector-bottom">
                    <option value="-1">Действия</option>
                    <option value="lock">Заблокировать</option>
                    <option value="unlock">Разблокировать</option>
                    <option value="reset_progress">Сбросить прогресс</option>
                </select>
                <input type="submit" class="button action" value="Применить">
            </div>
        </div>
    </form>
    
    <style>
        .ak-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 600;
        }
        .ak-badge svg {
            flex-shrink: 0;
        }
        .ak-badge-locked {
            background: #d63638;
            color: #fff;
        }
        .ak-badge-completed {
            background: #00a32a;
            color: #fff;
        }
        .ak-badge-available {
            background: #dba617;
            color: #fff;
        }
        .ak-type-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
        }
        .ak-type-badge.module {
            background: #f0f6fc;
            color: #0969da;
            border: 1px solid #0969da33;
        }
        .ak-type-badge.lesson {
            background: #fff8dc;
            color: #946c00;
            border: 1px solid #946c0033;
        }
        .ak-type-badge svg {
            flex-shrink: 0;
        }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        // Select all checkboxes
        $('#cb-select-all-1, #cb-select-all-2').on('change', function() {
            var checked = $(this).prop('checked');
            $('input[name="items[]"]').prop('checked', checked);
        });
    });
    </script>
    <?php
}

/**
 * Get all learning items (modules and lessons)
 */
function ak_get_all_learning_items() {
    $items = array();
    
    // Get all published courses
    $courses = get_posts(array(
        'post_type' => 'ak_course',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
        'post_status' => 'publish'
    ));
    
    foreach ($courses as $course) {
        // Get published modules
        $modules = get_posts(array(
            'post_type' => 'ak_module',
            'meta_key' => '_ak_course_id',
            'meta_value' => $course->ID,
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'post_status' => 'publish'
        ));
        
        foreach ($modules as $module) {
            // Add module
            $items[] = array(
                'id' => $module->ID,
                'title' => $module->post_title,
                'type' => 'module',
                'course_id' => $course->ID,
                'course_title' => $course->post_title,
            );
            
            // Get published lessons in module
            $lessons = get_posts(array(
                'post_type' => 'ak_lesson',
                'meta_key' => '_ak_module_id',
                'meta_value' => $module->ID,
                'posts_per_page' => -1,
                'orderby' => 'menu_order',
                'order' => 'ASC',
                'post_status' => 'publish'
            ));
            
            foreach ($lessons as $lesson) {
                $items[] = array(
                    'id' => $lesson->ID,
                    'title' => '— ' . $lesson->post_title,
                    'type' => 'lesson',
                    'course_id' => $course->ID,
                    'course_title' => $course->post_title,
                    'module_id' => $module->ID,
                );
            }
        }
    }
    
    return $items;
}

/**
 * Lock item (lesson or module) for user
 * Supports both single item and array of items
 */
function ak_lock_item_for_user($user_id, $item_ids) {
    if (!is_array($item_ids)) {
        $item_ids = array($item_ids);
    }
    
    $locked_items = get_user_meta($user_id, 'ak_locked_items', true);
    if (!is_array($locked_items)) {
        $locked_items = array();
    }
    
    foreach ($item_ids as $item_id) {
        if (!in_array($item_id, $locked_items)) {
            $locked_items[] = (int)$item_id;
        }
    }
    
    update_user_meta($user_id, 'ak_locked_items', array_unique($locked_items));
}

/**
 * Unlock item (lesson or module) for user
 * Supports both single item and array of items
 */
function ak_unlock_item_for_user($user_id, $item_ids) {
    if (!is_array($item_ids)) {
        $item_ids = array($item_ids);
    }
    
    $locked_items = get_user_meta($user_id, 'ak_locked_items', true);
    if (!is_array($locked_items)) {
        return;
    }
    
    $locked_items = array_diff($locked_items, $item_ids);
    update_user_meta($user_id, 'ak_locked_items', array_values($locked_items));
}

/**
 * Reset progress for specific items
 * Supports both single item and array of items
 */
function ak_reset_user_item_progress($user_id, $item_ids) {
    if (!is_array($item_ids)) {
        $item_ids = array($item_ids);
    }
    
    $progress = get_user_meta($user_id, 'ak_user_progress', true);
    $quiz_results = get_user_meta($user_id, 'ak_quiz_results', true);
    
    if (!is_array($progress)) $progress = array();
    if (!is_array($quiz_results)) $quiz_results = array();
    
    foreach ($item_ids as $item_id) {
        $post = get_post($item_id);
        if (!$post) continue;
        
        if ($post->post_type === 'ak_lesson') {
            // Get course ID through module
            $module_id = get_post_meta($item_id, '_ak_module_id', true);
            if ($module_id) {
                $course_id = get_post_meta($module_id, '_ak_course_id', true);
                
                if ($course_id && isset($progress[$course_id]['completed_lessons'])) {
                    // Remove from completed lessons
                    $key = array_search($item_id, $progress[$course_id]['completed_lessons']);
                    if ($key !== false) {
                        unset($progress[$course_id]['completed_lessons'][$key]);
                        $progress[$course_id]['completed_lessons'] = array_values($progress[$course_id]['completed_lessons']);
                    }
                }
            }
            
            // Remove quiz results
            if (isset($quiz_results[$item_id])) {
                unset($quiz_results[$item_id]);
            }
        } elseif ($post->post_type === 'ak_module') {
            // Reset all lessons in this module
            $course_id = get_post_meta($item_id, '_ak_course_id', true);
            
            // Get all published lessons in this module
            $lessons = get_posts(array(
                'post_type' => 'ak_lesson',
                'meta_key' => '_ak_module_id',
                'meta_value' => $item_id,
                'posts_per_page' => -1,
                'post_status' => 'publish'
            ));
            
            foreach ($lessons as $lesson) {
                if ($course_id && isset($progress[$course_id]['completed_lessons'])) {
                    // Remove from completed lessons
                    $key = array_search($lesson->ID, $progress[$course_id]['completed_lessons']);
                    if ($key !== false) {
                        unset($progress[$course_id]['completed_lessons'][$key]);
                    }
                }
                
                // Remove quiz results
                if (isset($quiz_results[$lesson->ID])) {
                    unset($quiz_results[$lesson->ID]);
                }
            }
            
            // Reindex completed lessons array
            if ($course_id && isset($progress[$course_id]['completed_lessons'])) {
                $progress[$course_id]['completed_lessons'] = array_values($progress[$course_id]['completed_lessons']);
            }
        }
    }
    
    update_user_meta($user_id, 'ak_user_progress', $progress);
    update_user_meta($user_id, 'ak_quiz_results', $quiz_results);
}

/**
 * Legacy functions for backward compatibility
 */
function ak_lock_lesson_for_user($user_id, $lesson_id) {
    ak_lock_item_for_user($user_id, $lesson_id);
}

function ak_unlock_lesson_for_user($user_id, $lesson_id) {
    ak_unlock_item_for_user($user_id, $lesson_id);
}

/**
 * Check if item (lesson or module) is locked for user
 */
function ak_is_lesson_locked($user_id, $lesson_id) {
    $locked_items = get_user_meta($user_id, 'ak_locked_items', true);
    if (!is_array($locked_items)) {
        return false;
    }
    
    return in_array($lesson_id, $locked_items);
}

/**
 * Reset user progress for entire course
 */
function ak_reset_user_course_progress($user_id, $course_id) {
    // Reset course progress
    $progress = get_user_meta($user_id, 'ak_user_progress', true);
    if (is_array($progress) && isset($progress[$course_id])) {
        unset($progress[$course_id]);
        update_user_meta($user_id, 'ak_user_progress', $progress);
    }
    
    // Reset quiz results for this course
    $modules = get_posts(array(
        'post_type' => 'ak_module',
        'meta_key' => '_ak_course_id',
        'meta_value' => $course_id,
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ));
    
    $quiz_results = get_user_meta($user_id, 'ak_quiz_results', true);
    if (is_array($quiz_results)) {
        foreach ($modules as $module) {
            $lessons = get_posts(array(
                'post_type' => 'ak_lesson',
                'meta_key' => '_ak_module_id',
                'meta_value' => $module->ID,
                'posts_per_page' => -1,
                'post_status' => 'publish'
            ));
            
            foreach ($lessons as $lesson) {
                if (isset($quiz_results[$lesson->ID])) {
                    unset($quiz_results[$lesson->ID]);
                }
            }
        }
        update_user_meta($user_id, 'ak_quiz_results', $quiz_results);
    }
}

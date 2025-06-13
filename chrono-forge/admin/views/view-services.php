<?php
/**
 * Шаблон управления услугами
 * 
 * @var array $services
 * @var array $categories
 */

// Если файл вызван напрямую, прекратить выполнение
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="chrono-forge-admin">
    <div class="cf-page-title">
        <h1><?php _e('Услуги', 'chrono-forge'); ?></h1>
        <div>
            <a href="#" class="cf-btn" data-modal="cf-new-category-modal">
                <?php _e('Новая категория', 'chrono-forge'); ?>
            </a>
            <a href="#" class="cf-btn cf-btn-primary" data-modal="cf-new-service-modal">
                <?php _e('Новая услуга', 'chrono-forge'); ?>
            </a>
        </div>
    </div>

    <!-- Фильтры -->
    <div class="cf-filters">
        <form method="get">
            <input type="hidden" name="page" value="chrono-forge-services">
            <div class="cf-filters-row">
                <div class="cf-form-group">
                    <label for="filter_category"><?php _e('Категория', 'chrono-forge'); ?></label>
                    <select id="filter_category" name="category" class="cf-filter">
                        <option value=""><?php _e('Все категории', 'chrono-forge'); ?></option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?php echo esc_attr($category->id); ?>" 
                                <?php selected($_GET['category'] ?? '', $category->id); ?>>
                            <?php echo esc_html($category->name); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="cf-form-group">
                    <label for="filter_status"><?php _e('Статус', 'chrono-forge'); ?></label>
                    <select id="filter_status" name="status" class="cf-filter">
                        <option value=""><?php _e('Все статусы', 'chrono-forge'); ?></option>
                        <option value="active" <?php selected($_GET['status'] ?? '', 'active'); ?>><?php _e('Активные', 'chrono-forge'); ?></option>
                        <option value="inactive" <?php selected($_GET['status'] ?? '', 'inactive'); ?>><?php _e('Неактивные', 'chrono-forge'); ?></option>
                    </select>
                </div>
                <div class="cf-form-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="cf-btn"><?php _e('Применить', 'chrono-forge'); ?></button>
                </div>
            </div>
        </form>
    </div>

    <!-- Таблица услуг -->
    <div class="cf-table-container">
        <?php if (!empty($services)): ?>
        <table class="cf-table">
            <thead>
                <tr>
                    <th>
                        <input type="checkbox" class="cf-select-all">
                    </th>
                    <th><?php _e('Название', 'chrono-forge'); ?></th>
                    <th><?php _e('Категория', 'chrono-forge'); ?></th>
                    <th><?php _e('Продолжительность', 'chrono-forge'); ?></th>
                    <th><?php _e('Цена', 'chrono-forge'); ?></th>
                    <th><?php _e('Статус', 'chrono-forge'); ?></th>
                    <th><?php _e('Действия', 'chrono-forge'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($services as $service): ?>
                <tr>
                    <td>
                        <input type="checkbox" class="cf-item-checkbox" value="<?php echo esc_attr($service->id); ?>">
                    </td>
                    <td>
                        <div style="display: flex; align-items: center;">
                            <span class="cf-color-indicator" style="background-color: <?php echo esc_attr($service->color); ?>;"></span>
                            <div>
                                <strong><?php echo esc_html($service->name); ?></strong>
                                <?php if (!empty($service->description)): ?>
                                <br><small><?php echo esc_html(wp_trim_words($service->description, 10)); ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td>
                        <?php if (!empty($service->category_name)): ?>
                        <span class="cf-color-indicator" style="background-color: <?php echo esc_attr($service->category_color); ?>;"></span>
                        <?php echo esc_html($service->category_name); ?>
                        <?php else: ?>
                        <span style="color: #999;"><?php _e('Без категории', 'chrono-forge'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html($service->duration); ?> <?php _e('мин.', 'chrono-forge'); ?></td>
                    <td>
                        <?php if ($service->price > 0): ?>
                            <?php echo chrono_forge_format_price($service->price); ?>
                        <?php else: ?>
                            <span style="color: #999;"><?php _e('Бесплатно', 'chrono-forge'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="cf-status <?php echo esc_attr($service->status); ?>">
                            <?php echo $service->status === 'active' ? __('Активна', 'chrono-forge') : __('Неактивна', 'chrono-forge'); ?>
                        </span>
                    </td>
                    <td class="cf-actions">
                        <a href="#" class="cf-btn" data-modal="cf-edit-service-modal" 
                           data-id="<?php echo esc_attr($service->id); ?>" data-type="service">
                            <?php _e('Редактировать', 'chrono-forge'); ?>
                        </a>
                        <a href="<?php echo wp_nonce_url(add_query_arg(['action' => 'delete_service', 'id' => $service->id]), 'delete_service'); ?>" 
                           class="cf-btn cf-btn-danger cf-delete-item" 
                           data-name="<?php echo esc_attr($service->name); ?>">
                            <?php _e('Удалить', 'chrono-forge'); ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Массовые действия -->
        <div style="padding: 15px; border-top: 1px solid #eee; background: #f8f9fa;">
            <strong><?php _e('Массовые действия:', 'chrono-forge'); ?></strong>
            <button type="button" class="cf-btn cf-bulk-action" data-action="activate">
                <?php _e('Активировать', 'chrono-forge'); ?>
            </button>
            <button type="button" class="cf-btn cf-bulk-action" data-action="deactivate">
                <?php _e('Деактивировать', 'chrono-forge'); ?>
            </button>
            <button type="button" class="cf-btn cf-btn-danger cf-bulk-action" data-action="delete">
                <?php _e('Удалить', 'chrono-forge'); ?>
            </button>
        </div>
        <?php else: ?>
        <div style="padding: 40px; text-align: center; color: #666;">
            <p><?php _e('Услуги не найдены.', 'chrono-forge'); ?></p>
            <a href="#" class="cf-btn cf-btn-primary" data-modal="cf-new-service-modal">
                <?php _e('Создать первую услугу', 'chrono-forge'); ?>
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Модальное окно новой/редактирования услуги -->
<div id="cf-new-service-modal" class="cf-modal" style="display: none;">
    <div class="cf-modal-content">
        <div class="cf-modal-header">
            <h3 class="cf-modal-title"><?php _e('Новая услуга', 'chrono-forge'); ?></h3>
            <button type="button" class="cf-modal-close">&times;</button>
        </div>
        
        <form class="cf-admin-form" method="post" action="">
            <?php wp_nonce_field('chrono_forge_admin_action'); ?>
            <input type="hidden" name="action" value="save_service">
            
            <div class="cf-form-row">
                <div class="cf-form-group">
                    <label for="service_name"><?php _e('Название', 'chrono-forge'); ?> *</label>
                    <input type="text" id="service_name" name="name" required>
                </div>
                <div class="cf-form-group">
                    <label for="service_category"><?php _e('Категория', 'chrono-forge'); ?></label>
                    <select id="service_category" name="category_id">
                        <option value=""><?php _e('Без категории', 'chrono-forge'); ?></option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?php echo esc_attr($category->id); ?>">
                            <?php echo esc_html($category->name); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="cf-form-group">
                <label for="service_description"><?php _e('Описание', 'chrono-forge'); ?></label>
                <textarea id="service_description" name="description" rows="3"></textarea>
            </div>
            
            <div class="cf-form-row">
                <div class="cf-form-group">
                    <label for="service_duration"><?php _e('Продолжительность (мин.)', 'chrono-forge'); ?> *</label>
                    <input type="number" id="service_duration" name="duration" min="1" value="60" required>
                </div>
                <div class="cf-form-group">
                    <label for="service_price"><?php _e('Цена', 'chrono-forge'); ?></label>
                    <input type="number" id="service_price" name="price" min="0" step="0.01" value="0">
                </div>
            </div>
            
            <div class="cf-form-row">
                <div class="cf-form-group">
                    <label for="service_buffer_time"><?php _e('Буферное время (мин.)', 'chrono-forge'); ?></label>
                    <input type="number" id="service_buffer_time" name="buffer_time" min="0" value="0">
                    <small><?php _e('Время между записями для подготовки', 'chrono-forge'); ?></small>
                </div>
                <div class="cf-form-group">
                    <label for="service_color"><?php _e('Цвет', 'chrono-forge'); ?></label>
                    <input type="color" id="service_color" name="color" value="#3498db" class="cf-color-picker">
                </div>
            </div>
            
            <div class="cf-form-group">
                <label for="service_status"><?php _e('Статус', 'chrono-forge'); ?></label>
                <select id="service_status" name="status">
                    <option value="active"><?php _e('Активна', 'chrono-forge'); ?></option>
                    <option value="inactive"><?php _e('Неактивна', 'chrono-forge'); ?></option>
                </select>
            </div>
            
            <div class="cf-modal-footer">
                <button type="button" class="cf-btn cf-btn-secondary cf-modal-close">
                    <?php _e('Отмена', 'chrono-forge'); ?>
                </button>
                <button type="submit" class="cf-btn cf-btn-primary">
                    <?php _e('Сохранить', 'chrono-forge'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Модальное окно новой/редактирования категории -->
<div id="cf-new-category-modal" class="cf-modal" style="display: none;">
    <div class="cf-modal-content">
        <div class="cf-modal-header">
            <h3 class="cf-modal-title"><?php _e('Новая категория', 'chrono-forge'); ?></h3>
            <button type="button" class="cf-modal-close">&times;</button>
        </div>
        
        <form class="cf-admin-form" method="post" action="">
            <?php wp_nonce_field('chrono_forge_admin_action'); ?>
            <input type="hidden" name="action" value="save_category">
            
            <div class="cf-form-group">
                <label for="category_name"><?php _e('Название', 'chrono-forge'); ?> *</label>
                <input type="text" id="category_name" name="name" required>
            </div>
            
            <div class="cf-form-group">
                <label for="category_description"><?php _e('Описание', 'chrono-forge'); ?></label>
                <textarea id="category_description" name="description" rows="3"></textarea>
            </div>
            
            <div class="cf-form-row">
                <div class="cf-form-group">
                    <label for="category_color"><?php _e('Цвет', 'chrono-forge'); ?></label>
                    <input type="color" id="category_color" name="color" value="#34495e" class="cf-color-picker">
                </div>
                <div class="cf-form-group">
                    <label for="category_sort_order"><?php _e('Порядок сортировки', 'chrono-forge'); ?></label>
                    <input type="number" id="category_sort_order" name="sort_order" min="0" value="0">
                </div>
            </div>
            
            <div class="cf-modal-footer">
                <button type="button" class="cf-btn cf-btn-secondary cf-modal-close">
                    <?php _e('Отмена', 'chrono-forge'); ?>
                </button>
                <button type="submit" class="cf-btn cf-btn-primary">
                    <?php _e('Сохранить', 'chrono-forge'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

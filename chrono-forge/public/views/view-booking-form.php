<?php
/**
 * Шаблон формы бронирования
 * 
 * @var array $categories
 * @var array $services
 * @var array $employees
 * @var array $atts
 */

// Если файл вызван напрямую, прекратить выполнение
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="chrono-forge-booking-form">
    <h2 class="cf-form-title"><?php _e('Записаться на услугу', 'chrono-forge'); ?></h2>
    
    <!-- Индикатор шагов -->
    <ul class="cf-steps-indicator">
        <?php if (!empty($categories) && $atts['show_categories'] === 'true'): ?>
        <li class="cf-step-indicator active"><?php _e('Категория', 'chrono-forge'); ?></li>
        <?php endif; ?>
        <li class="cf-step-indicator <?php echo empty($categories) || $atts['show_categories'] !== 'true' ? 'active' : ''; ?>">
            <?php _e('Услуга', 'chrono-forge'); ?>
        </li>
        <li class="cf-step-indicator"><?php _e('Специалист', 'chrono-forge'); ?></li>
        <li class="cf-step-indicator"><?php _e('Дата и время', 'chrono-forge'); ?></li>
        <li class="cf-step-indicator"><?php _e('Ваши данные', 'chrono-forge'); ?></li>
    </ul>

    <!-- Сообщения -->
    <div class="cf-form-messages"></div>

    <!-- Шаг 1: Выбор категории (если показывается) -->
    <?php if (!empty($categories) && $atts['show_categories'] === 'true'): ?>
    <div class="cf-step active" data-step="1">
        <h3><?php _e('Выберите категорию услуг', 'chrono-forge'); ?></h3>
        <div class="cf-categories-grid">
            <?php foreach ($categories as $category): ?>
            <div class="cf-category-item" data-category-id="<?php echo esc_attr($category->id); ?>" 
                 style="border-color: <?php echo esc_attr($category->color); ?>;">
                <h4><?php echo esc_html($category->name); ?></h4>
                <?php if (!empty($category->description)): ?>
                <p><?php echo esc_html($category->description); ?></p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="cf-form-navigation">
            <div></div>
            <button type="button" class="cf-btn cf-btn-primary cf-btn-next" disabled>
                <?php _e('Далее', 'chrono-forge'); ?>
            </button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Шаг 2: Выбор услуги -->
    <div class="cf-step <?php echo empty($categories) || $atts['show_categories'] !== 'true' ? 'active' : ''; ?>" 
         data-step="<?php echo !empty($categories) && $atts['show_categories'] === 'true' ? '2' : '1'; ?>">
        <h3><?php _e('Выберите услугу', 'chrono-forge'); ?></h3>
        
        <div class="cf-services-container">
            <?php if (!empty($services)): ?>
            <div class="cf-services-list">
                <?php foreach ($services as $service): ?>
                <div class="cf-service-item" data-service-id="<?php echo esc_attr($service->id); ?>" 
                     data-duration="<?php echo esc_attr($service->duration); ?>" 
                     data-price="<?php echo esc_attr($service->price); ?>">
                    <div class="cf-service-info">
                        <h4><?php echo esc_html($service->name); ?></h4>
                        <?php if (!empty($service->description)): ?>
                        <p><?php echo esc_html($service->description); ?></p>
                        <?php endif; ?>
                        <div class="cf-service-meta">
                            <span class="cf-service-duration"><?php echo esc_html($service->duration); ?> <?php _e('мин.', 'chrono-forge'); ?></span>
                            <?php if ($service->price > 0): ?>
                            <span class="cf-service-price"><?php echo chrono_forge_format_price($service->price); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p><?php _e('Услуги не найдены.', 'chrono-forge'); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="cf-form-navigation">
            <?php if (!empty($categories) && $atts['show_categories'] === 'true'): ?>
            <button type="button" class="cf-btn cf-btn-secondary cf-btn-prev">
                <?php _e('Назад', 'chrono-forge'); ?>
            </button>
            <?php else: ?>
            <div></div>
            <?php endif; ?>
            <button type="button" class="cf-btn cf-btn-primary cf-btn-next" disabled>
                <?php _e('Далее', 'chrono-forge'); ?>
            </button>
        </div>
    </div>

    <!-- Шаг 3: Выбор сотрудника -->
    <div class="cf-step" data-step="<?php echo !empty($categories) && $atts['show_categories'] === 'true' ? '3' : '2'; ?>">
        <h3><?php _e('Выберите специалиста', 'chrono-forge'); ?></h3>
        
        <div class="cf-employees-container">
            <!-- Опция "Любой доступный" -->
            <div class="cf-employee-item cf-any-employee" data-employee-id="any">
                <div class="cf-employee-photo">
                    <div class="cf-employee-avatar cf-any-avatar">
                        <i class="dashicons dashicons-groups"></i>
                    </div>
                </div>
                <div class="cf-employee-info">
                    <h4><?php _e('Любой доступный специалист', 'chrono-forge'); ?></h4>
                    <p><?php _e('Система автоматически подберет свободного специалиста', 'chrono-forge'); ?></p>
                </div>
            </div>

            <?php if (!empty($employees)): ?>
            <div class="cf-employees-grid">
                <?php foreach ($employees as $employee): ?>
                <div class="cf-employee-item" data-employee-id="<?php echo esc_attr($employee->id); ?>">
                    <div class="cf-employee-photo">
                        <?php if (!empty($employee->photo)): ?>
                        <img src="<?php echo esc_url($employee->photo); ?>" alt="<?php echo esc_attr($employee->name); ?>">
                        <?php else: ?>
                        <div class="cf-employee-avatar" style="background-color: <?php echo esc_attr($employee->color); ?>;">
                            <?php echo esc_html(mb_substr($employee->name, 0, 1)); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="cf-employee-info">
                        <h4><?php echo esc_html($employee->name); ?></h4>
                        <?php if (!empty($employee->description)): ?>
                        <p><?php echo esc_html($employee->description); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p><?php _e('Специалисты не найдены.', 'chrono-forge'); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="cf-form-navigation">
            <button type="button" class="cf-btn cf-btn-secondary cf-btn-prev">
                <?php _e('Назад', 'chrono-forge'); ?>
            </button>
            <button type="button" class="cf-btn cf-btn-primary cf-btn-next" disabled>
                <?php _e('Далее', 'chrono-forge'); ?>
            </button>
        </div>
    </div>

    <!-- Шаг 4: Выбор даты и времени -->
    <div class="cf-step" data-step="<?php echo !empty($categories) && $atts['show_categories'] === 'true' ? '4' : '3'; ?>">
        <h3><?php _e('Выберите дату и время', 'chrono-forge'); ?></h3>
        
        <div class="cf-datetime-selection">
            <div class="cf-date-picker">
                <h4><?php _e('Выберите дату', 'chrono-forge'); ?></h4>
                <input type="date" class="cf-date-input" 
                       min="<?php echo chrono_forge_get_min_booking_date(); ?>" 
                       max="<?php echo chrono_forge_get_max_booking_date(); ?>">
            </div>
            
            <div class="cf-time-slots">
                <h4><?php _e('Доступное время', 'chrono-forge'); ?></h4>
                <div class="cf-time-slots-container">
                    <p><?php _e('Сначала выберите дату', 'chrono-forge'); ?></p>
                </div>
            </div>
        </div>
        
        <div class="cf-form-navigation">
            <button type="button" class="cf-btn cf-btn-secondary cf-btn-prev">
                <?php _e('Назад', 'chrono-forge'); ?>
            </button>
            <button type="button" class="cf-btn cf-btn-primary cf-btn-next" disabled>
                <?php _e('Далее', 'chrono-forge'); ?>
            </button>
        </div>
    </div>

    <!-- Шаг 5: Данные клиента -->
    <div class="cf-step" data-step="<?php echo !empty($categories) && $atts['show_categories'] === 'true' ? '5' : '4'; ?>">
        <h3><?php _e('Ваши контактные данные', 'chrono-forge'); ?></h3>
        
        <form class="cf-booking-form cf-customer-form">
            <div class="cf-form-row">
                <div class="cf-form-group">
                    <label for="cf-first-name"><?php _e('Имя', 'chrono-forge'); ?> *</label>
                    <input type="text" id="cf-first-name" name="first_name" required>
                </div>
                <div class="cf-form-group">
                    <label for="cf-last-name"><?php _e('Фамилия', 'chrono-forge'); ?> *</label>
                    <input type="text" id="cf-last-name" name="last_name" required>
                </div>
            </div>
            
            <div class="cf-form-row">
                <div class="cf-form-group">
                    <label for="cf-email"><?php _e('Email', 'chrono-forge'); ?> *</label>
                    <input type="email" id="cf-email" name="email" required>
                </div>
                <div class="cf-form-group">
                    <label for="cf-phone"><?php _e('Телефон', 'chrono-forge'); ?></label>
                    <input type="tel" id="cf-phone" name="phone">
                </div>
            </div>
            
            <div class="cf-form-group">
                <label for="cf-notes"><?php _e('Комментарий', 'chrono-forge'); ?></label>
                <textarea id="cf-notes" name="notes" rows="4" 
                          placeholder="<?php _e('Дополнительная информация или пожелания', 'chrono-forge'); ?>"></textarea>
            </div>
            
            <div class="cf-form-navigation">
                <button type="button" class="cf-btn cf-btn-secondary cf-btn-prev">
                    <?php _e('Назад', 'chrono-forge'); ?>
                </button>
                <button type="submit" class="cf-btn cf-btn-primary">
                    <?php _e('Записаться', 'chrono-forge'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Обновляем состояние кнопок при выборе элементов
jQuery(document).ready(function($) {
    // Категории
    $(document).on('click', '.cf-category-item', function() {
        $('.cf-step[data-step="1"] .cf-btn-next').prop('disabled', false);
    });
    
    // Услуги
    $(document).on('click', '.cf-service-item', function() {
        const step = $('.cf-step.active').data('step');
        $('.cf-step[data-step="' + step + '"] .cf-btn-next').prop('disabled', false);
    });
    
    // Сотрудники
    $(document).on('click', '.cf-employee-item', function() {
        const step = $('.cf-step.active').data('step');
        $('.cf-step[data-step="' + step + '"] .cf-btn-next').prop('disabled', false);
    });
    
    // Время
    $(document).on('click', '.cf-time-slot', function() {
        const step = $('.cf-step.active').data('step');
        $('.cf-step[data-step="' + step + '"] .cf-btn-next').prop('disabled', false);
    });
});
</script>

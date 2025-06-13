<?php
/**
 * Шаблон формы поиска
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

<div class="chrono-forge-search-form">
    <h3><?php _e('Найти и записаться', 'chrono-forge'); ?></h3>
    
    <form class="cf-search-form" id="cf-search-form">
        <?php if ($atts['show_filters'] === 'true'): ?>
        <div class="cf-search-filters">
            <div class="cf-filter-row">
                <div class="cf-filter-group">
                    <label for="search_category"><?php _e('Категория', 'chrono-forge'); ?></label>
                    <select id="search_category" name="category">
                        <option value=""><?php _e('Любая категория', 'chrono-forge'); ?></option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?php echo esc_attr($category->id); ?>">
                            <?php echo esc_html($category->name); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="cf-filter-group">
                    <label for="search_service"><?php _e('Услуга', 'chrono-forge'); ?></label>
                    <select id="search_service" name="service">
                        <option value=""><?php _e('Любая услуга', 'chrono-forge'); ?></option>
                        <?php foreach ($services as $service): ?>
                        <option value="<?php echo esc_attr($service->id); ?>">
                            <?php echo esc_html($service->name); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="cf-filter-group">
                    <label for="search_employee"><?php _e('Специалист', 'chrono-forge'); ?></label>
                    <select id="search_employee" name="employee">
                        <option value=""><?php _e('Любой специалист', 'chrono-forge'); ?></option>
                        <?php if ($atts['show_any_employee'] === 'true'): ?>
                        <option value="any"><?php _e('Любой доступный', 'chrono-forge'); ?></option>
                        <?php endif; ?>
                        <?php foreach ($employees as $employee): ?>
                        <option value="<?php echo esc_attr($employee->id); ?>">
                            <?php echo esc_html($employee->name); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($atts['show_date_range'] === 'true'): ?>
        <div class="cf-date-range">
            <h4><?php _e('Предпочитаемые даты', 'chrono-forge'); ?></h4>
            <div class="cf-date-row">
                <div class="cf-date-group">
                    <label for="search_date_from"><?php _e('С', 'chrono-forge'); ?></label>
                    <input type="date" id="search_date_from" name="date_from" 
                           min="<?php echo chrono_forge_get_min_booking_date(); ?>"
                           max="<?php echo chrono_forge_get_max_booking_date(); ?>">
                </div>
                
                <div class="cf-date-group">
                    <label for="search_date_to"><?php _e('По', 'chrono-forge'); ?></label>
                    <input type="date" id="search_date_to" name="date_to"
                           min="<?php echo chrono_forge_get_min_booking_date(); ?>"
                           max="<?php echo chrono_forge_get_max_booking_date(); ?>">
                </div>
                
                <div class="cf-time-group">
                    <label for="search_time_preference"><?php _e('Время', 'chrono-forge'); ?></label>
                    <select id="search_time_preference" name="time_preference">
                        <option value=""><?php _e('Любое время', 'chrono-forge'); ?></option>
                        <option value="morning"><?php _e('Утром (9:00-12:00)', 'chrono-forge'); ?></option>
                        <option value="afternoon"><?php _e('Днем (12:00-17:00)', 'chrono-forge'); ?></option>
                        <option value="evening"><?php _e('Вечером (17:00-21:00)', 'chrono-forge'); ?></option>
                    </select>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="cf-search-actions">
            <button type="submit" class="cf-btn cf-btn-primary cf-btn-large">
                <?php _e('Найти доступное время', 'chrono-forge'); ?>
            </button>
        </div>
    </form>
    
    <div class="cf-search-results" id="cf-search-results" style="display: none;">
        <h4><?php _e('Доступные варианты', 'chrono-forge'); ?></h4>
        <div class="cf-results-container"></div>
    </div>
</div>

<style>
.chrono-forge-search-form {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    margin: 20px 0;
}

.chrono-forge-search-form h3 {
    margin: 0 0 25px 0;
    text-align: center;
    color: #2c3e50;
    font-size: 24px;
    font-weight: 600;
}

.cf-search-filters {
    margin-bottom: 25px;
}

.cf-filter-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.cf-filter-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.cf-filter-group label {
    font-weight: 600;
    color: #555;
    font-size: 14px;
}

.cf-filter-group select,
.cf-filter-group input {
    padding: 12px;
    border: 2px solid #e1e8ed;
    border-radius: 8px;
    font-size: 16px;
    transition: border-color 0.3s ease;
}

.cf-filter-group select:focus,
.cf-filter-group input:focus {
    outline: none;
    border-color: #3498db;
}

.cf-date-range {
    margin-bottom: 25px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.cf-date-range h4 {
    margin: 0 0 15px 0;
    color: #2c3e50;
    font-size: 16px;
    font-weight: 600;
}

.cf-date-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
}

.cf-date-group,
.cf-time-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.cf-search-actions {
    text-align: center;
}

.cf-btn-large {
    padding: 15px 30px;
    font-size: 18px;
    font-weight: 600;
}

.cf-search-results {
    margin-top: 30px;
    padding-top: 30px;
    border-top: 2px solid #eee;
}

.cf-search-results h4 {
    margin: 0 0 20px 0;
    color: #2c3e50;
    font-size: 18px;
    font-weight: 600;
}

.cf-results-container {
    display: grid;
    gap: 15px;
}

.cf-result-item {
    padding: 20px;
    border: 1px solid #eee;
    border-radius: 8px;
    background: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.cf-result-info h5 {
    margin: 0 0 5px 0;
    color: #2c3e50;
    font-size: 16px;
    font-weight: 600;
}

.cf-result-details {
    color: #666;
    font-size: 14px;
}

.cf-result-actions {
    flex-shrink: 0;
}

@media (max-width: 768px) {
    .cf-filter-row,
    .cf-date-row {
        grid-template-columns: 1fr;
    }
    
    .cf-result-item {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    $('#cf-search-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'chrono_forge_search_availability');
        formData.append('nonce', chronoForgeAjax.nonce);
        
        const $results = $('#cf-search-results');
        const $container = $('.cf-results-container');
        
        $container.html('<div class="cf-loading"><div class="cf-loading-spinner"></div><p>Поиск доступных вариантов...</p></div>');
        $results.show();
        
        $.ajax({
            url: chronoForgeAjax.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    let html = '';
                    response.data.forEach(function(result) {
                        html += '<div class="cf-result-item">';
                        html += '<div class="cf-result-info">';
                        html += '<h5>' + result.service_name + '</h5>';
                        html += '<div class="cf-result-details">';
                        html += result.employee_name + ' • ' + result.date + ' • ' + result.time;
                        if (result.price) {
                            html += ' • ' + result.price;
                        }
                        html += '</div>';
                        html += '</div>';
                        html += '<div class="cf-result-actions">';
                        html += '<button class="cf-btn cf-btn-primary cf-book-slot" ';
                        html += 'data-service="' + result.service_id + '" ';
                        html += 'data-employee="' + result.employee_id + '" ';
                        html += 'data-date="' + result.date + '" ';
                        html += 'data-time="' + result.time + '">Записаться</button>';
                        html += '</div>';
                        html += '</div>';
                    });
                    $container.html(html);
                } else {
                    $container.html('<div class="cf-empty-state"><p>К сожалению, на выбранные даты нет доступных вариантов. Попробуйте изменить критерии поиска.</p></div>');
                }
            },
            error: function() {
                $container.html('<div class="cf-message cf-message-error">Произошла ошибка при поиске. Попробуйте еще раз.</div>');
            }
        });
    });
    
    // Handle booking from search results
    $(document).on('click', '.cf-book-slot', function() {
        const $btn = $(this);
        const serviceId = $btn.data('service');
        const employeeId = $btn.data('employee');
        const date = $btn.data('date');
        const time = $btn.data('time');
        
        // Redirect to booking page with pre-filled data
        const bookingUrl = new URL(window.location.origin + '/booking/');
        bookingUrl.searchParams.set('service', serviceId);
        bookingUrl.searchParams.set('employee', employeeId);
        bookingUrl.searchParams.set('date', date);
        bookingUrl.searchParams.set('time', time);
        
        window.location.href = bookingUrl.toString();
    });
    
    // Update services when category changes
    $('#search_category').on('change', function() {
        const categoryId = $(this).val();
        
        if (categoryId) {
            $.ajax({
                url: chronoForgeAjax.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'chrono_forge_get_services',
                    category_id: categoryId,
                    nonce: chronoForgeAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const $serviceSelect = $('#search_service');
                        $serviceSelect.find('option:not(:first)').remove();
                        
                        if (response.data.services) {
                            response.data.services.forEach(function(service) {
                                $serviceSelect.append('<option value="' + service.id + '">' + service.name + '</option>');
                            });
                        }
                    }
                }
            });
        }
    });
});
</script>

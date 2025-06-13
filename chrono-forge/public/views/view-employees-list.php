<?php
/**
 * Шаблон списка сотрудников
 * 
 * @var array $employees
 * @var array $atts
 */

// Если файл вызван напрямую, прекратить выполнение
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="chrono-forge-employees-list">
    <?php if (!empty($employees)): ?>
    <div class="cf-employees-grid" style="grid-template-columns: repeat(<?php echo esc_attr($atts['columns']); ?>, 1fr);">
        <?php foreach ($employees as $employee): ?>
        <div class="cf-employee-card" data-employee-id="<?php echo esc_attr($employee->id); ?>">
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
                <h3 class="cf-employee-name"><?php echo esc_html($employee->name); ?></h3>
                
                <?php if ($atts['show_description'] === 'true' && !empty($employee->description)): ?>
                <div class="cf-employee-description">
                    <p><?php echo esc_html($employee->description); ?></p>
                </div>
                <?php endif; ?>
                
                <?php if ($atts['show_services'] === 'true'): ?>
                <div class="cf-employee-services">
                    <?php
                    $employee_services = chrono_forge()->db_manager->get_employee_services($employee->id);
                    if (!empty($employee_services)):
                    ?>
                    <h4><?php _e('Услуги:', 'chrono-forge'); ?></h4>
                    <div class="cf-services-tags">
                        <?php foreach (array_slice($employee_services, 0, 3) as $service): ?>
                        <span class="cf-service-tag" style="background-color: <?php echo esc_attr($service->color); ?>;">
                            <?php echo esc_html($service->name); ?>
                        </span>
                        <?php endforeach; ?>
                        <?php if (count($employee_services) > 3): ?>
                        <span class="cf-more-services">
                            +<?php echo count($employee_services) - 3; ?> <?php _e('еще', 'chrono-forge'); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($atts['show_book_button'] === 'true'): ?>
                <div class="cf-employee-actions">
                    <a href="#" class="cf-btn cf-btn-primary cf-book-employee" 
                       data-employee-id="<?php echo esc_attr($employee->id); ?>">
                        <?php _e('Записаться', 'chrono-forge'); ?>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="cf-empty-state">
        <p><?php _e('Сотрудники не найдены.', 'chrono-forge'); ?></p>
    </div>
    <?php endif; ?>
</div>

<style>
.chrono-forge-employees-list {
    margin: 20px 0;
}

.cf-employees-grid {
    display: grid;
    gap: 20px;
    margin-bottom: 20px;
}

.cf-employee-card {
    background: white;
    border: 1px solid #eee;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    text-align: center;
}

.cf-employee-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.cf-employee-photo {
    margin-bottom: 15px;
}

.cf-employee-photo img {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
}

.cf-employee-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 32px;
    font-weight: 600;
    margin: 0 auto;
}

.cf-employee-name {
    margin: 0 0 10px 0;
    font-size: 18px;
    font-weight: 600;
    color: #2c3e50;
}

.cf-employee-description {
    margin-bottom: 15px;
}

.cf-employee-description p {
    margin: 0;
    color: #666;
    line-height: 1.5;
    font-size: 14px;
}

.cf-employee-services {
    margin-bottom: 20px;
    text-align: left;
}

.cf-employee-services h4 {
    margin: 0 0 8px 0;
    font-size: 14px;
    font-weight: 600;
    color: #555;
}

.cf-services-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}

.cf-service-tag {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 12px;
    color: white;
    font-size: 11px;
    font-weight: 500;
}

.cf-more-services {
    color: #666;
    font-size: 11px;
    padding: 3px 8px;
}

.cf-employee-actions {
    text-align: center;
}

.cf-empty-state {
    text-align: center;
    padding: 40px;
    color: #666;
}

@media (max-width: 768px) {
    .cf-employees-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    $('.cf-book-employee').on('click', function(e) {
        e.preventDefault();
        
        const employeeId = $(this).data('employee-id');
        
        // Redirect to booking page with employee pre-selected
        const bookingUrl = new URL(window.location.origin + '/booking/');
        bookingUrl.searchParams.set('employee', employeeId);
        
        window.location.href = bookingUrl.toString();
    });
});
</script>

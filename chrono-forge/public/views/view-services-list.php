<?php
/**
 * Шаблон списка услуг
 * 
 * @var array $services
 * @var array $atts
 */

// Если файл вызван напрямую, прекратить выполнение
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="chrono-forge-services-list">
    <?php if (!empty($services)): ?>
    <div class="cf-services-grid" style="grid-template-columns: repeat(<?php echo esc_attr($atts['columns']); ?>, 1fr);">
        <?php foreach ($services as $service): ?>
        <div class="cf-service-card" data-service-id="<?php echo esc_attr($service->id); ?>">
            <div class="cf-service-header">
                <div class="cf-service-color" style="background-color: <?php echo esc_attr($service->color); ?>;"></div>
                <h3 class="cf-service-title"><?php echo esc_html($service->name); ?></h3>
            </div>
            
            <?php if ($atts['show_description'] === 'true' && !empty($service->description)): ?>
            <div class="cf-service-description">
                <p><?php echo esc_html($service->description); ?></p>
            </div>
            <?php endif; ?>
            
            <div class="cf-service-meta">
                <?php if ($atts['show_duration'] === 'true'): ?>
                <div class="cf-service-duration">
                    <i class="dashicons dashicons-clock"></i>
                    <?php echo esc_html($service->duration); ?> <?php _e('мин.', 'chrono-forge'); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($atts['show_price'] === 'true' && $service->price > 0): ?>
                <div class="cf-service-price">
                    <i class="dashicons dashicons-money-alt"></i>
                    <?php echo chrono_forge_format_price($service->price); ?>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ($atts['show_book_button'] === 'true'): ?>
            <div class="cf-service-actions">
                <a href="#" class="cf-btn cf-btn-primary cf-book-service" 
                   data-service-id="<?php echo esc_attr($service->id); ?>">
                    <?php _e('Записаться', 'chrono-forge'); ?>
                </a>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="cf-empty-state">
        <p><?php _e('Услуги не найдены.', 'chrono-forge'); ?></p>
    </div>
    <?php endif; ?>
</div>

<style>
.chrono-forge-services-list {
    margin: 20px 0;
}

.cf-services-grid {
    display: grid;
    gap: 20px;
    margin-bottom: 20px;
}

.cf-service-card {
    background: white;
    border: 1px solid #eee;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.cf-service-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.cf-service-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 15px;
}

.cf-service-color {
    width: 4px;
    height: 40px;
    border-radius: 2px;
}

.cf-service-title {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: #2c3e50;
}

.cf-service-description {
    margin-bottom: 15px;
}

.cf-service-description p {
    margin: 0;
    color: #666;
    line-height: 1.5;
}

.cf-service-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding: 10px 0;
    border-top: 1px solid #f0f0f0;
}

.cf-service-duration,
.cf-service-price {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 14px;
    color: #555;
}

.cf-service-price {
    font-weight: 600;
    color: #27ae60;
}

.cf-service-actions {
    text-align: center;
}

.cf-empty-state {
    text-align: center;
    padding: 40px;
    color: #666;
}

@media (max-width: 768px) {
    .cf-services-grid {
        grid-template-columns: 1fr !important;
    }
    
    .cf-service-meta {
        flex-direction: column;
        gap: 10px;
        align-items: flex-start;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    $('.cf-book-service').on('click', function(e) {
        e.preventDefault();
        
        const serviceId = $(this).data('service-id');
        
        // Redirect to booking page with service pre-selected
        const bookingUrl = new URL(window.location.origin + '/booking/');
        bookingUrl.searchParams.set('service', serviceId);
        
        window.location.href = bookingUrl.toString();
    });
});
</script>

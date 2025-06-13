<?php
/**
 * Шаблон каталога услуг
 * 
 * @var array $categories
 * @var array $services
 * @var array $atts
 */

// Если файл вызван напрямую, прекратить выполнение
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="chrono-forge-catalog">
    <div class="cf-catalog-header">
        <h2><?php _e('Каталог услуг', 'chrono-forge'); ?></h2>
        
        <?php if ($atts['show_filters'] === 'true'): ?>
        <div class="cf-catalog-filters">
            <div class="cf-filter-tabs">
                <button class="cf-filter-tab active" data-category="all">
                    <?php _e('Все услуги', 'chrono-forge'); ?>
                </button>
                <?php foreach ($categories as $category): ?>
                <button class="cf-filter-tab" data-category="<?php echo esc_attr($category->id); ?>">
                    <?php echo esc_html($category->name); ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="cf-catalog-content">
        <?php if ($atts['show_categories'] === 'true'): ?>
        <!-- Отображение по категориям -->
        <?php foreach ($categories as $category): ?>
        <div class="cf-category-section" data-category-id="<?php echo esc_attr($category->id); ?>">
            <div class="cf-category-header">
                <div class="cf-category-color" style="background-color: <?php echo esc_attr($category->color); ?>;"></div>
                <div class="cf-category-info">
                    <h3><?php echo esc_html($category->name); ?></h3>
                    <?php if (!empty($category->description)): ?>
                    <p><?php echo esc_html($category->description); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="cf-category-services">
                <?php
                $category_services = array_filter($services, function($service) use ($category) {
                    return $service->category_id == $category->id;
                });
                ?>
                
                <?php if (!empty($category_services)): ?>
                <div class="cf-services-grid">
                    <?php foreach ($category_services as $service): ?>
                    <div class="cf-service-card" data-service-id="<?php echo esc_attr($service->id); ?>">
                        <div class="cf-service-header">
                            <h4><?php echo esc_html($service->name); ?></h4>
                            <div class="cf-service-price">
                                <?php if ($service->price > 0): ?>
                                    <?php echo chrono_forge_format_price($service->price); ?>
                                <?php else: ?>
                                    <span class="cf-free"><?php _e('Бесплатно', 'chrono-forge'); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($service->description)): ?>
                        <div class="cf-service-description">
                            <p><?php echo esc_html($service->description); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <div class="cf-service-meta">
                            <div class="cf-service-duration">
                                <i class="dashicons dashicons-clock"></i>
                                <?php echo esc_html($service->duration); ?> <?php _e('мин.', 'chrono-forge'); ?>
                            </div>
                        </div>
                        
                        <div class="cf-service-actions">
                            <button class="cf-btn cf-btn-primary cf-book-service" 
                                    data-service-id="<?php echo esc_attr($service->id); ?>">
                                <?php _e('Записаться', 'chrono-forge'); ?>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="cf-empty-category">
                    <p><?php _e('В этой категории пока нет услуг.', 'chrono-forge'); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        
        <!-- Услуги без категории -->
        <?php
        $uncategorized_services = array_filter($services, function($service) {
            return empty($service->category_id);
        });
        ?>
        
        <?php if (!empty($uncategorized_services)): ?>
        <div class="cf-category-section" data-category-id="0">
            <div class="cf-category-header">
                <div class="cf-category-color" style="background-color: #95a5a6;"></div>
                <div class="cf-category-info">
                    <h3><?php _e('Другие услуги', 'chrono-forge'); ?></h3>
                </div>
            </div>
            
            <div class="cf-category-services">
                <div class="cf-services-grid">
                    <?php foreach ($uncategorized_services as $service): ?>
                    <div class="cf-service-card" data-service-id="<?php echo esc_attr($service->id); ?>">
                        <div class="cf-service-header">
                            <h4><?php echo esc_html($service->name); ?></h4>
                            <div class="cf-service-price">
                                <?php if ($service->price > 0): ?>
                                    <?php echo chrono_forge_format_price($service->price); ?>
                                <?php else: ?>
                                    <span class="cf-free"><?php _e('Бесплатно', 'chrono-forge'); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($service->description)): ?>
                        <div class="cf-service-description">
                            <p><?php echo esc_html($service->description); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <div class="cf-service-meta">
                            <div class="cf-service-duration">
                                <i class="dashicons dashicons-clock"></i>
                                <?php echo esc_html($service->duration); ?> <?php _e('мин.', 'chrono-forge'); ?>
                            </div>
                        </div>
                        
                        <div class="cf-service-actions">
                            <button class="cf-btn cf-btn-primary cf-book-service" 
                                    data-service-id="<?php echo esc_attr($service->id); ?>">
                                <?php _e('Записаться', 'chrono-forge'); ?>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php else: ?>
        <!-- Простое отображение всех услуг -->
        <div class="cf-all-services">
            <div class="cf-services-grid">
                <?php foreach ($services as $service): ?>
                <div class="cf-service-card" data-service-id="<?php echo esc_attr($service->id); ?>">
                    <div class="cf-service-header">
                        <h4><?php echo esc_html($service->name); ?></h4>
                        <div class="cf-service-price">
                            <?php if ($service->price > 0): ?>
                                <?php echo chrono_forge_format_price($service->price); ?>
                            <?php else: ?>
                                <span class="cf-free"><?php _e('Бесплатно', 'chrono-forge'); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($service->description)): ?>
                    <div class="cf-service-description">
                        <p><?php echo esc_html($service->description); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="cf-service-meta">
                        <div class="cf-service-duration">
                            <i class="dashicons dashicons-clock"></i>
                            <?php echo esc_html($service->duration); ?> <?php _e('мин.', 'chrono-forge'); ?>
                        </div>
                        <?php if (!empty($service->category_name)): ?>
                        <div class="cf-service-category">
                            <span class="cf-category-tag" style="background-color: <?php echo esc_attr($service->category_color); ?>;">
                                <?php echo esc_html($service->category_name); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="cf-service-actions">
                        <button class="cf-btn cf-btn-primary cf-book-service" 
                                data-service-id="<?php echo esc_attr($service->id); ?>">
                            <?php _e('Записаться', 'chrono-forge'); ?>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.chrono-forge-catalog {
    margin: 20px 0;
}

.cf-catalog-header {
    margin-bottom: 30px;
    text-align: center;
}

.cf-catalog-header h2 {
    margin: 0 0 20px 0;
    color: #2c3e50;
    font-size: 28px;
    font-weight: 600;
}

.cf-filter-tabs {
    display: flex;
    justify-content: center;
    gap: 10px;
    flex-wrap: wrap;
}

.cf-filter-tab {
    padding: 10px 20px;
    border: 2px solid #e1e8ed;
    background: white;
    color: #555;
    border-radius: 25px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 500;
}

.cf-filter-tab:hover,
.cf-filter-tab.active {
    border-color: #3498db;
    background: #3498db;
    color: white;
}

.cf-category-section {
    margin-bottom: 40px;
}

.cf-category-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #eee;
}

.cf-category-color {
    width: 6px;
    height: 50px;
    border-radius: 3px;
}

.cf-category-info h3 {
    margin: 0 0 5px 0;
    color: #2c3e50;
    font-size: 22px;
    font-weight: 600;
}

.cf-category-info p {
    margin: 0;
    color: #666;
    line-height: 1.5;
}

.cf-services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
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
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.cf-service-header h4 {
    margin: 0;
    color: #2c3e50;
    font-size: 18px;
    font-weight: 600;
    flex: 1;
}

.cf-service-price {
    font-weight: 600;
    color: #27ae60;
    font-size: 16px;
}

.cf-free {
    color: #3498db;
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
    padding-top: 15px;
    border-top: 1px solid #f0f0f0;
}

.cf-service-duration {
    display: flex;
    align-items: center;
    gap: 5px;
    color: #555;
    font-size: 14px;
}

.cf-category-tag {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 12px;
    color: white;
    font-size: 11px;
    font-weight: 500;
}

.cf-service-actions {
    text-align: center;
}

.cf-empty-category {
    text-align: center;
    padding: 40px;
    color: #666;
}

@media (max-width: 768px) {
    .cf-services-grid {
        grid-template-columns: 1fr;
    }
    
    .cf-filter-tabs {
        flex-direction: column;
        align-items: center;
    }
    
    .cf-category-header {
        flex-direction: column;
        text-align: center;
        gap: 10px;
    }
    
    .cf-service-header {
        flex-direction: column;
        gap: 10px;
        text-align: center;
    }
    
    .cf-service-meta {
        flex-direction: column;
        gap: 10px;
        align-items: center;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Filter functionality
    $('.cf-filter-tab').on('click', function() {
        const $tab = $(this);
        const categoryId = $tab.data('category');
        
        // Update active tab
        $('.cf-filter-tab').removeClass('active');
        $tab.addClass('active');
        
        // Show/hide categories
        if (categoryId === 'all') {
            $('.cf-category-section').show();
        } else {
            $('.cf-category-section').hide();
            $('.cf-category-section[data-category-id="' + categoryId + '"]').show();
        }
    });
    
    // Book service functionality
    $('.cf-book-service').on('click', function() {
        const serviceId = $(this).data('service-id');
        
        // Redirect to booking page with service pre-selected
        const bookingUrl = new URL(window.location.origin + '/booking/');
        bookingUrl.searchParams.set('service', serviceId);
        
        window.location.href = bookingUrl.toString();
    });
});
</script>

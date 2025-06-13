<?php
/**
 * Шаблон настроек
 * 
 * @var array $settings
 */

// Если файл вызван напрямую, прекратить выполнение
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="chrono-forge-admin">
    <div class="cf-page-title">
        <h1><?php _e('Настройки ChronoForge', 'chrono-forge'); ?></h1>
    </div>

    <form class="cf-admin-form" method="post" action="">
        <?php wp_nonce_field('chrono_forge_admin_action'); ?>
        <input type="hidden" name="action" value="save_settings">

        <!-- Общие настройки -->
        <div class="cf-form-container">
            <h2><?php _e('Общие настройки', 'chrono-forge'); ?></h2>
            
            <div class="cf-form-row">
                <div class="cf-form-group">
                    <label for="plugin_language"><?php _e('Язык плагина', 'chrono-forge'); ?></label>
                    <select id="plugin_language" name="plugin_language">
                        <option value="auto" <?php selected($settings['plugin_language'] ?? 'auto', 'auto'); ?>>
                            <?php _e('Автоматически (язык WordPress)', 'chrono-forge'); ?>
                        </option>
                        <option value="en_US" <?php selected($settings['plugin_language'] ?? 'auto', 'en_US'); ?>>
                            English
                        </option>
                        <option value="ru_RU" <?php selected($settings['plugin_language'] ?? 'auto', 'ru_RU'); ?>>
                            Русский
                        </option>
                    </select>
                    <small><?php _e('Выберите язык интерфейса плагина', 'chrono-forge'); ?></small>
                </div>
            </div>

            <div class="cf-form-row">
                <div class="cf-form-group">
                    <label for="currency"><?php _e('Валюта', 'chrono-forge'); ?></label>
                    <select id="currency" name="currency">
                        <option value="USD" <?php selected($settings['currency'] ?? 'USD', 'USD'); ?>>USD</option>
                        <option value="EUR" <?php selected($settings['currency'] ?? 'USD', 'EUR'); ?>>EUR</option>
                        <option value="RUB" <?php selected($settings['currency'] ?? 'USD', 'RUB'); ?>>RUB</option>
                        <option value="UAH" <?php selected($settings['currency'] ?? 'USD', 'UAH'); ?>>UAH</option>
                    </select>
                </div>
                <div class="cf-form-group">
                    <label for="currency_symbol"><?php _e('Символ валюты', 'chrono-forge'); ?></label>
                    <input type="text" id="currency_symbol" name="currency_symbol"
                           value="<?php echo esc_attr($settings['currency_symbol'] ?? '$'); ?>" maxlength="5">
                </div>
            </div>
            
            <div class="cf-form-row">
                <div class="cf-form-group">
                    <label for="date_format"><?php _e('Формат даты', 'chrono-forge'); ?></label>
                    <select id="date_format" name="date_format">
                        <option value="Y-m-d" <?php selected($settings['date_format'] ?? 'Y-m-d', 'Y-m-d'); ?>>YYYY-MM-DD</option>
                        <option value="d.m.Y" <?php selected($settings['date_format'] ?? 'Y-m-d', 'd.m.Y'); ?>>DD.MM.YYYY</option>
                        <option value="m/d/Y" <?php selected($settings['date_format'] ?? 'Y-m-d', 'm/d/Y'); ?>>MM/DD/YYYY</option>
                        <option value="d/m/Y" <?php selected($settings['date_format'] ?? 'Y-m-d', 'd/m/Y'); ?>>DD/MM/YYYY</option>
                    </select>
                </div>
                <div class="cf-form-group">
                    <label for="time_format"><?php _e('Формат времени', 'chrono-forge'); ?></label>
                    <select id="time_format" name="time_format">
                        <option value="H:i" <?php selected($settings['time_format'] ?? 'H:i', 'H:i'); ?>>24-часовой (HH:MM)</option>
                        <option value="g:i A" <?php selected($settings['time_format'] ?? 'H:i', 'g:i A'); ?>>12-часовой (H:MM AM/PM)</option>
                    </select>
                </div>
            </div>
            
            <div class="cf-form-row">
                <div class="cf-form-group">
                    <label for="min_booking_time"><?php _e('Минимальное время до записи (минут)', 'chrono-forge'); ?></label>
                    <input type="number" id="min_booking_time" name="min_booking_time" 
                           value="<?php echo esc_attr($settings['min_booking_time'] ?? 60); ?>" min="0">
                    <small><?php _e('За сколько минут до текущего времени можно записаться', 'chrono-forge'); ?></small>
                </div>
                <div class="cf-form-group">
                    <label for="max_booking_time"><?php _e('Максимальное время для записи (дней)', 'chrono-forge'); ?></label>
                    <input type="number" id="max_booking_time" name="max_booking_time" 
                           value="<?php echo esc_attr($settings['max_booking_time'] ?? 30); ?>" min="1">
                    <small><?php _e('На сколько дней вперед можно записаться', 'chrono-forge'); ?></small>
                </div>
            </div>
        </div>

        <!-- Настройки стилизации -->
        <div class="cf-form-container">
            <h2><?php _e('Стилизация', 'chrono-forge'); ?></h2>
            
            <div class="cf-form-row">
                <div class="cf-form-group">
                    <label for="primary_color"><?php _e('Основной цвет', 'chrono-forge'); ?></label>
                    <input type="color" id="primary_color" name="primary_color" 
                           value="<?php echo esc_attr($settings['primary_color'] ?? '#3498db'); ?>" class="cf-color-picker">
                </div>
                <div class="cf-form-group">
                    <label for="secondary_color"><?php _e('Дополнительный цвет', 'chrono-forge'); ?></label>
                    <input type="color" id="secondary_color" name="secondary_color" 
                           value="<?php echo esc_attr($settings['secondary_color'] ?? '#2c3e50'); ?>" class="cf-color-picker">
                </div>
            </div>
            
            <div class="cf-form-group">
                <label for="booking_form_style"><?php _e('Стиль формы бронирования', 'chrono-forge'); ?></label>
                <select id="booking_form_style" name="booking_form_style">
                    <option value="default" <?php selected($settings['booking_form_style'] ?? 'default', 'default'); ?>>
                        <?php _e('По умолчанию', 'chrono-forge'); ?>
                    </option>
                    <option value="minimal" <?php selected($settings['booking_form_style'] ?? 'default', 'minimal'); ?>>
                        <?php _e('Минималистичный', 'chrono-forge'); ?>
                    </option>
                    <option value="modern" <?php selected($settings['booking_form_style'] ?? 'default', 'modern'); ?>>
                        <?php _e('Современный', 'chrono-forge'); ?>
                    </option>
                </select>
            </div>
        </div>

        <!-- Настройки платежей -->
        <div class="cf-form-container">
            <h2><?php _e('Платежи', 'chrono-forge'); ?></h2>
            
            <div class="cf-form-group">
                <label>
                    <input type="checkbox" name="enable_payments" value="1" 
                           <?php checked($settings['enable_payments'] ?? false); ?>>
                    <?php _e('Включить онлайн-платежи', 'chrono-forge'); ?>
                </label>
            </div>
            
            <div class="cf-form-group">
                <label>
                    <input type="checkbox" name="payment_required" value="1"
                           <?php checked($settings['payment_required'] ?? false); ?>>
                    <?php _e('Требовать оплату при бронировании', 'chrono-forge'); ?>
                </label>
            </div>

            <div class="payment-settings" style="display: none;">
                <h3><?php _e('Настройки платежных шлюзов', 'chrono-forge'); ?></h3>

                <!-- Stripe Settings -->
                <div class="cf-form-container">
                    <h4>Stripe</h4>
                    <div class="cf-form-row">
                        <div class="cf-form-group">
                            <label for="stripe_publishable_key"><?php _e('Публичный ключ Stripe', 'chrono-forge'); ?></label>
                            <input type="text" id="stripe_publishable_key" name="stripe[publishable_key]"
                                   value="<?php echo esc_attr($settings['stripe']['publishable_key'] ?? ''); ?>">
                        </div>
                        <div class="cf-form-group">
                            <label for="stripe_secret_key"><?php _e('Секретный ключ Stripe', 'chrono-forge'); ?></label>
                            <input type="password" id="stripe_secret_key" name="stripe[secret_key]"
                                   value="<?php echo esc_attr($settings['stripe']['secret_key'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="cf-form-group">
                        <label>
                            <input type="checkbox" name="stripe[enabled]" value="1"
                                   <?php checked($settings['stripe']['enabled'] ?? false); ?>>
                            <?php _e('Включить Stripe', 'chrono-forge'); ?>
                        </label>
                    </div>
                </div>

                <!-- PayPal Settings -->
                <div class="cf-form-container">
                    <h4>PayPal</h4>
                    <div class="cf-form-row">
                        <div class="cf-form-group">
                            <label for="paypal_client_id"><?php _e('Client ID PayPal', 'chrono-forge'); ?></label>
                            <input type="text" id="paypal_client_id" name="paypal[client_id]"
                                   value="<?php echo esc_attr($settings['paypal']['client_id'] ?? ''); ?>">
                        </div>
                        <div class="cf-form-group">
                            <label for="paypal_client_secret"><?php _e('Client Secret PayPal', 'chrono-forge'); ?></label>
                            <input type="password" id="paypal_client_secret" name="paypal[client_secret]"
                                   value="<?php echo esc_attr($settings['paypal']['client_secret'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="cf-form-row">
                        <div class="cf-form-group">
                            <label for="paypal_email"><?php _e('Email PayPal', 'chrono-forge'); ?></label>
                            <input type="email" id="paypal_email" name="paypal[email]"
                                   value="<?php echo esc_attr($settings['paypal']['email'] ?? ''); ?>">
                        </div>
                        <div class="cf-form-group">
                            <label>
                                <input type="checkbox" name="paypal[sandbox]" value="1"
                                       <?php checked($settings['paypal']['sandbox'] ?? false); ?>>
                                <?php _e('Режим песочницы', 'chrono-forge'); ?>
                            </label>
                        </div>
                    </div>
                    <div class="cf-form-group">
                        <label>
                            <input type="checkbox" name="paypal[enabled]" value="1"
                                   <?php checked($settings['paypal']['enabled'] ?? false); ?>>
                            <?php _e('Включить PayPal', 'chrono-forge'); ?>
                        </label>
                    </div>
                </div>

                <!-- Square Settings -->
                <div class="cf-form-container">
                    <h4>Square</h4>
                    <div class="cf-form-row">
                        <div class="cf-form-group">
                            <label for="square_application_id"><?php _e('Application ID Square', 'chrono-forge'); ?></label>
                            <input type="text" id="square_application_id" name="square[application_id]"
                                   value="<?php echo esc_attr($settings['square']['application_id'] ?? ''); ?>">
                        </div>
                        <div class="cf-form-group">
                            <label for="square_access_token"><?php _e('Access Token Square', 'chrono-forge'); ?></label>
                            <input type="password" id="square_access_token" name="square[access_token]"
                                   value="<?php echo esc_attr($settings['square']['access_token'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="cf-form-group">
                        <label>
                            <input type="checkbox" name="square[sandbox]" value="1"
                                   <?php checked($settings['square']['sandbox'] ?? false); ?>>
                            <?php _e('Режим песочницы', 'chrono-forge'); ?>
                        </label>
                    </div>
                    <div class="cf-form-group">
                        <label>
                            <input type="checkbox" name="square[enabled]" value="1"
                                   <?php checked($settings['square']['enabled'] ?? false); ?>>
                            <?php _e('Включить Square', 'chrono-forge'); ?>
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="cf-form-group">
                <label for="default_appointment_status"><?php _e('Статус записи по умолчанию', 'chrono-forge'); ?></label>
                <select id="default_appointment_status" name="default_appointment_status">
                    <?php foreach (chrono_forge_get_appointment_statuses() as $key => $label): ?>
                    <option value="<?php echo esc_attr($key); ?>" 
                            <?php selected($settings['default_appointment_status'] ?? 'pending', $key); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Настройки уведомлений -->
        <div class="cf-form-container">
            <h2><?php _e('Уведомления', 'chrono-forge'); ?></h2>
            
            <div class="cf-form-group">
                <label>
                    <input type="checkbox" name="enable_notifications" value="1" 
                           <?php checked($settings['enable_notifications'] ?? true); ?>>
                    <?php _e('Включить уведомления', 'chrono-forge'); ?>
                </label>
            </div>
            
            <div class="cf-form-row">
                <div class="cf-form-group">
                    <label>
                        <input type="checkbox" name="admin_email_notifications" value="1" 
                               <?php checked($settings['admin_email_notifications'] ?? true); ?>>
                        <?php _e('Email-уведомления администратору', 'chrono-forge'); ?>
                    </label>
                </div>
                <div class="cf-form-group">
                    <label>
                        <input type="checkbox" name="customer_email_notifications" value="1" 
                               <?php checked($settings['customer_email_notifications'] ?? true); ?>>
                        <?php _e('Email-уведомления клиентам', 'chrono-forge'); ?>
                    </label>
                </div>
            </div>
            
            <div class="cf-form-group">
                <label>
                    <input type="checkbox" name="enable_sms_notifications" value="1"
                           <?php checked($settings['enable_sms_notifications'] ?? false); ?>>
                    <?php _e('SMS-уведомления (требует настройки)', 'chrono-forge'); ?>
                </label>
            </div>

            <div class="sms-settings" style="display: none;">
                <h4><?php _e('Настройки SMS', 'chrono-forge'); ?></h4>
                <div class="cf-form-row">
                    <div class="cf-form-group">
                        <label for="sms_provider"><?php _e('SMS провайдер', 'chrono-forge'); ?></label>
                        <select id="sms_provider" name="sms[provider]">
                            <option value=""><?php _e('Выберите провайдера', 'chrono-forge'); ?></option>
                            <option value="twilio" <?php selected($settings['sms']['provider'] ?? '', 'twilio'); ?>>Twilio</option>
                            <option value="nexmo" <?php selected($settings['sms']['provider'] ?? '', 'nexmo'); ?>>Nexmo/Vonage</option>
                        </select>
                    </div>
                </div>
                <div class="cf-form-row">
                    <div class="cf-form-group">
                        <label for="sms_api_key"><?php _e('API ключ', 'chrono-forge'); ?></label>
                        <input type="password" id="sms_api_key" name="sms[api_key]"
                               value="<?php echo esc_attr($settings['sms']['api_key'] ?? ''); ?>">
                    </div>
                    <div class="cf-form-group">
                        <label for="sms_api_secret"><?php _e('API секрет', 'chrono-forge'); ?></label>
                        <input type="password" id="sms_api_secret" name="sms[api_secret]"
                               value="<?php echo esc_attr($settings['sms']['api_secret'] ?? ''); ?>">
                    </div>
                </div>
                <div class="cf-form-group">
                    <label for="sms_from_number"><?php _e('Номер отправителя', 'chrono-forge'); ?></label>
                    <input type="text" id="sms_from_number" name="sms[from_number]"
                           value="<?php echo esc_attr($settings['sms']['from_number'] ?? ''); ?>"
                           placeholder="+1234567890">
                </div>
            </div>
        </div>

        <!-- Интеграции с календарями -->
        <div class="cf-form-container">
            <h2><?php _e('Интеграции с календарями', 'chrono-forge'); ?></h2>

            <!-- Google Calendar -->
            <div class="cf-form-container">
                <h3>Google Calendar</h3>
                <div class="cf-form-row">
                    <div class="cf-form-group">
                        <label for="google_client_id"><?php _e('Client ID Google', 'chrono-forge'); ?></label>
                        <input type="text" id="google_client_id" name="google_calendar[client_id]"
                               value="<?php echo esc_attr($settings['google_calendar']['client_id'] ?? ''); ?>">
                    </div>
                    <div class="cf-form-group">
                        <label for="google_client_secret"><?php _e('Client Secret Google', 'chrono-forge'); ?></label>
                        <input type="password" id="google_client_secret" name="google_calendar[client_secret]"
                               value="<?php echo esc_attr($settings['google_calendar']['client_secret'] ?? ''); ?>">
                    </div>
                </div>
                <div class="cf-form-group">
                    <label>
                        <input type="checkbox" name="google_calendar[enabled]" value="1"
                               <?php checked($settings['google_calendar']['enabled'] ?? false); ?>>
                        <?php _e('Включить синхронизацию с Google Calendar', 'chrono-forge'); ?>
                    </label>
                </div>
                <div class="cf-form-group">
                    <button type="button" class="cf-btn cf-btn-secondary" id="google-auth-btn">
                        <?php _e('Авторизоваться в Google', 'chrono-forge'); ?>
                    </button>
                    <button type="button" class="cf-btn cf-btn-primary" id="google-sync-btn">
                        <?php _e('Синхронизировать календарь', 'chrono-forge'); ?>
                    </button>
                </div>
            </div>

            <!-- Outlook Calendar -->
            <div class="cf-form-container">
                <h3>Outlook Calendar</h3>
                <div class="cf-form-row">
                    <div class="cf-form-group">
                        <label for="outlook_client_id"><?php _e('Client ID Outlook', 'chrono-forge'); ?></label>
                        <input type="text" id="outlook_client_id" name="outlook_calendar[client_id]"
                               value="<?php echo esc_attr($settings['outlook_calendar']['client_id'] ?? ''); ?>">
                    </div>
                    <div class="cf-form-group">
                        <label for="outlook_client_secret"><?php _e('Client Secret Outlook', 'chrono-forge'); ?></label>
                        <input type="password" id="outlook_client_secret" name="outlook_calendar[client_secret]"
                               value="<?php echo esc_attr($settings['outlook_calendar']['client_secret'] ?? ''); ?>">
                    </div>
                </div>
                <div class="cf-form-group">
                    <label>
                        <input type="checkbox" name="outlook_calendar[enabled]" value="1"
                               <?php checked($settings['outlook_calendar']['enabled'] ?? false); ?>>
                        <?php _e('Включить синхронизацию с Outlook Calendar', 'chrono-forge'); ?>
                    </label>
                </div>
                <div class="cf-form-group">
                    <button type="button" class="cf-btn cf-btn-secondary" id="outlook-auth-btn">
                        <?php _e('Авторизоваться в Microsoft', 'chrono-forge'); ?>
                    </button>
                    <button type="button" class="cf-btn cf-btn-primary" id="outlook-sync-btn">
                        <?php _e('Синхронизировать календарь', 'chrono-forge'); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Дополнительные настройки -->
        <div class="cf-form-container">
            <h2><?php _e('Дополнительные настройки', 'chrono-forge'); ?></h2>
            
            <div class="cf-form-group">
                <label for="admin_email"><?php _e('Email администратора для уведомлений', 'chrono-forge'); ?></label>
                <input type="email" id="admin_email" name="admin_email" 
                       value="<?php echo esc_attr($settings['admin_email'] ?? get_option('admin_email')); ?>">
            </div>
            
            <div class="cf-form-group">
                <label for="company_name"><?php _e('Название компании', 'chrono-forge'); ?></label>
                <input type="text" id="company_name" name="company_name" 
                       value="<?php echo esc_attr($settings['company_name'] ?? get_bloginfo('name')); ?>">
            </div>
            
            <div class="cf-form-group">
                <label for="company_address"><?php _e('Адрес компании', 'chrono-forge'); ?></label>
                <textarea id="company_address" name="company_address" rows="3"><?php echo esc_textarea($settings['company_address'] ?? ''); ?></textarea>
            </div>
            
            <div class="cf-form-row">
                <div class="cf-form-group">
                    <label for="company_phone"><?php _e('Телефон компании', 'chrono-forge'); ?></label>
                    <input type="tel" id="company_phone" name="company_phone" 
                           value="<?php echo esc_attr($settings['company_phone'] ?? ''); ?>">
                </div>
                <div class="cf-form-group">
                    <label for="company_email"><?php _e('Email компании', 'chrono-forge'); ?></label>
                    <input type="email" id="company_email" name="company_email" 
                           value="<?php echo esc_attr($settings['company_email'] ?? get_option('admin_email')); ?>">
                </div>
            </div>
        </div>

        <!-- Кнопка сохранения -->
        <div style="text-align: center; margin-top: 30px;">
            <button type="submit" class="cf-btn cf-btn-primary" style="padding: 15px 30px; font-size: 16px;">
                <?php _e('Сохранить настройки', 'chrono-forge'); ?>
            </button>
        </div>
    </form>

    <!-- Информация о плагине -->
    <div class="cf-form-container" style="margin-top: 30px; text-align: center; background: #f8f9fa;">
        <h3><?php _e('ChronoForge', 'chrono-forge'); ?></h3>
        <p><?php printf(__('Версия: %s', 'chrono-forge'), CHRONO_FORGE_VERSION); ?></p>
        <p><?php _e('Система управления бронированиями для WordPress', 'chrono-forge'); ?></p>
        
        <div style="margin-top: 20px;">
            <a href="#" class="cf-btn cf-btn-secondary"><?php _e('Документация', 'chrono-forge'); ?></a>
            <a href="#" class="cf-btn cf-btn-secondary"><?php _e('Поддержка', 'chrono-forge'); ?></a>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Показ/скрытие настроек платежей в зависимости от включения
    $('input[name="enable_payments"]').on('change', function() {
        const $paymentSettings = $(this).closest('.cf-form-container').find('.payment-settings');
        if ($(this).is(':checked')) {
            $paymentSettings.show();
        } else {
            $paymentSettings.hide();
        }
    }).trigger('change');
    
    // Показ/скрытие настроек уведомлений
    $('input[name="enable_notifications"]').on('change', function() {
        const $notificationSettings = $(this).closest('.cf-form-container').find('.notification-settings');
        if ($(this).is(':checked')) {
            $notificationSettings.show();
        } else {
            $notificationSettings.hide();
        }
    }).trigger('change');

    // Показ/скрытие настроек SMS
    $('input[name="enable_sms_notifications"]').on('change', function() {
        const $smsSettings = $(this).closest('.cf-form-container').find('.sms-settings');
        if ($(this).is(':checked')) {
            $smsSettings.show();
        } else {
            $smsSettings.hide();
        }
    }).trigger('change');

    // Calendar integration buttons
    $('#google-auth-btn').on('click', function() {
        $.ajax({
            url: chronoForgeAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'chrono_forge_google_auth',
                nonce: chronoForgeAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    window.open(response.data.auth_url, '_blank');
                } else {
                    alert(response.data);
                }
            }
        });
    });

    $('#outlook-auth-btn').on('click', function() {
        $.ajax({
            url: chronoForgeAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'chrono_forge_outlook_auth',
                nonce: chronoForgeAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    window.open(response.data.auth_url, '_blank');
                } else {
                    alert(response.data);
                }
            }
        });
    });

    $('#google-sync-btn').on('click', function() {
        const $btn = $(this);
        $btn.prop('disabled', true).text('Синхронизация...');

        $.ajax({
            url: chronoForgeAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'chrono_forge_sync_calendar',
                calendar_type: 'google',
                nonce: chronoForgeAdmin.nonce
            },
            success: function(response) {
                alert(response.success ? response.data : response.data);
            },
            complete: function() {
                $btn.prop('disabled', false).text('Синхронизировать календарь');
            }
        });
    });

    $('#outlook-sync-btn').on('click', function() {
        const $btn = $(this);
        $btn.prop('disabled', true).text('Синхронизация...');

        $.ajax({
            url: chronoForgeAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'chrono_forge_sync_calendar',
                calendar_type: 'outlook',
                nonce: chronoForgeAdmin.nonce
            },
            success: function(response) {
                alert(response.success ? response.data : response.data);
            },
            complete: function() {
                $btn.prop('disabled', false).text('Синхронизировать календарь');
            }
        });
    });
});
</script>

/**
 * JavaScript для публичной части плагина ChronoForge
 */

(function($) {
    'use strict';

    // Объект для управления формой бронирования
    const ChronoForgeBooking = {
        currentStep: 1,
        totalSteps: 5,
        selectedData: {
            category_id: null,
            service_id: null,
            employee_id: null,
            date: null,
            time: null
        },

        init: function() {
            this.bindEvents();
            this.updateStepIndicator();
            this.handleUrlParameters();
        },

        bindEvents: function() {
            // Выбор категории
            $(document).on('click', '.cf-category-item', this.selectCategory.bind(this));
            
            // Выбор услуги
            $(document).on('click', '.cf-service-item', this.selectService.bind(this));
            
            // Выбор сотрудника
            $(document).on('click', '.cf-employee-item', this.selectEmployee.bind(this));
            
            // Выбор даты
            $(document).on('change', '.cf-date-input', this.selectDate.bind(this));
            
            // Выбор времени
            $(document).on('click', '.cf-time-slot', this.selectTime.bind(this));
            
            // Навигация по шагам
            $(document).on('click', '.cf-btn-next', this.nextStep.bind(this));
            $(document).on('click', '.cf-btn-prev', this.prevStep.bind(this));
            
            // Отправка формы
            $(document).on('submit', '.cf-booking-form', this.submitForm.bind(this));
        },

        selectCategory: function(e) {
            const $item = $(e.currentTarget);
            const categoryId = $item.data('category-id');
            
            // Убираем выделение с других элементов
            $('.cf-category-item').removeClass('selected');
            $item.addClass('selected');
            
            this.selectedData.category_id = categoryId;
            
            // Загружаем услуги для выбранной категории
            this.loadServices(categoryId);
        },

        selectService: function(e) {
            const $item = $(e.currentTarget);
            const serviceId = $item.data('service-id');
            
            // Убираем выделение с других элементов
            $('.cf-service-item').removeClass('selected');
            $item.addClass('selected');
            
            this.selectedData.service_id = serviceId;
            
            // Загружаем сотрудников для выбранной услуги
            this.loadEmployees(serviceId);
        },

        selectEmployee: function(e) {
            const $item = $(e.currentTarget);
            const employeeId = $item.data('employee-id');
            
            // Убираем выделение с других элементов
            $('.cf-employee-item').removeClass('selected');
            $item.addClass('selected');
            
            this.selectedData.employee_id = employeeId;
            
            // Инициализируем календарь
            this.initDatePicker();
        },

        selectDate: function(e) {
            const date = $(e.target).val();
            
            if (!date) return;
            
            this.selectedData.date = date;
            
            // Загружаем доступные слоты
            this.loadAvailableSlots();
        },

        selectTime: function(e) {
            const $slot = $(e.currentTarget);
            
            if ($slot.hasClass('disabled')) return;
            
            const time = $slot.data('time');
            
            // Убираем выделение с других слотов
            $('.cf-time-slot').removeClass('selected');
            $slot.addClass('selected');
            
            this.selectedData.time = time;
        },

        loadServices: function(categoryId) {
            const $container = $('.cf-services-container');
            
            $container.html('<div class="cf-loading"><div class="cf-loading-spinner"></div><p>' + chronoForgeAjax.strings.loading + '</p></div>');
            
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
                        $container.html(response.data.html);
                    } else {
                        $container.html('<p class="cf-message cf-message-error">' + response.data + '</p>');
                    }
                },
                error: function() {
                    $container.html('<p class="cf-message cf-message-error">' + chronoForgeAjax.strings.error + '</p>');
                }
            });
        },

        loadEmployees: function(serviceId) {
            const $container = $('.cf-employees-container');
            
            $container.html('<div class="cf-loading"><div class="cf-loading-spinner"></div><p>' + chronoForgeAjax.strings.loading + '</p></div>');
            
            $.ajax({
                url: chronoForgeAjax.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'chrono_forge_get_employees',
                    service_id: serviceId,
                    nonce: chronoForgeAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $container.html(response.data.html);
                    } else {
                        $container.html('<p class="cf-message cf-message-error">' + response.data + '</p>');
                    }
                },
                error: function() {
                    $container.html('<p class="cf-message cf-message-error">' + chronoForgeAjax.strings.error + '</p>');
                }
            });
        },

        initDatePicker: function() {
            const $dateInput = $('.cf-date-input');
            
            // Устанавливаем минимальную и максимальную даты
            const today = new Date();
            const minDate = new Date(today.getTime() + (60 * 60 * 1000)); // +1 час
            const maxDate = new Date(today.getTime() + (30 * 24 * 60 * 60 * 1000)); // +30 дней
            
            $dateInput.attr('min', this.formatDate(minDate));
            $dateInput.attr('max', this.formatDate(maxDate));
        },

        loadAvailableSlots: function() {
            const $container = $('.cf-time-slots-container');
            
            if (!this.selectedData.service_id || !this.selectedData.employee_id || !this.selectedData.date) {
                return;
            }
            
            $container.html('<div class="cf-loading"><div class="cf-loading-spinner"></div><p>' + chronoForgeAjax.strings.loading + '</p></div>');
            
            $.ajax({
                url: chronoForgeAjax.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'chrono_forge_get_available_slots',
                    service_id: this.selectedData.service_id,
                    employee_id: this.selectedData.employee_id,
                    date: this.selectedData.date,
                    nonce: chronoForgeAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data.length > 0) {
                            let slotsHtml = '<div class="cf-time-slots-grid">';
                            response.data.forEach(function(slot) {
                                slotsHtml += '<div class="cf-time-slot" data-time="' + slot.time + '">' + slot.display_time + '</div>';
                            });
                            slotsHtml += '</div>';
                            $container.html(slotsHtml);
                        } else {
                            $container.html('<p class="cf-message cf-message-error">' + chronoForgeAjax.strings.noSlotsAvailable + '</p>');
                        }
                    } else {
                        $container.html('<p class="cf-message cf-message-error">' + response.data + '</p>');
                    }
                }.bind(this),
                error: function() {
                    $container.html('<p class="cf-message cf-message-error">' + chronoForgeAjax.strings.error + '</p>');
                }
            });
        },

        nextStep: function(e) {
            e.preventDefault();
            
            if (!this.validateCurrentStep()) {
                return;
            }
            
            if (this.currentStep < this.totalSteps) {
                this.currentStep++;
                this.showStep(this.currentStep);
                this.updateStepIndicator();
            }
        },

        prevStep: function(e) {
            e.preventDefault();
            
            if (this.currentStep > 1) {
                this.currentStep--;
                this.showStep(this.currentStep);
                this.updateStepIndicator();
            }
        },

        showStep: function(step) {
            $('.cf-step').removeClass('active');
            $('.cf-step[data-step="' + step + '"]').addClass('active');
        },

        updateStepIndicator: function() {
            $('.cf-step-indicator').each(function(index) {
                const $indicator = $(this);
                const stepNumber = index + 1;
                
                $indicator.removeClass('active completed');
                
                if (stepNumber < this.currentStep) {
                    $indicator.addClass('completed');
                } else if (stepNumber === this.currentStep) {
                    $indicator.addClass('active');
                }
            }.bind(this));
        },

        validateCurrentStep: function() {
            switch (this.currentStep) {
                case 1: // Категория (если показывается)
                    return $('.cf-step[data-step="1"]').length === 0 || this.selectedData.category_id !== null;
                case 2: // Услуга
                    return this.selectedData.service_id !== null;
                case 3: // Сотрудник
                    return this.selectedData.employee_id !== null;
                case 4: // Дата и время
                    return this.selectedData.date !== null && this.selectedData.time !== null;
                case 5: // Данные клиента
                    return this.validateCustomerForm();
                default:
                    return true;
            }
        },

        validateCustomerForm: function() {
            const $form = $('.cf-customer-form');
            let isValid = true;
            
            $form.find('input[required], textarea[required]').each(function() {
                const $field = $(this);
                if (!$field.val().trim()) {
                    $field.addClass('error');
                    isValid = false;
                } else {
                    $field.removeClass('error');
                }
            });
            
            // Валидация email
            const $email = $form.find('input[type="email"]');
            if ($email.length && $email.val() && !this.isValidEmail($email.val())) {
                $email.addClass('error');
                isValid = false;
            }
            
            return isValid;
        },

        submitForm: function(e) {
            e.preventDefault();
            
            if (!this.validateCustomerForm()) {
                return;
            }
            
            const $form = $(e.target);
            const $submitBtn = $form.find('button[type="submit"]');
            
            // Отключаем кнопку отправки
            $submitBtn.prop('disabled', true).text(chronoForgeAjax.strings.loading);
            
            // Собираем данные формы
            const formData = new FormData($form[0]);
            formData.append('action', 'chrono_forge_create_appointment');
            formData.append('nonce', chronoForgeAjax.nonce);
            formData.append('service_id', this.selectedData.service_id);
            formData.append('employee_id', this.selectedData.employee_id);
            formData.append('date', this.selectedData.date);
            formData.append('time', this.selectedData.time);
            
            $.ajax({
                url: chronoForgeAjax.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        // Показываем сообщение об успехе
                        $('.chrono-forge-booking-form').html(
                            '<div class="cf-message cf-message-success">' +
                            '<h3>Запись успешно создана!</h3>' +
                            '<p>' + response.data.message + '</p>' +
                            '</div>'
                        );
                    } else {
                        // Показываем ошибку
                        $('.cf-form-messages').html('<div class="cf-message cf-message-error">' + response.data + '</div>');
                        $submitBtn.prop('disabled', false).text('Записаться');
                    }
                },
                error: function() {
                    $('.cf-form-messages').html('<div class="cf-message cf-message-error">' + chronoForgeAjax.strings.error + '</div>');
                    $submitBtn.prop('disabled', false).text('Записаться');
                }
            });
        },

        formatDate: function(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return year + '-' + month + '-' + day;
        },

        isValidEmail: function(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        },

        handleUrlParameters: function() {
            const urlParams = new URLSearchParams(window.location.search);

            // Pre-select service if provided
            const serviceId = urlParams.get('service');
            if (serviceId) {
                const $serviceItem = $('.cf-service-item[data-service-id="' + serviceId + '"]');
                if ($serviceItem.length) {
                    $serviceItem.click();
                    this.selectedData.service_id = serviceId;
                }
            }

            // Pre-select employee if provided
            const employeeId = urlParams.get('employee');
            if (employeeId) {
                const $employeeItem = $('.cf-employee-item[data-employee-id="' + employeeId + '"]');
                if ($employeeItem.length) {
                    $employeeItem.click();
                    this.selectedData.employee_id = employeeId;
                }
            }

            // Pre-select date if provided
            const date = urlParams.get('date');
            if (date) {
                const $dateInput = $('.cf-date-input');
                if ($dateInput.length) {
                    $dateInput.val(date);
                    this.selectedData.date = date;
                    this.loadAvailableSlots();
                }
            }

            // Pre-select time if provided
            const time = urlParams.get('time');
            if (time) {
                // Wait for slots to load, then select time
                setTimeout(() => {
                    const $timeSlot = $('.cf-time-slot[data-time="' + time + '"]');
                    if ($timeSlot.length) {
                        $timeSlot.click();
                        this.selectedData.time = time;
                    }
                }, 1000);
            }
        }
    };

    // Инициализация при загрузке документа
    $(document).ready(function() {
        if ($('.chrono-forge-booking-form').length) {
            ChronoForgeBooking.init();
        }
    });

})(jQuery);

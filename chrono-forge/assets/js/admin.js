/**
 * JavaScript для админ-панели плагина ChronoForge
 */

(function($) {
    'use strict';

    // Объект для управления админ-панелью
    const ChronoForgeAdmin = {
        init: function() {
            this.bindEvents();
            this.initModals();
            this.initDatePickers();
            this.initColorPickers();
        },

        bindEvents: function() {
            // Модальные окна
            $(document).on('click', '[data-modal]', this.openModal.bind(this));
            $(document).on('click', '.cf-modal-close, .cf-modal-backdrop', this.closeModal.bind(this));
            
            // Формы
            $(document).on('submit', '.cf-admin-form', this.handleFormSubmit.bind(this));
            
            // Удаление записей
            $(document).on('click', '.cf-delete-item', this.confirmDelete.bind(this));
            
            // График работы
            $(document).on('change', '.cf-schedule-working', this.toggleScheduleDay.bind(this));
            $(document).on('click', '.cf-schedule-preset', this.applySchedulePreset.bind(this));
            
            // Фильтры
            $(document).on('change', '.cf-filter', this.applyFilters.bind(this));
            
            // Массовые действия
            $(document).on('change', '.cf-select-all', this.toggleSelectAll.bind(this));
            $(document).on('click', '.cf-bulk-action', this.handleBulkAction.bind(this));
        },

        initModals: function() {
            // Создаем backdrop для модальных окон
            if (!$('.cf-modal-backdrop').length) {
                $('body').append('<div class="cf-modal-backdrop"></div>');
            }
        },

        initDatePickers: function() {
            // Инициализация date picker'ов
            $('.cf-datepicker').each(function() {
                const $input = $(this);
                
                if ($input.hasClass('cf-datepicker-past')) {
                    // Для прошлых дат
                    $input.attr('max', new Date().toISOString().split('T')[0]);
                } else {
                    // Для будущих дат
                    $input.attr('min', new Date().toISOString().split('T')[0]);
                }
            });
        },

        initColorPickers: function() {
            // Инициализация color picker'ов
            $('.cf-color-picker').each(function() {
                const $input = $(this);
                const $preview = $('<div class="cf-color-preview"></div>');
                
                $preview.css('background-color', $input.val());
                $input.after($preview);
                
                $input.on('change', function() {
                    $preview.css('background-color', $(this).val());
                });
            });
        },

        openModal: function(e) {
            e.preventDefault();
            
            const $trigger = $(e.currentTarget);
            const modalId = $trigger.data('modal');
            const $modal = $('#' + modalId);
            
            if ($modal.length) {
                // Заполняем модальное окно данными, если они есть
                if ($trigger.data('id')) {
                    this.loadModalData($modal, $trigger.data('id'), $trigger.data('type'));
                }
                
                $modal.show();
                $('.cf-modal-backdrop').show();
                $('body').addClass('cf-modal-open');
            }
        },

        closeModal: function(e) {
            if (e.target === e.currentTarget) {
                $('.cf-modal').hide();
                $('.cf-modal-backdrop').hide();
                $('body').removeClass('cf-modal-open');
                
                // Очищаем формы в модальных окнах
                $('.cf-modal form')[0]?.reset();
            }
        },

        loadModalData: function($modal, id, type) {
            const $form = $modal.find('form');

            if (!$form.length) return;

            // Показываем индикатор загрузки
            $form.find('.cf-modal-content').append('<div class="cf-loading"><div class="cf-loading-spinner"></div></div>');

            // Special handling for schedule modal
            if ($modal.attr('id') === 'cf-schedule-modal') {
                $('#schedule_employee_id').val(id);
                this.loadEmployeeSchedule(id);
                $form.find('.cf-loading').remove();
                return;
            }

            $.ajax({
                url: chronoForgeAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'chrono_forge_get_' + type,
                    id: id,
                    nonce: chronoForgeAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Заполняем форму данными
                        const data = response.data;

                        Object.keys(data).forEach(function(key) {
                            const $field = $form.find('[name="' + key + '"]');
                            if ($field.length) {
                                if ($field.is(':checkbox')) {
                                    $field.prop('checked', data[key] == 1);
                                } else {
                                    $field.val(data[key]);
                                }
                            }
                        });

                        // Добавляем скрытое поле с ID
                        if (!$form.find('[name="' + type + '_id"]').length) {
                            $form.append('<input type="hidden" name="' + type + '_id" value="' + id + '">');
                        }
                    }
                },
                complete: function() {
                    $form.find('.cf-loading').remove();
                }
            });
        },

        loadEmployeeSchedule: function(employeeId) {
            $.ajax({
                url: chronoForgeAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'chrono_forge_get_employee_schedule',
                    employee_id: employeeId,
                    nonce: chronoForgeAdmin.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        const schedule = response.data;

                        // Reset all schedule inputs
                        $('.cf-schedule-working').prop('checked', false).trigger('change');

                        // Apply loaded schedule
                        schedule.forEach(function(daySchedule) {
                            const $dayContainer = $('.cf-schedule-day[data-day="' + daySchedule.day_of_week + '"]');

                            if (daySchedule.is_working == 1) {
                                $dayContainer.find('.cf-schedule-working').prop('checked', true).trigger('change');
                                $dayContainer.find('input[name*="[start_time]"]').val(daySchedule.start_time);
                                $dayContainer.find('input[name*="[end_time]"]').val(daySchedule.end_time);
                                $dayContainer.find('input[name*="[break_start]"]').val(daySchedule.break_start || '');
                                $dayContainer.find('input[name*="[break_end]"]').val(daySchedule.break_end || '');
                            }
                        });
                    }
                }
            });
        },

        handleFormSubmit: function(e) {
            const $form = $(e.target);
            const $submitBtn = $form.find('button[type="submit"]');
            
            // Отключаем кнопку отправки
            $submitBtn.prop('disabled', true);
            
            // Показываем индикатор загрузки
            const originalText = $submitBtn.text();
            $submitBtn.text('Сохранение...');
            
            // Через 3 секунды возвращаем кнопку в исходное состояние
            setTimeout(function() {
                $submitBtn.prop('disabled', false).text(originalText);
            }, 3000);
        },

        confirmDelete: function(e) {
            e.preventDefault();
            
            const $link = $(e.currentTarget);
            const itemName = $link.data('name') || 'элемент';
            
            if (confirm('Вы уверены, что хотите удалить ' + itemName + '?')) {
                window.location.href = $link.attr('href');
            }
        },

        toggleScheduleDay: function(e) {
            const $checkbox = $(e.target);
            const $day = $checkbox.closest('.cf-schedule-day');
            const $timeInputs = $day.find('input[type="time"]');

            if ($checkbox.is(':checked')) {
                $timeInputs.prop('disabled', false);
                $day.removeClass('cf-disabled');

                // Set default times if empty
                const $startTime = $day.find('input[name*="[start_time]"]');
                const $endTime = $day.find('input[name*="[end_time]"]');

                if (!$startTime.val()) $startTime.val('09:00');
                if (!$endTime.val()) $endTime.val('18:00');
            } else {
                $timeInputs.prop('disabled', true);
                $day.addClass('cf-disabled');

                // Clear break times when day is disabled
                $day.find('input[name*="[break_start]"]').val('');
                $day.find('input[name*="[break_end]"]').val('');
            }
        },

        applySchedulePreset: function(e) {
            e.preventDefault();

            const $btn = $(e.currentTarget);
            const preset = $btn.data('preset');
            const startTime = $btn.data('start');
            const endTime = $btn.data('end');
            const breakStart = $btn.data('break-start');
            const breakEnd = $btn.data('break-end');

            // Clear all schedules first
            $('.cf-schedule-working').prop('checked', false).trigger('change');

            let daysToSet = [];

            switch (preset) {
                case 'weekdays':
                    daysToSet = [1, 2, 3, 4, 5]; // Monday to Friday
                    break;
                case 'all':
                    daysToSet = [0, 1, 2, 3, 4, 5, 6]; // All days
                    break;
                case 'weekend':
                    daysToSet = [0, 6]; // Sunday and Saturday
                    break;
            }

            daysToSet.forEach(day => {
                const $dayContainer = $('.cf-schedule-day[data-day="' + day + '"]');
                const $checkbox = $dayContainer.find('.cf-schedule-working');

                // Enable the day
                $checkbox.prop('checked', true).trigger('change');

                // Set times
                $dayContainer.find('input[name*="[start_time]"]').val(startTime);
                $dayContainer.find('input[name*="[end_time]"]').val(endTime);
                $dayContainer.find('input[name*="[break_start]"]').val(breakStart);
                $dayContainer.find('input[name*="[break_end]"]').val(breakEnd);
            });
        },

        applyFilters: function() {
            const $form = $('.cf-filters form');
            
            if ($form.length) {
                $form.submit();
            }
        },

        toggleSelectAll: function(e) {
            const $checkbox = $(e.target);
            const isChecked = $checkbox.is(':checked');
            
            $('.cf-item-checkbox').prop('checked', isChecked);
        },

        handleBulkAction: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const action = $button.data('action');
            const $checkedItems = $('.cf-item-checkbox:checked');
            
            if ($checkedItems.length === 0) {
                alert('Выберите элементы для выполнения действия');
                return;
            }
            
            const itemIds = [];
            $checkedItems.each(function() {
                itemIds.push($(this).val());
            });
            
            if (confirm('Выполнить действие "' + $button.text() + '" для выбранных элементов?')) {
                $.ajax({
                    url: chronoForgeAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'chrono_forge_bulk_' + action,
                        ids: itemIds,
                        nonce: chronoForgeAdmin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Ошибка: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('Произошла ошибка при выполнении действия');
                    }
                });
            }
        }
    };

    // Объект для работы с календарем
    const ChronoForgeCalendar = {
        calendar: null,

        init: function() {
            if ($('#cf-calendar').length) {
                this.initFullCalendar();
            }
        },

        initFullCalendar: function() {
            const calendarEl = document.getElementById('cf-calendar');
            
            this.calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                locale: 'ru',
                events: {
                    url: chronoForgeAdmin.ajaxUrl,
                    method: 'POST',
                    extraParams: {
                        action: 'chrono_forge_get_calendar_events',
                        nonce: chronoForgeAdmin.nonce
                    },
                    failure: function() {
                        alert('Ошибка загрузки событий календаря');
                    }
                },
                eventClick: function(info) {
                    ChronoForgeCalendar.showEventDetails(info.event);
                },
                dateClick: function(info) {
                    ChronoForgeCalendar.createNewEvent(info.date);
                },
                eventDrop: function(info) {
                    ChronoForgeCalendar.updateEventDate(info.event, info.event.start);
                },
                eventResize: function(info) {
                    ChronoForgeCalendar.updateEventDuration(info.event, info.event.start, info.event.end);
                },
                editable: true,
                droppable: true
            });
            
            this.calendar.render();
        },

        showEventDetails: function(event) {
            // Показываем детали события в модальном окне
            const $modal = $('#cf-event-details-modal');
            
            if ($modal.length) {
                $modal.find('.cf-event-title').text(event.title);
                $modal.find('.cf-event-start').text(event.start.toLocaleString());
                $modal.find('.cf-event-end').text(event.end ? event.end.toLocaleString() : '');
                $modal.show();
            }
        },

        createNewEvent: function(date) {
            // Открываем модальное окно создания нового события
            const $modal = $('#cf-new-event-modal');
            
            if ($modal.length) {
                $modal.find('[name="appointment_date"]').val(date.toISOString().split('T')[0]);
                $modal.show();
            }
        },

        updateEventDate: function(event, newDate) {
            // Обновляем дату события через AJAX
            $.ajax({
                url: chronoForgeAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'chrono_forge_update_appointment_date',
                    appointment_id: event.id,
                    new_date: newDate.toISOString().split('T')[0],
                    new_time: newDate.toTimeString().split(' ')[0],
                    nonce: chronoForgeAdmin.nonce
                },
                success: function(response) {
                    if (!response.success) {
                        alert('Ошибка обновления: ' + response.data);
                        // Возвращаем событие на место
                        event.revert();
                    }
                },
                error: function() {
                    alert('Ошибка обновления события');
                    event.revert();
                }
            });
        },

        updateEventDuration: function(event, start, end) {
            // Обновляем продолжительность события
            $.ajax({
                url: chronoForgeAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'chrono_forge_update_appointment_duration',
                    appointment_id: event.id,
                    start_time: start.toTimeString().split(' ')[0],
                    end_time: end.toTimeString().split(' ')[0],
                    nonce: chronoForgeAdmin.nonce
                },
                success: function(response) {
                    if (!response.success) {
                        alert('Ошибка обновления: ' + response.data);
                        event.revert();
                    }
                },
                error: function() {
                    alert('Ошибка обновления события');
                    event.revert();
                }
            });
        }
    };

    // Инициализация при загрузке документа
    $(document).ready(function() {
        ChronoForgeAdmin.init();
        ChronoForgeCalendar.init();
    });

    // Закрытие модальных окон по Escape
    $(document).keydown(function(e) {
        if (e.keyCode === 27) { // Escape
            ChronoForgeAdmin.closeModal({ target: $('.cf-modal:visible')[0] });
        }
    });

})(jQuery);

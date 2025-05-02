/**
 * JavaScript для административной части плагина CryptoSchool
 *
 * @package CryptoSchool
 * @subpackage Admin
 */

(function($) {
    'use strict';

    /**
     * Инициализация медиа-загрузчика WordPress
     */
    function initMediaUploader() {
        // Инициализация загрузчика изображений
        $('.cryptoschool-media-upload-button').on('click', function(e) {
            e.preventDefault();
            
            var button = $(this);
            var previewContainer = button.siblings('.cryptoschool-thumbnail-preview');
            var hiddenInput = button.siblings('input[type="hidden"]');
            
            // Создание медиа-фрейма
            var mediaFrame = wp.media({
                title: cryptoschool_admin.media_title,
                button: {
                    text: cryptoschool_admin.media_button
                },
                multiple: false
            });
            
            // Обработка выбора изображения
            mediaFrame.on('select', function() {
                var attachment = mediaFrame.state().get('selection').first().toJSON();
                
                // Обновление превью и скрытого поля
                if (attachment.url) {
                    if (previewContainer.find('img').length === 0) {
                        previewContainer.html('<img src="' + attachment.url + '" alt="" />');
                    } else {
                        previewContainer.find('img').attr('src', attachment.url);
                    }
                    
                    hiddenInput.val(attachment.url);
                    button.text(cryptoschool_admin.media_change);
                    
                    // Показать кнопку удаления
                    button.siblings('.cryptoschool-media-remove-button').show();
                }
            });
            
            // Открытие медиа-фрейма
            mediaFrame.open();
        });
        
        // Удаление изображения
        $('.cryptoschool-media-remove-button').on('click', function(e) {
            e.preventDefault();
            
            var button = $(this);
            var previewContainer = button.siblings('.cryptoschool-thumbnail-preview');
            var hiddenInput = button.siblings('input[type="hidden"]');
            var uploadButton = button.siblings('.cryptoschool-media-upload-button');
            
            // Очистка превью и скрытого поля
            previewContainer.empty();
            hiddenInput.val('');
            uploadButton.text(cryptoschool_admin.media_select);
            
            // Скрыть кнопку удаления
            button.hide();
        });
    }

    /**
     * Инициализация сортировки элементов
     */
    function initSortable() {
        if ($.fn.sortable) {
            $('.cryptoschool-sortable').sortable({
                handle: '.cryptoschool-sortable-handle',
                update: function(event, ui) {
                    // Обновление порядковых номеров
                    $(this).find('.cryptoschool-sortable-item').each(function(index) {
                        $(this).find('.cryptoschool-sortable-order').val(index);
                    });
                }
            });
        }
    }

    /**
     * Инициализация табов
     */
    function initTabs() {
        $('.cryptoschool-tabs-nav a').on('click', function(e) {
            e.preventDefault();
            
            var tabId = $(this).attr('href');
            
            // Активация таба
            $(this).parent().addClass('active').siblings().removeClass('active');
            $(tabId).addClass('active').siblings().removeClass('active');
        });
    }

    /**
     * Инициализация динамических полей
     */
    function initDynamicFields() {
        // Добавление нового поля
        $('.cryptoschool-add-field').on('click', function(e) {
            e.preventDefault();
            
            var container = $(this).closest('.cryptoschool-dynamic-fields');
            var template = container.find('.cryptoschool-field-template').html();
            var fieldsContainer = container.find('.cryptoschool-fields-container');
            var index = fieldsContainer.find('.cryptoschool-field-item').length;
            
            // Замена плейсхолдеров индекса
            template = template.replace(/\{index\}/g, index);
            
            // Добавление нового поля
            fieldsContainer.append(template);
        });
        
        // Удаление поля
        $(document).on('click', '.cryptoschool-remove-field', function(e) {
            e.preventDefault();
            
            $(this).closest('.cryptoschool-field-item').remove();
        });
    }

    /**
     * Инициализация подсказок
     */
    function initTooltips() {
        $('.cryptoschool-tooltip').on('mouseenter', function() {
            var tooltip = $(this).find('.cryptoschool-tooltip-content');
            tooltip.fadeIn(200);
        }).on('mouseleave', function() {
            var tooltip = $(this).find('.cryptoschool-tooltip-content');
            tooltip.fadeOut(200);
        });
    }

    /**
     * Инициализация подтверждений действий
     */
    function initConfirmations() {
        $('.cryptoschool-confirm').on('click', function(e) {
            var message = $(this).data('confirm') || cryptoschool_admin.confirm_default;
            
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    }

    /**
     * Инициализация зависимых полей
     */
    function initDependentFields() {
        $('.cryptoschool-dependent-field').each(function() {
            var field = $(this);
            var dependsOn = field.data('depends-on');
            var dependsValue = field.data('depends-value');
            var dependsOperator = field.data('depends-operator') || '==';
            
            // Функция проверки зависимости
            function checkDependency() {
                var controlField = $('#' + dependsOn);
                var controlValue = controlField.val();
                var isVisible = false;
                
                // Проверка условия
                switch (dependsOperator) {
                    case '==':
                        isVisible = (controlValue == dependsValue);
                        break;
                    case '!=':
                        isVisible = (controlValue != dependsValue);
                        break;
                    case 'in':
                        isVisible = (dependsValue.indexOf(controlValue) !== -1);
                        break;
                    case 'not_in':
                        isVisible = (dependsValue.indexOf(controlValue) === -1);
                        break;
                }
                
                // Показать/скрыть поле
                if (isVisible) {
                    field.show();
                } else {
                    field.hide();
                }
            }
            
            // Проверка при загрузке
            checkDependency();
            
            // Проверка при изменении контрольного поля
            $('#' + dependsOn).on('change', checkDependency);
        });
    }

    /**
     * Инициализация всех компонентов
     */
    function init() {
        initMediaUploader();
        initSortable();
        initTabs();
        initDynamicFields();
        initTooltips();
        initConfirmations();
        initDependentFields();
    }

    // Инициализация при загрузке документа
    $(document).ready(function() {
        init();
    });

})(jQuery);

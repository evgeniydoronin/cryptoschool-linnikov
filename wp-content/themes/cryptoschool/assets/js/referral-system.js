/**
 * JavaScript для реферальной системы
 * 
 * Обрабатывает AJAX-запросы и интерактивность на странице реферальной программы
 */

(function($) {
    'use strict';

    // Объект для работы с реферальной системой
    const ReferralSystem = {
        
        // Текущие данные
        currentData: null,
        currentLinkIndex: 0,
        
        /**
         * Инициализация
         */
        init: function() {
            this.bindEvents();
            this.loadReferralData();
        },

        /**
         * Привязка событий
         */
        bindEvents: function() {
            // Кнопка "Новий код" (используем класс для более точного селектора)
            $(document).on('click', '.create-new-link-btn', this.showCreateLinkModal.bind(this));
            
            // Кнопка "Сгенерувати"
            $(document).on('click', '.generate-link-btn', this.generateReferralLink.bind(this));
            
            // Переключение между табами
            $(document).on('click', '[data-tabs-link-index]', this.switchTab.bind(this));
            
            // Изменение слайдера
            $(document).on('input', '.range-slider', this.updateSliderValues.bind(this));
            
            // Синхронизация названия таба при вводе
            $(document).on('input', '.account-discount-constructor-reference__input', this.syncTabName.bind(this));
            
            // Копирование ссылки
            $(document).on('click', '.copy-link-btn', this.copyToClipboard.bind(this));
        },

        /**
         * Загрузка данных реферальной программы
         */ 
        loadReferralData: function() {
            const self = this;
            
            $.ajax({
                url: cryptoschool_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_referral_data',
                    nonce: cryptoschool_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.currentData = response.data;
                        self.renderReferralData();
                    } else {
                        self.showError('Ошибка загрузки данных: ' + response.data);
                    }
                },
                error: function() {
                    self.showError('Ошибка соединения с сервером');
                }
            });
        },

        /**
         * Отображение данных реферальной программы
         */
        renderReferralData: function() {
            if (!this.currentData) return;
            
            // Обновляем табы с реферальными ссылками
            this.renderReferralTabs();
            
            // Создаем страницы для каждого таба
            this.renderTabPages();
            
            // Показываем данные первой ссылки
            this.showLinkData(0);
            
            // Скрываем индикатор загрузки и показываем основной контент
            $('#referral-loading').hide();
            $('.tabs').show();
        },

        /**
         * Отображение табов с реферальными ссылками
         */
        renderReferralTabs: function() {
            const links = this.currentData.links || [];
            const tabsContainer = $('.tabs__links');
            const tabsHeader = $('.tabs__header');
            
            // Очищаем существующие табы
            tabsContainer.empty();
            
            // Удаляем существующую кнопку "Новий код" если есть
            tabsHeader.find('.create-new-link-btn').remove();
            
            // Добавляем табы для каждой ссылки
            links.forEach((link, index) => {
                const isActive = index === 0 ? 'palette_active' : '';
                const tab = $(`
                    <div class="tabs__link text-small color-primary swiper-slide palette palette_blurred palette_hoverable ${isActive}" 
                         data-tabs-link-index="${index}" style="margin-right: 10px;">
                        ${link.name || 'Реф-код ' + (index + 1)}
                    </div>
                `);
                tabsContainer.append(tab);
            });
            
            // Добавляем кнопку "Новий код" если ссылок меньше 20
            if (links.length < 20) {
                const newCodeBtn = $(`
                    <button class="button button_filled button_rounded button_centered button_small create-new-link-btn">
                        <span class="button__icon icon-plus"></span>
                        <span class="text-small">Новий код</span>
                    </button>
                `);
                tabsHeader.append(newCodeBtn);
            }
        },

        /**
         * Отображение статистики
         */
        renderStatistics: function() {
            const stats = this.currentData.statistics || {};
            
            $('.account-total-statistics__item').each(function(index) {
                const $item = $(this);
                const $value = $item.find('.h5');
                
                switch(index) {
                    case 0:
                        $value.text(stats.total_invited || '0');
                        break;
                    case 1:
                        $value.text(stats.total_purchased || '0');
                        break;
                    case 2:
                        $value.text(stats.total_payments || '$0');
                        break;
                    case 3:
                        $value.text(stats.available_for_withdrawal || '$0');
                        break;
                }
            });
        },

        /**
         * Отображение последних выплат
         */
        renderRecentPayments: function() {
            const payments = this.currentData.recent_payments || [];
            const tbody = $('.account-last-payments-table .account-table-body');
            
            tbody.empty();
            
            payments.forEach(payment => {
                const row = $(`
                    <tr>
                        <td class="status-line palette">
                            <div class="status-line-indicator ${payment.status_class}"></div>
                            <div class="account-table-body__item">
                                <div class="text color-primary">
                                    <span class="hide-mobile">${payment.date}</span> ${payment.time}
                                </div>
                                <div class="text color-primary">${payment.amount}</div>
                                <div class="text ${payment.status_color}">${payment.status_text}</div>
                                <div class="text account-last-payments-table__comment hide-mobile">
                                    ${payment.comment || ''}
                                </div>
                            </div>
                        </td>
                    </tr>
                `);
                tbody.append(row);
            });
        },

        /**
         * Отображение рефералов
         */
        renderReferrals: function() {
            const referrals = this.currentData.referrals || [];
            const tbody = $('.account-my-referrals-table .account-table-body');
            
            tbody.empty();
            
            referrals.forEach(referral => {
                const row = $(`
                    <tr>
                        <td class="status-line palette">
                            <div class="status-line-indicator ${referral.status_class}"></div>
                            <div class="account-table-body__item">
                                <div class="text color-primary">
                                    <span class="hide-mobile">${referral.date}</span> ${referral.time}
                                </div>
                                <div class="text color-primary">${referral.telegram}</div>
                                <div class="text ${referral.status_color}">${referral.status_text}</div>
                            </div>
                        </td>
                    </tr>
                `);
                tbody.append(row);
            });
        },

        /**
         * Показ данных конкретной ссылки
         */
        showLinkData: function(linkIndex) {
            const links = this.currentData.links || [];
            const link = links[linkIndex];
            
            if (!link) return;
            
            this.currentLinkIndex = linkIndex;
            
            // Обновляем слайдер
            this.updateSlider(link.commission_percent, link.discount_percent);
            
            // Обновляем поле ссылки
            this.updateLinkField(link.url);
        },

        /**
         * Обновление слайдера
         */
        updateSlider: function(commission, discount) {
            // Находим только активный таб
            const activeTab = $('.tabs__page_active');
            const slider = activeTab.find('.range-slider');
            const leftValue = activeTab.find('[data-left]');
            const rightValue = activeTab.find('[data-right]');
            
            // Устанавливаем значение слайдера (commission)
            slider.val(commission);
            
            // Обновляем отображаемые значения
            leftValue.text(commission);
            rightValue.text(discount);
            
            // Также обновляем CSS-переменную для синхронизации зеленой полосы
            if (slider[0]) {
                this.updateSliderProgress(slider[0], commission);
            }
        },

        /**
         * Обновление поля ссылки
         */
        updateLinkField: function(url) {
            // Находим только поле в активном табе
            const activeTab = $('.tabs__page_active');
            const linkInput = activeTab.find('.account-discount-constructor-reference__input');
            linkInput.val(url);
        },

        /**
         * Обновление CSS-переменной --progress для синхронизации зеленой полосы
         */
        updateSliderProgress: function(slider, value) {
            const progress = (value / 40) * 100; // 40 это максимальное значение слайдера
            slider.style.setProperty('--progress', progress + '%');
        },

        /**
         * Обновление значений слайдера при изменении
         */
        updateSliderValues: function(e) {
            const $slider = $(e.target);
            const slider = e.target; // Нативный DOM элемент для работы с CSS
            const commission = Math.round(parseFloat($slider.val())); // Только целые числа
            const maxTotal = 40;
            const discount = maxTotal - commission; // Автоматически будет целым
            
            // Обновляем CSS-переменную для синхронизации зеленой полосы с индикатором
            this.updateSliderProgress(slider, commission);
            
            // Находим родительский контейнер текущего слайдера (активный таб)
            const $currentTab = $slider.closest('.tabs__page');
            
            // Обновляем отображаемые значения только в текущем табе
            $currentTab.find('[data-left]').text(commission);
            $currentTab.find('[data-right]').text(discount);
            
            // Также обновляем данные в объекте для синхронизации
            if (this.currentData && this.currentData.links) {
                const tabIndex = parseInt($currentTab.data('tabs-page-index'));
                if (this.currentData.links[tabIndex]) {
                    this.currentData.links[tabIndex].commission_percent = commission;
                    this.currentData.links[tabIndex].discount_percent = discount;
                }
            }
        },

        /**
         * Создание страниц для табов
         */
        renderTabPages: function() {
            const links = this.currentData.links || [];
            const tabsPages = $('.tabs__pages');
            
            // Очищаем существующие страницы
            tabsPages.empty();
            
            // Создаем страницу для каждой ссылки
            links.forEach((link, index) => {
                const isActive = index === 0 ? 'tabs__page_active' : '';
                const page = this.createTabPage(link, index, isActive);
                tabsPages.append(page);
            });
        },

        /**
         * Создание HTML для страницы таба
         */
        createTabPage: function(link, index, activeClass) {
            const stats = link.statistics || {};
            const payments = link.recent_payments || [];
            const referrals = link.referrals || [];
            
            // Создаем элемент страницы
            const $page = $(`
                <div class="tabs__page ${activeClass}" data-tabs-page-index="${index}">
                    <div class="account-block palette palette_blurred account-block_compressed account-discount-constructor">
                        <h5 class="account-block__title text">Конструктор знижок</h5>
                        <hr class="account-block__horizontal-row">
                        <div class="range account-discount-constructor__range">
                            <div class="range__body">
                                <div class="range__value range__value_left color-primary text">
                                    <span data-left="">${link.commission_percent}</span>%
                                </div>
                                <input class="range-slider range__control" type="range" min="0" max="40" step="1" value="${link.commission_percent}">
                                <div class="range__value range__value_right color-primary text">
                                    <span data-right="">${link.discount_percent}</span>%
                                </div>
                            </div>
                            <div class="range__footer">
                                <div class="range__caption range__caption_left text-small">Ваш REF-BACK</div>
                                <div class="range__tip text-small">
                                    <span data-left="">${link.commission_percent}</span>% на <span data-right="">${link.discount_percent}</span>%
                                </div>
                                <div class="range__caption range__caption_right text-small">Знижка реферала</div>
                            </div>
                        </div>
                        <hr class="account-block__horizontal-row account-discount-constructor__separator">
                        <div class="account-discount-constructor-reference">
                            <label for="discount-constructor-input-${index}" class="account-discount-constructor-reference__label text color-primary">
                                ${link.is_temporary ? 'Назва реферального коду' : 'Ваше реферальне посилання'}
                            </label>
                            <label for="discount-constructor-input-${index}" class="account-discount-constructor-reference__block palette palette_hide-tablet palette_hide-mobile">
                                <div class="account-discount-constructor-reference__input-block palette palette_hide-desktop">
                                    <input id="discount-constructor-input-${index}" 
                                           placeholder="${link.is_temporary ? 'Введіть назву коду' : 'Ваше посилання'}" 
                                           type="text" 
                                           class="account-discount-constructor-reference__input text-small" 
                                           value="${link.is_temporary ? link.name : link.url}"
                                           ${!link.is_temporary && link.url ? 'readonly' : ''}>
                                </div>
                                ${link.is_temporary || !link.url ? `
                                    <button class="button button_filled button_rounded button_small generate-link-btn">
                                        <span class="button__text">Сгенерувати</span>
                                    </button>
                                ` : `
                                    <button class="button button_filled button_rounded button_small copy-link-btn">
                                        <span class="button__text">Копіювати</span>
                                    </button>
                                `}
                            </label>
                        </div>
                    </div>
                    
                    <div class="account-block palette palette_blurred account-block_compressed account-total-statistics">
                        <h5 class="account-block__title text">Загальна статистика</h5>
                        <hr class="account-block__horizontal-row">
                        <div class="account-total-statistics__content">
                            <div class="account-total-statistics__item">
                                <h5 class="h5 color-primary">${stats.total_invited || '0'}</h5>
                                <div class="text-small account-total-statistics__description">Запрошено людей</div>
                            </div>
                            <div class="account-total-statistics__item">
                                <h5 class="h5 color-primary">${stats.total_purchased || '0'}</h5>
                                <div class="text-small account-total-statistics__description">Придбали програму</div>
                            </div>
                            <div class="account-total-statistics__item">
                                <h5 class="h5 color-primary">${stats.total_payments || '$0'}</h5>
                                <div class="text-small account-total-statistics__description">Загальна сума виплат</div>
                            </div>
                            <div class="account-total-statistics__item">
                                <h5 class="h5 color-primary">${stats.available_for_withdrawal || '$0'}</h5>
                                <div class="text-small account-total-statistics__description">Доступно для виведення</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="account-block palette palette_blurred account-block_compressed">
                        <h5 class="account-block__title text">Останні виплати</h5>
                        <table class="account-table account-table_status account-table_compressed account-last-payments-table">
                            <thead class="account-table-head">
                                <td class="account-table-head__column">Дата</td>
                                <td class="account-table-head__column">Сумма <span class="hide-mobile">виплати</span></td>
                                <td class="account-table-head__column">Статус <span class="hide-mobile">виплати</span></td>
                                <td class="account-table-head__column hide-mobile">Коментар</td>
                            </thead>
                            <tbody class="account-table-body">
                                ${this.renderPaymentsRows(payments)}
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="account-block palette palette_blurred account-block_compressed">
                        <h5 class="account-block__title text">Мої реферали</h5>
                        <table class="account-table account-table_status account-table_compressed account-my-referrals-table">
                            <thead class="account-table-head">
                                <td class="account-table-head__column">Дата</td>
                                <td class="account-table-head__column">Ник <span class="hide-mobile">Telegram</span></td>
                                <td class="account-table-head__column">Статус</td>
                            </thead>
                            <tbody class="account-table-body">
                                ${this.renderReferralsRows(referrals)}
                            </tbody>
                        </table>
                    </div>
                </div>
            `);
            
            // Устанавливаем начальное значение --progress для слайдера
            const slider = $page.find('.range-slider')[0];
            if (slider) {
                this.updateSliderProgress(slider, link.commission_percent);
            }
            
            return $page;
        },

        /**
         * Создание строк для таблицы выплат
         */
        renderPaymentsRows: function(payments) {
            return payments.map(payment => `
                <tr>
                    <td class="status-line palette">
                        <div class="status-line-indicator ${payment.status_class}"></div>
                        <div class="account-table-body__item">
                            <div class="text color-primary">
                                <span class="hide-mobile">${payment.date}</span> ${payment.time}
                            </div>
                            <div class="text color-primary">${payment.amount}</div>
                            <div class="text ${payment.status_color}">${payment.status_text}</div>
                            <div class="text account-last-payments-table__comment hide-mobile">
                                ${payment.comment || ''}
                            </div>
                        </div>
                    </td>
                </tr>
            `).join('');
        },

        /**
         * Создание строк для таблицы рефералов
         */
        renderReferralsRows: function(referrals) {
            return referrals.map(referral => `
                <tr>
                    <td class="status-line palette">
                        <div class="status-line-indicator ${referral.status_class}"></div>
                        <div class="account-table-body__item">
                            <div class="text color-primary">
                                <span class="hide-mobile">${referral.date}</span> ${referral.time}
                            </div>
                            <div class="text color-primary">${referral.telegram}</div>
                            <div class="text ${referral.status_color}">${referral.status_text}</div>
                        </div>
                    </td>
                </tr>
            `).join('');
        },

        /**
         * Переключение между табами
         */
        switchTab: function(e) {
            const linkIndex = parseInt($(e.target).data('tabs-link-index'));
            
            // Убираем активный класс со всех табов
            $('.tabs__link').removeClass('palette_active');
            $('.tabs__page').removeClass('tabs__page_active');
            
            // Добавляем активный класс к выбранному табу и странице
            $(e.target).addClass('palette_active');
            $(`.tabs__page[data-tabs-page-index="${linkIndex}"]`).addClass('tabs__page_active');
            
            // Обновляем CSS-переменную --progress для слайдера в новом активном табе
            const activeTab = $(`.tabs__page[data-tabs-page-index="${linkIndex}"]`);
            const slider = activeTab.find('.range-slider')[0];
            if (slider && this.currentData && this.currentData.links[linkIndex]) {
                this.updateSliderProgress(slider, this.currentData.links[linkIndex].commission_percent);
            }
            
            // Обновляем текущий индекс
            this.currentLinkIndex = linkIndex;
        },

        /**
         * Создание нового временного таба для новой ссылки
         */
        showCreateLinkModal: function() {
            // Создаем временный объект ссылки
            const tempLink = {
                id: 'temp_' + Date.now(),
                name: '',
                commission_percent: 20,
                discount_percent: 20,
                url: '',
                is_temporary: true,
                statistics: {
                    total_invited: 0,
                    total_purchased: 0,
                    total_payments: '$0',
                    available_for_withdrawal: '$0'
                },
                recent_payments: [],
                referrals: []
            };
            
            // Добавляем временную ссылку к данным
            if (!this.currentData.links) {
                this.currentData.links = [];
            }
            this.currentData.links.push(tempLink);
            
            // Перерендериваем интерфейс
            this.renderReferralTabs();
            this.renderTabPages();
            
            // Переключаемся на новый таб
            const newIndex = this.currentData.links.length - 1;
            this.switchToTab(newIndex);
        },

        /**
         * Переключение на конкретный таб программно
         */
        switchToTab: function(tabIndex) {
            // Убираем активный класс со всех табов
            $('.tabs__link').removeClass('palette_active');
            $('.tabs__page').removeClass('tabs__page_active');
            
            // Добавляем активный класс к выбранному табу и странице
            $(`.tabs__link[data-tabs-link-index="${tabIndex}"]`).addClass('palette_active');
            $(`.tabs__page[data-tabs-page-index="${tabIndex}"]`).addClass('tabs__page_active');
            
            // Обновляем CSS-переменную --progress для слайдера в новом активном табе
            const activeTab = $(`.tabs__page[data-tabs-page-index="${tabIndex}"]`);
            const slider = activeTab.find('.range-slider')[0];
            if (slider && this.currentData && this.currentData.links[tabIndex]) {
                this.updateSliderProgress(slider, this.currentData.links[tabIndex].commission_percent);
            }
            
            // Обновляем текущий индекс
            this.currentLinkIndex = tabIndex;
        },

        /**
         * Создание новой реферальной ссылки
         */
        createReferralLink: function(linkName) {
            const self = this;
            const commission = parseFloat($('.range-slider').val()) || 20;
            const discount = 40 - commission;
            
            $.ajax({
                url: cryptoschool_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'create_referral_link',
                    nonce: cryptoschool_ajax.nonce,
                    link_name: linkName,
                    commission_percent: commission,
                    discount_percent: discount
                },
                success: function(response) {
                    if (response.success) {
                        self.showSuccess('Реферальная ссылка создана успешно!');
                        // Перезагружаем данные
                        self.loadReferralData();
                    } else {
                        self.showError('Ошибка создания ссылки: ' + response.data);
                    }
                },
                error: function() {
                    self.showError('Ошибка соединения с сервером');
                }
            });
        },

        /**
         * Генерация реферальной ссылки
         */
        generateReferralLink: function(e) {
            e.preventDefault();
            
            const self = this;
            const links = this.currentData.links || [];
            const currentLink = links[this.currentLinkIndex];
            
            if (!currentLink) {
                this.showError('Ошибка: ссылка не найдена');
                return;
            }
            
            // Получаем данные из активного таба
            const activeTab = $('.tabs__page_active');
            const nameInput = activeTab.find('.account-discount-constructor-reference__input');
            const slider = activeTab.find('.range-slider');
            
            const linkName = nameInput.val().trim();
            const commission = parseFloat(slider.val()) || 20;
            const discount = 40 - commission;
            
            // Проверяем, что название введено
            if (!linkName) {
                this.showError('Введите название реферального кода');
                nameInput.focus();
                return;
            }
            
            // Если это временная ссылка, создаем новую в базе
            if (currentLink.is_temporary) {
                $.ajax({
                    url: cryptoschool_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'create_referral_link',
                        nonce: cryptoschool_ajax.nonce,
                        link_name: linkName,
                        commission_percent: commission,
                        discount_percent: discount
                    },
                    success: function(response) {
                        if (response.success) {
                            // Обновляем данные ссылки
                            currentLink.id = response.data.id;
                            currentLink.name = linkName;
                            currentLink.url = response.data.url;
                            currentLink.commission_percent = commission;
                            currentLink.discount_percent = discount;
                            currentLink.is_temporary = false;
                            
                            // Обновляем интерфейс
                            self.updateTabAfterGeneration(activeTab, currentLink);
                            
                            self.showSuccess('Реферальная ссылка создана успешно!');
                        } else {
                            self.showError('Ошибка создания ссылки: ' + response.data);
                        }
                    },
                    error: function() {
                        self.showError('Ошибка соединения с сервером');
                    }
                });
            } else {
                // Если это существующая ссылка, обновляем настройки
                $.ajax({
                    url: cryptoschool_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'update_referral_link',
                        nonce: cryptoschool_ajax.nonce,
                        link_id: currentLink.id,
                        commission_percent: commission,
                        discount_percent: discount
                    },
                    success: function(response) {
                        if (response.success) {
                            currentLink.commission_percent = commission;
                            currentLink.discount_percent = discount;
                            
                            self.showSuccess('Настройки ссылки обновлены!');
                        } else {
                            self.showError('Ошибка обновления ссылки: ' + response.data);
                        }
                    },
                    error: function() {
                        self.showError('Ошибка соединения с сервером');
                    }
                });
            }
        },

        /**
         * Обновление интерфейса таба после генерации ссылки
         */
        updateTabAfterGeneration: function(activeTab, link) {
            // Обновляем заголовок поля
            const label = activeTab.find('.account-discount-constructor-reference__label');
            label.text('Ваше реферальне посилання');
            
            // Обновляем поле ввода
            const input = activeTab.find('.account-discount-constructor-reference__input');
            input.attr('placeholder', 'Ваше посилання');
            input.val(link.url);
            input.attr('readonly', true); // Делаем поле только для чтения
            
            // Заменяем кнопку "Сгенерувати" на "Копіювати"
            const generateBtn = activeTab.find('.generate-link-btn');
            if (generateBtn.length) {
                const copyBtn = $(`
                    <button class="button button_filled button_rounded button_small copy-link-btn">
                        <span class="button__text">Копіювати</span>
                    </button>
                `);
                generateBtn.replaceWith(copyBtn);
            }
            
            // Обновляем название таба
            const tabIndex = parseInt(activeTab.data('tabs-page-index'));
            const tabLink = $(`.tabs__link[data-tabs-link-index="${tabIndex}"]`);
            tabLink.text(link.name);
        },

        /**
         * Синхронизация названия таба при вводе
         */
        syncTabName: function(e) {
            const $input = $(e.target);
            
            // Не обрабатываем readonly поля (готовые ссылки)
            if ($input.attr('readonly')) {
                return;
            }
            
            const activeTab = $input.closest('.tabs__page');
            const tabIndex = parseInt(activeTab.data('tabs-page-index'));
            const tabLink = $(`.tabs__link[data-tabs-link-index="${tabIndex}"]`);
            
            const inputValue = $input.val().trim();
            
            // Ограничиваем длину названия до 20 символов
            let displayName = inputValue || 'Новий код';
            if (displayName.length > 20) {
                displayName = displayName.substring(0, 17) + '...';
            }
            
            // Обновляем название таба
            tabLink.text(displayName);
            
            // Обновляем данные в объекте
            if (this.currentData && this.currentData.links[tabIndex]) {
                this.currentData.links[tabIndex].name = inputValue;
            }
        },

        /**
         * Копирование ссылки в буфер обмена
         */
        copyToClipboard: function(e) {
            // Находим только поле в активном табе
            const activeTab = $('.tabs__page_active');
            const linkInput = activeTab.find('.account-discount-constructor-reference__input');
            
            if (linkInput.length) {
                linkInput.select();
                document.execCommand('copy');
                // Убираем alert - копирование происходит тихо
            }
        },

        /**
         * Показ сообщения об успехе
         */
        showSuccess: function(message) {
            alert('✅ ' + message);
        },

        /**
         * Показ сообщения об ошибке
         */
        showError: function(message) {
            alert('❌ ' + message);
        }
    };

    // Инициализация при загрузке страницы
    $(document).ready(function() {
        // Проверяем, что мы на странице реферальной программы
        if ($('#referral-loading').length > 0 || $('.tabs').length > 0) {
            ReferralSystem.init();
        }
    });

})(jQuery);

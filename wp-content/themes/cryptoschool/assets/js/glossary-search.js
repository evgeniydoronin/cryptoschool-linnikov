/**
 * Glossary Search JavaScript
 * 
 * Обработка AJAX поиска по глоссарию
 */

jQuery(document).ready(function($) {
    
    // Проверяем наличие необходимых элементов
    const searchInput = $('.categories-list__search input[type="text"]');
    const searchContainer = $('.categories-list__content');
    
    if (searchInput.length === 0 || searchContainer.length === 0) {
        return; // Если элементы не найдены, выходим
    }
    
    // Сохраняем изначальный контент для восстановления
    const originalContent = searchContainer.html();
    
    let searchTimeout;
    let currentRequest;
    
    /**
     * Показать индикатор загрузки
     */
    function showLoadingIndicator() {
        const loadingHtml = `
            <div class="categories-list__section">
                <div class="categories-list__section-content">
                    <div class="categories-list__section-row">
                        <div class="categories-list__section-row-text text">
                            ${glossarySearch.translations.searching}
                        </div>
                    </div>
                </div>
            </div>
        `;
        searchContainer.html(loadingHtml);
    }
    
    /**
     * Восстановить изначальный контент
     */
    function restoreOriginalContent() {
        searchContainer.html(originalContent);
    }
    
    /**
     * Выполнить поиск
     */
    function performSearch(query) {
        // Отменяем предыдущий запрос если он есть
        if (currentRequest && currentRequest.readyState !== 4) {
            currentRequest.abort();
        }
        
        // Показываем индикатор загрузки
        showLoadingIndicator();
        
        // Подготавливаем данные для AJAX запроса
        const requestData = {
            action: 'cryptoschool_glossary_search',
            nonce: glossarySearch.nonce,
            query: query,
            page_type: glossarySearch.pageType,
            current_term: glossarySearch.currentTerm
        };
        
        // Отправляем AJAX запрос
        currentRequest = $.ajax({
            url: glossarySearch.ajaxUrl,
            type: 'POST',
            data: requestData,
            success: function(response) {
                if (response.success && response.data) {
                    // Показываем результаты поиска
                    searchContainer.html(response.data.html);
                    
                    // Добавляем информацию о количестве найденных результатов
                    if (response.data.found > 0) {
                        console.log('Найдено результатов:', response.data.found);
                    }
                } else {
                    // Показываем сообщение об ошибке
                    const errorHtml = `
                        <div class="categories-list__section">
                            <div class="categories-list__section-content">
                                <div class="categories-list__section-row">
                                    <div class="categories-list__section-row-text text">
                                        ${glossarySearch.translations.errorOccurred}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    searchContainer.html(errorHtml);
                }
            },
            error: function(xhr, status, error) {
                // Обрабатываем ошибки (кроме отмененных запросов)
                if (status !== 'abort') {
                    console.error('Ошибка поиска:', error);
                    
                    const errorHtml = `
                        <div class="categories-list__section">
                            <div class="categories-list__section-content">
                                <div class="categories-list__section-row">
                                    <div class="categories-list__section-row-text text">
                                        ${glossarySearch.translations.connectionError}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    searchContainer.html(errorHtml);
                }
            }
        });
    }
    
    /**
     * Обработчик ввода в поисковое поле
     */
    searchInput.on('input', function() {
        const query = $(this).val().trim();
        
        // Очищаем предыдущий таймер
        clearTimeout(searchTimeout);
        
        // Если поле пустое, восстанавливаем исходный контент
        if (query === '') {
            restoreOriginalContent();
            return;
        }
        
        // Если запрос меньше минимальной длины, не выполняем поиск
        if (query.length < glossarySearch.minLength) {
            return;
        }
        
        // Устанавливаем задержку перед выполнением поиска (debounce)
        searchTimeout = setTimeout(function() {
            performSearch(query);
        }, 300);
    });
    
    /**
     * Обработчик фокуса на поисковом поле
     */
    searchInput.on('focus', function() {
        const query = $(this).val().trim();
        
        // Если есть текст и достаточная длина, выполняем поиск
        if (query !== '' && query.length >= glossarySearch.minLength) {
            performSearch(query);
        }
    });
    
    /**
     * Обработчик потери фокуса (опционально)
     */
    searchInput.on('blur', function() {
        // Можно добавить логику для обработки потери фокуса
        // Например, небольшая задержка перед восстановлением исходного контента
        // если поле пустое
    });
    
    /**
     * Обработчик нажатия клавиш
     */
    searchInput.on('keydown', function(e) {
        // Обработка специальных клавиш
        if (e.key === 'Escape') {
            // При нажатии Escape очищаем поле и восстанавливаем контент
            $(this).val('');
            restoreOriginalContent();
        }
    });
    
    // Добавляем плейсхолдер с переводом
    if (searchInput.attr('placeholder') === 'Search...') {
        searchInput.attr('placeholder', glossarySearch.translations.placeholder);
    }
    
    // Логирование для отладки
    console.log('Glossary Search initialized:', {
        pageType: glossarySearch.pageType,
        currentTerm: glossarySearch.currentTerm,
        minLength: glossarySearch.minLength
    });
});
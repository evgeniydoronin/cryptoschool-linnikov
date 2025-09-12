/**
 * JavaScript для функциональности прогресса урока
 * 
 * @package CryptoSchool
 */

jQuery(document).ready(function($) {
    // Обработка чекбоксов заданий
    const taskCheckboxes = $('.checkbox__input');
    const submitButton = $('#lesson-tasks-form button[type="submit"]');
    
    // Проверяем, завершен ли урок (значение передается из PHP)
    const isLessonCompleted = window.cryptoschoolLessonData ? window.cryptoschoolLessonData.isCompleted : false;
    
    /**
     * Функция проверки, все ли задания выполнены
     */
    function checkAllTasksCompleted() {
        // Если урок уже завершен, не меняем состояние кнопки
        if (isLessonCompleted) {
            return;
        }
        
        let allCompleted = true;
        let totalCheckboxes = 0;
        let checkedCheckboxes = 0;
        
        // Проверяем каждый чекбокс
        taskCheckboxes.each(function() {
            totalCheckboxes++;
            if ($(this).prop('checked')) {
                checkedCheckboxes++;
            } else {
                allCompleted = false;
            }
        });
        
        console.log('Всего заданий: ' + totalCheckboxes + ', Выполнено: ' + checkedCheckboxes);
        
        // Активируем/деактивируем кнопку в зависимости от выполнения всех заданий
        if (allCompleted && totalCheckboxes > 0) {
            submitButton.prop('disabled', false);
            submitButton.removeClass('button_disabled');
        } else {
            submitButton.prop('disabled', true);
            submitButton.addClass('button_disabled');
        }
    }
    
    // Инициализация кнопки при загрузке страницы
    if (!isLessonCompleted) {
        // По умолчанию кнопка неактивна, если не все задания выполнены
        submitButton.prop('disabled', true);
        submitButton.addClass('button_disabled');
    }
    
    // Проверяем при загрузке страницы
    checkAllTasksCompleted();
    
    // Проверяем при изменении чекбоксов
    taskCheckboxes.on('change', function() {
        checkAllTasksCompleted();
    });
});

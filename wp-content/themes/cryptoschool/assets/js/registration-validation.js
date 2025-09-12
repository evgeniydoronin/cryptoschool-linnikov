/**
 * Валидация формы регистрации
 * Предотвращает отправку формы с некорректными данными
 */
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('register-form');
    const submitBtn = document.getElementById('register-submit-btn');
    const passwordInput = document.getElementById('user_pass');
    const passwordConfirmInput = document.getElementById('user_pass2');
    const usernameInput = document.getElementById('user_login');
    const emailInput = document.getElementById('user_email');
    const agreeCheckbox = document.getElementById('agree');
    const passwordHint = document.getElementById('password-hint');

    if (!form || !submitBtn) return;

    // Состояние валидации
    const validation = {
        username: false,
        email: false,
        password: false,
        passwordConfirm: false,
        agree: false
    };

    // Функция проверки силы пароля
    function validatePassword(password) {
        if (password.length < 8) return false;
        
        const hasLowercase = /[a-z]/.test(password);
        const hasUppercase = /[A-Z]/.test(password);
        const hasDigit = /\d/.test(password);
        const hasSpecial = /[^a-zA-Z\d]/.test(password);
        
        const complexityScore = hasLowercase + hasUppercase + hasDigit + hasSpecial;
        
        // Проверка на слабые пароли
        const weakPasswords = [
            'password', '12345678', 'qwerty123', 'admin123', 'letmein123',
            'password1', 'password123', 'qwertyuiop', '1234567890'
        ];
        
        if (weakPasswords.includes(password.toLowerCase())) return false;
        
        // Проверка, что пароль не содержит имя пользователя
        const username = usernameInput ? usernameInput.value.trim().toLowerCase() : '';
        if (username.length >= 3 && password.toLowerCase().includes(username)) {
            return false;
        }
        
        // Проверка, что пароль не содержит часть email
        const email = emailInput ? emailInput.value.trim().toLowerCase() : '';
        if (email.length >= 3) {
            const emailParts = email.split('@');
            const emailUsername = emailParts[0];
            if (emailUsername.length >= 3 && password.toLowerCase().includes(emailUsername)) {
                return false;
            }
        }
        
        return complexityScore >= 3;
    }

    // Функция обновления состояния кнопки
    function updateSubmitButton() {
        const allValid = Object.values(validation).every(v => v);
        submitBtn.disabled = !allValid;
        
        if (allValid) {
            submitBtn.style.opacity = '1';
            submitBtn.style.cursor = 'pointer';
        } else {
            submitBtn.style.opacity = '0.6';
            submitBtn.style.cursor = 'not-allowed';
        }
    }

    // Валидация имени пользователя
    if (usernameInput) {
        usernameInput.addEventListener('input', function() {
            const value = this.value.trim();
            validation.username = value.length >= 3 && value.length <= 60 && /^[a-zA-Z0-9_\-\.]+$/.test(value);
            updateSubmitButton();
        });
    }

    // Валидация email
    if (emailInput) {
        emailInput.addEventListener('input', function() {
            const value = this.value.trim();
            validation.email = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
            updateSubmitButton();
        });
    }

    // Валидация пароля
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            validation.password = validatePassword(password);
            
            // Показываем/скрываем подсказку
            if (password.length > 0 && !validation.password) {
                passwordHint.style.display = 'block';
            } else {
                passwordHint.style.display = 'none';
            }
            
            // Проверяем совпадение паролей
            if (passwordConfirmInput && passwordConfirmInput.value) {
                validation.passwordConfirm = password === passwordConfirmInput.value;
            }
            
            updateSubmitButton();
        });
    }

    // Валидация подтверждения пароля
    if (passwordConfirmInput) {
        passwordConfirmInput.addEventListener('input', function() {
            const confirmPassword = this.value;
            validation.passwordConfirm = confirmPassword === passwordInput.value && confirmPassword.length > 0;
            updateSubmitButton();
        });
    }

    // Валидация чекбокса согласия
    if (agreeCheckbox) {
        agreeCheckbox.addEventListener('change', function() {
            validation.agree = this.checked;
            updateSubmitButton();
        });
    }

    // Предотвращение отправки формы при невалидных данных
    form.addEventListener('submit', function(e) {
        const allValid = Object.values(validation).every(v => v);
        
        if (!allValid) {
            e.preventDefault();
            e.stopPropagation();
            
            // Показываем сообщение об ошибке
            let errorMessage = 'Пожалуйста, исправьте следующие ошибки:\n';
            
            if (!validation.username) errorMessage += '- Некорректное имя пользователя\n';
            if (!validation.email) errorMessage += '- Некорректный email\n';
            if (!validation.password) errorMessage += '- Слабый пароль\n';
            if (!validation.passwordConfirm) errorMessage += '- Пароли не совпадают\n';
            if (!validation.agree) errorMessage += '- Необходимо согласиться с условиями\n';
            
            alert(errorMessage);
            return false;
        }
        
        return true;
    });

    // Инициализация - проверяем начальное состояние
    updateSubmitButton();
});

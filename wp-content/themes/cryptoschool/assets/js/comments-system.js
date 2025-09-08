/**
 * Comments System JavaScript
 * 
 * Обрабатывает AJAX функциональность комментариев
 */

class CommentsSystem {
    constructor() {
        this.apiUrl = cryptoschoolComments.apiUrl;
        this.postId = cryptoschoolComments.postId;
        this.nonce = cryptoschoolComments.nonce;
        this.isLoggedIn = cryptoschoolComments.isLoggedIn;
        this.currentUserId = cryptoschoolComments.currentUserId;
        
        this.currentPage = 1;
        this.currentSort = 'newest';
        this.loading = false;
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadComments();
    }

    bindEvents() {
        // Отправка комментария
        const submitBtn = document.getElementById('submit-comment');
        if (submitBtn) {
            submitBtn.addEventListener('click', (e) => this.submitComment(e));
        }

        // Загрузить еще комментарии
        const loadMoreBtn = document.getElementById('load-more-comments');
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', (e) => this.loadMoreComments(e));
        }

        // Сортировка комментариев
        const sortBtn = document.querySelector('[data-comments-sorter]');
        if (sortBtn) {
            sortBtn.addEventListener('click', (e) => this.toggleSort(e));
        }

        // Обработка нажатия Enter в textarea
        const textarea = document.getElementById('new-comment-text');
        if (textarea) {
            textarea.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && e.ctrlKey) {
                    this.submitComment(e);
                }
            });
        }
    }

    async loadComments(page = 1, reset = false) {
        if (this.loading) return;
        
        this.loading = true;
        
        try {
            const response = await fetch(
                `${this.apiUrl}comments/${this.postId}?page=${page}&per_page=3&sort=${this.currentSort}`,
                {
                    headers: {
                        'X-WP-Nonce': this.nonce
                    }
                }
            );

            if (!response.ok) {
                throw new Error('Failed to load comments');
            }

            const data = await response.json();
            this.renderComments(data, reset);
            this.updateLoadMoreButton(data);
            this.updateCommentsCount(data.total);

        } catch (error) {
            console.error('Error loading comments:', error);
            this.showError('Ошибка загрузки комментариев');
        } finally {
            this.loading = false;
        }
    }

    async submitComment(e) {
        e.preventDefault();
        
        if (!this.isLoggedIn) {
            alert('Войдите, чтобы оставить комментарий');
            return;
        }

        const textarea = document.getElementById('new-comment-text');
        const content = textarea.value.trim();
        
        if (!content) {
            alert('Введите текст комментария');
            textarea.focus();
            return;
        }

        if (this.loading) return;
        this.loading = true;

        // Блокируем кнопку отправки
        const submitBtn = document.getElementById('submit-comment');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Отправка...';

        try {
            const response = await fetch(`${this.apiUrl}comments`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': this.nonce
                },
                body: JSON.stringify({
                    post_id: this.postId,
                    content: content,
                    parent: 0
                })
            });

            if (!response.ok) {
                throw new Error('Failed to submit comment');
            }

            const data = await response.json();
            
            if (data.success) {
                textarea.value = '';
                this.showSuccess('Комментарий добавлен!');
                
                // Добавляем новый комментарий в начало списка
                this.prependNewComment(data.comment);
                
                // Обновляем счетчик
                const currentCount = parseInt(document.querySelector('.comments-count').textContent) || 0;
                this.updateCommentsCount(currentCount + 1);
                
            } else {
                throw new Error(data.message || 'Ошибка добавления комментария');
            }

        } catch (error) {
            console.error('Error submitting comment:', error);
            this.showError('Ошибка отправки комментария');
        } finally {
            this.loading = false;
            // Восстанавливаем кнопку
            const submitBtn = document.getElementById('submit-comment');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Post';
            }
        }
    }

    async loadMoreComments(e) {
        e.preventDefault();
        
        if (this.loading) return;
        
        const loadMoreBtn = e.target;
        const originalText = loadMoreBtn.innerHTML;
        
        loadMoreBtn.disabled = true;
        loadMoreBtn.innerHTML = 'Загрузка...';
        
        this.currentPage++;
        
        try {
            await this.loadComments(this.currentPage, false);
        } finally {
            loadMoreBtn.disabled = false;
            // Восстановим текст, если кнопка еще нужна
            if (loadMoreBtn.style.display !== 'none') {
                loadMoreBtn.innerHTML = originalText;
            }
        }
    }

    async toggleSort(e) {
        e.preventDefault();
        
        const sortLabel = document.querySelector('[data-comments-sorter-label]');
        this.currentSort = this.currentSort === 'newest' ? 'oldest' : 'newest';
        sortLabel.textContent = this.currentSort === 'newest' ? 'Newest' : 'Oldest';
        
        this.currentPage = 1;
        await this.loadComments(1, true);
    }

    renderComments(data, reset = false) {
        const commentsList = document.getElementById('comments-list');
        
        if (reset) {
            commentsList.innerHTML = '';
        }

        if (data.comments.length === 0 && reset) {
            commentsList.innerHTML = '<div class="no-comments">Пока нет комментариев. Будьте первым!</div>';
            return;
        }

        data.comments.forEach(comment => {
            const commentElement = this.createCommentElement(comment);
            commentsList.appendChild(commentElement);
        });

        // Удаляем индикатор загрузки
        const loading = commentsList.querySelector('.comments-loading');
        if (loading) {
            loading.remove();
        }
    }

    prependNewComment(comment) {
        const commentsList = document.getElementById('comments-list');
        
        // Удаляем сообщение "нет комментариев" если есть
        const noComments = commentsList.querySelector('.no-comments');
        if (noComments) {
            noComments.remove();
        }
        
        // Создаем элемент комментария
        const commentElement = this.createCommentElement(comment);
        
        // Добавляем в начало списка
        commentsList.insertBefore(commentElement, commentsList.firstChild);
        
        // Анимация появления
        commentElement.style.opacity = '0';
        commentElement.style.transform = 'translateY(-10px)';
        requestAnimationFrame(() => {
            commentElement.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            commentElement.style.opacity = '1';
            commentElement.style.transform = 'translateY(0)';
        });
    }

    createCommentElement(comment) {
        const commentDiv = document.createElement('div');
        commentDiv.className = 'blog-article-comment';
        commentDiv.dataset.commentId = comment.id;

        commentDiv.innerHTML = `
            <img class="blog-article-comment__avatar" src="${comment.avatar}" alt="${comment.author}">
            <div class="blog-article-comment__content">
                <div class="text-small color-primary blog-article-comment__name">${comment.author}</div>
                <div class="text-small blog-article-comment__text">${comment.text}</div>
                <div class="blog-article-comment__actions">
                    ${comment.canLike ? `
                        <div class="blog-article-comment__action ${comment.userLiked ? 'liked' : ''}" 
                             data-action="like" data-comment-id="${comment.id}">
                            Like ${comment.likes > 0 ? `(${comment.likes})` : ''}
                        </div>
                        <div class="blog-article-comment__actions-separator"></div>
                    ` : ''}
                    ${comment.canReply ? `
                        <div class="blog-article-comment__action" data-action="reply" data-comment-id="${comment.id}">Reply</div>
                        <div class="blog-article-comment__actions-separator"></div>
                    ` : ''}
                    <div class="blog-article-comment__info">${comment.date}</div>
                </div>
                ${comment.replies && comment.replies.length > 0 ? `
                    <div class="blog-article-comment__replies">
                        ${comment.replies.map(reply => `
                            <div class="blog-article-comment" data-comment-id="${reply.id}">
                                <img class="blog-article-comment__avatar" src="${reply.avatar}" alt="${reply.author}">
                                <div class="blog-article-comment__content">
                                    <div class="text-small color-primary blog-article-comment__name">${reply.author}</div>
                                    <div class="text-small blog-article-comment__text">${reply.text}</div>
                                    <div class="blog-article-comment__actions">
                                        ${reply.canLike ? `
                                            <div class="blog-article-comment__action ${reply.userLiked ? 'liked' : ''}" 
                                                 data-action="like" data-comment-id="${reply.id}">
                                                Like ${reply.likes > 0 ? `(${reply.likes})` : ''}
                                            </div>
                                            <div class="blog-article-comment__actions-separator"></div>
                                        ` : ''}
                                        <div class="blog-article-comment__info">${reply.date}</div>
                                    </div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                ` : ''}
            </div>
        `;

        // Добавляем обработчики событий для лайков и ответов
        this.bindCommentEvents(commentDiv);

        return commentDiv;
    }

    bindCommentEvents(commentElement) {
        // Лайки
        const likeButtons = commentElement.querySelectorAll('[data-action="like"]');
        likeButtons.forEach(btn => {
            btn.addEventListener('click', (e) => this.likeComment(e));
        });

        // Ответы
        const replyButtons = commentElement.querySelectorAll('[data-action="reply"]');
        replyButtons.forEach(btn => {
            btn.addEventListener('click', (e) => this.replyToComment(e));
        });
    }

    async likeComment(e) {
        e.preventDefault();
        
        if (!this.isLoggedIn) {
            alert('Войдите, чтобы оставить лайк');
            return;
        }

        const button = e.target;
        const commentId = button.dataset.commentId;

        try {
            const response = await fetch(`${this.apiUrl}comments/${commentId}/like`, {
                method: 'POST',
                headers: {
                    'X-WP-Nonce': this.nonce
                }
            });

            if (!response.ok) {
                throw new Error('Failed to like comment');
            }

            const data = await response.json();
            
            if (data.success) {
                // Обновляем UI
                if (data.action === 'liked') {
                    button.classList.add('liked');
                } else {
                    button.classList.remove('liked');
                }
                
                const likesText = data.likes_count > 0 ? ` (${data.likes_count})` : '';
                button.textContent = `Like${likesText}`;
            }

        } catch (error) {
            console.error('Error liking comment:', error);
            this.showError('Ошибка при добавлении лайка');
        }
    }

    replyToComment(e) {
        e.preventDefault();
        
        if (!this.isLoggedIn) {
            alert('Войдите, чтобы ответить на комментарий');
            return;
        }

        const commentId = e.target.dataset.commentId;
        const commentElement = e.target.closest('.blog-article-comment');
        
        // Проверяем, есть ли уже форма ответа
        const existingForm = commentElement.querySelector('.reply-form');
        if (existingForm) {
            existingForm.remove();
            return;
        }
        
        // Убираем все другие формы ответа
        document.querySelectorAll('.reply-form').forEach(form => form.remove());
        
        // Создаем форму ответа
        this.createReplyForm(commentElement, commentId);
    }

    createReplyForm(commentElement, parentId) {
        const replyForm = document.createElement('div');
        replyForm.className = 'reply-form';
        replyForm.innerHTML = `
            <div class="blog-article-comments__compose" style="margin-top: 15px; padding-left: 20px;">
                <img class="blog-article-comments__avatar" src="${this.getCurrentUserAvatar()}" alt="Your avatar">
                <div class="blog-article-comments__textbox">
                    <textarea placeholder="Write a reply..." class="blog-article-comments__textarea reply-textarea"></textarea>
                    <div class="blog-article-comments__publish">
                        <button class="blog-article-comments__send reply-submit" data-parent-id="${parentId}">Reply</button>
                        <button class="reply-cancel" style="margin-left: 10px; background: #ccc;">Cancel</button>
                    </div>
                </div>
            </div>
        `;
        
        // Вставляем форму после actions
        const actionsElement = commentElement.querySelector('.blog-article-comment__actions');
        actionsElement.parentNode.insertBefore(replyForm, actionsElement.nextSibling);
        
        // Добавляем обработчики
        const submitBtn = replyForm.querySelector('.reply-submit');
        const cancelBtn = replyForm.querySelector('.reply-cancel');
        const textarea = replyForm.querySelector('.reply-textarea');
        
        submitBtn.addEventListener('click', (e) => this.submitReply(e));
        cancelBtn.addEventListener('click', () => replyForm.remove());
        
        // Фокус на textarea
        textarea.focus();
        
        // Обработка Ctrl+Enter
        textarea.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && e.ctrlKey) {
                this.submitReply(e, submitBtn);
            }
        });
    }

    async submitReply(e, button = null) {
        e.preventDefault();
        
        const submitBtn = button || e.target;
        const parentId = submitBtn.dataset.parentId;
        const replyForm = submitBtn.closest('.reply-form');
        const textarea = replyForm.querySelector('.reply-textarea');
        const content = textarea.value.trim();
        
        if (!content) {
            alert('Введите текст ответа');
            textarea.focus();
            return;
        }

        if (this.loading) return;
        this.loading = true;
        
        // Блокируем кнопку
        submitBtn.disabled = true;
        submitBtn.textContent = 'Отправка...';

        try {
            const response = await fetch(`${this.apiUrl}comments`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': this.nonce
                },
                body: JSON.stringify({
                    post_id: this.postId,
                    content: content,
                    parent: parentId
                })
            });

            if (!response.ok) {
                throw new Error('Failed to submit reply');
            }

            const data = await response.json();
            
            if (data.success) {
                // Добавляем ответ в DOM
                this.addReplyToComment(parentId, data.comment);
                
                // Удаляем форму
                replyForm.remove();
                
                this.showSuccess('Ответ добавлен!');
                
                // Обновляем общий счетчик комментариев
                const currentCount = parseInt(document.querySelector('.comments-count').textContent) || 0;
                this.updateCommentsCount(currentCount + 1);
                
            } else {
                throw new Error(data.message || 'Ошибка добавления ответа');
            }

        } catch (error) {
            console.error('Error submitting reply:', error);
            this.showError('Ошибка отправки ответа');
        } finally {
            this.loading = false;
            submitBtn.disabled = false;
            submitBtn.textContent = 'Reply';
        }
    }

    addReplyToComment(parentId, reply) {
        const parentComment = document.querySelector(`[data-comment-id="${parentId}"]`);
        if (!parentComment) return;
        
        // Ищем или создаем контейнер для ответов
        let repliesContainer = parentComment.querySelector('.blog-article-comment__replies');
        if (!repliesContainer) {
            repliesContainer = document.createElement('div');
            repliesContainer.className = 'blog-article-comment__replies';
            parentComment.querySelector('.blog-article-comment__content').appendChild(repliesContainer);
        }
        
        // Создаем элемент ответа
        const replyElement = document.createElement('div');
        replyElement.className = 'blog-article-comment';
        replyElement.dataset.commentId = reply.id;
        replyElement.innerHTML = `
            <img class="blog-article-comment__avatar" src="${reply.avatar}" alt="${reply.author}">
            <div class="blog-article-comment__content">
                <div class="text-small color-primary blog-article-comment__name">${reply.author}</div>
                <div class="text-small blog-article-comment__text">${reply.text}</div>
                <div class="blog-article-comment__actions">
                    ${reply.canLike ? `
                        <div class="blog-article-comment__action ${reply.userLiked ? 'liked' : ''}" 
                             data-action="like" data-comment-id="${reply.id}">
                            Like ${reply.likes > 0 ? `(${reply.likes})` : ''}
                        </div>
                        <div class="blog-article-comment__actions-separator"></div>
                    ` : ''}
                    <div class="blog-article-comment__info">${reply.date}</div>
                </div>
            </div>
        `;
        
        // Добавляем в контейнер ответов
        repliesContainer.appendChild(replyElement);
        
        // Добавляем обработчики событий
        this.bindCommentEvents(replyElement);
        
        // Анимация появления
        replyElement.style.opacity = '0';
        replyElement.style.transform = 'translateY(-10px)';
        requestAnimationFrame(() => {
            replyElement.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            replyElement.style.opacity = '1';
            replyElement.style.transform = 'translateY(0)';
        });
    }

    getCurrentUserAvatar() {
        // Получаем аватар из существующей формы комментария
        const existingAvatar = document.querySelector('.blog-article-comments__compose .blog-article-comments__avatar');
        return existingAvatar ? existingAvatar.src : '';
    }

    updateLoadMoreButton(data) {
        const loadMoreBtn = document.getElementById('load-more-comments');
        const remainingCount = document.querySelector('.remaining-count');
        
        if (data.hasMore) {
            loadMoreBtn.style.display = 'block';
            if (remainingCount) {
                remainingCount.textContent = data.total - data.loaded;
            }
        } else {
            loadMoreBtn.style.display = 'none';
        }
    }

    updateCommentsCount(total) {
        const countElement = document.querySelector('.comments-count');
        if (countElement) {
            countElement.textContent = total;
        }
    }

    showSuccess(message) {
        this.showNotification(message, 'success');
    }

    showError(message) {
        this.showNotification(message, 'error');
    }

    showNotification(message, type = 'info') {
        // Создаем простое уведомление
        const notification = document.createElement('div');
        notification.className = `comments-notification comments-notification--${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 4px;
            color: white;
            z-index: 9999;
            background-color: ${type === 'success' ? '#4CAF50' : '#f44336'};
        `;

        document.body.appendChild(notification);

        // Удаляем через 3 секунды
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 3000);
    }
}

// Инициализируем систему комментариев когда DOM готов
document.addEventListener('DOMContentLoaded', () => {
    if (typeof cryptoschoolComments !== 'undefined') {
        new CommentsSystem();
    }
});
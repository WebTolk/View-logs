/**
 * @package       View logs
 * @version       2.2.0.1
 * @Author        Sergey Tolkachyov, https://web-tolk.ru
 * @copyright     Copyright (c) 2019 - 2025 Sergey Tolkachyov. All rights reserved.
 * @license       GNU/GPL3 http://www.gnu.org/licenses/gpl-3.0.html
 * @since         1.0.0
 */

document.addEventListener('DOMContentLoaded', function () {

    ((ViewLogs) => {
        ViewLogs.init = () => {
            const token = Joomla.getOptions('csrf.token');
            const content = document.getElementById('content');

            // Инициализация поиска
            ViewLogs.initSearch();

            document.querySelectorAll('.log-item').forEach(logItem => {

                const buttons = logItem.querySelectorAll('button[data-task][data-log-filename]');

                buttons.forEach(button => {
                    button.addEventListener('click', function () {
                        // Получаем данные из data-атрибутов
                        const task = this.dataset.task;
                        const filename = this.dataset.logFilename;
                        const jlog = this.dataset.jlog;
                        const downloadType = this.dataset.downloadType;

                        // Формируем базовый URL
                        let url = `index.php?option=com_vlogs&view=item&filename=${filename}&jlog=${jlog}&${token}=1&ajax=1`;

                        // Добавляем параметр download_type если есть
                        if (downloadType) {
                            url += `&download_type=${downloadType}`;
                        }

                        // Создаем FormData
                        const formData = new FormData();
                        formData.append('task', task);
                        formData.append(token, '1');

                        // Отправляем запрос
                        Joomla.request({
                            url: url,
                            method: 'POST',
                            data: formData,
                            onSuccess: (response, xhr) => {
                                try {
                                    const data = JSON.parse(response);
                                    // Показываем сообщения об успехе/ошибке
                                    if (data.message) {

                                        if (data.success === true) {
                                            ViewLogs.logItemResult(logItem, true);
                                            Joomla.renderMessages({
                                                success: [data.message]
                                            });
                                        } else {
                                            ViewLogs.logItemResult(logItem, false);
                                            Joomla.renderMessages({
                                                warning: [data.message]
                                            });
                                        }

                                    }

                                    // Если это действие удаления и оно успешно - удаляем элемент из DOM
                                    if (task === 'item.delete' && data.success) {
                                        logItem.remove();
                                        // wait 2 sec
                                        setTimeout(function () {
                                            // Делаем запрос для обновления списка
                                            Joomla.request({
                                                url: 'index.php?option=com_vlogs&view=items&tmpl=component',
                                                method: 'GET',
                                                onSuccess: function (response) {
                                                    content.innerHTML = response;
                                                    ViewLogs.init();
                                                },
                                                onError: function (xhr) {
                                                    console.error('Error updating logs list', xhr);
                                                }
                                            }, 2000);
                                        })
                                    }

                                    // Если это загрузка - эмулируем клик по ссылке на файл
                                    if (task === 'item.download' && data.success && data.data.download_url) {

                                        ViewLogs.logItemResult(logItem, true);

                                        const link = document.createElement('a');
                                        link.href = data.data.download_url;
                                        link.download = data.data.filename;
                                        document.body.appendChild(link);
                                        link.click();
                                        document.body.removeChild(link);
                                    }
                                } catch (e) {
                                    ViewLogs.logItemResult(logItem, false);
                                    console.error('Error parsing response:', e);
                                    Joomla.renderMessages({
                                        error: ['Error parsing JSON response']
                                    });
                                }
                            },
                            onError: (xhr) => {
                                ViewLogs.logItemResult(logItem, false);
                                Joomla.renderMessages({
                                    error: ['Request failed. Look at browser console for details.']
                                });
                            }
                        });
                    });
                });
            });
        }

        ViewLogs.initSearch = () => {
            const searchInput = document.getElementById('logfilesearch');
            if (!searchInput) {
                // Если элемент поиска не найден, выходим
                return;
            }
            const logItems = document.querySelectorAll('.log-item');

            const filterLogItems = () => {
                const searchTerm = searchInput.value.toLowerCase();

                logItems.forEach(item => {
                    const linkElement = item.querySelector('[data-log-filename]');
                    if (linkElement) {
                        const filename = linkElement.getAttribute('data-log-filename').toLowerCase();
                        if (filename.includes(searchTerm)) {
                            item.classList.remove('d-none');
                        } else {
                            item.classList.add('d-none');
                        }
                    } else {
                         item.classList.add('d-none');
                    }
                });
            };

            searchInput.addEventListener('input', filterLogItems);

            filterLogItems();
        };


        ViewLogs.logItemResult = (logItem, success) => {
            logItem.classList.add(success ? 'border-success' : 'border-danger');
            setTimeout(() => {
                logItem.classList.remove(success ? 'border-success' : 'border-danger');
            }, 1000);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', ViewLogs.init);
        } else {
            ViewLogs.init();
        }

    })(window.ViewLogs = window.ViewLogs || {});
});

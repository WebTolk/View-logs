/**
 * @package       View logs
 * @version       2.0.1
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

            document.querySelectorAll('.log-item').forEach(logItem => {

                const buttons = logItem.querySelectorAll('button[data-task][data-log-filename]');

                buttons.forEach(button => {
                    button.addEventListener('click', function() {
                        // Получаем данные из data-атрибутов
                        const task = this.dataset.task;
                        const filename = this.dataset.logFilename;
                        const downloadType = this.dataset.downloadType;

                        // Формируем базовый URL
                        let url = `index.php?option=com_vlogs&view=item&filename=${filename}&${token}=1&ajax=1`;

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

                                        if(data.success === true) {
                                            Joomla.renderMessages({
                                                success: [data.message]
                                            });
                                        } else {
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
                                                onSuccess: function(response) {
                                                    content.innerHTML = response;
                                                    ViewLogs.init();
                                                },
                                                onError: function(xhr) {
                                                    console.error('Error updating logs list', xhr);
                                                }
                                            }, 2000);
                                        })

                                    }

                                    // Если это действие загрузки - обрабатываем файл
                                    if (task === 'item.download' && data.success && data.file) {
                                        // Создаем временную ссылку для скачивания
                                        const link = document.createElement('a');
                                        link.href = data.file.url;
                                        link.download = data.file.name;
                                        document.body.appendChild(link);
                                        link.click();
                                        document.body.removeChild(link);
                                    }
                                } catch (e) {
                                    console.error('Error parsing response:', e);
                                    Joomla.renderMessages([['Error processing response', 'danger']]);
                                }
                            },
                            onError: (xhr) => {
                                Joomla.renderMessages([['Request failed', 'danger']]);
                            }
                        });
                    });
                });
            });
        }


        if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ViewLogs.init);
    } else {
        ViewLogs.init();
    }

    })(window.ViewLogs = window.ViewLogs || {});

});

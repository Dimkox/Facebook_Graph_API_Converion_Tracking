<?php
// ... (начало файла с константами и функцией render_log_data без изменений) ...
define('LOG_FILE', __DIR__ . '/capi_log.txt');
define('EXPLANATIONS', [
    'event_name' => 'Название события (Lead или Purchase).', 'event_time' => 'Время события в формате Unix Timestamp.',
    'action_source' => 'Источник действия (сервер = website).', 'event_id' => 'Уникальный ID этого события для дедупликации.',
    'fbc' => 'Click ID Facebook. Главный идентификатор для сопоставления.', 'fbp' => 'Browser ID Facebook. Дополнительный идентификатор.',
    'client_ip_address' => 'IP-адрес пользователя (не хешируется).', 'client_user_agent' => 'Браузер и ОС пользователя (не хешируется).',
    'ct' => 'Город (хешированный).', 'st' => 'Регион/штат (хешированный).', 'country' => 'Код страны (хешированный).',
    'ph' => 'Номер телефона (хешированный).', 'value' => 'Сумма покупки (только для Purchase).', 'currency' => 'Валюта (только для Purchase).',
    'source' => 'Источник трафика (из макроса {source}).', 'creative_id' => 'ID креатива (из макроса {creative_id}).',
    'ad_campaign_id' => 'ID рекламной кампании (из макроса {ad_campaign_id}).',
]);
function render_log_data(array $data) {
    echo '<ul>';
    foreach ($data as $key => $value) {
        $explanation = EXPLANATIONS[$key] ?? 'Пользовательский параметр (например, sub_id).';
        echo '<li>';
        echo "<strong>{$key}:</strong> ";
        if (is_array($value)) {
            echo '<div class="nested">';
            render_log_data($value);
            echo '</div>';
        } else {
            echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
        echo "<small class='explanation'> — {$explanation}</small>";
        echo '</li>';
    }
    echo '</ul>';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Лог отправки в Facebook CAPI</title>
    <meta name="robots" content="noindex, nofollow">
    <style>
        /* ... (все стили из предыдущего шага остаются без изменений) ... */
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background-color: #1e1e1e; color: #d4d4d4; line-height: 1.6; margin: 0; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; } h1 { border-bottom: 2px solid #444; padding-bottom: 10px; }
        .log-entry { background-color: #252526; border: 1px solid #333; border-radius: 8px; margin-bottom: 20px; overflow: hidden; }
        .log-header { padding: 10px 15px; background-color: #333; font-weight: bold; display: flex; justify-content: space-between; align-items: center; }
        .log-header .status-success { color: #4CAF50; } .log-header .status-error { color: #F44336; }
        .log-body { padding: 15px; } h3 { color: #569cd6; margin-top: 0; }
        ul { list-style: none; padding-left: 0; } li { word-wrap: break-word; }
        .nested { border-left: 2px solid #444; margin-left: 10px; padding-left: 15px; }
        pre { background-color: #1a1a1a; padding: 10px; border-radius: 4px; white-space: pre-wrap; word-wrap: break-word; color: #ce9178; }
        .explanation { color: #888; } .resend-btn { background-color: #569cd6; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; transition: background-color 0.3s; }
        .resend-btn:hover { background-color: #408ac9; } .resend-btn.loading { background-color: #6c757d; cursor: not-allowed; }
        .resend-btn.success { background-color: #4CAF50; } .resend-btn.error { background-color: #F44336; }
        .attempts-counter { font-size: 12px; color: #bbb; margin-left: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Лог отправки в Facebook CAPI <button onclick="location.reload()" style="float: right; cursor: pointer;">Обновить</button></h1>
        <?php
        if (!file_exists(LOG_FILE)) {
            echo '<p>Лог-файл пока пуст. Ожидание первого постбэка...</p>';
        } else {
            $log_lines = file(LOG_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $log_lines = array_reverse($log_lines); 

            foreach ($log_lines as $line_num => $line) {
                $log = json_decode($line, true);
                if (!$log) continue;
                $status_class = $log['status'] === 'SUCCESS' ? 'status-success' : 'status-error';
                // НОВОЕ: Уникальный ID для каждого блока в логе
                $log_id = 'log-entry-' . ($log['sent_to_facebook']['event_id'] ?? $line_num);
        ?>
        <div class="log-entry" id="<?= $log_id ?>">
            <div class="log-header">
                <div>
                    <span class="timestamp"><?= htmlspecialchars($log['timestamp'], ENT_QUOTES, 'UTF-8') ?></span> |
                    Статус: <span class="status-text <?= $status_class ?>"><?= htmlspecialchars($log['status'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <span class="attempts-counter" data-log-id="<?= $log_id ?>"></span>
            </div>
            <div class="log-body">
                <h3><span style="color: #9cdcfe;">1.</span> Что было отправлено в Facebook:</h3>
                <?php render_log_data($log['sent_to_facebook']); ?>

                <h3><span style="color: #9cdcfe;">2.</span> Ответ от Facebook:</h3>
                <pre class="fb-response"><code><?= htmlspecialchars(json_encode($log['facebook_response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?></code></pre>
                
                <form class="resend-form" action="handler.php" method="POST" style="margin-top: 15px;">
                    <?php foreach ($log['incoming_request'] as $key => $value): ?>
                        <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>">
                    <?php endforeach; ?>
                    <button type="submit" class="resend-btn" data-log-id="<?= $log_id ?>">Переотправить событие</button>
                </form>
            </div>
        </div>
        <?php
            }
        }
        ?>
    </div>
    
    <script>
        // НОВОЕ: Полностью переписанный JS для динамического обновления
        const attemptsStorage = {
            get: (id) => parseInt(localStorage.getItem(id) || '0'),
            increment: (id) => {
                const count = attemptsStorage.get(id) + 1;
                localStorage.setItem(id, count);
                return count;
            },
            display: (id) => {
                const count = attemptsStorage.get(id);
                const counterElement = document.querySelector(`.attempts-counter[data-log-id="${id}"]`);
                if (counterElement) {
                    counterElement.textContent = count > 0 ? `Попыток: ${count}` : '';
                }
            }
        };

        document.querySelectorAll('.log-entry').forEach(entry => {
            attemptsStorage.display(entry.id);
        });

        document.addEventListener('submit', function(e) {
            if (!e.target || !e.target.classList.contains('resend-form')) return;
            
            e.preventDefault();
            const form = e.target;
            const button = form.querySelector('.resend-btn');
            const logId = button.dataset.logId;
            const logEntryElement = document.getElementById(logId);
            const statusElement = logEntryElement.querySelector('.status-text');
            const responseElement = logEntryElement.querySelector('.fb-response code');
            
            const originalText = button.textContent;

            button.textContent = 'Отправка...';
            button.disabled = true;
            button.className = 'resend-btn loading';

            fetch(form.action, {
                method: 'POST',
                body: new FormData(form)
            })
            .then(response => response.json())
            .then(data => {
                const count = attemptsStorage.increment(logId);
                attemptsStorage.display(logId);

                responseElement.textContent = JSON.stringify(data.fb_response, null, 4);

                if (data.success) {
                    button.textContent = 'Успешно!';
                    button.className = 'resend-btn success';
                    statusElement.textContent = 'SUCCESS';
                    statusElement.className = 'status-text status-success';
                } else {
                    button.textContent = 'Ошибка!';
                    button.className = 'resend-btn error';
                    statusElement.textContent = 'ERROR';
                    statusElement.className = 'status-text status-error';
                }
            })
            .catch(error => {
                button.textContent = 'Сетевая ошибка!';
                button.className = 'resend-btn error';
                console.error('Error:', error);
            })
            .finally(() => {
                setTimeout(() => {
                    button.textContent = originalText;
                    button.disabled = false;
                    button.classList.remove('success', 'error', 'loading');
                }, 3000);
            });
        });
    </script>
</body>
</html>
<?php

/**
 * @var string $message
 * @var string $trace
 * @var string $type
 * @var int $statusCode
 * @var string $xDebugTag
 * @var boolean $showTrace
 */

ini_set('xdebug.overdump', 1222222);

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ошибка при обработке запроса</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 20px;
            margin: 20px;
            color: #333;
        }

        .error-container {
            background-color: #ffcccc;
            padding: 20px;
            border-radius: 5px;
        }

        h2 {
            margin-top: 2em;
        }

        pre {
            background-color: #ffcccc;
            padding: 10px;
            white-space: pre-wrap;
            overflow-wrap: break-word;
        }
    </style>
</head>
<body>

<div class="error-container">
    <?php if ($statusCode >= 400 && $statusCode < 500): ?>
    <p>
        Запрос не может быть обработан<br>
        Ошибка: <strong><?= htmlspecialchars($statusCode) ?></strong><br>
        <?= htmlspecialchars($message) ?><br><br>
        Идентификатор сеанса: <?= htmlspecialchars($xDebugTag) ?>
    </p>
    <?php endif; ?>

    <?php if ($statusCode >= 500): ?>
        <p>
            Запрос не может быть обработан<br>
            Произошла внутренняя ошибка сервера<br><br>

            Обратитесь к администратору системы<br>
            support@efko.ru<br>
            В запросе укажите идентификатор сеанса<br>
            Идентификатор сеанса: <?= htmlspecialchars($xDebugTag) ?>
        </p>
    <?php endif; ?>

</div>

<?php if ($showTrace === true): ?>
    <h2>Трейс вызова</h2>
    <div class="error-container">
        <h3><?= $type . ': ' . $message ?></h3>
        <pre><?= $trace ?></pre>
    </div>
<?php endif; ?>

</body>
</html>

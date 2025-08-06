<?php

/**
 * @var Throwable $exception
 * @var string $xDebugTag
 * @var boolean $showTrace
 */

use Monoelf\Framework\http\exceptions\HttpException;

ini_set('xdebug.overdump', 1222222);

$statusCode = $exception instanceof HttpException ? $exception->getStatusCode() : 500;
$trace = str_replace(["\n", ": "], ["\n\n", ":\n"], $exception->getTraceAsString());

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
        <?= htmlspecialchars($exception->getMessage()) ?><br><br>
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
        <h3><?= $exception::class . ': ' . $exception->getMessage() ?></h3>
        <pre><?= $trace ?></pre>
    </div>
<?php endif; ?>

</body>
</html>

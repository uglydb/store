<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Запрос к API Kaspi</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
    </style>
</head>
<body>
    <h1>Сформировать запрос к API Kaspi</h1>
    <form method="get" action="">
        <label for="pageNumber">Номер страницы:</label>
        <input type="number" id="pageNumber" name="pageNumber" required>
        <br>
        <label for="pageSize">Размер страницы:</label>
        <input type="number" id="pageSize" name="pageSize" required>
        <br>
        <label for="startDate">Дата начала (ГГГГ-ММ-ДД):</label>
        <input type="date" id="startDate" name="startDate" required>
        <br>
        <label for="endDate">Дата окончания (ГГГГ-ММ-ДД):</label>
        <input type="date" id="endDate" name="endDate" required>
        <br>
        <label for="orderState">Состояние заказа:</label>
        <select id="orderState" name="orderState" required>
            <option value="NEW">Новый</option>
            <option value="SIGN_REQUIRED">Нужно подписать документы</option>
            <option value="PICKUP">Самовывоз</option>
            <option value="DELIVERY">Доставка</option>
            <option value="KASPI_DELIVERY">Kaspi Доставка</option>
            <option value="ARCHIVE">Архивный</option>
        </select>
        <br>
        <input type="submit" value="Отправить">
    </form>
    
    <?php
    if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['pageNumber']) && isset($_GET['pageSize']) && isset($_GET['orderState']) && isset($_GET['startDate']) && isset($_GET['endDate'])) {
        $pageNumber = intval($_GET['pageNumber']);
        $pageSize = intval($_GET['pageSize']);
        $orderState = $_GET['orderState'];
        $startDate = strtotime($_GET['startDate']) * 1000; // Преобразование даты в миллисекунды
        $endDate = strtotime($_GET['endDate']) * 1000; // Преобразование даты в миллисекунды

        // Проверка разницы дат
        $maxDays = 14 * 24 * 60 * 60 * 1000; // 14 дней в миллисекундах
        if ($endDate - $startDate > $maxDays) {
            echo 'Разница между датами не должна превышать 14 дней.';
            exit();
        }

        // Построение параметров запроса
        $queryParams = http_build_query([
            'page[number]' => $pageNumber,
            'page[size]' => $pageSize,
            'filter[orders][status]' => 'COMPLETED',
            'filter[orders][creationDate][$ge]' => $startDate,
            'filter[orders][creationDate][$le]' => $endDate,
            'filter[orders][state]' => $orderState
        ]);

        // Построение URL с параметрами запроса
        $apiUrl = "https://kaspi.kz/shop/api/v2/orders?$queryParams";
        
        $authToken = 'n8ouyqIkA+B05nimFNWm12159FvW/KZvoypwCz2YOoc=';
        
        // Инициализация cURL сессии
        $ch = curl_init($apiUrl);

        // Настройка опций cURL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/vnd.api+json',
            'X-Auth-Token: ' . $authToken
        ]);

        // Выполнение cURL запроса
        $response = curl_exec($ch);

        // Проверка на ошибки cURL
        if (curl_errno($ch)) {
            echo 'Ошибка:' . curl_error($ch);
            exit();
        }

        // Закрытие cURL сессии
        curl_close($ch);

        // Декодирование JSON ответа
        $data = json_decode($response, true);

        // Отладочная информация
        echo "<p>URL запроса: $apiUrl</p>";
        echo "<p>Ответ API: <pre>" . htmlspecialchars($response) . "</pre></p>";

        // Проверка валидности данных ответа
        if (!isset($data['data']) || empty($data['data'])) {
            echo 'Нет данных для отображения';
            exit();
        }

        // Отображение данных в таблице
        echo '<h2>Ответ API</h2>';
        echo '<table>';
        echo '<thead><tr><th>ID заказа</th><th>Код заказа</th><th>Общая стоимость</th><th>Дата создания</th><th>Статус</th><th>Имя клиента</th><th>Адрес доставки</th></tr></thead>';
        echo '<tbody>';
        foreach ($data['data'] as $order) {
            $attributes = $order['attributes'];
            $customer = $attributes['customer'];
            $deliveryAddress = $attributes['deliveryAddress'];
            echo '<tr>';
            echo '<td>' . htmlspecialchars($order['id']) . '</td>';
            echo '<td>' . htmlspecialchars($attributes['code']) . '</td>';
            echo '<td>' . htmlspecialchars($attributes['totalPrice']) . '</td>';
            echo '<td>' . htmlspecialchars(date('Y-m-d H:i:s', $attributes['creationDate'] / 1000)) . '</td>';
            echo '<td>' . htmlspecialchars($attributes['status']) . '</td>';
            echo '<td>' . htmlspecialchars($customer['firstName'] . ' ' . $customer['lastName']) . '</td>';
            echo '<td>' . htmlspecialchars($deliveryAddress['formattedAddress']) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }
    ?>
</body>
</html>

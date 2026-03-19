<?php
header('Content-Type: application/json');

$input = file_get_contents("php://input");
$request = json_decode($input, true);

$intentName = $request['queryResult']['intent']['displayName'] ?? '';
$queryText = strtolower(trim($request['queryResult']['queryText'] ?? ''));

require_once __DIR__ . '/../config/paths.php';
require_once ROOT . '/config/db_connect.php'; 

function sendJsonResponse(array $data): void {
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    if ($intentName !== 'SearchProduct') {
        sendJsonResponse([
            'fulfillmentText' => 'I am not set up for that request yet.'
        ]);
    }

    // Remove common filler words
    $stopWords = [
        'show', 'me', 'find', 'i', 'want', 'a', 'an', 'the',
        'for', 'do', 'you', 'have', 'search'
    ];

    $words = preg_split('/\s+/', $queryText);
    $keywords = [];

    foreach ($words as $word) {
        $word = trim($word);
        $word = preg_replace('/[^a-z0-9\-]/', '', $word);

        if ($word !== '' && !in_array($word, $stopWords, true)) {
            $keywords[] = $word;
        }
    }

    $keywords = array_values(array_unique($keywords));

    if (count($keywords) === 0) {
        sendJsonResponse([
            'fulfillmentText' => 'Please tell me what type of product you want, such as dress, hoodie, shirt, or jacket.'
        ]);
    }

    $bindings = [];
    $conditions = [];
    foreach ($keywords as $index => $keyword) {
        $conditions[] = "(p.name LIKE :kw{$index} OR p.description LIKE :kw{$index})";
        $bindings[":kw{$index}"] = '%' . $keyword . '%';
    }

    $sql = "
        SELECT p.product_id, p.name, p.description, p.price,
               COALESCE(SUM(pv.stock_quantity), 0) AS total_stock
        FROM products p
        LEFT JOIN product_variants pv ON p.product_id = pv.product_id
        WHERE (" . implode(" OR ", $conditions) . ")
        GROUP BY p.product_id, p.name, p.description, p.price
        HAVING total_stock > 0
        ORDER BY p.product_id DESC LIMIT 3
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($bindings);
    $products = $stmt->fetchAll();

    if (!$products) {
        sendJsonResponse([
            'fulfillmentText' => 'I could not find any matching products right now. Try searching with words like dress, hoodie, shirt, or jacket.'
        ]);
    }

    $reply = "I found these products for you:\n";

    foreach ($products as $product) {
        $reply .= "- " . $product['name'] . " ($" . number_format((float)$product['price'], 2) . ")\n";
    }

    sendJsonResponse([
        'fulfillmentText' => trim($reply)
    ]);

} catch (Exception $e) {
    sendJsonResponse([
        'fulfillmentText' => 'There was a problem checking the product database.'
    ]);
}
?>
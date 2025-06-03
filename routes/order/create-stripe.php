<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../globals/connection.php';
require_once __DIR__ . '/../../globals/helpers.php';
require_once __DIR__ . '/../../globals/config.php';

header('Content-Type: application/json');

// Leer el email del body si viene (JSON)
$input = json_decode(file_get_contents('php://input'), true);
$email = isset($input['email']) ? trim($input['email']) : null;

// Configura tu clave secreta de Stripe desde la configuración
\Stripe\Stripe::setApiKey($config->STRIPE_SECRET_KEY);

session_start();
$order = isset($_SESSION['order']) ? $_SESSION['order'] : null;
if (!$order || !isset($order['items']) || count($order['items']) === 0) {
    http_response_code(400);
    echo json_encode(['error' => 'No hay pedido activo.']);
    exit;
}

$total = 0;
foreach ($order['items'] as $item) {
    $price = isset($item['product_snapshot']['price']) ? $item['product_snapshot']['price'] : 0;
    $total += $price * $item['quantity'];
}

if ($total <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'El total debe ser mayor a cero.']);
    exit;
}

try {
    $params = [
        'amount' => intval($total * 100), // Stripe usa centavos
        'currency' => 'mxn', // Cambia a tu moneda
        'metadata' => [
            'order_id' => session_id(),
            'table' => $order['table'] ?? ''
        ]
    ];
    if ($email) {
        $params['receipt_email'] = $email;
    }
    $paymentIntent = \Stripe\PaymentIntent::create($params);
    echo json_encode(['client_secret' => $paymentIntent->client_secret]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

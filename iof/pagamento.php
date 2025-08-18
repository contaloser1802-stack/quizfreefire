<?php
header('Content-Type: application/json');

// Configurações BuckPay
$secretToken = 'sk_live_69b0ed89aaa545ef5e67bfcef2c3e0c4';

// Recebe dados do POST (ajuste se necessário)
$amount = isset($_POST['priceInCents']) ? intval($_POST['priceInCents']) : 1990;
$email = isset($_POST['email']) ? $_POST['email'] : 'teste@email.com';
$telefone = isset($_POST['telefone']) ? $_POST['telefone'] : '5511999999999';
$telefone = preg_replace('/\D/', '', $telefone);
if (strlen($telefone) < 12) {
    echo json_encode(['success' => false, 'error' => 'Telefone deve conter DDI + DDD + número']);
    exit;
}
$document = isset($_POST['document']) ? $_POST['document'] : '12345678909';
$nome = isset($_POST['nome']) ? $_POST['nome'] : 'Cliente Recarga';

// Captura parâmetros UTM enviados via POST
$trackingParameters = [
    'src' => $_POST['src'] ?? null,
    'sck' => $_POST['sck'] ?? null,
    'utm_source' => $_POST['utm_source'] ?? null,
    'utm_campaign' => $_POST['utm_campaign'] ?? null,
    'utm_medium' => $_POST['utm_medium'] ?? null,
    'utm_content' => $_POST['utm_content'] ?? null,
    'utm_term' => $_POST['utm_term'] ?? null,
    'xcod' => $_POST['xcod'] ?? null,
    'fbclid' => $_POST['fbclid'] ?? null,
    'gclid' => $_POST['gclid'] ?? null,
    'ttclid' => $_POST['ttclid'] ?? null
];

// Gera external_id único
$external_id = "iof-pedido-" . uniqid() . "-" . time();

// Monta dados para envio para API BuckPay
$data = [
    "external_id" => $external_id,
    "payment_method" => "pix",
    "amount" => $amount,
    "buyer" => [
        "name" => $nome,
        "email" => $email,
        "document" => $document,
        "phone" => $telefone
    ],
    "product" => [
        "id" => "recarga-freefire-iof",
        "name" => "Recarga Free Fire IOF"
    ],
    "offer" => [
        "id" => "oferta-recarga-iof",
        "name" => "Recarga Jogo IOF",
        "discount_price" => 0,
        "quantity" => 1
    ]
];

// Inicializa CURL para BuckPay
$curl = curl_init('https://api.realtechdev.com.br/v1/transactions');

curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $secretToken",
        "Content-Type: application/json",
    ],
]);

$response = curl_exec($curl);
$err = curl_error($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

// Log para debug
file_put_contents('log_buckpay_php.txt', date('Y-m-d H:i:s') . " - HTTP $httpCode\nPayload: " . json_encode($data) . "\nResponse: $response\nError: $err\n\n", FILE_APPEND);

if ($err) {
    echo json_encode(['success' => false, 'error' => "Curl error: $err"]);
    exit;
}

$result = json_decode($response, true);

if (($httpCode == 200 || $httpCode == 201) && isset($result['data'])) {
    $transactionData = $result['data'];
    
    if (isset($transactionData['pix'])) {
        // Envia dados para UTMify (pagamento pendente)
        $utmifyData = [
            'external_id' => $external_id,
            'amount' => $amount,
            'buyer' => [
                'name' => $nome,
                'email' => $email,
                'document' => $document,
                'phone' => $telefone
            ],
            'product' => [
                'id' => 'recarga-freefire-iof',
                'name' => 'Recarga Free Fire IOF'
            ],
            'offer' => [
                'id' => 'oferta-recarga-iof',
                'name' => 'Recarga Jogo IOF',
                'quantity' => 1
            ],
            'trackingParameters' => $trackingParameters
        ];
        
        // Envia para UTMify de forma assíncrona
        $utmifyPayload = json_encode($utmifyData);
        $ch = curl_init('http://localhost/iof/utmify_pendente.php');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $utmifyPayload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 5
        ]);
        curl_exec($ch);
        curl_close($ch);
        
        echo json_encode([
            'success' => true,
            'qrCodeUrl' => $transactionData['pix']['qrcode_base64'],
            'pixCode' => $transactionData['pix']['code'],
            'token' => $transactionData['id'],
            'external_id' => $external_id,
            'status' => $transactionData['status']
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'PIX não encontrado na resposta', 'response' => $result]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Erro na API BuckPay', 'response' => $result, 'http_code' => $httpCode]);
}

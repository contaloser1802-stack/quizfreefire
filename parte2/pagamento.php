<?php
header('Content-Type: application/json');

$secretToken = 'sk_live_24f5f78fec2d518816353b2f44a5465b';

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

$external_id = "pedido-" . uniqid() . "-" . time();

// Captura a URL completa da requisição
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$fullUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$referer = $_SERVER['HTTP_REFERER'] ?? 'Não informado';

// Dados do cliente para salvar no JSON
$clienteData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'external_id' => $external_id,
    'cliente' => [
        'nome' => $nome,
        'email' => $email,
        'telefone' => $telefone,
        'document' => $document
    ],
    'pagamento' => [
        'valor' => $amount,
        'valor_real' => number_format($amount / 100, 2, ',', '.')
    ],
    'urls' => [
        'url_atual' => $fullUrl,
        'url_origem' => $referer
    ],
    'utm_parameters' => $trackingParameters,
    'server_info' => [
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Não informado',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Não informado',
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'Não informado'
    ]
];

// Salva os dados do cliente no arquivo JSON
$jsonFile = 'clientes_pagamento.json';
$existingData = [];
if (file_exists($jsonFile)) {
    $existingData = json_decode(file_get_contents($jsonFile), true) ?: [];
}
$existingData[] = $clienteData;
file_put_contents($jsonFile, json_encode($existingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// Salva dados da transação temporariamente para uso no verificar.php
$transactionData = [
    'external_id' => $external_id,
    'nome' => $nome,
    'email' => $email,
    'telefone' => $telefone,
    'document' => $document,
    'valor' => $amount,
    'trackingParameters' => $trackingParameters
];

// Salva em arquivo temporário
$tempDir = __DIR__ . '/temp';
if (!file_exists($tempDir)) {
    mkdir($tempDir, 0777, true);
}
file_put_contents($tempDir . '/transaction_' . $external_id . '.json', json_encode($transactionData));

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
        "id" => "recarga-freefire",
        "name" => "Recarga Free Fire"
    ],
    "offer" => [
        "id" => "oferta-recarga",
        "name" => "Recarga Jogo",
        "discount_price" => 0,
        "quantity" => 1
    ]
];

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
                'id' => 'recarga-freefire',
                'name' => 'Recarga Free Fire'
            ],
            'offer' => [
                'id' => 'oferta-recarga',
                'name' => 'Recarga Jogo',
                'quantity' => 1
            ],
            'trackingParameters' => [
                'src' => $trackingParameters['src'],
                'sck' => $trackingParameters['sck'],
                'utm_source' => $trackingParameters['utm_source'],
                'utm_campaign' => $trackingParameters['utm_campaign'],
                'utm_medium' => $trackingParameters['utm_medium'],
                'utm_content' => $trackingParameters['utm_content'],
                'utm_term' => $trackingParameters['utm_term'],
                'xcod' => $trackingParameters['xcod'],
                'fbclid' => $trackingParameters['fbclid'],
                'gclid' => $trackingParameters['gclid'],
                'ttclid' => $trackingParameters['ttclid']
            ]
        ];
        
        // Envia para UTMify de forma assíncrona
        $utmifyPayload = json_encode($utmifyData);
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        $ch = curl_init($baseUrl . '/parte2/utmify_pendente.php');
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

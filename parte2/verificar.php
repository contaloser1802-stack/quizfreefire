<?php
header('Content-Type: application/json');

$secretToken = 'sk_live_69b0ed89aaa545ef5e67bfcef2c3e0c4';

$utmifyApiKey = 'oPNL0wMgnj3jgDWfRa5WTGvkY1zZE5tkagKA';

$id = $_GET['id'] ?? '';
$external_id = $_GET['external_id'] ?? '';

if (!$id && !$external_id) {
    echo json_encode(["success" => false, "error" => "ID ou external_id da transação não informado"]);
    exit;
}

$url = "https://api.realtechdev.com.br/v1/transactions/external_id/$id";

file_put_contents('log_url_debug.txt', "URL: $url\n", FILE_APPEND);
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $secretToken",
        "Content-Type: application/json"
    ],
]);
$response = curl_exec($curl);
$err = curl_error($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

if ($err) {
    echo json_encode(["success" => false, "error" => "Erro na requisição: $err"]);
    exit;
}

$result = json_decode($response, true);

file_put_contents('log_buckpay_verificar.txt', date('Y-m-d H:i:s') . " - HTTP $httpCode\nURL: $url\nResponse: $response\nError: $err\n\n", FILE_APPEND);

if ($httpCode != 200) {
    echo json_encode(["success" => false, "error" => "Erro na API BuckPay", "http_code" => $httpCode, "response" => $result]);
    exit;
}

// Extrai dados da resposta BuckPay
$transactionData = $result['data'] ?? $result;
$status = $transactionData['status'] ?? 'unknown';
$transactionId = $transactionData['id'] ?? $id;

if ($status === 'paid' || $status === 'approved') {
    // Recupera dados salvos temporariamente
    $tempDir = __DIR__ . '/temp';
    $tempFile = $tempDir . '/transaction_' . $id . '.json';
    
    $nome = 'Cliente';
    $email = 'sem@email.com';
    $telefone = '00000000000';
    $document = '00000000000';
    $valor = $transactionData['amount'] ?? 0;
    $trackingParameters = [];
    
    if (file_exists($tempFile)) {
        $savedData = json_decode(file_get_contents($tempFile), true);
        if ($savedData) {
            $nome = $savedData['nome'] ?? $nome;
            $email = $savedData['email'] ?? $email;
            $telefone = $savedData['telefone'] ?? $telefone;
            $document = $savedData['document'] ?? $document;
            $valor = $savedData['valor'] ?? $valor;
            $trackingParameters = $savedData['trackingParameters'] ?? [];
            
            // Remove arquivo temporário após uso
            unlink($tempFile);
        }
    }

    $utmifyData = [
        'external_id' => $transactionData['external_id'] ?? $transactionId,
        'amount' => $valor,
        'createdAt' => $transactionData['created_at'] ?? gmdate('Y-m-d H:i:s'),
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
        'trackingParameters' => $trackingParameters
    ];
    
    // Envia para UTMify de forma assíncrona
    $utmifyPayload = json_encode($utmifyData);
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
    $ch = curl_init($baseUrl . '/parte2/utmify_pago.php');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $utmifyPayload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 5
    ]);
    curl_exec($ch);
    curl_close($ch);
}

echo json_encode([
    "success" => true,
    "status" => $status,
    "transaction_id" => $transactionId,
    "external_id" => $external_id,
    "response" => $transactionData
]);

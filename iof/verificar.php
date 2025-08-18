<?php
header('Content-Type: application/json');

// Configurações BuckPay
$secretToken = 'sk_live_69b0ed89aaa545ef5e67bfcef2c3e0c4';

$id = $_GET['id'] ?? '';

if (!$id) {
    echo json_encode(["success" => false, "error" => "ID da transação não informado"]);
    exit;
}

// Inicializa CURL para verificar transação no BuckPay
$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.realtechdev.com.br/v1/transactions/external_id/$id",
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

// Log para debug
file_put_contents('log_buckpay_verificar.txt', date('Y-m-d H:i:s') . " - Verificando ID: $id - HTTP $httpCode\nResponse: $response\nError: $err\n\n", FILE_APPEND);

if ($err) {
    echo json_encode(["success" => false, "error" => "Erro na requisição: $err"]);
    exit;
}

$result = json_decode($response, true);

if ($httpCode == 200 && isset($result['data'])) {
    $transactionData = $result['data'];
    $status = $transactionData['status'] ?? 'unknown';
    
    // Se pagamento foi aprovado, envia para UTMify
    if ($status === 'paid' || $status === 'approved') {
        // Busca dados salvos do pagamento para enviar para UTMify
        $input = json_decode(file_get_contents('php://input'), true);
        $nome = $input['nome'] ?? 'Cliente';
        $email = $input['email'] ?? 'sem@email.com';
        $telefone = $input['telefone'] ?? '00000000000';
        $document = $input['document'] ?? '00000000000';
        $valor = $transactionData['amount'] ?? 0;
        
        // Captura UTMs se enviados
        $trackingParameters = [
            'src' => $input['src'] ?? null,
            'sck' => $input['sck'] ?? null,
            'utm_source' => $input['utm_source'] ?? null,
            'utm_campaign' => $input['utm_campaign'] ?? null,
            'utm_medium' => $input['utm_medium'] ?? null,
            'utm_content' => $input['utm_content'] ?? null,
            'utm_term' => $input['utm_term'] ?? null,
            'xcod' => $input['xcod'] ?? null,
            'fbclid' => $input['fbclid'] ?? null,
            'gclid' => $input['gclid'] ?? null,
            'ttclid' => $input['ttclid'] ?? null
        ];

        $utmifyData = [
            'external_id' => $transactionData['external_id'] ?? $id,
            'amount' => $valor,
            'createdAt' => $transactionData['created_at'] ?? gmdate('Y-m-d H:i:s'),
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
        $ch = curl_init('http://localhost/iof/utmify_pago.php');
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
        "transaction_id" => $id,
        "external_id" => $transactionData['external_id'] ?? '',
        "amount" => $transactionData['amount'] ?? 0,
        "payment_method" => $transactionData['payment_method'] ?? '',
        "response" => $transactionData  // opcional para debug
    ]);
} else {
    echo json_encode([
        "success" => false, 
        "error" => "Erro ao verificar transação",
        "http_code" => $httpCode,
        "response" => $result
    ]);
}

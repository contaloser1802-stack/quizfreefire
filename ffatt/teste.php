<?php
header('Content-Type: application/json');

$token = 'sk_live_heQQ4EqbP5Fk2Fnh82LtMlbCKsmptns5Hg6OkpVqnH29kxup';

$data = [
    "payment_method" => "pix",
    "amount" => 1990,  // R$ 19,90 em centavos
    "customer" => [
        "phone" => "11999999999",
        "name" => "Teste Cliente"
    ]
];

$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.assetpay.com.br/v1/charges",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $token",
        "Content-Type: application/json"
    ],
]);

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
    echo json_encode(["error" => $err]);
} else {
    echo $response;
}

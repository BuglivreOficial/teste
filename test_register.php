<?php
/**
 * Script de teste para registro de usuário
 * Execute: php test_register.php
 */

// Simula uma requisição POST para /register
$testData = [
    'email' => 'teste@exemplo.com',
    'password' => '123456',
    'metadata' => [
        'name' => 'Usuário Teste'
    ]
];

$url = 'http://localhost/register';
$jsonData = json_encode($testData);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($jsonData)
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);

echo "Testando registro de usuário...\n";
echo "URL: $url\n";
echo "Dados: $jsonData\n\n";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Código HTTP: $httpCode\n";
echo "Resposta:\n$response\n";
<?php
// Arquivo para limpar os dados dos clientes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jsonFile = 'clientes_pagamento.json';
    
    // Cria backup antes de limpar
    if (file_exists($jsonFile)) {
        $backupFile = 'backup_clientes_' . date('Y-m-d_H-i-s') . '.json';
        copy($jsonFile, $backupFile);
    }
    
    // Limpa o arquivo
    file_put_contents($jsonFile, '[]');
    
    echo json_encode(['success' => true, 'message' => 'Dados limpos com sucesso']);
} else {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
}
?>
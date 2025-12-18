<?php
// test_supplier_direct.php - Coloque na raiz do projeto e acesse via browser

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'app/config/database.php';
require_once 'app/core/Database.php';
require_once 'app/models/AccountsPayableModel.php';

session_start();
$_SESSION['company_id'] = 1; // Defina manualmente para teste

echo "<h1>🔧 TESTE DIRETO - Fornecedor</h1>";

try {
    $model = new AccountsPayableModel();
    
    echo "<h2>1. Testando conexão com banco...</h2>";
    $testConnection = $model->testConnection();
    echo $testConnection ? "✅ Conexão OK<br>" : "❌ Falha na conexão<br>";
    
    if (!$testConnection) {
        die("Parando teste - Sem conexão com banco");
    }
    
    echo "<h2>2. Testando inserção direta...</h2>";
    $testData = [
        'company_id' => 1,
        'name' => 'FORNECEDOR TESTE DIRETO',
        'fantasy_name' => 'TESTE DIRETO LTDA',
        'cpf_cnpj' => '12345678000195',
        'email' => 'teste@teste.com',
        'phone' => '(11) 9999-9999',
        'address' => 'Rua Teste, 123'
    ];
    
    $result = $model->createSupplier($testData);
    
    if ($result) {
        echo "✅ INSEÇÃO OK - Fornecedor criado com sucesso!<br>";
        
        // Verificar se está na lista
        $suppliers = $model->getSuppliersByCompany(1);
        echo "📋 Total de fornecedores: " . count($suppliers) . "<br>";
        foreach ($suppliers as $supplier) {
            echo " - " . $supplier['name'] . " (ID: " . $supplier['id'] . ")<br>";
        }
    } else {
        echo "❌ FALHA NA INSERÇÃO<br>";
        echo "Erro: " . $model->getError() . "<br>";
    }
    
} catch (Exception $e) {
    echo "💥 EXCEPTION: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>3. Verificando estrutura da tabela...</h2>";
echo "Executando: SELECT * FROM suppliers LIMIT 1<br>";

$db = new Database();
$result = $db->query("SELECT * FROM suppliers LIMIT 1");
if ($result) {
    echo "✅ Tabela suppliers existe<br>";
    $data = $result->fetch(PDO::FETCH_ASSOC);
    echo "Estrutura: " . json_encode($data) . "<br>";
} else {
    echo "❌ Tabela suppliers NÃO existe ou está vazia<br>";
}
?>
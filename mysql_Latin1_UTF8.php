<?php

header('Access-Control-Allow-Origin: ' . (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '"*"'));
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Max-Age: 1000');
header('Access-Control-Allow-Headers: Content-Type');

set_time_limit(0);

// Busca todas as tables do banco de dados
$arrayTabelas = array();
$query = "show tables";
$result = getData($query);
while($row = mysqli_fetch_array($result)){
    array_push($arrayTabelas, $row['Tables_in_mysmartclinic']);
}

// Cria uma tabela de backup antes de mexer na original
foreach($arrayTabelas as $tabela) {

    // Se já existe, só vai retornar erro e seguir o processo
    $createBackup = "CREATE TABLE ".$tabela."_BACKUP_UTF8 LIKE ".$tabela."; ";
    $createBackup .= "INSERT ".$tabela."_BACKUP_UTF8 SELECT * FROM ".$tabela."; ";
    execMultiCommand($createBackup);

    // Busca todas as colunas da tabela que sejam varchar e text e não possuam _id
    $arrayColunas = array();
    $query = "describe ".$tabela;
    $result = getData($query);
    while($row = mysqli_fetch_array($result)){
        // Se for um campo string e não for a chave
        if (stripos($row['Type'], 'varchar') === 0 || stripos($row['Type'], 'text') === 0){
            if (stripos($row['Field'], 'id_') !== 0){
                array_push($arrayColunas, $row['Field']);
            }
        }
    }

    $update = "UPDATE ".$tabela." SET ";

    $primeiraVez = 1;
    foreach($arrayColunas as $coluna) {
        if (!$primeiraVez == 1){
            $update .= ",";
        }
        $primeiraVez = 0;
        $update .= $coluna." = convert(cast(convert(".$coluna." using latin1) as binary) using utf8)";
    }
    $update .= "WHERE 1";
    execMultiCommand($update);

}

?>
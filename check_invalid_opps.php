<?php
require 'api/config/database.php';
$stmt = $pdo->query("SELECT o.id, o.titulo, ef.nome as etapa, (SELECT COUNT(1) FROM propostas WHERE oportunidade_id = o.id) as props FROM oportunidades o JOIN etapas_funil ef ON o.etapa_id = ef.id WHERE LOWER(ef.nome) IN ('fechado', 'contrato', 'homologado', 'empenhado', 'ganho', 'vendido', 'negociação', 'em negociação', 'análise') HAVING props = 0");
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(['count' => count($res)], JSON_PRETTY_PRINT);

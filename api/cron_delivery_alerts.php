<?php
// api/cron_delivery_alerts.php

// This script should be run daily via a cron job on the server.
// Example: 0 8 * * * /usr/bin/php /path/to/api/cron_delivery_alerts.php

require_once dirname(__DIR__) . '/core/database.php';
require_once dirname(__DIR__) . '/core/helpers.php';

error_log("[CRON Delivery Alerts] Starting script execution...");

try {
    // We want to find agendamentos of type 'Controle de Entrega' 
    // where data_entrega is exactly 2 days from today (today + 2 days).
    $targetDate = date('Y-m-d', strtotime('+2 days'));

    $sql = "
        SELECT a.id, a.titulo, a.data_entrega, o.titulo as oportunidade_titulo,
               u_criador.nome as criado_por_nome, u_criador.email as criado_por_email,
               GROUP_CONCAT(DISTINCT u_para.email SEPARATOR ',') as para_usuario_emails,
               GROUP_CONCAT(DISTINCT u_para.nome SEPARATOR ', ') as para_usuario_nomes
        FROM agendamentos a
        LEFT JOIN oportunidades o ON a.oportunidade_id = o.id
        LEFT JOIN usuarios u_criador ON a.criado_por_id = u_criador.id
        LEFT JOIN agendamento_usuarios au ON a.id = au.agendamento_id
        LEFT JOIN usuarios u_para ON au.usuario_id = u_para.id
        WHERE a.tipo = 'Controle de Entrega'
          AND a.data_entrega = ?
        GROUP BY a.id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$targetDate]);
    $agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($agendamentos)) {
        error_log("[CRON Delivery Alerts] No deliveries due on {$targetDate}.");
        exit(0);
    }

    foreach ($agendamentos as $ag) {
        $recipientEmails = [];

        // Collect associated users
        if (!empty($ag['para_usuario_emails'])) {
            $associatedEmails = explode(',', $ag['para_usuario_emails']);
            $recipientEmails = array_merge($recipientEmails, $associatedEmails);
        }

        // Collect creator
        if (!empty($ag['criado_por_email'])) {
            $recipientEmails[] = $ag['criado_por_email'];
        }

        $recipientEmails = array_unique(array_filter($recipientEmails));

        if (!empty($recipientEmails)) {
            $dataEntregaFormatted = date('d/m/Y', strtotime($ag['data_entrega']));

            $subject = "[CRON CRM] Alerta de Entrega Próxima: " . $ag['titulo'];
            $htmlBody = "<h2>Alerta de Entrega - Faltam 2 Dias</h2>" .
                "<p>Você tem um Controle de Entrega próximo:</p>" .
                "<ul>" .
                "<li><strong>Título:</strong> " . htmlspecialchars($ag['titulo']) . "</li>" .
                "<li><strong>Data de Entrega:</strong> " . $dataEntregaFormatted . "</li>" .
                (!empty($ag['oportunidade_titulo']) ? "<li><strong>Oportunidade Associada:</strong> " . htmlspecialchars($ag['oportunidade_titulo']) . "</li>" : "") .
                "<li><strong>Direcionado para:</strong> " . htmlspecialchars($ag['para_usuario_nomes'] ?: 'N/A') . "</li>" .
                "</ul>" .
                "<p>Verifique o funil de vendas e certifique-se de que a entrega está sob controle.</p>";

            if (function_exists('send_email_notification')) {
                if (send_email_notification($recipientEmails, $subject, $htmlBody)) {
                    error_log("[CRON Delivery Alerts] Sent alert for Agendamento ID: {$ag['id']} to: " . implode(', ', $recipientEmails));
                } else {
                    error_log("[CRON Delivery Alerts] Failed to send alert for Agendamento ID: {$ag['id']}");
                }
            } else {
                error_log("[CRON Delivery Alerts] ERROR: send_email_notification function not found.");
            }
        }
    }

    error_log("[CRON Delivery Alerts] Script finished successfully.");

} catch (Exception $e) {
    error_log("[CRON Delivery Alerts] Exception: " . $e->getMessage());
}

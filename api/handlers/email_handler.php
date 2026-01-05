 <?php
 // api/handlers/email_handler.php
require_once dirname(__DIR__) . '/core/helpers.php';
 // --- Carregamento da Biblioteca SendGrid ---

 // Opção 1: Se instalou via Composer (recomendado)
 // Descomente a linha abaixo e comente a Opção 2
 // require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

 // Opção 2: Se instalou manualmente na pasta 'lib/sendgrid-php'
 // Certifique-se de que este caminho está correto para a sua estrutura
 require_once dirname(__DIR__, 2) . '/lib/sendgrid-php/sendgrid-php.php';


 /**
  * Lida com o envio de e-mails em massa para leads selecionados.
  * Recebe uma lista de e-mails, assunto e corpo (HTML).
  * Usa a API do SendGrid para o envio.
  */
 function handle_send_bulk_email_leads($pdo, $data) {
     // --- Validação dos Dados Recebidos ---
     $recipientEmails = $data['emails'] ?? [];
     $subject = $data['subject'] ?? '';
     $body = $data['body'] ?? ''; // Corpo HTML vindo do TinyMCE

     if (empty($recipientEmails)) {
         json_response(['success' => false, 'error' => 'Nenhum destinatário fornecido.'], 400);
         return;
     }
     if (empty($subject) || empty($body)) {
         json_response(['success' => false, 'error' => 'Assunto e corpo do e-mail são obrigatórios.'], 400);
         return;
     }

     // --- Configuração do SendGrid ---
     // API Key obtida do guia SendGrid
     $sendgrid_api_key = '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'; // <<< API KEY DO SEU PDF

     // Insira o seu e-mail remetente VERIFICADO no SendGrid
     $from_email = 'marketing@frpe.app.br'; // <<< SEU EMAIL VERIFICADO
     $from_name = 'FR Produtos Médicos CRM'; // Nome que aparecerá como remetente

     // --- Preparação do Envio ---
     // Verifica se as classes SendGrid existem antes de as usar
     if (!class_exists('\SendGrid\Mail\Mail') || !class_exists('\SendGrid')) {
          error_log("Erro Crítico: As classes SendGrid não foram encontradas. Verifique o require_once no topo do email_handler.php.");
          json_response(['success' => false, 'error' => 'Erro interno do servidor ao carregar a biblioteca de envio. Contacte o administrador.'], 500);
          return; // Interrompe a execução
     }


     $email = new \SendGrid\Mail\Mail();
     $email->setFrom($from_email, $from_name);
     $email->setSubject($subject);
     // Adiciona o conteúdo HTML (vindo do TinyMCE)
     $email->addContent("text/html", $body);
     // Adiciona uma versão em texto simples (opcional, mas bom para compatibilidade)
     $email->addContent("text/plain", strip_tags($body)); // Remove tags HTML para texto simples

     // Adiciona destinatários individualmente para melhor tracking
     $validRecipientCount = 0;
     foreach ($recipientEmails as $recipient) {
         if (filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
              // Adiciona cada e-mail válido como um destinatário "To" separado.
              // O SendGrid trata isso como envios individuais na prática.
              $email->addTo($recipient);
              $validRecipientCount++;
         } else {
              error_log("Email inválido ignorado no envio em massa: " . $recipient);
         }
     }

     if ($validRecipientCount === 0) {
         json_response(['success' => false, 'error' => 'Nenhum endereço de e-mail válido encontrado para envio.'], 400);
         return;
     }

     // --- Envio via SendGrid ---
     $sendgrid = new \SendGrid($sendgrid_api_key);
     try {
         $response = $sendgrid->send($email);

         // Verifica a resposta do SendGrid (Status 2xx indica sucesso na aceitação)
         if ($response->statusCode() >= 200 && $response->statusCode() < 300) {
             json_response([
                 'success' => true,
                 'sentCount' => $validRecipientCount, // Número de emails válidos enviados para a API
                 'message' => 'Campanha enviada para processamento pela SendGrid. Status: ' . $response->statusCode()
             ]);
         } else {
             // Loga o erro detalhado no servidor
              error_log("Erro SendGrid: Status " . $response->statusCode() . " Corpo: " . $response->body());
              // Retorna um erro genérico para o cliente
              json_response(['success' => false, 'error' => 'Falha ao enviar e-mails pela SendGrid (Código: ' . $response->statusCode() . '). Verifique os logs do servidor.', 'details' => json_decode($response->body())], 500); // Tenta descodificar o corpo
         }
     } catch (Exception $e) {
         error_log("Exceção SendGrid: " . $e->getMessage());
         json_response(['success' => false, 'error' => 'Ocorreu um erro ao comunicar com a SendGrid.', 'details' => $e->getMessage()], 500);
     }
 }

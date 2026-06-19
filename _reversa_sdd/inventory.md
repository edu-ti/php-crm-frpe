# Inventário do Sistema — crm-frpe

## Estrutura de Diretórios
- `api/` (core, handlers)
- `banco-de-dados/` (dumps SQL)
- `css/` (folhas de estilo)
- `fpdf/` (biblioteca de geração de PDFs)
- `imagens/`
- `js/` (scripts client-side)
- `lib/`
- `public/`
- `scripts/`
- `sql/` (migrações e dumps)
- `uploads/`
- `vendor/` (dependências via composer)

## Tecnologias e Frameworks
- **Linguagem Principal**: PHP
- **Outras Linguagens**: HTML, CSS, JavaScript, SQL
- **Frameworks/Bibliotecas**: PHPMailer, SendGrid, FPDF
- **Gerenciador de Pacotes**: Composer

## Pontos de Entrada
- `index.php` (Frontend)
- `api.php` (API Central)
- `login.html` / `register.html` (Autenticação Web)

## Arquivos de Configuração
- `composer.json`
- `config.php`
- `.env.example`
- `.htaccess`
- `manifest.json`

## Banco de Dados
- Dumps e scripts localizados nas pastas `sql/` e `banco-de-dados/`.

## Testes
- Nenhum framework ou arquivo de testes identificado.

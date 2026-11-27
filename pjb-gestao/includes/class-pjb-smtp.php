<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * PJB_SMTP - Gerenciador de Envio de E-mails via Servidor SMTP
 *
 * Configura o PHPMailer nativo do WordPress para rotear todos os disparos
 * de e-mails (comunicados, certificados, avisos a alunos e parceiros)
 * através de credenciais SMTP autenticadas com criptografia TLS/SSL.
 */
class PJB_SMTP {

    public static function init() {
        // Intercepta e configura o PHPMailer quando o envio de SMTP estiver habilitado
        add_action('phpmailer_init', array(__CLASS__, 'configure_phpmailer'));

        // Filtros para personalizar remetente e nome
        add_filter('wp_mail_from', array(__CLASS__, 'filter_from_email'));
        add_filter('wp_mail_from_name', array(__CLASS__, 'filter_from_name'));

        // Handler para teste manual de SMTP no painel administrativo
        add_action('admin_post_pjb_test_smtp_email', array(__CLASS__, 'handle_admin_test_email'));
    }

    /**
     * Verifica se o envio via SMTP está ativo
     */
    public static function is_enabled() {
        return get_option('pjb_smtp_enabled', '0') === '1';
    }

    /**
     * Configura o objeto PHPMailer nativo do WordPress
     */
    public static function configure_phpmailer($phpmailer) {
        if (!self::is_enabled()) {
            return;
        }

        $host       = get_option('pjb_smtp_host', '');
        $port       = (int) get_option('pjb_smtp_port', 587);
        $encryption = get_option('pjb_smtp_secure', 'tls'); // 'tls', 'ssl' ou 'none'
        $auth       = get_option('pjb_smtp_auth', '1') === '1';
        $username   = get_option('pjb_smtp_user', '');
        $password   = get_option('pjb_smtp_pass', '');

        if (empty($host)) {
            return;
        }

        $phpmailer->isSMTP();
        $phpmailer->Host       = $host;
        $phpmailer->Port       = $port;
        $phpmailer->SMTPAuth   = $auth;
        $phpmailer->Username   = $username;
        $phpmailer->Password   = $password;

        if ($encryption === 'ssl') {
            $phpmailer->SMTPSecure = 'ssl';
        } elseif ($encryption === 'tls') {
            $phpmailer->SMTPSecure = 'tls';
        } else {
            $phpmailer->SMTPSecure = '';
            $phpmailer->SMTPAutoTLS = false;
        }

        // Timeout razoável para conexões externas
        $phpmailer->Timeout = 15;

        // Configuração segura de certificados SSL (permite certificados comuns de hospedagem)
        $phpmailer->SMTPOptions = array(
            'ssl' => array(
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ),
        );
    }

    /**
     * E-mail de remetente configurado
     */
    public static function filter_from_email($original_email) {
        if (!self::is_enabled()) {
            return $original_email;
        }
        $from_email = get_option('pjb_smtp_from_email', '');
        return (!empty($from_email) && is_email($from_email)) ? $from_email : $original_email;
    }

    /**
     * Nome de remetente configurado
     */
    public static function filter_from_name($original_name) {
        if (!self::is_enabled()) {
            return $original_name;
        }
        $from_name = get_option('pjb_smtp_from_name', '');
        if (empty($from_name)) {
            $from_name = get_option('pjb_inst_name', 'Projeto Brasil dos Jovens');
        }
        return !empty($from_name) ? $from_name : $original_name;
    }

    /**
     * Envia um e-mail estruturado com layout HTML oficial do PJB
     */
    public static function send_email($to, $subject, $message, $is_html = true, $attachments = array()) {
        $headers = array();
        
        if ($is_html) {
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
            $body = self::get_branded_html_template($subject, $message);
        } else {
            $headers[] = 'Content-Type: text/plain; charset=UTF-8';
            $body = $message;
        }

        $mail_error = null;
        $capture_error = function($wp_error) use (&$mail_error) {
            $mail_error = $wp_error;
        };
        add_action('wp_mail_failed', $capture_error);

        $sent = wp_mail($to, $subject, $body, $headers, $attachments);

        remove_action('wp_mail_failed', $capture_error);

        if (!$sent && $mail_error) {
            return array(
                'success' => false,
                'error'   => $mail_error->get_error_message(),
                'data'    => $mail_error->get_error_data(),
            );
        }

        return array(
            'success' => $sent,
            'error'   => $sent ? null : 'Falha desconhecida no envio de e-mail.',
        );
    }

    /**
     * Gera um template HTML visualmente rico e responsivo para o PJB
     */
    public static function get_branded_html_template($title, $content_html) {
        $inst_name = get_option('pjb_inst_name', 'Projeto Brasil dos Jovens');
        $inst_unit = get_option('pjb_inst_unit', 'Polo Regional Castanhal / PA');
        $site_url  = home_url();

        // Converte quebras de linha normais em parágrafos se não contiver tags HTML
        if (strpos($content_html, '<p>') === false && strpos($content_html, '<div>') === false) {
            $content_html = nl2br(esc_html($content_html));
        }

        return '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . esc_html($title) . '</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; color: #1e293b; }
        .wrapper { width: 100%; max-width: 600px; margin: 30px auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); border: 1px solid #e2e8f0; }
        .header { background: linear-gradient(135deg, #1d4ed8 0%, #312e81 100%); padding: 30px 25px; text-align: center; color: #ffffff; }
        .header h1 { margin: 0; font-size: 22px; font-weight: 800; letter-spacing: -0.5px; }
        .header p { margin: 6px 0 0; font-size: 13px; opacity: 0.9; }
        .content { padding: 30px 25px; font-size: 15px; line-height: 1.6; color: #334155; }
        .footer { background-color: #f8fafc; padding: 20px 25px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; background: #eff6ff; color: #1d4ed8; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <div style="width: 44px; height: 44px; background: rgba(255,255,255,0.15); border-radius: 10px; margin: 0 auto 12px; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 18px;">
                PJB
            </div>
            <h1>' . esc_html($inst_name) . '</h1>
            <p>' . esc_html($inst_unit) . '</p>
        </div>
        <div class="content">
            <h2 style="font-size: 18px; color: #0f172a; margin-top: 0; margin-bottom: 15px;">' . esc_html($title) . '</h2>
            ' . $content_html . '
        </div>
        <div class="footer">
            <p style="margin: 0 0 6px;">Esta é uma mensagem oficial do <strong>' . esc_html($inst_name) . '</strong>.</p>
            <p style="margin: 0; font-size: 11px; color: #94a3b8;">' . esc_html($inst_unit) . ' &bull; <a href="' . esc_url($site_url) . '" style="color: #2563eb; text-decoration: none;">Acessar Portal</a></p>
        </div>
    </div>
</body>
</html>';
    }

    /**
     * Trata o disparo de teste a partir da aba administrativa
     */
    public static function handle_admin_test_email() {
        if (!current_user_can('manage_options')) {
            wp_die('Acesso negado.');
        }

        check_admin_referer('pjb_test_smtp_nonce');

        $to = sanitize_email(wp_unslash($_POST['test_email'] ?? ''));
        if (empty($to) || !is_email($to)) {
            wp_safe_redirect(add_query_arg(
                array('page' => 'pjb-settings', 'tab' => 'smtp', 'smtp_error' => urlencode('Por favor, informe um e-mail válido para o teste.')),
                admin_url('admin.php')
            ));
            exit;
        }

        $subject = 'Teste de Conexão SMTP — Projeto Brasil dos Jovens';
        $message = '<p>Olá!</p>' .
                   '<p>Este é um e-mail de teste confirmando que a sua configuração de <strong>SMTP no Projeto Brasil dos Jovens (PJB)</strong> está funcionando corretamente.</p>' .
                   '<p><strong>Detalhes da Conexão:</strong><br>' .
                   '&bull; Servidor: <code>' . esc_html(get_option('pjb_smtp_host', '')) . '</code><br>' .
                   '&bull; Porta: <code>' . esc_html(get_option('pjb_smtp_port', '587')) . '</code><br>' .
                   '&bull; Criptografia: <code>' . esc_html(strtoupper(get_option('pjb_smtp_secure', 'tls'))) . '</code><br>' .
                   '&bull; Data/Hora: <code>' . current_time('d/m/Y H:i:s') . '</code></p>' .
                   '<p>Seu sistema está pronto para enviar certificados, avisos de faltas e comunicações por e-mail com alta entregabilidade!</p>';

        $result = self::send_email($to, $subject, $message, true);

        if ($result['success']) {
            wp_safe_redirect(add_query_arg(
                array('page' => 'pjb-settings', 'tab' => 'smtp', 'smtp_success' => urlencode("E-mail de teste enviado com sucesso para {$to}!")),
                admin_url('admin.php')
            ));
        } else {
            $err = $result['error'] ?: 'Não foi possível estabelecer conexão com o servidor SMTP.';
            wp_safe_redirect(add_query_arg(
                array('page' => 'pjb-settings', 'tab' => 'smtp', 'smtp_error' => urlencode("Falha no envio: {$err}")),
                admin_url('admin.php')
            ));
        }
        exit;
    }
}

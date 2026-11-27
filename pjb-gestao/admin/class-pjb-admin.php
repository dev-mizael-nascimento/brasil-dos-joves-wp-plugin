<?php
if (!defined('ABSPATH')) {
    exit;
}

class PJB_Admin {
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'register_admin_menus'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
    }

    public static function register_admin_menus() {
        // Menu Principal do PJB
        add_menu_page(
            'Projeto Brasil dos Jovens - Gestão',
            'PJB Gestão',
            'read',
            'pjb-gestao',
            array(__CLASS__, 'render_app_page'),
            'dashicons-welcome-learn-more',
            26
        );

        // Submenu: Painel do Sistema (React)
        add_submenu_page(
            'pjb-gestao',
            'Painel do Sistema',
            'Painel do Sistema',
            'read',
            'pjb-gestao',
            array(__CLASS__, 'render_app_page')
        );

        // Submenu: Configurações & Gestão de Equipe (Nativo WP)
        add_submenu_page(
            'pjb-gestao',
            'Configurações & Equipe PJB',
            'Configurações & Equipe',
            'manage_options',
            'pjb-settings',
            array(__CLASS__, 'render_settings_page')
        );
    }

    public static function enqueue_assets($hook) {
        if ($hook !== 'toplevel_page_pjb-gestao') {
            return;
        }

        // Detecta os arquivos compilados do React na pasta build/assets/
        $css_files = glob(PJB_PLUGIN_DIR . 'build/assets/*.css');
        $js_files = glob(PJB_PLUGIN_DIR . 'build/assets/*.js');

        if (!empty($css_files)) {
            $css_url = PJB_PLUGIN_URL . 'build/assets/' . basename($css_files[0]);
            wp_enqueue_style('pjb-react-css', $css_url, array(), filemtime($css_files[0]));
        }

        if (!empty($js_files)) {
            $js_url = PJB_PLUGIN_URL . 'build/assets/' . basename($js_files[0]);
            wp_enqueue_script('pjb-react-js', $js_url, array(), filemtime($js_files[0]), true);

            // Injeta configurações, Nonce de segurança e usuário autenticado
            $current_user = wp_get_current_user();
            $custom_validation_url = get_option('pjb_custom_validation_url', '');
            if (empty($custom_validation_url)) {
                $custom_validation_url = home_url('/validar');
            }

            wp_localize_script('pjb-react-js', 'pjbSettings', array(
                'apiUrl'        => esc_url_raw(rest_url('pjb/v1')),
                'nonce'         => wp_create_nonce('wp_rest'),
                'siteUrl'       => site_url(),
                'homeUrl'       => home_url(),
                'validationUrl' => esc_url_raw($custom_validation_url),
                'pluginUrl'     => PJB_PLUGIN_URL,
                'defaultTab'    => 'dashboard',
                'user'          => array(
                    'id'    => $current_user->ID,
                    'name'  => $current_user->display_name ?: $current_user->user_login,
                    'email' => $current_user->user_email,
                    'role'  => PJB_Roles::get_current_user_pjb_role(),
                ),
            ));
        }

        // Estilos para transformar o PJB Gestão em um Canvas imersivo limpo sem conflito com o menu do WP
        wp_add_inline_style('wp-admin', '
            #wpadminbar,
            #adminmenumain,
            #adminmenuback,
            #adminmenuwrap,
            #wpfooter {
                display: none !important;
            }

            html.wp-toolbar {
                padding-top: 0 !important;
            }

            #wpcontent {
                margin-left: 0 !important;
                padding: 0 !important;
            }

            #wpbody-content {
                padding: 0 !important;
                float: none !important;
            }

            .notice, div.error, div.updated, .update-nag {
                display: none !important;
            }

            #root, #pjb-root, .pjb-admin-wrap {
                width: 100% !important;
                min-height: 100vh !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            body.toplevel_page_pjb-gestao {
                background: #090d16 !important;
                overflow-x: hidden;
            }
        ');
    }

    public static function render_app_page() {
        require_once PJB_PLUGIN_DIR . 'admin/views/admin-display.php';
    }

    public static function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Acesso restrito à Coordenação Geral.');
        }

        $feedback = null;

        // 1. Ação: Criar Novo Usuário na Equipe
        if (isset($_POST['pjb_action_create_user']) && check_admin_referer('pjb_create_user_action', 'pjb_create_user_nonce')) {
            $username = sanitize_user($_POST['user_login'] ?? '');
            $email = sanitize_email($_POST['user_email'] ?? '');
            $name = sanitize_text_field($_POST['display_name'] ?? '');
            $password = $_POST['user_pass'] ?? '';
            $role = sanitize_text_field($_POST['role'] ?? 'pjb_social');

            if (empty($username) || empty($email) || empty($password)) {
                $feedback = array('type' => 'error', 'message' => 'Usuário, e-mail e senha são obrigatórios.');
            } elseif (username_exists($username)) {
                $feedback = array('type' => 'error', 'message' => 'Este nome de usuário já está em uso.');
            } elseif (email_exists($email)) {
                $feedback = array('type' => 'error', 'message' => 'Este e-mail já está cadastrado.');
            } else {
                $user_id = wp_create_user($username, $password, $email);
                if (is_wp_error($user_id)) {
                    $feedback = array('type' => 'error', 'message' => $user_id->get_error_message());
                } else {
                    $user = new WP_User($user_id);
                    $user->set_role($role);
                    if (!empty($name)) {
                        wp_update_user(array('ID' => $user_id, 'display_name' => $name, 'first_name' => $name));
                    }
                    $feedback = array('type' => 'success', 'message' => "Operador <strong>{$name}</strong> cadastrado com sucesso com a função selecionada!");
                }
            }
        }

        // 2. Ação: Alterar Função de um Usuário
        if (isset($_POST['pjb_action_update_role']) && check_admin_referer('pjb_update_role_action', 'pjb_update_role_nonce')) {
            $user_id = (int) ($_POST['user_id'] ?? 0);
            $new_role = sanitize_text_field($_POST['new_role'] ?? '');
            if ($user_id > 0 && in_array($new_role, array('pjb_admin', 'pjb_social', 'pjb_instructor', 'administrator'))) {
                $user = new WP_User($user_id);
                $user->set_role($new_role);
                $feedback = array('type' => 'success', 'message' => "Função do usuário <strong>{$user->display_name}</strong> atualizada com sucesso.");
            }
        }

        // 3. Ação: Salvar Chave da IA (Gemini) e Modelo
        if (isset($_POST['pjb_action_save_ai']) && check_admin_referer('pjb_save_ai_action', 'pjb_save_ai_nonce')) {
            $key = sanitize_text_field($_POST['pjb_gemini_api_key'] ?? '');
            $model = sanitize_text_field($_POST['pjb_gemini_model'] ?? 'gemini-2.5-flash');
            update_option('pjb_gemini_api_key', $key);
            update_option('pjb_gemini_model', $model);
            $feedback = array('type' => 'success', 'message' => 'Configurações de Inteligência Artificial salvas com sucesso.');
        }

        // 4. Ação: Testar Conexão com a IA ao Vivo
        if (isset($_POST['pjb_action_test_ai']) && check_admin_referer('pjb_test_ai_action', 'pjb_test_ai_nonce')) {
            $key = get_option('pjb_gemini_api_key', defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '');
            $model = get_option('pjb_gemini_model', 'gemini-2.5-flash');
            if (empty($key)) {
                $feedback = array('type' => 'error', 'message' => 'Nenhuma chave Gemini cadastrada para teste. Salve a chave primeiro.');
            } else {
                $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . urlencode($model) . ':generateContent?key=' . urlencode($key);
                $response = wp_remote_post($url, array(
                    'headers' => array('Content-Type' => 'application/json'),
                    'body' => json_encode(array(
                        'contents' => array(
                            array('parts' => array(array('text' => 'Responda apenas: CONEXAO_PJB_OK')))
                        )
                    )),
                    'timeout' => 12,
                ));

                if (is_wp_error($response)) {
                    $feedback = array('type' => 'error', 'message' => 'Falha na comunicação com Google AI: ' . $response->get_error_message());
                } else {
                    $code = wp_remote_retrieve_response_code($response);
                    if ($code === 200) {
                        $feedback = array('type' => 'success', 'message' => "✅ <strong>Conexão com a IA realizada com sucesso!</strong> O modelo <strong>{$model}</strong> respondeu normalmente.");
                    } else {
                        $body = json_decode(wp_remote_retrieve_body($response), true);
                        $err_msg = $body['error']['message'] ?? "Código HTTP $code";
                        $feedback = array('type' => 'error', 'message' => 'Erro na API Gemini: ' . esc_html($err_msg));
                    }
                }
            }
        }

        // 5. Ação: Salvar Integrações (WhatsApp & Gateways)
        if (isset($_POST['pjb_action_save_integrations']) && check_admin_referer('pjb_save_integrations_action', 'pjb_save_integrations_nonce')) {
            update_option('pjb_whatsapp_provider', sanitize_text_field($_POST['pjb_whatsapp_provider'] ?? 'direct'));
            update_option('pjb_whatsapp_api_url', sanitize_text_field($_POST['pjb_whatsapp_api_url'] ?? ''));
            update_option('pjb_whatsapp_api_token', sanitize_text_field($_POST['pjb_whatsapp_api_token'] ?? ''));
            update_option('pjb_whatsapp_instance', sanitize_text_field($_POST['pjb_whatsapp_instance'] ?? ''));
            $feedback = array('type' => 'success', 'message' => 'Configurações de integração e mensageria atualizadas com sucesso.');
        }

        // 6. Ação: Salvar Dados Institucionais do Polo
        if (isset($_POST['pjb_action_save_institution']) && check_admin_referer('pjb_save_institution_action', 'pjb_save_institution_nonce')) {
            update_option('pjb_inst_name', sanitize_text_field($_POST['pjb_inst_name'] ?? 'Projeto Brasil dos Jovens'));
            update_option('pjb_inst_unit', sanitize_text_field($_POST['pjb_inst_unit'] ?? 'Polo Regional Castanhal / PA'));
            update_option('pjb_inst_cnpj', sanitize_text_field($_POST['pjb_inst_cnpj'] ?? ''));
            update_option('pjb_inst_phone', sanitize_text_field($_POST['pjb_inst_phone'] ?? ''));
            update_option('pjb_inst_address', sanitize_text_field($_POST['pjb_inst_address'] ?? 'Castanhal - PA'));
            update_option('pjb_cert_signer_name', sanitize_text_field($_POST['pjb_cert_signer_name'] ?? 'Coordenação Geral PJB'));
            update_option('pjb_cert_signer_role', sanitize_text_field($_POST['pjb_cert_signer_role'] ?? 'Diretoria Executiva'));
            update_option('pjb_custom_validation_url', esc_url_raw($_POST['pjb_custom_validation_url'] ?? ''));
            $feedback = array('type' => 'success', 'message' => 'Dados institucionais, validação de QR Code e certificados salvos com sucesso.');
        }

        // 7. Ação: Exportar Backup do Banco de Dados (Download do Arquivo JSON)
        if (isset($_POST['pjb_action_export_backup']) && check_admin_referer('pjb_export_backup_action', 'pjb_export_backup_nonce')) {
            $backup = PJB_Database::export_backup();
            $filename = 'pjb_backup_' . gmdate('Y-m-d_H-i-s') . '.json';
            
            if (ob_get_length()) {
                ob_clean();
            }
            header('Content-Description: File Transfer');
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            echo json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 8. Ação: Importar Backup do Banco de Dados (Upload de Arquivo JSON)
        if (isset($_POST['pjb_action_import_backup']) && check_admin_referer('pjb_import_backup_action', 'pjb_import_backup_nonce')) {
            $mode = sanitize_text_field($_POST['import_mode'] ?? 'merge');
            if (empty($_FILES['backup_file']['tmp_name'])) {
                $feedback = array('type' => 'error', 'message' => 'Por favor, selecione um arquivo JSON de backup para importar.');
            } else {
                $file_content = file_get_contents($_FILES['backup_file']['tmp_name']);
                $parsed = json_decode($file_content, true);

                if (empty($parsed) || !is_array($parsed)) {
                    $feedback = array('type' => 'error', 'message' => 'Arquivo inválido ou corrompido. O arquivo deve ser um JSON válido gerado pelo sistema PJB.');
                } else {
                    $result = PJB_Database::import_backup($parsed, $mode);
                    if ($result['success']) {
                        $imported = $result['imported'] ?? array();
                        $total_items = array_sum($imported);
                        $mode_label = ($mode === 'replace') ? 'Substituição Completa' : 'Mesclagem (Merge)';
                        $feedback = array(
                            'type' => 'success',
                            'message' => "✅ <strong>Backup importado com sucesso! ({$mode_label})</strong> Foram processados {$total_items} registros: " .
                                "{$imported['beneficiaries']} alunos/beneficiários, {$imported['courses']} cursos, {$imported['classes']} turmas, " .
                                "{$imported['attendances']} presenças, {$imported['certificates']} certificados, {$imported['campaigns']} campanhas, {$imported['donations']} doações."
                        );
                    } else {
                        $feedback = array('type' => 'error', 'message' => 'Erro ao importar dados: ' . esc_html($result['error'] ?? 'Erro desconhecido.'));
                    }
                }
            }
        }

        // 9. Ação: Salvar Configurações de SMTP / E-mail
        if (isset($_POST['pjb_action_save_smtp']) && check_admin_referer('pjb_save_smtp_action', 'pjb_save_smtp_nonce')) {
            update_option('pjb_smtp_enabled', isset($_POST['pjb_smtp_enabled']) ? '1' : '0');
            update_option('pjb_smtp_host', sanitize_text_field(wp_unslash($_POST['pjb_smtp_host'] ?? '')));
            update_option('pjb_smtp_port', (int) ($_POST['pjb_smtp_port'] ?? 587));
            update_option('pjb_smtp_secure', sanitize_text_field(wp_unslash($_POST['pjb_smtp_secure'] ?? 'tls')));
            update_option('pjb_smtp_auth', isset($_POST['pjb_smtp_auth']) ? '1' : '0');
            update_option('pjb_smtp_user', sanitize_text_field(wp_unslash($_POST['pjb_smtp_user'] ?? '')));
            
            // Só altera a senha caso um novo valor tenha sido digitado
            if (!empty($_POST['pjb_smtp_pass'])) {
                update_option('pjb_smtp_pass', wp_unslash($_POST['pjb_smtp_pass']));
            }
            
            update_option('pjb_smtp_from_email', sanitize_email(wp_unslash($_POST['pjb_smtp_from_email'] ?? '')));
            update_option('pjb_smtp_from_name', sanitize_text_field(wp_unslash($_POST['pjb_smtp_from_name'] ?? '')));

            $feedback = array('type' => 'success', 'message' => 'Configurações de Servidor SMTP salvas com sucesso.');
        }

        if (isset($_GET['smtp_success'])) {
            $feedback = array('type' => 'success', 'message' => '✅ ' . esc_html(urldecode(sanitize_text_field(wp_unslash($_GET['smtp_success'])))));
        }

        if (isset($_GET['smtp_error'])) {
            $feedback = array('type' => 'error', 'message' => '⚠️ ' . esc_html(urldecode(sanitize_text_field(wp_unslash($_GET['smtp_error'])))));
        }

        require_once PJB_PLUGIN_DIR . 'admin/views/settings-display.php';
    }
}

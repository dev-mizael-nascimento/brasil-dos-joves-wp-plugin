<?php
if (!defined('ABSPATH')) {
    exit;
}

$current_tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'users';

// Carrega dados de IA
$gemini_key = get_option('pjb_gemini_api_key', '');
$gemini_model = get_option('pjb_gemini_model', 'gemini-2.5-flash');
$has_constant_key = defined('GEMINI_API_KEY') && !empty(GEMINI_API_KEY);

// Carrega integrações
$wa_provider = get_option('pjb_whatsapp_provider', 'direct');
$wa_api_url = get_option('pjb_whatsapp_api_url', '');
$wa_api_token = get_option('pjb_whatsapp_api_token', '');
$wa_instance = get_option('pjb_whatsapp_instance', '');

// Carrega dados institucionais
$inst_name = get_option('pjb_inst_name', 'Projeto Brasil dos Jovens');
$inst_unit = get_option('pjb_inst_unit', 'Polo Regional Castanhal / PA');
$inst_cnpj = get_option('pjb_inst_cnpj', '');
$inst_phone = get_option('pjb_inst_phone', '(91) 98000-0000');
$inst_address = get_option('pjb_inst_address', 'Castanhal - PA');
$cert_signer_name = get_option('pjb_cert_signer_name', 'Coordenação Geral PJB');
$cert_signer_role = get_option('pjb_cert_signer_role', 'Diretoria Executiva');
$custom_validation_url = get_option('pjb_custom_validation_url', '');

// Carrega configurações de SMTP
$smtp_enabled   = get_option('pjb_smtp_enabled', '0');
$smtp_host      = get_option('pjb_smtp_host', '');
$smtp_port      = get_option('pjb_smtp_port', 587);
$smtp_secure    = get_option('pjb_smtp_secure', 'tls');
$smtp_auth      = get_option('pjb_smtp_auth', '1');
$smtp_user      = get_option('pjb_smtp_user', '');
$smtp_pass      = get_option('pjb_smtp_pass', '');
$smtp_from_mail = get_option('pjb_smtp_from_email', '');
$smtp_from_name = get_option('pjb_smtp_from_name', get_option('pjb_inst_name', 'Projeto Brasil dos Jovens'));

// Carrega resumo do banco para backup
$backup_summary = PJB_Database::get_backup_summary();

// Busca usuários da equipe PJB
$users_query = new WP_User_Query(array(
    'role__in' => array('administrator', 'pjb_admin', 'pjb_social', 'pjb_instructor'),
    'orderby'  => 'display_name',
    'order'    => 'ASC',
));
$team_users = $users_query->get_results();

$count_admin = 0;
$count_social = 0;
$count_instructor = 0;

foreach ($team_users as $u) {
    if (in_array('administrator', $u->roles) || in_array('pjb_admin', $u->roles)) $count_admin++;
    if (in_array('pjb_social', $u->roles)) $count_social++;
    if (in_array('pjb_instructor', $u->roles)) $count_instructor++;
}
?>

<div class="wrap pjb-settings-wrap" style="max-width: 1100px; margin-top: 20px;">
    
    <!-- Cabeçalho Principal -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h1 style="font-size: 24px; font-weight: 700; color: #1e293b; margin: 0;">
                ⚙️ Configurações & Gestão da Equipe PJB
            </h1>
            <p style="color: #64748b; font-size: 13px; margin: 4px 0 0;">
                Central administrativa oficial do <strong>Projeto Brasil dos Jovens (Polo Castanhal/PA)</strong>
            </p>
        </div>
        <div>
            <a href="<?php echo esc_url(admin_url('admin.php?page=pjb-gestao')); ?>" class="button button-primary" style="height: 38px; line-height: 36px; padding: 0 18px; font-weight: 600; font-size: 13px; background: #2563eb; border-color: #1d4ed8;">
                ▶ Abrir Painel Interativo PJB
            </a>
        </div>
    </div>

    <!-- Feedback / Alertas -->
    <?php if (!empty($feedback)): ?>
        <div class="notice notice-<?php echo esc_attr($feedback['type']); ?> is-dismissible" style="margin-bottom: 20px;">
            <p><?php echo wp_kses_post($feedback['message']); ?></p>
        </div>
    <?php endif; ?>

    <!-- Barra de Abas de Navegação -->
    <nav class="nav-tab-wrapper" style="margin-bottom: 25px;">
        <a href="?page=pjb-settings&tab=users" class="nav-tab <?php echo $current_tab === 'users' ? 'nav-tab-active' : ''; ?>" style="font-weight: 600;">
            👥 Equipe & Operadores (<?php echo count($team_users); ?>)
        </a>
        <a href="?page=pjb-settings&tab=backup" class="nav-tab <?php echo $current_tab === 'backup' ? 'nav-tab-active' : ''; ?>" style="font-weight: 600; color: #b45309;">
            💾 Backup & Migração de Dados
        </a>
        <a href="?page=pjb-settings&tab=ai" class="nav-tab <?php echo $current_tab === 'ai' ? 'nav-tab-active' : ''; ?>" style="font-weight: 600;">
            🤖 Inteligência Artificial (Gemini)
        </a>
        <a href="?page=pjb-settings&tab=integrations" class="nav-tab <?php echo $current_tab === 'integrations' ? 'nav-tab-active' : ''; ?>" style="font-weight: 600;">
            📲 WhatsApp & Mensageria
        </a>
        <a href="?page=pjb-settings&tab=smtp" class="nav-tab <?php echo $current_tab === 'smtp' ? 'nav-tab-active' : ''; ?>" style="font-weight: 600;">
            📧 E-mail & SMTP <?php if ($smtp_enabled === '1'): ?><span style="background: #10b981; color: #fff; font-size: 9px; padding: 1px 6px; border-radius: 10px; margin-left: 4px;">ATIVO</span><?php endif; ?>
        </a>
        <a href="?page=pjb-settings&tab=institution" class="nav-tab <?php echo $current_tab === 'institution' ? 'nav-tab-active' : ''; ?>" style="font-weight: 600;">
            🏛️ Polo Castanhal & Certificados
        </a>
        <a href="?page=pjb-settings&tab=shortcodes" class="nav-tab <?php echo $current_tab === 'shortcodes' ? 'nav-tab-active' : ''; ?>" style="font-weight: 600;">
            📋 Shortcodes do Site
        </a>
    </nav>

    <!-- ======================================================== -->
    <!-- ABA 1: GESTÃO DA EQUIPE & OPERADORES                     -->
    <!-- ======================================================== -->
    <?php if ($current_tab === 'users'): ?>
        
        <!-- Cards de Resumo da Equipe -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px;">
            <div style="background: #fff; padding: 18px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Total de Operadores</span>
                <div style="font-size: 26px; font-weight: 800; color: #0f172a; margin-top: 4px;"><?php echo count($team_users); ?></div>
                <span style="font-size: 11px; color: #10b981;">● Com acesso ao PJB</span>
            </div>
            <div style="background: #fff; padding: 18px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Coordenação Geral</span>
                <div style="font-size: 26px; font-weight: 800; color: #2563eb; margin-top: 4px;"><?php echo absint($count_admin); ?></div>
                <span style="font-size: 11px; color: #64748b;">Acesso Total</span>
            </div>
            <div style="background: #fff; padding: 18px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Assistentes Sociais</span>
                <div style="font-size: 26px; font-weight: 800; color: #9333ea; margin-top: 4px;"><?php echo absint($count_social); ?></div>
                <span style="font-size: 11px; color: #64748b;">Prontuário & Doações</span>
            </div>
            <div style="background: #fff; padding: 18px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Instrutores / Professores</span>
                <div style="font-size: 26px; font-weight: 800; color: #059669; margin-top: 4px;"><?php echo absint($count_instructor); ?></div>
                <span style="font-size: 11px; color: #64748b;">Frequência & Turmas</span>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1.2fr; gap: 25px; align-items: start;">
            
            <!-- Tabela de Membros Cadastrados -->
            <div style="background: #fff; padding: 22px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                <h3 style="margin-top: 0; font-size: 16px; font-weight: 700; color: #1e293b;">
                    Equipe Ativa da Plataforma
                </h3>
                <p style="font-size: 12px; color: #64748b; margin-top: -6px; margin-bottom: 16px;">
                    Operadores com permissão para gerenciar turmas, alunos ou dados sociais do PJB.
                </p>

                <table class="wp-list-table widefat fixed striped table-view-list" style="border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0;">
                    <thead>
                        <tr>
                            <th style="width: 48px;">Foto</th>
                            <th>Nome & Login</th>
                            <th>E-mail</th>
                            <th>Função no PJB</th>
                            <th style="width: 80px; text-align: right;">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($team_users as $user): ?>
                            <?php
                            $user_role = 'pjb_social';
                            if (in_array('administrator', $user->roles) || in_array('pjb_admin', $user->roles)) {
                                $user_role = 'pjb_admin';
                            } elseif (in_array('pjb_instructor', $user->roles)) {
                                $user_role = 'pjb_instructor';
                            }
                            ?>
                            <tr>
                                <td>
                                    <?php echo get_avatar($user->ID, 36, '', '', array('class' => 'rounded-full')); ?>
                                </td>
                                <td>
                                    <strong><?php echo esc_html($user->display_name); ?></strong><br>
                                    <span style="font-size: 11px; color: #94a3b8;">@<?php echo esc_html($user->user_login); ?></span>
                                </td>
                                <td style="font-size: 12px;">
                                    <?php echo esc_html($user->user_email); ?>
                                </td>
                                <td>
                                    <form method="post" action="" style="display: flex; gap: 6px; align-items: center;">
                                        <?php wp_nonce_field('pjb_update_role_action', 'pjb_update_role_nonce'); ?>
                                        <input type="hidden" name="user_id" value="<?php echo esc_attr($user->ID); ?>">
                                        <select name="new_role" style="font-size: 11px; padding: 2px 6px; height: 28px;">
                                            <option value="pjb_admin" <?php selected($user_role, 'pjb_admin'); ?>>Coordenação Geral</option>
                                            <option value="pjb_social" <?php selected($user_role, 'pjb_social'); ?>>Assistente Social</option>
                                            <option value="pjb_instructor" <?php selected($user_role, 'pjb_instructor'); ?>>Instrutor / Professor</option>
                                        </select>
                                        <button type="submit" name="pjb_action_update_role" class="button button-small" title="Salvar alteração">Salvar</button>
                                    </form>
                                </td>
                                <td style="text-align: right;">
                                    <a href="<?php echo esc_url(get_edit_user_link($user->ID)); ?>" class="button button-small" title="Editar Usuário no WordPress">Editar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Formulário: Adicionar Novo Membro -->
            <div style="background: #fff; padding: 22px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                <h3 style="margin-top: 0; font-size: 16px; font-weight: 700; color: #1e293b;">
                    ➕ Cadastrar Novo Membro
                </h3>
                <p style="font-size: 12px; color: #64748b; margin-top: -6px; margin-bottom: 16px;">
                    Crie uma conta de acesso para um professor, assistente social ou coordenador.
                </p>

                <form method="post" action="">
                    <?php wp_nonce_field('pjb_create_user_action', 'pjb_create_user_nonce'); ?>

                    <div style="margin-bottom: 12px;">
                        <label style="display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px;">Nome Completo</label>
                        <input type="text" name="display_name" class="widefat" placeholder="Ex: Prof. Roberto Mendes" required>
                    </div>

                    <div style="margin-bottom: 12px;">
                        <label style="display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px;">Nome de Usuário (Login)</label>
                        <input type="text" name="user_login" class="widefat" placeholder="ex: professor.roberto" required>
                    </div>

                    <div style="margin-bottom: 12px;">
                        <label style="display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px;">E-mail Institucional</label>
                        <input type="email" name="user_email" class="widefat" placeholder="ex: roberto@brasildosjovens.org.br" required>
                    </div>

                    <div style="margin-bottom: 12px;">
                        <label style="display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px;">Senha Provisória</label>
                        <input type="password" name="user_pass" class="widefat" placeholder="Digite uma senha forte" required>
                    </div>

                    <div style="margin-bottom: 18px;">
                        <label style="display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px;">Função na Plataforma</label>
                        <select name="role" class="widefat" style="height: 36px;">
                            <option value="pjb_instructor">Instrutor / Professor (Chamadas e Turmas)</option>
                            <option value="pjb_social">Assistente Social (Prontuário e Cestas)</option>
                            <option value="pjb_admin">Coordenação Geral (Acesso Completo)</option>
                        </select>
                    </div>

                    <button type="submit" name="pjb_action_create_user" class="button button-primary" style="width: 100%; height: 38px; font-weight: 600;">
                        Cadastrar Operador PJB
                    </button>
                </form>
            </div>

        </div>

    <!-- ======================================================== -->
    <!-- ABA 2: INTELIGÊNCIA ARTIFICIAL (GEMINI)                  -->
    <!-- ======================================================== -->
    <?php elseif ($current_tab === 'ai'): ?>

        <div style="background: #fff; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
            <div style="display: flex; gap: 15px; align-items: center; margin-bottom: 15px;">
                <div style="width: 48px; height: 48px; border-radius: 12px; background: #eff6ff; display: flex; align-items: center; justify-content: center; font-size: 24px;">
                    🤖
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 18px; font-weight: 700; color: #0f172a;">Motor de IA Google Gemini</h3>
                    <p style="margin: 2px 0 0; font-size: 13px; color: #64748b;">
                        Geração automática de pareceres sociofamiliares, relatórios de impacto e minutas para editais.
                    </p>
                </div>
            </div>

            <?php if ($has_constant_key): ?>
                <div class="notice notice-info inline" style="margin: 15px 0;">
                    <p><strong>Configuração Segura Ativa:</strong> A chave de API do Gemini está declarada diretamente na constante <code>GEMINI_API_KEY</code> do seu <code>wp-config.php</code>.</p>
                </div>
            <?php endif; ?>

            <form method="post" action="">
                <?php wp_nonce_field('pjb_save_ai_action', 'pjb_save_ai_nonce'); ?>

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="pjb_gemini_api_key">Chave de API (Google AI Studio)</label></th>
                            <td>
                                <input name="pjb_gemini_api_key" type="password" id="pjb_gemini_api_key" value="<?php echo esc_attr($gemini_key); ?>" class="regular-text" placeholder="AIzaSy..." <?php echo $has_constant_key ? 'disabled' : ''; ?> style="width: 100%; max-width: 450px;">
                                <p class="description">
                                    Crie uma chave gratuita e sem custos no <a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener">Google AI Studio (aistudio.google.com)</a>.
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="pjb_gemini_model">Modelo de Linguagem</label></th>
                            <td>
                                <select name="pjb_gemini_model" id="pjb_gemini_model" style="min-width: 250px;">
                                    <option value="gemini-2.5-flash" <?php selected($gemini_model, 'gemini-2.5-flash'); ?>>Gemini 2.5 Flash (Ultrarrápido & Recomendado)</option>
                                    <option value="gemini-2.0-flash" <?php selected($gemini_model, 'gemini-2.0-flash'); ?>>Gemini 2.0 Flash</option>
                                    <option value="gemini-2.5-pro" <?php selected($gemini_model, 'gemini-2.5-pro'); ?>>Gemini 2.5 Pro (Raciocínio Profundo)</option>
                                </select>
                                <p class="description">O modelo Flash oferece respostas em menos de 2 segundos para pareceres sociais e editais.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <input type="submit" name="pjb_action_save_ai" class="button button-primary" value="Salvar Configurações de IA">
                </div>
            </form>

            <hr style="margin: 30px 0; border: 0; border-top: 1px solid #e2e8f0;">

            <!-- Teste de Conexão -->
            <div>
                <h4 style="margin: 0 0 8px; font-size: 14px; font-weight: 700; color: #1e293b;">Diagnóstico de Conexão com o Google</h4>
                <p style="font-size: 12px; color: #64748b; margin-bottom: 12px;">
                    Envie um ping de teste para validar se o seu servidor consegue alcançar a API da Google com sucesso.
                </p>
                <form method="post" action="">
                    <?php wp_nonce_field('pjb_test_ai_action', 'pjb_test_ai_nonce'); ?>
                    <button type="submit" name="pjb_action_test_ai" class="button button-secondary">
                        ⚡ Testar Conexão com a IA Agora
                    </button>
                </form>
            </div>
        </div>

    <!-- ======================================================== -->
    <!-- ABA 3: WHATSAPP & INTEGRAÇÕES DE MENSAGENS               -->
    <!-- ======================================================== -->
    <?php elseif ($current_tab === 'integrations'): ?>

        <div style="background: #fff; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
            <div style="display: flex; gap: 15px; align-items: center; margin-bottom: 15px;">
                <div style="width: 48px; height: 48px; border-radius: 12px; background: #ecfdf5; display: flex; align-items: center; justify-content: center; font-size: 24px;">
                    📲
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 18px; font-weight: 700; color: #0f172a;">Integração com WhatsApp & Avisos</h3>
                    <p style="margin: 2px 0 0; font-size: 13px; color: #64748b;">
                        Envio de mensagens de boas-vindas, alertas de falta nas aulas e avisos de certificados prontos.
                    </p>
                </div>
            </div>

            <form method="post" action="">
                <?php wp_nonce_field('pjb_save_integrations_action', 'pjb_save_integrations_nonce'); ?>

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="pjb_whatsapp_provider">Modo de Envio</label></th>
                            <td>
                                <select name="pjb_whatsapp_provider" id="pjb_whatsapp_provider" style="min-width: 300px;">
                                    <option value="direct" <?php selected($wa_provider, 'direct'); ?>>Link Direto WhatsApp Web (Sem custo / Abre no celular do operador)</option>
                                    <option value="zapi" <?php selected($wa_provider, 'zapi'); ?>>Z-API (Disparo Automático em Segundo Plano)</option>
                                    <option value="evolution" <?php selected($wa_provider, 'evolution'); ?>>Evolution API (Servidor Próprio / Open Source)</option>
                                    <option value="twilio" <?php selected($wa_provider, 'twilio'); ?>>Twilio / WhatsApp Business Cloud</option>
                                </select>
                                <p class="description">No modo "Link Direto", o sistema gera um botão pronto que abre o WhatsApp com a mensagem pré-formatada sem nenhum custo.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="pjb_whatsapp_api_url">URL da API / Gateway (Opcional)</label></th>
                            <td>
                                <input name="pjb_whatsapp_api_url" type="url" id="pjb_whatsapp_api_url" value="<?php echo esc_attr($wa_api_url); ?>" class="regular-text" placeholder="https://api.z-api.io/instances/..." style="width: 100%; max-width: 450px;">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="pjb_whatsapp_api_token">Token / Client Secret</label></th>
                            <td>
                                <input name="pjb_whatsapp_api_token" type="password" id="pjb_whatsapp_api_token" value="<?php echo esc_attr($wa_api_token); ?>" class="regular-text" placeholder="Token de autenticação" style="width: 100%; max-width: 450px;">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="pjb_whatsapp_instance">Instância / ID do Dispositivo</label></th>
                            <td>
                                <input name="pjb_whatsapp_instance" type="text" id="pjb_whatsapp_instance" value="<?php echo esc_attr($wa_instance); ?>" class="regular-text" placeholder="Ex: PJB_CASTANHAL_01">
                            </td>
                        </tr>
                    </tbody>
                </table>

                <p class="submit">
                    <input type="submit" name="pjb_action_save_integrations" class="button button-primary" value="Salvar Integrações de Mensageria">
                </p>
            </form>
        </div>

    <!-- ======================================================== -->
    <!-- ABA: CONFIGURAÇÃO DE E-MAIL & SERVIDOR SMTP              -->
    <!-- ======================================================== -->
    <?php elseif ($current_tab === 'smtp'): ?>

        <div style="background: #fff; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
            <div style="display: flex; gap: 15px; align-items: center; margin-bottom: 20px;">
                <div style="width: 48px; height: 48px; border-radius: 12px; background: #eff6ff; display: flex; align-items: center; justify-content: center; font-size: 24px;">
                    📧
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 18px; font-weight: 700; color: #0f172a;">Servidor de E-mail (SMTP Autenticado)</h3>
                    <p style="margin: 2px 0 0; font-size: 13px; color: #64748b;">
                        Configure o envio oficial de e-mails para envio de certificados, avisos de faltas, convites e confirmação de matrículas sem cair na caixa de SPAM.
                    </p>
                </div>
            </div>

            <!-- Cards de Status da Conexão -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px;">
                <div style="background: #f8fafc; padding: 16px; border-radius: 10px; border: 1px solid #e2e8f0;">
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Status do SMTP</span>
                    <div style="margin-top: 6px;">
                        <?php if ($smtp_enabled === '1'): ?>
                            <span style="display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; background: #dcfce7; color: #15803d;">
                                ● SMTP Ativo
                            </span>
                        <?php else: ?>
                            <span style="display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; background: #f1f5f9; color: #64748b;">
                                ○ Desativado (Mail Padrão)
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="background: #f8fafc; padding: 16px; border-radius: 10px; border: 1px solid #e2e8f0;">
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Servidor Configurado</span>
                    <div style="font-size: 15px; font-weight: 700; color: #0f172a; margin-top: 6px; word-break: break-all;">
                        <?php echo !empty($smtp_host) ? esc_html($smtp_host . ':' . $smtp_port) : '<em style="color:#94a3b8; font-weight:normal;">Não definido</em>'; ?>
                    </div>
                    <span style="font-size: 11px; color: #64748b;">Segurança: <?php echo esc_html(strtoupper($smtp_secure)); ?></span>
                </div>

                <div style="background: #f8fafc; padding: 16px; border-radius: 10px; border: 1px solid #e2e8f0;">
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Remetente Padrão</span>
                    <div style="font-size: 14px; font-weight: 700; color: #2563eb; margin-top: 6px; word-break: break-all;">
                        <?php echo !empty($smtp_from_mail) ? esc_html($smtp_from_mail) : '<em style="color:#94a3b8; font-weight:normal;">E-mail do Admin WP</em>'; ?>
                    </div>
                    <span style="font-size: 11px; color: #64748b;"><?php echo esc_html($smtp_from_name); ?></span>
                </div>
            </div>

            <!-- Botões de Preenchimento Rápido (Presets) -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 18px; margin-bottom: 25px;">
                <div style="font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 8px;">
                    ⚡ Predefinições Rápidas (Clique para preencher os dados de conexão):
                </div>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <button type="button" class="button" onclick="setPjbSmtpPreset('gmail')" style="font-size: 12px;">
                        🔴 Gmail / Workspace (Porta 587 TLS)
                    </button>
                    <button type="button" class="button" onclick="setPjbSmtpPreset('outlook')" style="font-size: 12px;">
                        🔵 Outlook / Office 365 (Porta 587 TLS)
                    </button>
                    <button type="button" class="button" onclick="setPjbSmtpPreset('hostinger_ssl')" style="font-size: 12px;">
                        🟣 Hostinger / cPanel (Porta 465 SSL)
                    </button>
                    <button type="button" class="button" onclick="setPjbSmtpPreset('hostinger_tls')" style="font-size: 12px;">
                        🟡 Hostinger / cPanel (Porta 587 TLS)
                    </button>
                </div>
            </div>

            <script>
            function setPjbSmtpPreset(type) {
                var host = document.getElementById('pjb_smtp_host');
                var port = document.getElementById('pjb_smtp_port');
                var secure = document.getElementById('pjb_smtp_secure');
                var auth = document.getElementById('pjb_smtp_auth');
                var enabled = document.getElementById('pjb_smtp_enabled');

                if (enabled) enabled.checked = true;
                if (auth) auth.checked = true;

                if (type === 'gmail') {
                    host.value = 'smtp.gmail.com';
                    port.value = '587';
                    secure.value = 'tls';
                } else if (type === 'outlook') {
                    host.value = 'smtp.office365.com';
                    port.value = '587';
                    secure.value = 'tls';
                } else if (type === 'hostinger_ssl') {
                    host.value = 'smtp.hostinger.com';
                    port.value = '465';
                    secure.value = 'ssl';
                } else if (type === 'hostinger_tls') {
                    host.value = 'smtp.hostinger.com';
                    port.value = '587';
                    secure.value = 'tls';
                }
            }
            </script>

            <!-- Formulário Principal de Configuração -->
            <form method="post" action="">
                <?php wp_nonce_field('pjb_save_smtp_action', 'pjb_save_smtp_nonce'); ?>

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">Ativação do SMTP</th>
                            <td>
                                <label for="pjb_smtp_enabled" style="font-weight: 600;">
                                    <input name="pjb_smtp_enabled" type="checkbox" id="pjb_smtp_enabled" value="1" <?php checked($smtp_enabled, '1'); ?>>
                                    Ativar envio de e-mails via servidor SMTP autenticado
                                </label>
                                <p class="description">Quando desmarcado, o sistema continuará usando a função mail() padrão do PHP do servidor.</p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="pjb_smtp_host">Servidor SMTP (Host)</label></th>
                            <td>
                                <input name="pjb_smtp_host" type="text" id="pjb_smtp_host" value="<?php echo esc_attr($smtp_host); ?>" class="regular-text" placeholder="Ex: smtp.gmail.com ou mail.seusite.com.br" style="width: 100%; max-width: 450px;">
                                <p class="description">Endereço do servidor SMTP fornecido pela sua hospedagem ou provedor de e-mail.</p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="pjb_smtp_port">Porta do Servidor</label></th>
                            <td>
                                <input name="pjb_smtp_port" type="number" id="pjb_smtp_port" value="<?php echo esc_attr($smtp_port); ?>" class="small-text" style="width: 100px;">
                                <p class="description">Geralmente <strong>587</strong> para TLS ou <strong>465</strong> para SSL (25 costuma ser bloqueada).</p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="pjb_smtp_secure">Tipo de Criptografia</label></th>
                            <td>
                                <select name="pjb_smtp_secure" id="pjb_smtp_secure">
                                    <option value="tls" <?php selected($smtp_secure, 'tls'); ?>>TLS (Recomendado para porta 587)</option>
                                    <option value="ssl" <?php selected($smtp_secure, 'ssl'); ?>>SSL (Recomendado para porta 465)</option>
                                    <option value="none" <?php selected($smtp_secure, 'none'); ?>>Nenhuma / Desativada</option>
                                </select>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">Autenticação</th>
                            <td>
                                <label for="pjb_smtp_auth">
                                    <input name="pjb_smtp_auth" type="checkbox" id="pjb_smtp_auth" value="1" <?php checked($smtp_auth, '1'); ?>>
                                    Requer autenticação (Usuário e Senha)
                                </label>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="pjb_smtp_user">Usuário SMTP / E-mail</label></th>
                            <td>
                                <input name="pjb_smtp_user" type="text" id="pjb_smtp_user" value="<?php echo esc_attr($smtp_user); ?>" class="regular-text" placeholder="contato@brasildosjovens.org.br" style="width: 100%; max-width: 450px;">
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="pjb_smtp_pass">Senha do SMTP</label></th>
                            <td>
                                <input name="pjb_smtp_pass" type="password" id="pjb_smtp_pass" value="<?php echo esc_attr($smtp_pass); ?>" class="regular-text" placeholder="<?php echo !empty($smtp_pass) ? '••••••••••••••••' : 'Digite a senha'; ?>" style="width: 100%; max-width: 450px;">
                                <p class="description">
                                    <?php if (!empty($smtp_pass)): ?><span style="color: #10b981;">✓ Senha já cadastrada. Deixe em branco se não quiser alterar.</span><br><?php endif; ?>
                                    Para contas do Gmail ou Outlook, utilize uma <strong>Senha de Aplicativo (App Password)</strong> gerada nas configurações de segurança da sua conta.
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="pjb_smtp_from_email">E-mail do Remetente (From)</label></th>
                            <td>
                                <input name="pjb_smtp_from_email" type="email" id="pjb_smtp_from_email" value="<?php echo esc_attr($smtp_from_mail); ?>" class="regular-text" placeholder="contato@brasildosjovens.org.br" style="width: 100%; max-width: 450px;">
                                <p class="description">O e-mail que aparecerá como remetente nas mensagens recebidas.</p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="pjb_smtp_from_name">Nome do Remetente</label></th>
                            <td>
                                <input name="pjb_smtp_from_name" type="text" id="pjb_smtp_from_name" value="<?php echo esc_attr($smtp_from_name); ?>" class="regular-text" placeholder="Projeto Brasil dos Jovens" style="width: 100%; max-width: 450px;">
                                <p class="description">Nome da instituição ou projeto que assina o e-mail.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <p class="submit">
                    <input type="submit" name="pjb_action_save_smtp" class="button button-primary" value="Salvar Configurações de SMTP">
                </p>
            </form>

            <hr style="margin: 35px 0; border: 0; border-top: 1px solid #e2e8f0;">

            <!-- Teste de Envio em Tempo Real -->
            <div>
                <h4 style="margin: 0 0 8px; font-size: 15px; font-weight: 700; color: #0f172a;">⚡ Teste de Conexão e Disparo de E-mail</h4>
                <p style="font-size: 13px; color: #64748b; margin-bottom: 15px;">
                    Envie um e-mail de teste agora mesmo para conferir se as credenciais e a porta do seu servidor SMTP estão autorizadas e funcionando perfeitamente.
                </p>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <input type="hidden" name="action" value="pjb_test_smtp_email">
                    <?php wp_nonce_field('pjb_test_smtp_nonce'); ?>
                    
                    <input type="email" name="test_email" required class="regular-text" placeholder="Digite seu e-mail pessoal para receber o teste..." style="width: 100%; max-width: 380px;">
                    
                    <button type="submit" class="button button-secondary" style="height: 38px; line-height: 36px; padding: 0 16px; font-weight: 600;">
                        🚀 Enviar E-mail de Teste
                    </button>
                </form>
            </div>
        </div>

    <!-- ======================================================== -->
    <!-- ABA 4: DADOS INSTITUCIONAIS DO POLO (CASTANHAL)          -->
    <!-- ======================================================== -->
    <?php elseif ($current_tab === 'institution'): ?>

        <div style="background: #fff; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
            <div style="display: flex; gap: 15px; align-items: center; margin-bottom: 15px;">
                <div style="width: 48px; height: 48px; border-radius: 12px; background: #f8fafc; display: flex; align-items: center; justify-content: center; font-size: 24px;">
                    🏛️
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 18px; font-weight: 700; color: #0f172a;">Dados Institucionais do Polo & Certificados</h3>
                    <p style="margin: 2px 0 0; font-size: 13px; color: #64748b;">
                        Informações que constarão nos Certificados Oficiais para impressão e nos formulários públicos.
                    </p>
                </div>
            </div>

            <form method="post" action="">
                <?php wp_nonce_field('pjb_save_institution_action', 'pjb_save_institution_nonce'); ?>

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="pjb_inst_name">Nome da Organização</label></th>
                            <td>
                                <input name="pjb_inst_name" type="text" id="pjb_inst_name" value="<?php echo esc_attr($inst_name); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="pjb_inst_unit">Unidade / Polo Regional</label></th>
                            <td>
                                <input name="pjb_inst_unit" type="text" id="pjb_inst_unit" value="<?php echo esc_attr($inst_unit); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="pjb_inst_cnpj">CNPJ Oficial</label></th>
                            <td>
                                <input name="pjb_inst_cnpj" type="text" id="pjb_inst_cnpj" value="<?php echo esc_attr($inst_cnpj); ?>" class="regular-text" placeholder="00.000.000/0001-00">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="pjb_inst_phone">Telefone / WhatsApp Institucional</label></th>
                            <td>
                                <input name="pjb_inst_phone" type="text" id="pjb_inst_phone" value="<?php echo esc_attr($inst_phone); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="pjb_inst_address">Endereço da Sede em Castanhal</label></th>
                            <td>
                                <input name="pjb_inst_address" type="text" id="pjb_inst_address" value="<?php echo esc_attr($inst_address); ?>" class="regular-text" style="width: 100%; max-width: 450px;">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="pjb_cert_signer_name">Nome do Assinante no Certificado</label></th>
                            <td>
                                <input name="pjb_cert_signer_name" type="text" id="pjb_cert_signer_name" value="<?php echo esc_attr($cert_signer_name); ?>" class="regular-text">
                                <p class="description">Nome impresso na linha de assinatura do certificado A4.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="pjb_cert_signer_role">Cargo do Assinante</label></th>
                            <td>
                                <input name="pjb_cert_signer_role" type="text" id="pjb_cert_signer_role" value="<?php echo esc_attr($cert_signer_role); ?>" class="regular-text">
                                <p class="description">Ex: Coordenação Geral PJB / Diretor Executivo.</p>
                            </td>
                        </tr>
                        <tr style="background: #f8fafc; border-top: 2px solid #e2e8f0; border-bottom: 2px solid #e2e8f0;">
                            <th scope="row">
                                <label for="pjb_custom_validation_url">
                                    🔗 URL Base do QR Code<br>
                                    <span style="font-size: 11px; font-weight: normal; color: #0284c7;">(Domínio Personalizado)</span>
                                </label>
                            </th>
                            <td>
                                <input name="pjb_custom_validation_url" type="url" id="pjb_custom_validation_url" value="<?php echo esc_attr($custom_validation_url); ?>" class="regular-text" style="width: 100%; max-width: 520px;" placeholder="<?php echo esc_attr(home_url('/validar')); ?>">
                                <p class="description" style="margin-top: 6px; line-height: 1.5;">
                                    Endereço apontado pelo <strong>QR Code oficial impresso nos certificados</strong> (sem hashtags <code>#</code>).<br>
                                    ✅ <strong>Automático / Padrão:</strong> Deixe em branco para usar automaticamente o domínio deste site (<code><?php echo esc_html(home_url('/validar')); ?></code>).<br>
                                    🌐 <strong>Domínio Próprio:</strong> Caso use um domínio ou subdomínio exclusivo para validação (ex: <code>https://validar.brasildosjovens.org.br</code>), informe aqui e os certificados gerados apontarão diretamente para ele.
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <p class="submit">
                    <input type="submit" name="pjb_action_save_institution" class="button button-primary" value="Salvar Dados Institucionais">
                </p>
            </form>
        </div>

    <!-- ======================================================== -->
    <!-- ABA: BACKUP & MIGRAÇÃO DE DADOS                          -->
    <!-- ======================================================== -->
    <?php elseif ($current_tab === 'backup'): ?>

        <!-- Métricas Rápidas do Banco de Dados -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px; margin-bottom: 25px;">
            <div style="background: #fff; padding: 14px 16px; border-radius: 10px; border: 1px solid #e2e8f0; text-align: center;">
                <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Beneficiários</span>
                <div style="font-size: 22px; font-weight: 800; color: #2563eb; margin-top: 2px;"><?php echo (int)($backup_summary['counts']['beneficiaries'] ?? 0); ?></div>
            </div>
            <div style="background: #fff; padding: 14px 16px; border-radius: 10px; border: 1px solid #e2e8f0; text-align: center;">
                <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Cursos</span>
                <div style="font-size: 22px; font-weight: 800; color: #059669; margin-top: 2px;"><?php echo (int)($backup_summary['counts']['courses'] ?? 0); ?></div>
            </div>
            <div style="background: #fff; padding: 14px 16px; border-radius: 10px; border: 1px solid #e2e8f0; text-align: center;">
                <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Turmas</span>
                <div style="font-size: 22px; font-weight: 800; color: #d97706; margin-top: 2px;"><?php echo (int)($backup_summary['counts']['classes'] ?? 0); ?></div>
            </div>
            <div style="background: #fff; padding: 14px 16px; border-radius: 10px; border: 1px solid #e2e8f0; text-align: center;">
                <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Chamadas</span>
                <div style="font-size: 22px; font-weight: 800; color: #0284c7; margin-top: 2px;"><?php echo (int)($backup_summary['counts']['attendances'] ?? 0); ?></div>
            </div>
            <div style="background: #fff; padding: 14px 16px; border-radius: 10px; border: 1px solid #e2e8f0; text-align: center;">
                <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Certificados</span>
                <div style="font-size: 22px; font-weight: 800; color: #7c3aed; margin-top: 2px;"><?php echo (int)($backup_summary['counts']['certificates'] ?? 0); ?></div>
            </div>
            <div style="background: #fff; padding: 14px 16px; border-radius: 10px; border: 1px solid #e2e8f0; text-align: center;">
                <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Campanhas</span>
                <div style="font-size: 22px; font-weight: 800; color: #e11d48; margin-top: 2px;"><?php echo (int)($backup_summary['counts']['campaigns'] ?? 0); ?></div>
            </div>
            <div style="background: #fff; padding: 14px 16px; border-radius: 10px; border: 1px solid #e2e8f0; text-align: center;">
                <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Doações</span>
                <div style="font-size: 22px; font-weight: 800; color: #16a34a; margin-top: 2px;"><?php echo (int)($backup_summary['counts']['donations'] ?? 0); ?></div>
            </div>
            <div style="background: #fff; padding: 14px 16px; border-radius: 10px; border: 1px solid #e2e8f0; text-align: center;">
                <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Total Registros</span>
                <div style="font-size: 22px; font-weight: 800; color: #0f172a; margin-top: 2px;"><?php echo (int)($backup_summary['total_records'] ?? 0); ?></div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            
            <!-- Card 1: Exportar Backup -->
            <div style="background: #fff; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.04); display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                        <span style="font-size: 24px;">📥</span>
                        <h3 style="margin: 0; font-size: 18px; font-weight: 700; color: #0f172a;">
                            Exportar Backup Completo
                        </h3>
                    </div>
                    
                    <p style="font-size: 13px; color: #64748b; line-height: 1.5; margin-bottom: 16px;">
                        Gere um arquivo portátil com a cópia exata de todos os dados do <strong>PJB</strong> (alunos, cadastros, turmas, notas de assistência social, frequências e certificados).
                    </p>

                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; margin-bottom: 20px; font-size: 12px; color: #475569;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                            <span>📦 <strong>Formato:</strong> JSON Estruturado PJB</span>
                            <span>🔒 <strong>Criptografia:</strong> UTF-8</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>🕒 <strong>Última verificação:</strong> <?php echo esc_html($backup_summary['last_backup_date'] ?? 'Nenhum'); ?></span>
                            <span>⚡ <strong>Versão:</strong> 1.0.0</span>
                        </div>
                    </div>

                    <div style="padding: 12px 14px; background: #ecfdf5; border-left: 4px solid #10b981; border-radius: 6px; font-size: 12px; color: #065f46; margin-bottom: 20px;">
                        💡 <strong>Dica de Segurança:</strong> Recomendamos baixar um backup antes de grandes atualizações ou no final de cada mês letivo para arquivamento seguro em nuvem ou pen-drive.
                    </div>
                </div>

                <form method="post" action="">
                    <?php wp_nonce_field('pjb_export_backup_action', 'pjb_export_backup_nonce'); ?>
                    <button type="submit" name="pjb_action_export_backup" class="button button-primary" style="width: 100%; height: 42px; font-size: 14px; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 8px; background: #059669; border-color: #047857;">
                        <span>⬇️</span> Baixar Arquivo de Backup (.json)
                    </button>
                </form>
            </div>

            <!-- Card 2: Importar / Restaurar Backup -->
            <div style="background: #fff; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                    <span style="font-size: 24px;">📤</span>
                    <h3 style="margin: 0; font-size: 18px; font-weight: 700; color: #0f172a;">
                        Restaurar ou Importar Dados
                    </h3>
                </div>

                <p style="font-size: 13px; color: #64748b; line-height: 1.5; margin-bottom: 16px;">
                    Envie um arquivo de backup (.json) para migrar dados entre servidores ou restaurar informações salvas anteriormente.
                </p>

                <form method="post" action="" enctype="multipart/form-data">
                    <?php wp_nonce_field('pjb_import_backup_action', 'pjb_import_backup_nonce'); ?>

                    <div style="margin-bottom: 16px;">
                        <label style="display: block; font-weight: 600; font-size: 13px; color: #334155; margin-bottom: 6px;">
                            Arquivo JSON de Backup:
                        </label>
                        <input type="file" name="backup_file" accept=".json,application/json" required style="width: 100%; font-size: 13px; padding: 8px; border: 1px dashed #cbd5e1; border-radius: 8px; background: #f8fafc;">
                    </div>

                    <div style="margin-bottom: 18px;">
                        <label style="display: block; font-weight: 600; font-size: 13px; color: #334155; margin-bottom: 6px;">
                            Modo de Importação:
                        </label>
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <label style="font-size: 12px; color: #334155; display: flex; align-items: flex-start; gap: 8px; cursor: pointer; background: #f0fdf4; padding: 10px; border-radius: 8px; border: 1px solid #bbf7d0;">
                                <input type="radio" name="import_mode" value="merge" checked style="margin-top: 2px;">
                                <div>
                                    <strong style="color: #166534;">🟢 Mesclar & Atualizar (Recomendado)</strong>
                                    <p style="margin: 2px 0 0; color: #4b5563; font-size: 11px;">Atualiza dados que já existem pelo ID e adiciona os novos sem apagar nada do banco atual.</p>
                                </div>
                            </label>

                            <label style="font-size: 12px; color: #334155; display: flex; align-items: flex-start; gap: 8px; cursor: pointer; background: #fef2f2; padding: 10px; border-radius: 8px; border: 1px solid #fecaca;">
                                <input type="radio" name="import_mode" value="replace" style="margin-top: 2px;">
                                <div>
                                    <strong style="color: #991b1b;">🔴 Substituição Completa (Reset & Restaurar)</strong>
                                    <p style="margin: 2px 0 0; color: #4b5563; font-size: 11px;">Apaga os registros atuais das tabelas PJB e restaura exatamente o que está no arquivo.</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <button type="submit" name="pjb_action_import_backup" onclick="return confirm('Confirma a importação do arquivo de backup selecionado?');" class="button button-primary" style="width: 100%; height: 42px; font-size: 14px; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 8px; background: #2563eb; border-color: #1d4ed8;">
                        <span>🚀</span> Processar Importação de Dados
                    </button>
                </form>
            </div>

        </div>

        <!-- Card 3: Informações de Interoperabilidade e Migração -->
        <div style="background: #fff; padding: 20px 25px; border-radius: 12px; border: 1px solid #e2e8f0; margin-top: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
            <h4 style="margin: 0 0 8px; font-size: 15px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                <span>🔄</span> Interoperabilidade e Migração entre Ambientes
            </h4>
            <p style="font-size: 12px; color: #64748b; line-height: 1.6; margin: 0;">
                O arquivo de backup gerado é <strong>100% universal</strong>. Você pode exportar o banco do <strong>WordPress (MySQL/MariaDB)</strong> e importá-lo no sistema nativo <strong>Node/Postgres/SQLite</strong> ou vice-versa, permitindo migração contínua e sem perdas de cadastros e notas de acompanhamento social.
            </p>
        </div>

    <!-- ======================================================== -->
    <!-- ABA 5: SHORTCODES DO SITE                                -->
    <!-- ======================================================== -->
    <?php elseif ($current_tab === 'shortcodes'): ?>

        <div style="background: #fff; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
            <h3 style="margin-top: 0; font-size: 18px; font-weight: 700; color: #0f172a;">
                📌 Shortcodes Disponíveis para Páginas e Menus
            </h3>
            <p style="font-size: 13px; color: #64748b; margin-bottom: 20px;">
                Incorpore partes do sistema diretamente no site público da ONG colando os códigos abaixo em qualquer página (Gutenberg, Elementor ou Classic Editor):
            </p>

            <div style="margin-bottom: 20px; padding: 18px; background: #f8fafc; border-radius: 10px; border: 1px solid #e2e8f0;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <code style="font-size: 14px; font-weight: bold; background: #e2e8f0; padding: 4px 10px; border-radius: 6px;">[pjb_inscricao]</code>
                    <span style="font-size: 11px; background: #dcfce7; color: #15803d; padding: 2px 8px; border-radius: 4px; font-weight: 600;">Público</span>
                </div>
                <p style="font-size: 13px; color: #475569; margin: 8px 0 0;">
                    Exibe o formulário oficial de matrícula online e catálogo de cursos abertos em Castanhal para que jovens e familiares se inscrevam pelo site.
                </p>
            </div>

            <div style="margin-bottom: 20px; padding: 18px; background: #f8fafc; border-radius: 10px; border: 1px solid #e2e8f0;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <code style="font-size: 14px; font-weight: bold; background: #e2e8f0; padding: 4px 10px; border-radius: 6px;">[pjb_validar]</code>
                    <span style="font-size: 11px; background: #dbeafe; color: #1e40af; padding: 2px 8px; border-radius: 4px; font-weight: 600;">Público</span>
                </div>
                <p style="font-size: 13px; color: #475569; margin: 8px 0 0;">
                    Exibe o portal de validação e consulta de autenticidade documental de certificados por QR Code ou digitação do código.
                </p>
            </div>

            <div style="padding: 18px; background: #f8fafc; border-radius: 10px; border: 1px solid #e2e8f0;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <code style="font-size: 14px; font-weight: bold; background: #e2e8f0; padding: 4px 10px; border-radius: 6px;">[pjb_painel]</code>
                    <span style="font-size: 11px; background: #fef3c7; color: #b45309; padding: 2px 8px; border-radius: 4px; font-weight: 600;">Requer Login</span>
                </div>
                <p style="font-size: 13px; color: #475569; margin: 8px 0 0;">
                    Embarca o painel completo de gestão diretamente em uma página interna protegida para operadores do projeto.
                </p>
            </div>
        </div>

    <?php endif; ?>

</div>

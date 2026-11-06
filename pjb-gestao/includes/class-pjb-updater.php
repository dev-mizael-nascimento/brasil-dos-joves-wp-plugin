<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * PJB_Updater - Gerenciador de Atualizações Automáticas via GitHub
 *
 * Permite que o WordPress verifique atualizações diretamente do repositório
 * Git (viebmiza/PJB-brasil-dos-joves-) e instale com 1 clique no Painel de Plugins.
 */
class PJB_Updater {

    private static $repo_owner = 'viebmiza';
    private static $repo_name  = 'PJB-brasil-dos-joves-';
    private static $manifest_url = 'https://raw.githubusercontent.com/viebmiza/PJB-brasil-dos-joves-/main/wordpress-plugin/update-info.json';

    public static function init() {
        // Filtro nativo de checagem de atualizações de plugins do WordPress
        add_filter('pre_set_site_transient_update_plugins', array(__CLASS__, 'check_for_update'));

        // Filtro nativo para a janela modal "Ver detalhes da versão"
        add_filter('plugins_api', array(__CLASS__, 'plugin_info_popup'), 20, 3);

        // Autenticação automática para repositório privado via token nas requisições HTTP
        add_filter('http_request_args', array(__CLASS__, 'authenticate_github_requests'), 10, 2);

        // Garantir integridade da pasta pjb-gestao após a descompactação
        add_filter('upgrader_post_install', array(__CLASS__, 'post_install_cleanup'), 10, 3);

        // Ação administrativa para forçar verificação imediata via botão
        add_action('admin_post_pjb_force_git_update_check', array(__CLASS__, 'handle_force_update_check'));
    }

    /**
     * Recupera o token do GitHub configurado nas opções (se o repositório for privado)
     */
    public static function get_github_token() {
        return trim(get_option('pjb_github_token', ''));
    }

    /**
     * Injeta cabeçalho de autorização em requisições para o GitHub caso haja token salvo
     */
    public static function authenticate_github_requests($args, $url) {
        if (strpos($url, self::$repo_name) !== false || strpos($url, 'api.github.com') !== false) {
            $token = self::get_github_token();
            if (!empty($token)) {
                $args['headers']['Authorization'] = 'Bearer ' . $token;
                $args['headers']['User-Agent'] = 'WordPress/' . get_bloginfo('version') . '; ' . home_url();
            }
        }
        return $args;
    }

    /**
     * Consulta o manifesto de atualização no GitHub
     */
    public static function fetch_remote_info() {
        $transient_key = 'pjb_github_update_info';
        $cached = get_transient($transient_key);
        if ($cached !== false && is_object($cached)) {
            return $cached;
        }

        $token = self::get_github_token();
        $args = array(
            'timeout' => 15,
            'headers' => array(
                'Accept'     => 'application/json',
                'User-Agent' => 'WordPress/PJB-Plugin-Updater',
            ),
        );

        if (!empty($token)) {
            $args['headers']['Authorization'] = 'Bearer ' . $token;
        }

        $response = wp_remote_get(self::$manifest_url, $args);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);

        if (!empty($data) && isset($data->version)) {
            set_transient($transient_key, $data, 3600); // Cache por 1 hora
            return $data;
        }

        return false;
    }

    /**
     * Compara versão e commit para notificar o WordPress
     */
    public static function check_for_update($transient) {
        if (empty($transient) || !is_object($transient)) {
            return $transient;
        }

        $remote_info = self::fetch_remote_info();
        if (!$remote_info) {
            return $transient;
        }

        $current_version = defined('PJB_VERSION') ? PJB_VERSION : '1.0.0';
        $current_commit  = get_option('pjb_installed_commit', '');

        // Detecta atualização se a versão remota for maior OU se houver commit diferente
        $has_version_bump = version_compare($remote_info->version, $current_version, '>');
        $has_new_commit   = !empty($remote_info->last_commit) && ($remote_info->last_commit !== $current_commit);

        if ($has_version_bump || $has_new_commit) {
            $plugin_basename = PJB_PLUGIN_BASENAME;

            $item = new stdClass();
            $item->id            = 'pjb-gestao';
            $item->slug          = 'pjb-gestao';
            $item->plugin        = $plugin_basename;
            $item->new_version   = $remote_info->version . (!empty($remote_info->last_commit) ? ' (' . substr($remote_info->last_commit, 0, 7) . ')' : '');
            $item->url           = 'https://github.com/' . self::$repo_owner . '/' . self::$repo_name;
            $item->package       = $remote_info->download_url;
            $item->tested        = isset($remote_info->tested) ? $remote_info->tested : '6.7';
            $item->requires      = isset($remote_info->requires) ? $remote_info->requires : '6.0';
            $item->requires_php  = isset($remote_info->requires_php) ? $remote_info->requires_php : '7.4';

            $transient->response[$plugin_basename] = $item;
        }

        return $transient;
    }

    /**
     * Modal com detalhes da nova versão
     */
    public static function plugin_info_popup($result, $action, $args) {
        if ($action !== 'plugin_information' || !isset($args->slug) || $args->slug !== 'pjb-gestao') {
            return $result;
        }

        $remote_info = self::fetch_remote_info();
        if (!$remote_info) {
            return $result;
        }

        $info = new stdClass();
        $info->name          = isset($remote_info->name) ? $remote_info->name : 'Projeto Brasil dos Jovens (PJB)';
        $info->slug          = 'pjb-gestao';
        $info->version       = $remote_info->version;
        $info->author        = '<a href="https://brasildosjovens.org.br">Projeto Brasil dos Jovens</a>';
        $info->homepage      = 'https://github.com/' . self::$repo_owner . '/' . self::$repo_name;
        $info->download_link = $remote_info->download_url;
        $info->last_updated  = isset($remote_info->last_updated) ? $remote_info->last_updated : date('Y-m-d');
        $info->sections      = array(
            'description' => isset($remote_info->sections->description) ? $remote_info->sections->description : 'Sistema Integrado PJB.',
            'changelog'   => isset($remote_info->sections->changelog) ? $remote_info->sections->changelog : 'Atualização com últimas melhorias do Git.',
        );

        return $info;
    }

    /**
     * Pós-instalação: garante que a pasta continue sendo wp-content/plugins/pjb-gestao
     */
    public static function post_install_cleanup($response, $hook_extra, $result) {
        if (!isset($hook_extra['plugin']) || $hook_extra['plugin'] !== PJB_PLUGIN_BASENAME) {
            return $response;
        }

        // Salva o commit atualizado após o update
        $remote_info = self::fetch_remote_info();
        if ($remote_info && !empty($remote_info->last_commit)) {
            update_option('pjb_installed_commit', $remote_info->last_commit);
        }

        delete_transient('pjb_github_update_info');
        return $response;
    }

    /**
     * Ação disparada ao clicar no botão "Verificar Atualizações do Git Agora"
     */
    public static function handle_force_update_check() {
        if (!current_user_can('update_plugins')) {
            wp_die('Acesso negado.');
        }

        check_admin_referer('pjb_force_update_nonce');

        // Limpa todos os caches
        delete_transient('pjb_github_update_info');
        delete_site_transient('update_plugins');

        // Força o WordPress a checar os plugins imediatamente
        wp_update_plugins();

        wp_redirect(add_query_arg(
            array(
                'page'           => 'pjb-settings',
                'pjb_git_checked' => '1',
            ),
            admin_url('admin.php')
        ));
        exit;
    }
}

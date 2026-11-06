<?php
if (!defined('ABSPATH')) {
    exit;
}

class PJB_Shortcodes {
    public static function init() {
        add_shortcode('pjb_inscricao', array(__CLASS__, 'render_enrollment_shortcode'));
        add_shortcode('pjb_validar', array(__CLASS__, 'render_verify_shortcode'));
        add_shortcode('pjb_painel', array(__CLASS__, 'render_panel_shortcode'));

        // Registra endpoints públicos amigáveis no WordPress (/validar e /inscrever)
        add_action('init', array(__CLASS__, 'register_public_rewrites'));
        add_filter('query_vars', array(__CLASS__, 'register_query_vars'));
        add_action('template_redirect', array(__CLASS__, 'handle_public_template_redirect'));
    }

    public static function register_public_rewrites() {
        add_rewrite_rule('^validar/?$', 'index.php?pjb_portal=validar', 'top');
        add_rewrite_rule('^inscrever/?$', 'index.php?pjb_portal=inscrever', 'top');
    }

    public static function register_query_vars($vars) {
        $vars[] = 'pjb_portal';
        return $vars;
    }

    public static function handle_public_template_redirect() {
        $portal = get_query_var('pjb_portal');
        if (empty($portal)) {
            if (isset($_GET['pjb_portal'])) {
                $portal = sanitize_text_field($_GET['pjb_portal']);
            } elseif (isset($_GET['portal'])) {
                $portal = sanitize_text_field($_GET['portal']);
            } else {
                $uri = trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
                if ($uri === 'validar' || preg_match('#(^|/)validar/?$#', $uri)) {
                    $portal = 'validar';
                } elseif ($uri === 'inscrever' || preg_match('#(^|/)inscrever/?$#', $uri)) {
                    $portal = 'inscrever';
                }
            }
        }

        if ($portal === 'validar') {
            status_header(200);
            self::render_standalone_portal('verify-cert', 'Consulta e Validação Oficial de Certificados — Projeto Brasil dos Jovens');
            exit;
        }

        if ($portal === 'inscrever') {
            status_header(200);
            self::render_standalone_portal('public-enrollment', 'Inscrições Abertas — Projeto Brasil dos Jovens');
            exit;
        }
    }

    public static function render_standalone_portal($tab = 'verify-cert', $title = 'Projeto Brasil dos Jovens') {
        self::enqueue_frontend_assets($tab, true);
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo esc_html($title); ?></title>
            <?php wp_head(); ?>
            <style>
                body, html { margin: 0; padding: 0; background: #0b1329; color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
                #wpadminbar { display: none !important; }
            </style>
        </head>
        <body <?php body_class('pjb-portal-body'); ?>>
            <div id="root"></div>
            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
    }

    private static function enqueue_frontend_assets($default_tab = 'public-enrollment', $is_shortcode = true) {
        $css_files = glob(PJB_PLUGIN_DIR . 'build/assets/*.css');
        $js_files = glob(PJB_PLUGIN_DIR . 'build/assets/*.js');

        if (!empty($css_files)) {
            $css_url = PJB_PLUGIN_URL . 'build/assets/' . basename($css_files[0]);
            wp_enqueue_style('pjb-react-css', $css_url, array(), filemtime($css_files[0]));
        }

        if (!empty($js_files)) {
            $js_url = PJB_PLUGIN_URL . 'build/assets/' . basename($js_files[0]);
            wp_enqueue_script('pjb-react-js', $js_url, array(), filemtime($js_files[0]), true);

            $user_data = null;
            if (is_user_logged_in()) {
                $current_user = wp_get_current_user();
                $user_data = array(
                    'id'    => $current_user->ID,
                    'name'  => $current_user->display_name ?: $current_user->user_login,
                    'email' => $current_user->user_email,
                    'role'  => PJB_Roles::get_current_user_pjb_role(),
                );
            }

            $custom_val_url = get_option('pjb_custom_validation_url', '');
            if (empty($custom_val_url)) {
                $custom_val_url = home_url('/validar');
            }

            wp_localize_script('pjb-react-js', 'pjbSettings', array(
                'apiUrl'        => esc_url_raw(rest_url('pjb/v1')),
                'nonce'         => wp_create_nonce('wp_rest'),
                'siteUrl'       => site_url(),
                'homeUrl'       => home_url(),
                'validationUrl' => esc_url_raw($custom_val_url),
                'pluginUrl'     => PJB_PLUGIN_URL,
                'defaultTab'    => $default_tab,
                'isShortcode'   => $is_shortcode,
                'user'          => $user_data,
            ));
        }
    }

    public static function render_enrollment_shortcode() {
        self::enqueue_frontend_assets('public-enrollment', true);
        return '<div class="pjb-shortcode-wrapper"><div id="root"></div></div>';
    }

    public static function render_verify_shortcode() {
        self::enqueue_frontend_assets('verify-cert', true);
        return '<div class="pjb-shortcode-wrapper"><div id="root"></div></div>';
    }

    public static function render_panel_shortcode() {
        if (!is_user_logged_in()) {
            return '<p class="pjb-notice-login">Você precisa estar logado para acessar o painel do Projeto Brasil dos Jovens.</p>';
        }
        self::enqueue_frontend_assets('dashboard', false);
        return '<div class="pjb-shortcode-wrapper" style="min-height: 80vh;"><div id="root"></div></div>';
    }
}

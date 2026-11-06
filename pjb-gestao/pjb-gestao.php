<?php
/**
 * Plugin Name:       Projeto Brasil dos Jovens (PJB) - Gestão Social & Acadêmica
 * Plugin URI:        https://brasildosjovens.org.br
 * Description:       Sistema completo de gestão social, qualificação profissional, controle de turmas, frequência, emissão de certificados oficiais com QR Code e captação de recursos com IA para o Projeto Brasil dos Jovens (PJB).
 * Version:           1.0.0
 * Author:            Projeto Brasil dos Jovens & Castanhal-PA
 * Author URI:        https://instagram.com/brasildosjovens
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       pjb-gestao
 * Domain Path:       /languages
 */

// Se este arquivo for chamado diretamente, aborta a execução.
if (!defined('ABSPATH')) {
    exit;
}

// Constantes globais do plugin
define('PJB_VERSION', '1.0.0');
define('PJB_DB_VERSION', '1.0.0');
define('PJB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PJB_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PJB_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Carregamento de módulos
require_once PJB_PLUGIN_DIR . 'includes/class-pjb-activator.php';
require_once PJB_PLUGIN_DIR . 'includes/class-pjb-deactivator.php';
require_once PJB_PLUGIN_DIR . 'includes/class-pjb-roles.php';
require_once PJB_PLUGIN_DIR . 'includes/class-pjb-database.php';
require_once PJB_PLUGIN_DIR . 'includes/class-pjb-crypto.php';
require_once PJB_PLUGIN_DIR . 'includes/class-pjb-ai-service.php';
require_once PJB_PLUGIN_DIR . 'includes/class-pjb-rest-api.php';
require_once PJB_PLUGIN_DIR . 'admin/class-pjb-admin.php';
require_once PJB_PLUGIN_DIR . 'public/class-pjb-shortcodes.php';

require_once PJB_PLUGIN_DIR . 'includes/class-pjb-updater.php';
require_once PJB_PLUGIN_DIR . 'includes/class-pjb-smtp.php';

// Hooks de ciclo de vida do WordPress
register_activation_hook(__FILE__, array('PJB_Activator', 'activate'));
register_deactivation_hook(__FILE__, array('PJB_Deactivator', 'deactivate'));

// Inicialização dos serviços
add_action('plugins_loaded', function () {
    // Migração automática de banco de dados se a versão mudou
    if (get_option('pjb_db_version') !== PJB_DB_VERSION) {
        PJB_Activator::activate();
    }

    PJB_Roles::init();
    PJB_Admin::init();
    PJB_Shortcodes::init();
    PJB_REST_API::init();
    PJB_Updater::init();
    PJB_SMTP::init();
});

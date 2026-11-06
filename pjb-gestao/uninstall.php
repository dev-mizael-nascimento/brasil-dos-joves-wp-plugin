<?php
/**
 * Desinstalação do Plugin PJB Gestão
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Por segurança dos dados sociais da instituição e dos jovens de Castanhal,
// as tabelas do banco de dados são preservadas mesmo se o plugin for desinstalado.
// Para exclusão total, remova as tabelas prefixadas com wp_pjb_* manualmente pelo phpMyAdmin.
delete_transient('pjb_stats_summary');

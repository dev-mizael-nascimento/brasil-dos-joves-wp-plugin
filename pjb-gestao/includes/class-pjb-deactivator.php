<?php
if (!defined('ABSPATH')) {
    exit;
}

class PJB_Deactivator {
    public static function deactivate() {
        // Limpa transients de estatísticas se houver
        delete_transient('pjb_stats_summary');
        flush_rewrite_rules();
    }
}

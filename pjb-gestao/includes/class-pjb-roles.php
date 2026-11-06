<?php
if (!defined('ABSPATH')) {
    exit;
}

class PJB_Roles {
    public static function init() {
        // Concede permissões para o administrador padrão do WordPress
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('manage_pjb_admin');
            $admin->add_cap('manage_pjb_data');
            $admin->add_cap('manage_pjb_classes');
        }

        // 1. Coordenação Geral PJB
        if (!get_role('pjb_admin')) {
            add_role('pjb_admin', 'Coordenação PJB', array(
                'read'               => true,
                'manage_pjb_admin'   => true,
                'manage_pjb_data'    => true,
                'manage_pjb_classes' => true,
            ));
        }

        // 2. Assistência Social PJB
        if (!get_role('pjb_social')) {
            add_role('pjb_social', 'Assistente Social PJB', array(
                'read'            => true,
                'manage_pjb_data' => true,
            ));
        }

        // 3. Instrutor / Professor PJB
        if (!get_role('pjb_instructor')) {
            add_role('pjb_instructor', 'Instrutor PJB', array(
                'read'               => true,
                'manage_pjb_classes' => true,
            ));
        }
    }

    public static function get_current_user_pjb_role() {
        if (!is_user_logged_in()) {
            return null;
        }

        if (current_user_can('manage_pjb_admin') || current_user_can('administrator')) {
            return 'admin';
        }

        if (current_user_can('manage_pjb_data')) {
            return 'social_worker';
        }

        if (current_user_can('manage_pjb_classes')) {
            return 'instructor';
        }

        return 'admin';
    }
}

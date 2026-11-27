<?php
if (!defined('ABSPATH')) {
    exit;
}

class PJB_REST_API {
    const NAMESPACE = 'pjb/v1';

    public static function init() {
        add_action('rest_api_init', array(__CLASS__, 'register_routes'));
    }

    public static function check_auth() {
        // No contexto do painel do WordPress, basta estar autenticado
        return is_user_logged_in();
    }

    public static function register_routes() {
        // Autenticação Nativa WordPress (Área Restrita)
        register_rest_route(self::NAMESPACE, '/auth/login', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'auth_login'),
            'permission_callback' => '__return_true',
        ));

        // Estatísticas do Painel
        register_rest_route(self::NAMESPACE, '/stats', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_stats'),
            'permission_callback' => array(__CLASS__, 'check_auth'),
        ));

        // Beneficiários
        register_rest_route(self::NAMESPACE, '/beneficiaries', array(
            array(
                'methods' => 'GET',
                'callback' => array(__CLASS__, 'get_beneficiaries'),
                'permission_callback' => array(__CLASS__, 'check_auth'),
            ),
            array(
                'methods' => 'POST',
                'callback' => array(__CLASS__, 'create_beneficiary'),
                'permission_callback' => '__return_true', // Permitido no portal público
            ),
        ));

        register_rest_route(self::NAMESPACE, '/beneficiaries/lookup-by-cpf/(?P<cpf>[\w.-]+)', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'lookup_beneficiary_by_cpf'),
            'permission_callback' => '__return_true', // Busca rápida de beneficiário por CPF para agendamento
        ));

        register_rest_route(self::NAMESPACE, '/beneficiaries/(?P<id>[\w-]+)', array(
            array(
                'methods' => 'GET',
                'callback' => array(__CLASS__, 'get_beneficiary_details'),
                'permission_callback' => array(__CLASS__, 'check_auth'),
            ),
            array(
                'methods' => 'PUT',
                'callback' => array(__CLASS__, 'update_beneficiary'),
                'permission_callback' => array(__CLASS__, 'check_auth'),
            ),
            array(
                'methods' => 'DELETE',
                'callback' => array(__CLASS__, 'delete_beneficiary'),
                'permission_callback' => array(__CLASS__, 'check_auth'),
            ),
        ));

        // Cursos
        register_rest_route(self::NAMESPACE, '/courses', array(
            array(
                'methods' => 'GET',
                'callback' => array(__CLASS__, 'get_courses'),
                'permission_callback' => '__return_true', // Público para o portal de inscrição
            ),
            array(
                'methods' => 'POST',
                'callback' => array(__CLASS__, 'create_course'),
                'permission_callback' => array(__CLASS__, 'check_auth'),
            ),
        ));

        register_rest_route(self::NAMESPACE, '/courses/(?P<id>[\w-]+)', array(
            array(
                'methods' => 'PUT',
                'callback' => array(__CLASS__, 'update_course'),
                'permission_callback' => array(__CLASS__, 'check_auth'),
            ),
            array(
                'methods' => 'DELETE',
                'callback' => array(__CLASS__, 'delete_course'),
                'permission_callback' => array(__CLASS__, 'check_auth'),
            ),
        ));

        // Turmas
        register_rest_route(self::NAMESPACE, '/classes', array(
            array(
                'methods' => 'GET',
                'callback' => array(__CLASS__, 'get_classes'),
                'permission_callback' => '__return_true', // Público para o portal de inscrição
            ),
            array(
                'methods' => 'POST',
                'callback' => array(__CLASS__, 'create_class'),
                'permission_callback' => array(__CLASS__, 'check_auth'),
            ),
        ));

        register_rest_route(self::NAMESPACE, '/classes/(?P<id>[\w-]+)', array(
            array(
                'methods' => 'PUT',
                'callback' => array(__CLASS__, 'update_class'),
                'permission_callback' => array(__CLASS__, 'check_auth'),
            ),
            array(
                'methods' => 'DELETE',
                'callback' => array(__CLASS__, 'delete_class'),
                'permission_callback' => array(__CLASS__, 'check_auth'),
            ),
        ));

        register_rest_route(self::NAMESPACE, '/classes/(?P<id>[\w-]+)/enroll', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'enroll_student'),
            'permission_callback' => array(__CLASS__, 'check_auth'),
        ));

        register_rest_route(self::NAMESPACE, '/classes/(?P<id>[\w-]+)/public-enroll', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'public_enroll_class'),
            'permission_callback' => '__return_true', // Matrícula pública por etapas
        ));

        register_rest_route(self::NAMESPACE, '/classes/(?P<id>[\w-]+)/enroll/(?P<studentId>[\w-]+)', array(
            'methods' => 'DELETE',
            'callback' => array(__CLASS__, 'remove_student'),
            'permission_callback' => array(__CLASS__, 'check_auth'),
        ));

        register_rest_route(self::NAMESPACE, '/classes/(?P<id>[\w-]+)/students', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_class_students'),
            'permission_callback' => array(__CLASS__, 'check_auth'),
        ));

        // Frequência
        register_rest_route(self::NAMESPACE, '/attendance/(?P<classId>[\w-]+)', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_attendance'),
            'permission_callback' => array(__CLASS__, 'check_auth'),
        ));

        register_rest_route(self::NAMESPACE, '/attendance', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'record_attendance'),
            'permission_callback' => array(__CLASS__, 'check_auth'),
        ));

        register_rest_route(self::NAMESPACE, '/attendance/absence-alerts', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_absence_alerts'),
            'permission_callback' => array(__CLASS__, 'check_auth'),
        ));

        // Certificados
        register_rest_route(self::NAMESPACE, '/certificates', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_certificates'),
            'permission_callback' => array(__CLASS__, 'check_auth'),
        ));

        register_rest_route(self::NAMESPACE, '/certificates/issue', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'issue_certificate'),
            'permission_callback' => array(__CLASS__, 'check_auth'),
        ));

        register_rest_route(self::NAMESPACE, '/certificates/verify/(?P<code>[\w-]+)', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'verify_certificate'),
            'permission_callback' => '__return_true', // Validação pública por QR Code
        ));

        register_rest_route(self::NAMESPACE, '/certificates/lookup-by-cpf/(?P<cpf>[\w.-]+)', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'lookup_certificates_by_cpf'),
            'permission_callback' => '__return_true', // Consulta pública de 2ª via por CPF
        ));

        // Doações & Campanhas
        register_rest_route(self::NAMESPACE, '/campaigns', array(
            array(
                'methods' => 'GET',
                'callback' => array(__CLASS__, 'get_campaigns'),
                'permission_callback' => '__return_true',
            ),
            array(
                'methods' => 'POST',
                'callback' => array(__CLASS__, 'create_campaign'),
                'permission_callback' => array(__CLASS__, 'check_auth'),
            ),
        ));

        register_rest_route(self::NAMESPACE, '/campaigns/(?P<id>[a-zA-Z0-9_\-]+)', array(
            array(
                'methods' => 'GET',
                'callback' => array(__CLASS__, 'get_campaign'),
                'permission_callback' => '__return_true',
            ),
            array(
                'methods' => 'PUT',
                'callback' => array(__CLASS__, 'update_campaign'),
                'permission_callback' => array(__CLASS__, 'check_auth'),
            ),
            array(
                'methods' => 'DELETE',
                'callback' => array(__CLASS__, 'delete_campaign'),
                'permission_callback' => array(__CLASS__, 'check_auth'),
            ),
        ));

        register_rest_route(self::NAMESPACE, '/campaigns/(?P<id>[a-zA-Z0-9_\-]+)/status', array(
            'methods' => 'PATCH',
            'callback' => array(__CLASS__, 'update_campaign_status'),
            'permission_callback' => array(__CLASS__, 'check_auth'),
        ));

        register_rest_route(self::NAMESPACE, '/donations', array(
            array(
                'methods' => 'GET',
                'callback' => array(__CLASS__, 'get_donations'),
                'permission_callback' => array(__CLASS__, 'check_auth'),
            ),
            array(
                'methods' => 'POST',
                'callback' => array(__CLASS__, 'create_donation'),
                'permission_callback' => array(__CLASS__, 'check_auth'),
            ),
        ));

        register_rest_route(self::NAMESPACE, '/donations/(?P<id>[\w-]+)', array(
            'methods' => 'DELETE',
            'callback' => array(__CLASS__, 'delete_donation'),
            'permission_callback' => array(__CLASS__, 'check_auth'),
        ));

        // Notificações
        register_rest_route(self::NAMESPACE, '/notifications', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_notifications'),
            'permission_callback' => array(__CLASS__, 'check_auth'),
        ));

        register_rest_route(self::NAMESPACE, '/notifications/send', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'send_notification'),
            'permission_callback' => array(__CLASS__, 'check_auth'),
        ));

        register_rest_route(self::NAMESPACE, '/notifications/whatsapp-link', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'get_whatsapp_link'),
            'permission_callback' => array(__CLASS__, 'check_auth'),
        ));

        register_rest_route(self::NAMESPACE, '/notifications/email-send', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'send_email_notification'),
            'permission_callback' => array(__CLASS__, 'check_auth'),
        ));

        // Inteligência Artificial (Gemini)
        register_rest_route(self::NAMESPACE, '/ai/social-assessment', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'ai_social_assessment'),
            'permission_callback' => array(__CLASS__, 'check_auth'),
        ));

        register_rest_route(self::NAMESPACE, '/ai/grant-report', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'ai_grant_report'),
            'permission_callback' => array(__CLASS__, 'check_auth'),
        ));

        register_rest_route(self::NAMESPACE, '/ai/grant-proposal', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'ai_grant_proposal'),
            'permission_callback' => array(__CLASS__, 'check_auth'),
        ));

        // Backup & Migração do Banco de Dados
        register_rest_route(self::NAMESPACE, '/backup/export', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'export_backup'),
            'permission_callback' => array(__CLASS__, 'check_auth'),
        ));

        register_rest_route(self::NAMESPACE, '/backup/import', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'import_backup'),
            'permission_callback' => array(__CLASS__, 'check_auth'),
        ));

        register_rest_route(self::NAMESPACE, '/backup/summary', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_backup_summary'),
            'permission_callback' => array(__CLASS__, 'check_auth'),
        ));

        // Esporte, Cultura, Música, Jogos & Ações Comunitárias
        register_rest_route(self::NAMESPACE, '/activities', array(
            array(
                'methods' => 'GET',
                'callback' => array(__CLASS__, 'get_activities'),
                'permission_callback' => '__return_true',
            ),
            array(
                'methods' => 'POST',
                'callback' => array(__CLASS__, 'create_activity'),
                'permission_callback' => array(__CLASS__, 'check_auth'),
            ),
        ));

        register_rest_route(self::NAMESPACE, '/activities/(?P<id>[a-zA-Z0-9_\-]+)', array(
            array(
                'methods' => 'GET',
                'callback' => array(__CLASS__, 'get_activity'),
                'permission_callback' => '__return_true',
            ),
            array(
                'methods' => 'PUT',
                'callback' => array(__CLASS__, 'update_activity'),
                'permission_callback' => array(__CLASS__, 'check_auth'),
            ),
            array(
                'methods' => 'DELETE',
                'callback' => array(__CLASS__, 'delete_activity'),
                'permission_callback' => array(__CLASS__, 'check_auth'),
            ),
        ));

        register_rest_route(self::NAMESPACE, '/activities/(?P<id>[a-zA-Z0-9_\-]+)/status', array(
            'methods' => 'PATCH',
            'callback' => array(__CLASS__, 'update_activity_status'),
            'permission_callback' => array(__CLASS__, 'check_auth'),
        ));

        register_rest_route(self::NAMESPACE, '/activities/(?P<id>[a-zA-Z0-9_\-]+)/participants', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'add_activity_participant'),
            'permission_callback' => array(__CLASS__, 'check_auth'),
        ));

        register_rest_route(self::NAMESPACE, '/activities/(?P<id>[a-zA-Z0-9_\-]+)/participants/(?P<participantId>[a-zA-Z0-9_\-]+)', array(
            array(
                'methods' => 'PUT',
                'callback' => array(__CLASS__, 'update_activity_participant'),
                'permission_callback' => array(__CLASS__, 'check_auth'),
            ),
            array(
                'methods' => 'DELETE',
                'callback' => array(__CLASS__, 'delete_activity_participant'),
                'permission_callback' => array(__CLASS__, 'check_auth'),
            ),
        ));

        register_rest_route(self::NAMESPACE, '/activities/(?P<id>[a-zA-Z0-9_\-]+)/supporters', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'add_activity_supporter'),
            'permission_callback' => array(__CLASS__, 'check_auth'),
        ));

        register_rest_route(self::NAMESPACE, '/activities/(?P<id>[a-zA-Z0-9_\-]+)/supporters/(?P<supporterId>[a-zA-Z0-9_\-]+)', array(
            'methods' => 'DELETE',
            'callback' => array(__CLASS__, 'delete_activity_supporter'),
            'permission_callback' => array(__CLASS__, 'check_auth'),
        ));

        // Saúde & Jurídico - Atendimentos e Profissionais Voluntários
        register_rest_route(self::NAMESPACE, '/attendances/stats', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_attendance_stats'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route(self::NAMESPACE, '/attendances/professionals', array(
            array(
                'methods' => 'GET',
                'callback' => array(__CLASS__, 'get_attendance_professionals'),
                'permission_callback' => '__return_true',
            ),
            array(
                'methods' => 'POST',
                'callback' => array(__CLASS__, 'create_attendance_professional'),
                'permission_callback' => array(__CLASS__, 'check_auth'),
            ),
        ));

        register_rest_route(self::NAMESPACE, '/attendances/professionals/(?P<id>[a-zA-Z0-9_\-]+)', array(
            array(
                'methods' => 'GET',
                'callback' => array(__CLASS__, 'get_attendance_professional'),
                'permission_callback' => '__return_true',
            ),
            array(
                'methods' => 'PUT',
                'callback' => array(__CLASS__, 'update_attendance_professional'),
                'permission_callback' => array(__CLASS__, 'check_auth'),
            ),
            array(
                'methods' => 'DELETE',
                'callback' => array(__CLASS__, 'delete_attendance_professional'),
                'permission_callback' => array(__CLASS__, 'check_auth'),
            ),
        ));

        register_rest_route(self::NAMESPACE, '/attendances', array(
            array(
                'methods' => 'GET',
                'callback' => array(__CLASS__, 'get_service_attendances'),
                'permission_callback' => '__return_true',
            ),
            array(
                'methods' => 'POST',
                'callback' => array(__CLASS__, 'create_service_attendance'),
                'permission_callback' => array(__CLASS__, 'check_auth'),
            ),
        ));

        register_rest_route(self::NAMESPACE, '/attendances/available-slots', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_available_slots'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route(self::NAMESPACE, '/attendances/public-schedule', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'create_public_schedule'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route(self::NAMESPACE, '/attendances/(?P<id>[a-zA-Z0-9_\-]+)', array(
            array(
                'methods' => 'GET',
                'callback' => array(__CLASS__, 'get_service_attendance'),
                'permission_callback' => '__return_true',
            ),
            array(
                'methods' => 'PUT',
                'callback' => array(__CLASS__, 'update_service_attendance'),
                'permission_callback' => array(__CLASS__, 'check_auth'),
            ),
            array(
                'methods' => 'DELETE',
                'callback' => array(__CLASS__, 'delete_service_attendance'),
                'permission_callback' => array(__CLASS__, 'check_auth'),
            ),
        ));

        register_rest_route(self::NAMESPACE, '/attendances/(?P<id>[a-zA-Z0-9_\-]+)/status', array(
            'methods' => 'PATCH',
            'callback' => array(__CLASS__, 'update_service_attendance_status'),
            'permission_callback' => array(__CLASS__, 'check_auth'),
        ));

        // Gestão Financeira, Doações Monetárias & Prestação de Contas
        register_rest_route(self::NAMESPACE, '/financial/stats', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_financial_stats'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route(self::NAMESPACE, '/financial/transactions', array(
            array(
                'methods' => 'GET',
                'callback' => array(__CLASS__, 'get_financial_transactions'),
                'permission_callback' => '__return_true',
            ),
            array(
                'methods' => 'POST',
                'callback' => array(__CLASS__, 'create_financial_transaction'),
                'permission_callback' => array(__CLASS__, 'check_auth'),
            ),
        ));

        register_rest_route(self::NAMESPACE, '/financial/transactions/(?P<id>[a-zA-Z0-9_\-]+)', array(
            array(
                'methods' => 'GET',
                'callback' => array(__CLASS__, 'get_financial_transaction'),
                'permission_callback' => '__return_true',
            ),
            array(
                'methods' => 'PUT',
                'callback' => array(__CLASS__, 'update_financial_transaction'),
                'permission_callback' => array(__CLASS__, 'check_auth'),
            ),
            array(
                'methods' => 'DELETE',
                'callback' => array(__CLASS__, 'delete_financial_transaction'),
                'permission_callback' => array(__CLASS__, 'check_auth'),
            ),
        ));

        register_rest_route(self::NAMESPACE, '/financial/transactions/(?P<id>[a-zA-Z0-9_\-]+)/status', array(
            'methods' => 'PATCH',
            'callback' => array(__CLASS__, 'update_financial_transaction_status'),
            'permission_callback' => array(__CLASS__, 'check_auth'),
        ));
    }

    // ========================================================
    // CONTROLADORES / HANDLERS
    // ========================================================

    public static function get_stats($request) {
        $stats = PJB_Database::get_stats_summary();
        return rest_ensure_response(array('success' => true, 'data' => $stats));
    }

    public static function get_beneficiaries($request) {
        $filters = array(
            'query' => $request->get_param('query'),
            'neighborhood' => $request->get_param('neighborhood'),
            'vulnerability' => $request->get_param('vulnerability'),
            'status' => $request->get_param('status'),
        );
        $list = PJB_Database::search_beneficiaries($filters);
        return rest_ensure_response(array('success' => true, 'count' => count($list), 'data' => $list));
    }

    public static function get_beneficiary_details($request) {
        $id = $request->get_param('id');
        $ben = PJB_Database::get_beneficiary_by_id($id);
        if (!$ben) {
            return new WP_Error('not_found', 'Beneficiário não encontrado.', array('status' => 404));
        }

        $all_classes = PJB_Database::get_classes();
        $enrolled = array_filter($all_classes, function ($c) use ($id) {
            return in_array($id, $c['enrolledStudentIds']);
        });

        $attendance_stats = PJB_Database::get_student_stats($id);
        $certificates = PJB_Database::get_certificates_by_student($id);
        $donations = PJB_Database::get_donations_by_beneficiary($id);

        return rest_ensure_response(array(
            'success' => true,
            'data' => array(
                'beneficiary' => $ben,
                'enrolledClasses' => array_values($enrolled),
                'attendanceStats' => $attendance_stats,
                'certificates' => $certificates,
                'donationsReceived' => $donations,
            ),
        ));
    }

    public static function create_beneficiary($request) {
        $params = $request->get_json_params() ?: $request->get_params();

        if (empty($params['name']) || empty($params['cpf']) || empty($params['phone']) || empty($params['address']['neighborhood'])) {
            return new WP_Error('invalid_data', 'Nome, CPF, Telefone e Bairro são obrigatórios.', array('status' => 400));
        }

        $existing = PJB_Database::get_beneficiary_by_cpf($params['cpf']);
        if ($existing) {
            return new WP_Error('conflict', 'Já existe um cadastro com este CPF no Projeto Brasil dos Jovens.', array('status' => 409));
        }

        $created = PJB_Database::create_beneficiary($params);
        if (!$created) {
            return new WP_Error('db_error', 'Falha ao salvar beneficiário no banco de dados.', array('status' => 500));
        }

        // Gera link de boas-vindas para o WhatsApp
        $clean_phone = preg_replace('/\D/', '', $created['phone']);
        $whatsapp_link = null;
        if (!empty($clean_phone)) {
            $msg = "Olá, {$created['name']}! Bem-vindo(a) ao Projeto Brasil dos Jovens (PJB) em Castanhal! Seu cadastro institucional foi confirmado com sucesso. Em breve informaremos sobre o início das turmas e oficinas!";
            $whatsapp_link = "https://wa.me/55{$clean_phone}?text=" . rawurlencode($msg);
        }

        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Cadastro realizado com sucesso!',
            'data' => $created,
            'whatsappLink' => $whatsapp_link,
        ));
    }

    public static function update_beneficiary($request) {
        $id = $request->get_param('id');
        $params = $request->get_json_params() ?: $request->get_params();

        $updated = PJB_Database::update_beneficiary($id, $params);
        if (!$updated) {
            return new WP_Error('not_found', 'Beneficiário não encontrado para atualização.', array('status' => 404));
        }

        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Cadastro atualizado com sucesso.',
            'data' => $updated,
        ));
    }

    public static function delete_beneficiary($request) {
        $id = $request->get_param('id');
        $deleted = PJB_Database::delete_beneficiary($id);
        if (!$deleted) {
            return new WP_Error('not_found', 'Beneficiário não encontrado.', array('status' => 404));
        }
        return rest_ensure_response(array('success' => true, 'message' => 'Registro excluído com sucesso.'));
    }

    public static function get_courses($request) {
        $list = PJB_Database::get_courses();
        return rest_ensure_response(array('success' => true, 'count' => count($list), 'data' => $list));
    }

    public static function create_course($request) {
        $params = $request->get_json_params() ?: $request->get_params();
        if (empty($params['name']) || empty($params['workloadHours'])) {
            return new WP_Error('invalid_data', 'Nome e carga horária são obrigatórios.', array('status' => 400));
        }
        $created = PJB_Database::create_course($params);
        return rest_ensure_response(array('success' => true, 'data' => $created));
    }

    public static function update_course($request) {
        $id = $request->get_param('id');
        $params = $request->get_json_params() ?: $request->get_params();
        $updated = PJB_Database::update_course($id, $params);
        if (!$updated) {
            return new WP_Error('not_found', 'Curso não encontrado para atualização.', array('status' => 404));
        }
        return rest_ensure_response(array('success' => true, 'message' => 'Curso atualizado com sucesso.', 'data' => $updated));
    }

    public static function delete_course($request) {
        $id = $request->get_param('id');
        $all_classes = PJB_Database::get_classes();
        foreach ($all_classes as $c) {
            if ($c['courseId'] === $id) {
                return new WP_Error('has_linked_classes', 'Não é possível excluir este curso pois existem turmas vinculadas a ele.', array('status' => 400));
            }
        }
        $deleted = PJB_Database::delete_course($id);
        if (!$deleted) {
            return new WP_Error('not_found', 'Curso não encontrado.', array('status' => 404));
        }
        return rest_ensure_response(array('success' => true, 'message' => 'Curso excluído com sucesso.'));
    }

    public static function get_classes($request) {
        $list = PJB_Database::get_classes();
        return rest_ensure_response(array('success' => true, 'count' => count($list), 'data' => $list));
    }

    public static function create_class($request) {
        $params = $request->get_json_params() ?: $request->get_params();
        if (empty($params['courseId'])) {
            return new WP_Error('invalid_data', 'Curso selecionado é obrigatório.', array('status' => 400));
        }

        $course = PJB_Database::get_course_by_id($params['courseId']);
        if (!$course) {
            return new WP_Error('not_found', 'Curso não existe.', array('status' => 400));
        }

        $params['courseName'] = $course['name'];
        if (empty($params['code'])) {
            $params['code'] = 'TURMA-' . substr(time(), -4);
        }

        $created = PJB_Database::create_class($params);
        return rest_ensure_response(array('success' => true, 'data' => $created));
    }

    public static function update_class($request) {
        $id = $request->get_param('id');
        $params = $request->get_json_params() ?: $request->get_params();
        $updated = PJB_Database::update_class($id, $params);
        if (!$updated) {
            return new WP_Error('not_found', 'Turma não encontrada para atualização.', array('status' => 404));
        }
        return rest_ensure_response(array('success' => true, 'message' => 'Turma atualizada com sucesso.', 'data' => $updated));
    }

    public static function delete_class($request) {
        $id = $request->get_param('id');
        $deleted = PJB_Database::delete_class($id);
        if (!$deleted) {
            return new WP_Error('not_found', 'Turma não encontrada.', array('status' => 404));
        }
        return rest_ensure_response(array('success' => true, 'message' => 'Turma excluída com sucesso.'));
    }

    public static function enroll_student($request) {
        $class_id = $request->get_param('id');
        $params = $request->get_json_params() ?: $request->get_params();
        $student_id = $params['studentId'] ?? '';
        $student_ids = $params['studentIds'] ?? null;

        if (empty($student_id) && empty($student_ids)) {
            return new WP_Error('invalid_data', 'studentId ou studentIds é obrigatório.', array('status' => 400));
        }

        $target_ids = !empty($student_ids) && is_array($student_ids) ? $student_ids : array($student_id);

        $updated = PJB_Database::enroll_student($class_id, $target_ids);
        if (!$updated) {
            return new WP_Error('not_found', 'Turma ou aluno não localizado.', array('status' => 404));
        }
        return rest_ensure_response(array('success' => true, 'message' => 'Matrícula efetuada com sucesso.', 'data' => $updated));
    }

    public static function remove_student($request) {
        $class_id = $request->get_param('id');
        $student_id = $request->get_param('studentId');

        $updated = PJB_Database::remove_student($class_id, $student_id);
        if (!$updated) {
            return new WP_Error('not_found', 'Turma não encontrada.', array('status' => 404));
        }
        return rest_ensure_response(array('success' => true, 'message' => 'Aluno desmatriculado da turma.', 'data' => $updated));
    }

    public static function public_enroll_class($request) {
        $class_id = $request->get_param('id');
        $params = $request->get_json_params() ?: $request->get_params();

        $cls = PJB_Database::get_class_by_id($class_id);
        if (!$cls) {
            return new WP_Error('not_found', 'Turma selecionada não encontrada.', array('status' => 404));
        }
        if ($cls['status'] === 'concluida') {
            return new WP_Error('invalid_status', 'Esta turma já foi encerrada.', array('status' => 400));
        }

        $student_id = $params['studentId'] ?? '';
        $cpf = preg_replace('/\D/', '', $params['cpf'] ?? '');
        $target_student = null;

        if (!empty($student_id)) {
            $target_student = PJB_Database::get_beneficiary_by_id($student_id);
        }

        if (!$target_student && strlen($cpf) === 11) {
            $by_cpf = PJB_Database::search_beneficiaries(array('cpf' => $cpf));
            if (!empty($by_cpf)) {
                $target_student = $by_cpf[0];
            }
        }

        if (!$target_student) {
            $name = sanitize_text_field($params['name'] ?? '');
            $phone = sanitize_text_field($params['phone'] ?? '');
            if (empty($name)) {
                return new WP_Error('invalid_data', 'Nome completo é obrigatório.', array('status' => 400));
            }
            if (strlen($cpf) !== 11) {
                return new WP_Error('invalid_data', 'CPF válido (11 dígitos) é obrigatório.', array('status' => 400));
            }
            if (empty($phone)) {
                return new WP_Error('invalid_data', 'Telefone / WhatsApp é obrigatório.', array('status' => 400));
            }

            $birth_date = !empty($params['birthDate']) ? sanitize_text_field($params['birthDate']) : '2008-01-01';
            $b_year = (int) substr($birth_date, 0, 4);
            $age = $b_year > 1900 ? max(1, (int) date('Y') - $b_year) : 16;

            $new_bene = array(
                'name' => $name,
                'cpf' => $cpf,
                'phone' => $phone,
                'email' => sanitize_email($params['email'] ?? ''),
                'birthDate' => $birth_date,
                'age' => $age,
                'street' => sanitize_text_field($params['address'] ?? 'Não informado'),
                'neighborhood' => sanitize_text_field($params['neighborhood'] ?? 'Centro'),
                'city' => 'Castanhal',
                'state' => 'PA',
                'guardianName' => sanitize_text_field($params['guardianName'] ?? ''),
                'guardianPhone' => sanitize_text_field($params['guardianPhone'] ?? ''),
                'schoolingStatus' => sanitize_text_field($params['schoolingStatus'] ?? 'Não informado'),
                'publicSchoolName' => sanitize_text_field($params['publicSchoolName'] ?? ''),
                'vulnerabilityLevel' => 'baixa',
                'serviceInterest' => $cls['courseName'],
            );
            $target_student = PJB_Database::create_beneficiary($new_bene);
        }

        if (!$target_student) {
            return new WP_Error('creation_failed', 'Falha ao processar cadastro do aluno.', array('status' => 500));
        }

        $enrolled_ids = $cls['enrolledStudentIds'] ?? array();
        $is_already_enrolled = in_array($target_student['id'], $enrolled_ids);
        $updated_class = $cls;

        if (!$is_already_enrolled) {
            if (count($enrolled_ids) >= (int) ($cls['maxStudents'] ?? 25)) {
                return new WP_Error('no_vacancies', 'Desculpe, as vagas para esta turma acabaram de ser preenchidas.', array('status' => 400));
            }
            $updated_class = PJB_Database::enroll_student($cls['id'], array($target_student['id']));
        }

        $course = PJB_Database::get_course_by_id($cls['courseId']);
        $protocol = 'MAT-' . date('Y') . '-' . ($cls['code'] ?: 'PJB') . '-' . strtoupper(substr(wp_generate_password(6, false), 0, 5));

        $phone_clean = preg_replace('/\D/', '', $target_student['phone'] ?: ($params['phone'] ?? ''));
        $whats_msg = "Olá, " . $target_student['name'] . "! 👋 Sua matrícula no curso *" . ($course['name'] ?? $cls['courseName']) . "* (Turma: " . $cls['code'] . ") foi confirmada com sucesso pelo Projeto Brasil dos Jovens.\n\n📋 *Protocolo:* " . $protocol . "\n⏰ *Horário:* " . $cls['schedule'] . "\n📍 *Local:* " . ($cls['room'] ?: 'Sede PJB Castanhal') . "\n📅 *Início:* " . $cls['startDate'] . "\n\nGuarde este comprovante e nos vemos na aula!";
        $whatsapp_link = !empty($phone_clean) ? 'https://api.whatsapp.com/send?phone=55' . $phone_clean . '&text=' . urlencode($whats_msg) : null;

        return rest_ensure_response(array(
            'success' => true,
            'message' => $is_already_enrolled ? 'Você já está matriculado nesta turma.' : 'Matrícula confirmada com sucesso!',
            'protocol' => $protocol,
            'data' => array(
                'protocol' => $protocol,
                'student' => $target_student,
                'classGroup' => $updated_class ?: $cls,
                'course' => $course ?: array('name' => $cls['courseName'], 'workloadHours' => 40, 'instructorName' => 'Instrutor PJB'),
                'whatsappLink' => $whatsapp_link,
                'alreadyEnrolled' => $is_already_enrolled,
            ),
        ));
    }

    public static function get_class_students($request) {
        $class_id = $request->get_param('id');
        $students = PJB_Database::get_class_students($class_id);
        return rest_ensure_response(array('success' => true, 'count' => count($students), 'data' => $students));
    }

    public static function get_attendance($request) {
        $class_id = $request->get_param('classId');
        $list = PJB_Database::get_attendance_by_class($class_id);
        return rest_ensure_response(array('success' => true, 'data' => $list));
    }

    public static function record_attendance($request) {
        $params = $request->get_json_params() ?: $request->get_params();
        if (empty($params['classId']) || empty($params['date']) || !isset($params['records'])) {
            return new WP_Error('invalid_data', 'classId, date e records são obrigatórios.', array('status' => 400));
        }

        $recorded = PJB_Database::record_attendance(
            $params['classId'],
            $params['date'],
            $params['topic'] ?? 'Aula regular',
            $params['records']
        );

        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Frequência registrada com sucesso.',
            'data' => $recorded,
        ));
    }

    public static function get_absence_alerts($request) {
        // Retorna alertas vazios ou computados
        return rest_ensure_response(array('success' => true, 'count' => 0, 'data' => array()));
    }

    public static function get_certificates($request) {
        $list = PJB_Database::get_certificates();
        return rest_ensure_response(array('success' => true, 'count' => count($list), 'data' => $list));
    }

    public static function issue_certificate($request) {
        $params = $request->get_json_params() ?: $request->get_params();
        if (empty($params['studentId']) || empty($params['courseId'])) {
            return new WP_Error('invalid_data', 'studentId e courseId são obrigatórios.', array('status' => 400));
        }

        $issued = PJB_Database::issue_certificate(
            $params['studentId'],
            $params['courseId'],
            $params['issuedBy'] ?? 'Coordenação Geral PJB'
        );

        if (!$issued) {
            return new WP_Error('issue_failed', 'Não foi possível emitir o certificado. Verifique aluno e curso.', array('status' => 400));
        }

        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Certificado emitido com sucesso.',
            'data' => $issued,
        ));
    }

    public static function verify_certificate($request) {
        $code = $request->get_param('code');
        $cert = PJB_Database::get_certificate_by_code($code);

        if (!$cert) {
            return new WP_Error('not_found', 'Código de certificado não localizado ou inválido no registro oficial do PJB.', array('status' => 404));
        }

        $sig = PJB_Crypto::generate_signature(array(
            'code' => $cert['code'],
            'studentId' => $cert['studentId'],
            'courseId' => $cert['courseId'],
            'issueDate' => $cert['issueDate'],
            'workloadHours' => $cert['workloadHours'],
        ));

        return rest_ensure_response(array(
            'success' => true,
            'valid' => true,
            'message' => 'Certificado autêntico expedido pelo Projeto Brasil dos Jovens (Castanhal/PA).',
            'data' => array_merge($cert, array(
                'digitalSignature' => $sig['signature'],
                'signatureAlgorithm' => $sig['algorithm'],
                'keyId' => $sig['keyId'],
                'institution' => $sig['institution'],
                'signedAt' => $sig['signedAt'],
            )),
        ));
    }

    public static function lookup_certificates_by_cpf($request) {
        $raw_cpf = $request->get_param('cpf');
        $clean_cpf = preg_replace('/\D/', '', $raw_cpf);

        if (empty($clean_cpf) || strlen($clean_cpf) < 11) {
            return new WP_Error('invalid_cpf', 'Por favor, informe um CPF válido com 11 dígitos.', array('status' => 400));
        }

        $certs = PJB_Database::get_certificates_by_cpf($clean_cpf);

        if (empty($certs)) {
            return new WP_Error('not_found', 'Nenhum certificado emitido foi localizado para este CPF no Projeto Brasil dos Jovens.', array('status' => 404));
        }

        $enhanced = array_map(function ($cert) {
            $sig = PJB_Crypto::generate_signature(array(
                'code' => $cert['code'],
                'studentId' => $cert['studentId'],
                'courseId' => $cert['courseId'],
                'issueDate' => $cert['issueDate'],
                'workloadHours' => $cert['workloadHours'],
            ));

            return array_merge($cert, array(
                'digitalSignature' => $sig['signature'],
                'signatureAlgorithm' => $sig['algorithm'],
                'keyId' => $sig['keyId'],
                'institution' => $sig['institution'],
                'signedAt' => $sig['signedAt'],
            ));
        }, $certs);

        return rest_ensure_response(array(
            'success' => true,
            'count' => count($enhanced),
            'message' => count($enhanced) . ' certificado(s) localizado(s) com sucesso.',
            'data' => $enhanced,
        ));
    }

    public static function get_campaigns($request) {
        $list = PJB_Database::get_campaigns();
        return rest_ensure_response(array('success' => true, 'count' => count($list), 'data' => $list));
    }

    public static function get_campaign($request) {
        $id = $request->get_param('id');
        $camp = PJB_Database::get_campaign_by_id($id);
        if (!$camp) {
            return new WP_Error('not_found', 'Campanha não encontrada.', array('status' => 404));
        }
        $donations = PJB_Database::get_donations();
        $linked = array_values(array_filter($donations, function($d) use ($id) {
            return !empty($d['campaignId']) && $d['campaignId'] === $id;
        }));
        $camp['donations'] = $linked;
        return rest_ensure_response(array('success' => true, 'data' => $camp));
    }

    public static function create_campaign($request) {
        $params = $request->get_json_params() ?: $request->get_params();
        $created = PJB_Database::create_campaign($params);
        return rest_ensure_response(array('success' => true, 'data' => $created));
    }

    public static function update_campaign($request) {
        $id = $request->get_param('id');
        $params = $request->get_json_params() ?: $request->get_params();
        $updated = PJB_Database::update_campaign($id, $params);
        if (!$updated) {
            return new WP_Error('not_found', 'Campanha não encontrada.', array('status' => 404));
        }
        return rest_ensure_response(array('success' => true, 'data' => $updated));
    }

    public static function update_campaign_status($request) {
        $id = $request->get_param('id');
        $params = $request->get_json_params() ?: $request->get_params();
        $status = sanitize_text_field($params['status'] ?? '');
        if (!in_array($status, array('planejamento', 'em_andamento', 'concluida'))) {
            return new WP_Error('invalid_status', 'Status inválido. Use planejamento, em_andamento ou concluida.', array('status' => 400));
        }
        $updated = PJB_Database::update_campaign($id, array('status' => $status));
        if (!$updated) {
            return new WP_Error('not_found', 'Campanha não encontrada.', array('status' => 404));
        }
        return rest_ensure_response(array('success' => true, 'data' => $updated));
    }

    public static function delete_campaign($request) {
        $id = $request['id'];
        $deleted = PJB_Database::delete_campaign($id);
        return rest_ensure_response(array('success' => $deleted));
    }

    public static function get_donations($request) {
        $list = PJB_Database::get_donations();
        $inv = PJB_Database::get_inventory_summary();
        return rest_ensure_response(array(
            'success' => true,
            'inventory' => $inv,
            'count' => count($list),
            'data' => $list,
        ));
    }

    public static function create_donation($request) {
        $params = $request->get_json_params() ?: $request->get_params();
        if (empty($params['type']) || empty($params['category']) || empty($params['itemDescription']) || empty($params['quantity'])) {
            return new WP_Error('invalid_data', 'Campos essenciais da movimentação não informados.', array('status' => 400));
        }

        if ($params['type'] === 'saida' && !empty($params['beneficiaryId'])) {
            $ben = PJB_Database::get_beneficiary_by_id($params['beneficiaryId']);
            if ($ben) {
                $params['beneficiaryName'] = $ben['name'];
            }
        }

        $created = PJB_Database::create_donation($params);
        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Movimentação registrada com sucesso.',
            'data' => $created,
        ));
    }

    public static function delete_donation($request) {
        $id = $request->get_param('id');
        $deleted = PJB_Database::delete_donation($id);
        if (!$deleted) {
            return new WP_Error('not_found', 'Registro de doação não encontrado.', array('status' => 404));
        }
        return rest_ensure_response(array('success' => true, 'message' => 'Registro de doação excluído com sucesso.'));
    }

    public static function get_notifications($request) {
        $list = PJB_Database::get_notifications();
        return rest_ensure_response(array('success' => true, 'count' => count($list), 'data' => $list));
    }

    public static function send_notification($request) {
        $params = $request->get_json_params() ?: $request->get_params();
        if (empty($params['recipientPhone']) || empty($params['recipientName']) || empty($params['message'])) {
            return new WP_Error('invalid_data', 'recipientPhone, recipientName e message são obrigatórios.', array('status' => 400));
        }

        $log = PJB_Database::log_notification($params);
        return rest_ensure_response(array('success' => true, 'message' => 'Notificação registrada com sucesso.', 'data' => $log));
    }

    public static function get_whatsapp_link($request) {
        $params = $request->get_json_params() ?: $request->get_params();
        $phone = preg_replace('/\D/', '', $params['phone'] ?? '');
        $msg = $params['message'] ?? '';
        $link = "https://wa.me/55{$phone}?text=" . rawurlencode($msg);
        return rest_ensure_response(array('success' => true, 'link' => $link));
    }

    public static function send_email_notification($request) {
        $params = $request->get_json_params() ?: $request->get_params();
        $to = sanitize_email($params['recipientEmail'] ?? $params['to'] ?? '');
        $name = sanitize_text_field($params['recipientName'] ?? '');
        $subject = sanitize_text_field($params['subject'] ?? 'Comunicado Oficial — Projeto Brasil dos Jovens');
        $message = $params['message'] ?? '';
        $beneficiary_id = sanitize_text_field($params['beneficiaryId'] ?? '');

        if (empty($to) || !is_email($to) || empty($message)) {
            return new WP_Error('invalid_data', 'Destinatário (e-mail válido) e mensagem são obrigatórios.', array('status' => 400));
        }

        if (class_exists('PJB_SMTP')) {
            $res = PJB_SMTP::send_email($to, $subject, $message, true);
        } else {
            $sent = wp_mail($to, $subject, $message, array('Content-Type: text/html; charset=UTF-8'));
            $res = array('success' => $sent, 'error' => $sent ? null : 'Falha no wp_mail.');
        }

        if (!$res['success']) {
            return new WP_Error('mail_failed', 'Falha ao enviar e-mail: ' . ($res['error'] ?? 'Erro no servidor SMTP'), array('status' => 500));
        }

        // Registra no histórico de notificações
        $log = PJB_Database::log_notification(array(
            'channel' => 'email',
            'type' => $params['type'] ?? 'custom',
            'recipientName' => $name ?: $to,
            'recipientPhone' => $to,
            'message' => $message,
            'beneficiaryId' => $beneficiary_id,
            'status' => 'sent',
        ));

        return rest_ensure_response(array(
            'success' => true,
            'message' => 'E-mail enviado com sucesso via SMTP!',
            'data' => $log,
        ));
    }

    public static function ai_social_assessment($request) {
        $params = $request->get_json_params() ?: $request->get_params();
        $ben_id = $params['beneficiaryId'] ?? '';
        $ben = PJB_Database::get_beneficiary_by_id($ben_id);
        if (!$ben) {
            return new WP_Error('not_found', 'Beneficiário não encontrado.', array('status' => 404));
        }

        $all_classes = PJB_Database::get_classes();
        $classes = array_values(array_filter($all_classes, function ($c) use ($ben_id) {
            return in_array($ben_id, $c['enrolledStudentIds']);
        }));
        $donations = PJB_Database::get_donations_by_beneficiary($ben_id);

        $result = PJB_AI_Service::generate_social_assessment($ben, $classes, $donations);
        return rest_ensure_response(array('success' => true, 'data' => $result));
    }

    public static function ai_grant_report($request) {
        $summary = PJB_Database::get_stats_summary();
        $campaigns = PJB_Database::get_campaigns();
        $donations = PJB_Database::get_donations();

        $result = PJB_AI_Service::generate_grant_report($summary, $campaigns, $donations);
        return rest_ensure_response(array('success' => true, 'data' => $result));
    }

    public static function ai_grant_proposal($request) {
        $params = $request->get_json_params() ?: $request->get_params();
        $result = PJB_AI_Service::generate_grant_proposal($params);
        return rest_ensure_response(array('success' => true, 'data' => $result));
    }

    // ========================================================
    // BACKUP E MIGRAÇÃO DE DADOS
    // ========================================================

    public static function export_backup($request) {
        $backup = PJB_Database::export_backup();
        $response = rest_ensure_response($backup);
        $filename = 'pjb_backup_' . gmdate('Y-m-d_H-i-s') . '.json';
        $response->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        return $response;
    }

    public static function import_backup($request) {
        $params = $request->get_json_params();
        if (empty($params)) {
            $params = $request->get_params();
        }
        $mode = isset($params['mode']) ? sanitize_text_field($params['mode']) : 'merge';
        $payload = isset($params['data']) ? $params['data'] : $params;

        $result = PJB_Database::import_backup($payload, $mode);
        if (!$result['success']) {
            return new WP_Error('import_error', $result['error'] ?? 'Falha na importação do backup.', array('status' => 400));
        }

        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Backup importado com sucesso.',
            'imported' => $result['imported'] ?? array(),
            'stats' => PJB_Database::get_backup_summary(),
        ));
    }

    public static function get_backup_summary($request) {
        $summary = PJB_Database::get_backup_summary();
        return rest_ensure_response(array('success' => true, 'data' => $summary));
    }

    // ========================================================
    // AÇÕES CULTURAIS, ESPORTIVAS, MÚSICA & JOGOS
    // ========================================================
    public static function get_activities($request) {
        $filters = array(
            'type' => $request->get_param('type') ?: 'todos',
            'status' => $request->get_param('status') ?: 'todos',
            'modality' => $request->get_param('modality') ?: 'todas',
        );
        $list = PJB_Database::get_cultural_activities($filters);
        return rest_ensure_response(array('success' => true, 'count' => count($list), 'data' => $list));
    }

    public static function get_activity($request) {
        $id = $request->get_param('id');
        $act = PJB_Database::get_cultural_activity_by_id($id);
        if (!$act) {
            return new WP_Error('not_found', 'Ação cultural/esportiva não encontrada.', array('status' => 404));
        }
        return rest_ensure_response(array('success' => true, 'data' => $act));
    }

    public static function create_activity($request) {
        $params = $request->get_json_params() ?: $request->get_params();
        if (empty($params['title']) || empty($params['modality'])) {
            return new WP_Error('invalid_data', 'Título e modalidade são obrigatórios.', array('status' => 400));
        }
        $created = PJB_Database::create_cultural_activity($params);
        if (!$created) {
            return new WP_Error('create_failed', 'Erro ao criar ação cultural/esportiva.', array('status' => 500));
        }
        return rest_ensure_response(array('success' => true, 'data' => $created));
    }

    public static function update_activity($request) {
        $id = $request->get_param('id');
        $params = $request->get_json_params() ?: $request->get_params();
        $updated = PJB_Database::update_cultural_activity($id, $params);
        if (!$updated) {
            return new WP_Error('not_found', 'Ação cultural/esportiva não encontrada.', array('status' => 404));
        }
        return rest_ensure_response(array('success' => true, 'data' => $updated));
    }

    public static function update_activity_status($request) {
        $id = $request->get_param('id');
        $params = $request->get_json_params() ?: $request->get_params();
        $status = sanitize_text_field($params['status'] ?? '');
        if (!in_array($status, array('planejado', 'em_andamento', 'concluido', 'cancelado'))) {
            return new WP_Error('invalid_status', 'Status inválido. Use planejado, em_andamento, concluido ou cancelado.', array('status' => 400));
        }
        $updated = PJB_Database::update_cultural_activity($id, array('status' => $status));
        if (!$updated) {
            return new WP_Error('not_found', 'Ação cultural/esportiva não encontrada.', array('status' => 404));
        }
        return rest_ensure_response(array('success' => true, 'data' => $updated));
    }

    public static function delete_activity($request) {
        $id = $request->get_param('id');
        $deleted = PJB_Database::delete_cultural_activity($id);
        return rest_ensure_response(array('success' => $deleted));
    }

    public static function add_activity_participant($request) {
        $id = $request->get_param('id');
        $params = $request->get_json_params() ?: $request->get_params();
        if (empty($params['beneficiaryName'])) {
            return new WP_Error('invalid_data', 'Nome do beneficiário participante é obrigatório.', array('status' => 400));
        }
        $updated = PJB_Database::add_cultural_participant($id, $params);
        if (!$updated) {
            return new WP_Error('not_found', 'Ação não encontrada.', array('status' => 404));
        }
        return rest_ensure_response(array('success' => true, 'data' => $updated));
    }

    public static function update_activity_participant($request) {
        $id = $request->get_param('id');
        $participant_id = $request->get_param('participantId');
        $params = $request->get_json_params() ?: $request->get_params();
        $updated = PJB_Database::update_cultural_participant($id, $participant_id, $params);
        if (!$updated) {
            return new WP_Error('not_found', 'Participante ou ação não encontrada.', array('status' => 404));
        }
        return rest_ensure_response(array('success' => true, 'data' => $updated));
    }

    public static function delete_activity_participant($request) {
        $id = $request->get_param('id');
        $participant_id = $request->get_param('participantId');
        $updated = PJB_Database::remove_cultural_participant($id, $participant_id);
        if (!$updated) {
            return new WP_Error('not_found', 'Participante ou ação não encontrada.', array('status' => 404));
        }
        return rest_ensure_response(array('success' => true, 'data' => $updated));
    }

    public static function add_activity_supporter($request) {
        $id = $request->get_param('id');
        $params = $request->get_json_params() ?: $request->get_params();
        if (empty($params['name']) || empty($params['contribution'])) {
            return new WP_Error('invalid_data', 'Nome e contribuição do apoiador/beneficiador são obrigatórios.', array('status' => 400));
        }
        $updated = PJB_Database::add_cultural_supporter($id, $params);
        if (!$updated) {
            return new WP_Error('not_found', 'Ação não encontrada.', array('status' => 404));
        }
        return rest_ensure_response(array('success' => true, 'data' => $updated));
    }

    public static function delete_activity_supporter($request) {
        $id = $request->get_param('id');
        $supporter_id = $request->get_param('supporterId');
        $updated = PJB_Database::remove_cultural_supporter($id, $supporter_id);
        if (!$updated) {
            return new WP_Error('not_found', 'Apoiador ou ação não encontrada.', array('status' => 404));
        }
        return rest_ensure_response(array('success' => true, 'data' => $updated));
    }

    // ========================================================
    // SAÚDE & JURÍDICO - ATENDIMENTOS E PROFISSIONAIS
    // ========================================================

    public static function get_attendance_stats($request) {
        $stats = PJB_Database::get_attendance_stats();
        return rest_ensure_response(array('success' => true, 'data' => $stats));
    }

    public static function get_attendance_professionals($request) {
        $area = $request->get_param('area');
        $active = $request->get_param('active');
        $filters = array();
        if ($area) $filters['area'] = sanitize_text_field($area);
        if ($active !== null) $filters['active'] = $active;

        $list = PJB_Database::get_attendance_professionals($filters);
        return rest_ensure_response(array('success' => true, 'count' => count($list), 'data' => $list));
    }

    public static function get_attendance_professional($request) {
        $id = $request->get_param('id');
        $item = PJB_Database::get_attendance_professional_by_id($id);
        if (!$item) {
            return new WP_Error('not_found', 'Profissional não encontrado.', array('status' => 404));
        }
        return rest_ensure_response(array('success' => true, 'data' => $item));
    }

    public static function create_attendance_professional($request) {
        $params = $request->get_json_params() ?: $request->get_params();
        if (empty($params['name']) || empty($params['area']) || empty($params['specialty'])) {
            return new WP_Error('invalid_data', 'Nome, área e especialidade são campos obrigatórios.', array('status' => 400));
        }
        $created = PJB_Database::create_attendance_professional($params);
        if (!$created) {
            return new WP_Error('create_failed', 'Erro ao cadastrar profissional.', array('status' => 500));
        }
        return rest_ensure_response(array('success' => true, 'data' => $created));
    }

    public static function update_attendance_professional($request) {
        $id = $request->get_param('id');
        $params = $request->get_json_params() ?: $request->get_params();
        $updated = PJB_Database::update_attendance_professional($id, $params);
        if (!$updated) {
            return new WP_Error('not_found', 'Profissional não encontrado para atualização.', array('status' => 404));
        }
        return rest_ensure_response(array('success' => true, 'data' => $updated));
    }

    public static function delete_attendance_professional($request) {
        $id = $request->get_param('id');
        $deleted = PJB_Database::delete_attendance_professional($id);
        return rest_ensure_response(array('success' => $deleted));
    }

    public static function get_service_attendances($request) {
        $filters = array(
            'area' => $request->get_param('area'),
            'status' => $request->get_param('status'),
            'specialty' => $request->get_param('specialty'),
            'beneficiaryId' => $request->get_param('beneficiaryId'),
            'search' => $request->get_param('search'),
        );
        $list = PJB_Database::get_attendances($filters);
        return rest_ensure_response(array('success' => true, 'count' => count($list), 'data' => $list));
    }

    public static function get_service_attendance($request) {
        $id = $request->get_param('id');
        $item = PJB_Database::get_attendance_by_id($id);
        if (!$item) {
            return new WP_Error('not_found', 'Atendimento não encontrado.', array('status' => 404));
        }
        return rest_ensure_response(array('success' => true, 'data' => $item));
    }

    public static function create_service_attendance($request) {
        $params = $request->get_json_params() ?: $request->get_params();
        if (empty($params['beneficiaryName']) || empty($params['area']) || empty($params['specialty']) || empty($params['chiefComplaint'])) {
            return new WP_Error('invalid_data', 'Beneficiário, área, especialidade e queixa principal são obrigatórios.', array('status' => 400));
        }
        $created = PJB_Database::create_attendance($params);
        if (!$created) {
            return new WP_Error('create_failed', 'Erro ao registrar atendimento.', array('status' => 500));
        }
        return rest_ensure_response(array('success' => true, 'data' => $created));
    }

    public static function update_service_attendance($request) {
        $id = $request->get_param('id');
        $params = $request->get_json_params() ?: $request->get_params();
        $updated = PJB_Database::update_attendance($id, $params);
        if (!$updated) {
            return new WP_Error('not_found', 'Atendimento não encontrado para atualização.', array('status' => 404));
        }
        return rest_ensure_response(array('success' => true, 'data' => $updated));
    }

    public static function update_service_attendance_status($request) {
        $id = $request->get_param('id');
        $params = $request->get_json_params() ?: $request->get_params();
        $status = sanitize_text_field($params['status'] ?? '');
        $updated = PJB_Database::update_attendance_status($id, $status);
        if (!$updated) {
            return new WP_Error('invalid_status', 'Status inválido ou atendimento não encontrado.', array('status' => 400));
        }
        return rest_ensure_response(array('success' => true, 'data' => $updated));
    }

    public static function delete_service_attendance($request) {
        $id = $request->get_param('id');
        $deleted = PJB_Database::delete_attendance($id);
        return rest_ensure_response(array('success' => $deleted));
    }

    // ========================================================
    // GESTÃO FINANCEIRA & DOAÇÕES MONETÁRIAS HANDLERS
    // ========================================================
    public static function get_financial_stats($request) {
        $stats = PJB_Database::get_financial_stats();
        return rest_ensure_response(array('success' => true, 'data' => $stats));
    }

    public static function get_financial_transactions($request) {
        $filters = array(
            'type' => $request->get_param('type'),
            'status' => $request->get_param('status'),
            'paymentMethod' => $request->get_param('paymentMethod'),
            'search' => $request->get_param('search'),
        );
        $list = PJB_Database::get_financial_transactions($filters);
        return rest_ensure_response(array('success' => true, 'count' => count($list), 'data' => $list));
    }

    public static function get_financial_transaction($request) {
        $id = $request->get_param('id');
        $item = PJB_Database::get_financial_transaction_by_id($id);
        if (!$item) {
            return new WP_Error('not_found', 'Lançamento financeiro não encontrado.', array('status' => 404));
        }
        return rest_ensure_response(array('success' => true, 'data' => $item));
    }

    public static function create_financial_transaction($request) {
        $params = $request->get_json_params() ?: $request->get_params();
        if (empty($params['type']) || empty($params['description']) || !isset($params['amount'])) {
            return new WP_Error('invalid_data', 'Tipo, descrição e valor são obrigatórios.', array('status' => 400));
        }
        $created = PJB_Database::create_financial_transaction($params);
        if (!$created) {
            return new WP_Error('create_failed', 'Erro ao registrar movimentação financeira.', array('status' => 500));
        }
        return rest_ensure_response(array('success' => true, 'data' => $created));
    }

    public static function update_financial_transaction($request) {
        $id = $request->get_param('id');
        $params = $request->get_json_params() ?: $request->get_params();
        $updated = PJB_Database::update_financial_transaction($id, $params);
        if (!$updated) {
            return new WP_Error('not_found', 'Lançamento financeiro não encontrado para atualização.', array('status' => 404));
        }
        return rest_ensure_response(array('success' => true, 'data' => $updated));
    }

    public static function update_financial_transaction_status($request) {
        $id = $request->get_param('id');
        $params = $request->get_json_params() ?: $request->get_params();
        $status = sanitize_text_field($params['status'] ?? '');
        $updated = PJB_Database::update_financial_transaction_status($id, $status);
        if (!$updated) {
            return new WP_Error('invalid_status', 'Status inválido ou lançamento não encontrado.', array('status' => 400));
        }
        return rest_ensure_response(array('success' => true, 'data' => $updated));
    }

    public static function delete_financial_transaction($request) {
        $id = $request->get_param('id');
        $deleted = PJB_Database::delete_financial_transaction($id);
        return rest_ensure_response(array('success' => $deleted));
    }

    // ========================================================
    // MÓDULO DE AGENDAMENTO PÚBLICO E CONSULTA DE BENEFICIÁRIO
    // ========================================================
    public static function lookup_beneficiary_by_cpf($request) {
        $raw_cpf = $request->get_param('cpf');
        $clean_cpf = preg_replace('/\D/', '', $raw_cpf);
        if (strlen($clean_cpf) < 11) {
            return new WP_Error('invalid_cpf', 'Por favor, informe um CPF válido com 11 dígitos.', array('status' => 400));
        }

        $beneficiary = PJB_Database::get_beneficiary_by_cpf($clean_cpf);
        if (!$beneficiary) {
            return rest_ensure_response(array(
                'success' => true,
                'found'   => false,
                'message' => 'Beneficiário não localizado para este CPF.',
            ));
        }

        return rest_ensure_response(array(
            'success' => true,
            'found'   => true,
            'data'    => array(
                'id'               => $beneficiary['id'],
                'name'             => $beneficiary['name'],
                'cpf'              => $beneficiary['cpf'],
                'birthDate'        => $beneficiary['birthDate'] ?? '',
                'phone'            => $beneficiary['phone'] ?? '',
                'email'            => $beneficiary['email'] ?? '',
                'neighborhood'     => $beneficiary['address']['neighborhood'] ?? '',
                'address'          => $beneficiary['address']['street'] ?? '',
                'city'             => $beneficiary['address']['city'] ?? 'Castanhal',
                'schoolingStatus'  => $beneficiary['schoolingStatus'] ?? '',
                'publicSchoolName' => $beneficiary['publicSchoolName'] ?? '',
                'guardianName'     => $beneficiary['guardianName'] ?? '',
                'guardianPhone'    => $beneficiary['guardianPhone'] ?? '',
            ),
        ));
    }

    public static function get_available_slots($request) {
        $professional_id = $request->get_param('professionalId');
        $date = sanitize_text_field($request->get_param('date'));
        if (empty($date)) {
            return new WP_Error('missing_date', 'A data do agendamento é obrigatória.', array('status' => 400));
        }

        $timestamp = strtotime($date);
        $day_of_week = intval(date('w', $timestamp)); // 0=Dom, 1=Seg, ..., 6=Sab

        $all_slots = array('08:00', '09:00', '10:00', '11:00', '14:00', '15:00', '16:00', '17:00');
        $prof = null;
        $is_available_date = true;
        $unavailability_reason = '';

        if (!empty($professional_id)) {
            $prof = PJB_Database::get_attendance_professional_by_id($professional_id);
            if ($prof) {
                // 1. Validação estrita da escala
                if (!empty($prof['scheduleType']) && $prof['scheduleType'] === 'specific_dates') {
                    $specific = is_array($prof['specificDates'] ?? null) ? $prof['specificDates'] : array();
                    if (!in_array($date, $specific)) {
                        $is_available_date = false;
                        $unavailability_reason = 'Este profissional atende apenas nas datas específicas de sua escala.';
                    }
                } elseif (!empty($prof['scheduleType']) && $prof['scheduleType'] === 'weekly') {
                    $days = is_array($prof['availableDays'] ?? null) ? $prof['availableDays'] : array();
                    if (!empty($days) && !in_array($day_of_week, $days)) {
                        $is_available_date = false;
                        $unavailability_reason = 'Este profissional não atende neste dia da semana.';
                    }
                } else {
                    if (!empty($prof['specificDates']) && is_array($prof['specificDates'])) {
                        if (!in_array($date, $prof['specificDates'])) {
                            if (!empty($prof['availableDays']) && is_array($prof['availableDays'])) {
                                if (!in_array($day_of_week, $prof['availableDays'])) {
                                    $is_available_date = false;
                                    $unavailability_reason = 'Data fora da escala do profissional.';
                                }
                            } else {
                                $is_available_date = false;
                                $unavailability_reason = 'Data fora da escala do profissional.';
                            }
                        }
                    } elseif (!empty($prof['availableDays']) && is_array($prof['availableDays'])) {
                        if (!in_array($day_of_week, $prof['availableDays'])) {
                            $is_available_date = false;
                            $unavailability_reason = 'Este profissional não atende neste dia da semana.';
                        }
                    }
                }

                // 2. Fatiamento dos horários pela duração média
                if (!empty($prof['shiftStartTime']) && !empty($prof['shiftEndTime'])) {
                    $start_parts = explode(':', $prof['shiftStartTime']);
                    $end_parts = explode(':', $prof['shiftEndTime']);
                    $start_minutes = (intval($start_parts[0]) * 60) + intval($start_parts[1] ?? 0);
                    $end_minutes = (intval($end_parts[0]) * 60) + intval($end_parts[1] ?? 0);
                    $duration = !empty($prof['slotDurationMinutes']) ? intval($prof['slotDurationMinutes']) : 30;

                    if ($start_minutes < $end_minutes && $duration > 0) {
                        $generated = array();
                        for ($cur = $start_minutes; $cur + $duration <= $end_minutes; $cur += $duration) {
                            $h = floor($cur / 60);
                            $m = $cur % 60;
                            $generated[] = sprintf('%02d:%02d', $h, $m);
                        }
                        if (!empty($generated)) {
                            $all_slots = $generated;
                        }
                    }
                } elseif (!empty($prof['availableHours']) && is_array($prof['availableHours'])) {
                    $all_slots = $prof['availableHours'];
                }
            }
        }

        if (!$is_available_date) {
            return rest_ensure_response(array(
                'success'         => true,
                'date'            => $date,
                'isAvailableDate' => false,
                'reason'          => $unavailability_reason,
                'professional'    => $prof ? array('id' => $prof['id'], 'name' => $prof['name'], 'specialty' => $prof['specialty']) : null,
                'allSlots'        => array(),
                'bookedSlots'     => array(),
                'availableSlots'  => array(),
            ));
        }

        $existing = PJB_Database::get_attendances(array(
            'professionalId' => $professional_id,
        ));

        $booked_slots = array();
        foreach ($existing as $atd) {
            if ($atd['date'] === $date && $atd['status'] !== 'cancelado' && !empty($atd['time'])) {
                $booked_slots[] = $atd['time'];
            }
        }

        $available = array_values(array_diff($all_slots, $booked_slots));

        return rest_ensure_response(array(
            'success'         => true,
            'date'            => $date,
            'isAvailableDate' => true,
            'professional'    => $prof ? array(
                'id'                  => $prof['id'],
                'name'                => $prof['name'],
                'specialty'           => $prof['specialty'],
                'slotDurationMinutes' => $prof['slotDurationMinutes'] ?? 30,
                'shiftStartTime'      => $prof['shiftStartTime'] ?? '14:00',
                'shiftEndTime'        => $prof['shiftEndTime'] ?? '18:00',
                'scheduleType'        => $prof['scheduleType'] ?? 'weekly',
            ) : null,
            'allSlots'        => $all_slots,
            'bookedSlots'     => $booked_slots,
            'availableSlots'  => $available,
        ));
    }

    public static function create_public_schedule($request) {
        $params = $request->get_json_params() ?: $request->get_params();

        $specialty = sanitize_text_field($params['specialty'] ?? '');
        $date = sanitize_text_field($params['date'] ?? '');
        $time = sanitize_text_field($params['time'] ?? '');
        $area = sanitize_text_field($params['area'] ?? 'saude');
        $prof_id = !empty($params['professionalId']) ? sanitize_text_field($params['professionalId']) : null;
        $complaint = sanitize_textarea_field($params['chiefComplaint'] ?? 'Agendamento solicitado pelo portal público do PJB');
        $beneficiary_input = $params['beneficiary'] ?? array();

        if (empty($specialty) || empty($date) || empty($time)) {
            return new WP_Error('missing_fields', 'Especialidade, data e horário são obrigatórios.', array('status' => 400));
        }

        $raw_cpf = $beneficiary_input['cpf'] ?? '';
        $clean_cpf = preg_replace('/\D/', '', $raw_cpf);
        if (strlen($clean_cpf) < 11) {
            return new WP_Error('invalid_cpf', 'CPF válido com 11 dígitos é obrigatório.', array('status' => 400));
        }

        $name = sanitize_text_field($beneficiary_input['name'] ?? '');
        if (empty($name)) {
            return new WP_Error('missing_name', 'Nome completo é obrigatório.', array('status' => 400));
        }

        // Localiza ou cadastra o beneficiário
        $target_ben = PJB_Database::get_beneficiary_by_cpf($clean_cpf);
        if (!$target_ben) {
            $created_ben = PJB_Database::create_beneficiary(array(
                'name'         => $name,
                'cpf'          => $clean_cpf,
                'birthDate'    => sanitize_text_field($beneficiary_input['birthDate'] ?? date('Y-m-d')),
                'phone'        => sanitize_text_field($beneficiary_input['phone'] ?? ''),
                'email'        => sanitize_email($beneficiary_input['email'] ?? ''),
                'neighborhood' => sanitize_text_field($beneficiary_input['neighborhood'] ?? 'Castanhal'),
                'address'      => sanitize_text_field($beneficiary_input['address'] ?? ''),
                'vulnerability'=> 'baixa',
                'status'       => 'ativo',
                'notes'        => 'Cadastrado automaticamente via Portal de Agendamento Público.',
            ));
            $target_ben = $created_ben;
        }

        $prof_name = 'Equipe de Acolhimento PJB';
        $prof_reg = null;
        if ($prof_id) {
            $prof = PJB_Database::get_attendance_professional_by_id($prof_id);
            if ($prof) {
                $prof_name = $prof['name'];
                $prof_reg = $prof['registrationNumber'] ?? null;
            }
        }

        $created_attendance = PJB_Database::create_attendance(array(
            'area'                     => $area,
            'specialty'                => $specialty,
            'beneficiaryId'            => $target_ben['id'] ?? null,
            'beneficiaryName'          => $target_ben['name'] ?? $name,
            'beneficiaryCpf'           => $clean_cpf,
            'beneficiaryPhone'         => sanitize_text_field($beneficiary_input['phone'] ?? ($target_ben['phone'] ?? '')),
            'beneficiaryBirthDate'     => $target_ben['birthDate'] ?? null,
            'professionalId'           => $prof_id,
            'professionalName'         => $prof_name,
            'professionalRegistration' => $prof_reg,
            'date'                     => $date,
            'time'                     => $time,
            'location'                 => 'Sede PJB - Projeto Brasil dos Jovens (Castanhal - PA)',
            'status'                   => 'agendado',
            'priority'                 => 'normal',
            'chiefComplaint'           => $complaint,
        ));

        if (!$created_attendance) {
            return new WP_Error('create_failed', 'Erro ao registrar agendamento no banco de dados.', array('status' => 500));
        }

        return rest_ensure_response(array(
            'success'        => true,
            'message'        => 'Agendamento confirmado com sucesso!',
            'data'           => $created_attendance,
            'protocolNumber' => $created_attendance['protocolNumber'] ?? '',
        ));
    }

    public static function auth_login($request) {
        $params = $request->get_json_params() ?: $request->get_params();
        $login = sanitize_text_field($params['email'] ?? ($params['username'] ?? ''));
        $password = $params['password'] ?? '';

        if (empty($login) || empty($password)) {
            return new WP_Error('missing_credentials', 'Informe seu e-mail/usuário e senha.', array('status' => 400));
        }

        $user = wp_authenticate($login, $password);
        if (is_wp_error($user)) {
            return new WP_Error('invalid_credentials', 'Credenciais inválidas no WordPress.', array('status' => 401));
        }

        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, true);

        $role = 'admin';
        if (in_array('pjb_social', $user->roles, true)) {
            $role = 'social';
        } elseif (in_array('pjb_professor', $user->roles, true)) {
            $role = 'professor';
        }

        return rest_ensure_response(array(
            'success' => true,
            'token'   => wp_create_nonce('wp_rest'),
            'user'    => array(
                'id'    => (string) $user->ID,
                'name'  => $user->display_name ?: $user->user_login,
                'email' => $user->user_email,
                'role'  => $role,
            ),
        ));
    }
}


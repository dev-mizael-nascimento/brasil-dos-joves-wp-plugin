<?php
if (!defined('ABSPATH')) {
    exit;
}

class PJB_Activator {
    public static function activate() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // 1. Tabela de Beneficiários e Jovens
        $table_beneficiaries = $wpdb->prefix . 'pjb_beneficiaries';
        $sql_beneficiaries = "CREATE TABLE $table_beneficiaries (
            id varchar(64) NOT NULL,
            name varchar(255) NOT NULL,
            cpf varchar(20) NOT NULL,
            rg varchar(30) DEFAULT NULL,
            birth_date date NOT NULL,
            age int NOT NULL,
            gender varchar(50) DEFAULT 'Não informado',
            phone varchar(30) NOT NULL,
            email varchar(150) DEFAULT NULL,
            street varchar(255) DEFAULT 'Não informado',
            number varchar(30) DEFAULT 'S/N',
            neighborhood varchar(100) NOT NULL,
            city varchar(100) DEFAULT 'Castanhal',
            state varchar(10) DEFAULT 'PA',
            cep varchar(20) DEFAULT '68740-000',
            guardian_name varchar(255) DEFAULT NULL,
            guardian_phone varchar(30) DEFAULT NULL,
            nis varchar(50) DEFAULT NULL,
            cad_unico tinyint(1) DEFAULT 0,
            family_income decimal(10,2) DEFAULT 0.00,
            family_members_count int DEFAULT 1,
            per_capita_income decimal(10,2) DEFAULT 0.00,
            schooling_status varchar(100) DEFAULT 'Não informado',
            public_school_name varchar(255) DEFAULT NULL,
            vulnerability_level varchar(50) NOT NULL,
            notes text DEFAULT NULL,
            registration_date datetime NOT NULL,
            status varchar(30) DEFAULT 'ativo',
            PRIMARY KEY (id),
            UNIQUE KEY idx_cpf (cpf),
            KEY idx_neighborhood (neighborhood),
            KEY idx_status (status)
        ) $charset_collate;";
        dbDelta($sql_beneficiaries);

        // 2. Tabela de Cursos Profissionalizantes
        $table_courses = $wpdb->prefix . 'pjb_courses';
        $sql_courses = "CREATE TABLE $table_courses (
            id varchar(64) NOT NULL,
            name varchar(255) NOT NULL,
            description text DEFAULT NULL,
            category varchar(100) DEFAULT 'Qualificação',
            workload_hours int NOT NULL,
            target_age_min int DEFAULT 14,
            target_age_max int DEFAULT 29,
            total_vacancies int DEFAULT 30,
            active_vacancies int DEFAULT 30,
            status varchar(30) DEFAULT 'aberto',
            instructor_name varchar(255) DEFAULT 'Instrutor PJB',
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($sql_courses);

        // 3. Tabela de Turmas
        $table_classes = $wpdb->prefix . 'pjb_classes';
        $sql_classes = "CREATE TABLE $table_classes (
            id varchar(64) NOT NULL,
            course_id varchar(64) NOT NULL,
            course_name varchar(255) NOT NULL,
            code varchar(50) NOT NULL,
            schedule varchar(150) NOT NULL,
            room varchar(100) DEFAULT 'Sala PJB Castanhal',
            start_date date NOT NULL,
            end_date date NOT NULL,
            max_students int DEFAULT 25,
            enrolled_students longtext DEFAULT NULL,
            status varchar(30) DEFAULT 'ativa',
            PRIMARY KEY (id),
            KEY idx_course (course_id)
        ) $charset_collate;";
        dbDelta($sql_classes);

        // 4. Tabela de Frequência / Chamada
        $table_attendances = $wpdb->prefix . 'pjb_attendances';
        $sql_attendances = "CREATE TABLE $table_attendances (
            id varchar(64) NOT NULL,
            class_id varchar(64) NOT NULL,
            date date NOT NULL,
            topic varchar(255) DEFAULT 'Aula regular',
            records longtext NOT NULL,
            PRIMARY KEY (id),
            KEY idx_class_date (class_id, date)
        ) $charset_collate;";
        dbDelta($sql_attendances);

        // 5. Tabela de Certificados Oficiais
        $table_certificates = $wpdb->prefix . 'pjb_certificates';
        $sql_certificates = "CREATE TABLE $table_certificates (
            id varchar(64) NOT NULL,
            code varchar(100) NOT NULL,
            student_id varchar(64) NOT NULL,
            student_name varchar(255) NOT NULL,
            student_cpf varchar(20) NOT NULL,
            course_id varchar(64) NOT NULL,
            course_name varchar(255) NOT NULL,
            workload_hours int NOT NULL,
            issue_date date NOT NULL,
            verification_hash varchar(64) NOT NULL,
            qr_code_url text DEFAULT NULL,
            issued_by varchar(255) DEFAULT 'Coordenação Geral PJB',
            PRIMARY KEY (id),
            UNIQUE KEY idx_code (code),
            KEY idx_student (student_id)
        ) $charset_collate;";
        dbDelta($sql_certificates);

        // 6. Tabela de Campanhas Solidárias
        $table_campaigns = $wpdb->prefix . 'pjb_campaigns';
        $sql_campaigns = "CREATE TABLE $table_campaigns (
            id varchar(64) NOT NULL,
            title varchar(255) NOT NULL,
            description text DEFAULT NULL,
            category varchar(100) DEFAULT 'Alimentação',
            target_goal decimal(10,2) NOT NULL,
            unit varchar(50) DEFAULT 'Unidades',
            start_date date NOT NULL,
            end_date date NOT NULL,
            location varchar(150) DEFAULT 'Sede PJB Castanhal',
            status varchar(30) DEFAULT 'em_andamento',
            tags text DEFAULT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($sql_campaigns);

        // 7. Tabela de Doações & Movimentações
        $table_donations = $wpdb->prefix . 'pjb_donations';
        $sql_donations = "CREATE TABLE $table_donations (
            id varchar(64) NOT NULL,
            receipt_number varchar(64) NOT NULL,
            type varchar(20) NOT NULL,
            category varchar(50) NOT NULL,
            item_description varchar(255) NOT NULL,
            quantity decimal(10,2) NOT NULL,
            unit varchar(30) DEFAULT 'unidade',
            partner_name varchar(255) DEFAULT NULL,
            beneficiary_id varchar(64) DEFAULT NULL,
            beneficiary_name varchar(255) DEFAULT NULL,
            campaign_id varchar(64) DEFAULT NULL,
            date date NOT NULL,
            registered_by varchar(100) DEFAULT 'Equipe PJB',
            notes text DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_beneficiary (beneficiary_id)
        ) $charset_collate;";
        dbDelta($sql_donations);

        // 8. Tabela de Notificações / Histórico
        $table_notifications = $wpdb->prefix . 'pjb_notifications';
        $sql_notifications = "CREATE TABLE $table_notifications (
            id varchar(64) NOT NULL,
            recipient_phone varchar(30) NOT NULL,
            recipient_name varchar(255) NOT NULL,
            type varchar(50) NOT NULL,
            channel varchar(30) DEFAULT 'whatsapp',
            message text NOT NULL,
            status varchar(30) DEFAULT 'sent',
            beneficiary_id varchar(64) DEFAULT NULL,
            timestamp datetime NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($sql_notifications);

        // 9. Tabela de Ações Esportivas & Culturais
        $table_cultural_activities = $wpdb->prefix . 'pjb_cultural_activities';
        $sql_cultural_activities = "CREATE TABLE $table_cultural_activities (
            id varchar(64) NOT NULL,
            title varchar(255) NOT NULL,
            modality varchar(100) NOT NULL,
            type varchar(50) DEFAULT 'cultura',
            description text DEFAULT NULL,
            target_age_min int DEFAULT 6,
            target_age_max int DEFAULT 29,
            location varchar(150) DEFAULT 'Sede PJB Castanhal',
            schedule varchar(150) DEFAULT NULL,
            start_date date NOT NULL,
            end_date date DEFAULT NULL,
            status varchar(30) DEFAULT 'planejado',
            max_participants int DEFAULT 30,
            instructor_name varchar(255) DEFAULT 'Equipe PJB',
            tags text DEFAULT NULL,
            achievements_summary text DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($sql_cultural_activities);

        // 10. Tabela de Participantes das Ações (Beneficiários)
        $table_cultural_participants = $wpdb->prefix . 'pjb_cultural_participants';
        $sql_cultural_participants = "CREATE TABLE $table_cultural_participants (
            id varchar(64) NOT NULL,
            activity_id varchar(64) NOT NULL,
            beneficiary_id varchar(64) DEFAULT NULL,
            beneficiary_name varchar(255) NOT NULL,
            beneficiary_cpf varchar(20) DEFAULT NULL,
            beneficiary_phone varchar(30) DEFAULT NULL,
            role varchar(50) DEFAULT 'participante',
            status varchar(30) DEFAULT 'confirmado',
            achievement varchar(255) DEFAULT NULL,
            notes text DEFAULT NULL,
            registration_date date NOT NULL,
            PRIMARY KEY (id),
            KEY idx_activity (activity_id)
        ) $charset_collate;";
        dbDelta($sql_cultural_participants);

        // 11. Tabela de Apoiadores & Beneficiadores das Ações
        $table_cultural_supporters = $wpdb->prefix . 'pjb_cultural_supporters';
        $sql_cultural_supporters = "CREATE TABLE $table_cultural_supporters (
            id varchar(64) NOT NULL,
            activity_id varchar(64) NOT NULL,
            name varchar(255) NOT NULL,
            type varchar(50) DEFAULT 'empresa_parceira',
            contact varchar(100) DEFAULT NULL,
            contribution varchar(255) NOT NULL,
            notes text DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_activity_sup (activity_id)
        ) $charset_collate;";
        dbDelta($sql_cultural_supporters);

        // 12. Tabela de Profissionais Voluntários & Especialistas (Saúde e Jurídico)
        $table_professionals = $wpdb->prefix . 'pjb_attendance_professionals';
        $sql_professionals = "CREATE TABLE $table_professionals (
            id varchar(64) NOT NULL,
            name varchar(255) NOT NULL,
            area varchar(30) NOT NULL,
            specialty varchar(100) NOT NULL,
            registration_number varchar(100) NOT NULL,
            phone varchar(30) NOT NULL,
            email varchar(100) DEFAULT NULL,
            organization varchar(150) DEFAULT 'Voluntário PJB',
            shifts_description varchar(255) DEFAULT NULL,
            schedule_type varchar(30) DEFAULT 'weekly',
            specific_dates text DEFAULT NULL,
            available_days varchar(50) DEFAULT '1,2,3,4,5',
            shift_start_time varchar(10) DEFAULT '14:00',
            shift_end_time varchar(10) DEFAULT '18:00',
            slot_duration_minutes int(11) DEFAULT 30,
            available_hours text DEFAULT NULL,
            active tinyint(1) DEFAULT 1,
            notes text DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY idx_prof_area (area)
        ) $charset_collate;";
        dbDelta($sql_professionals);

        // 13. Tabela de Atendimentos de Saúde e Assessoria Jurídica
        $table_attendances = $wpdb->prefix . 'pjb_service_attendances';
        $sql_attendances = "CREATE TABLE $table_attendances (
            id varchar(64) NOT NULL,
            protocol_number varchar(64) NOT NULL,
            area varchar(30) NOT NULL,
            specialty varchar(100) NOT NULL,
            beneficiary_id varchar(64) DEFAULT NULL,
            beneficiary_name varchar(255) NOT NULL,
            beneficiary_cpf varchar(20) DEFAULT NULL,
            beneficiary_phone varchar(30) DEFAULT NULL,
            beneficiary_birth_date date DEFAULT NULL,
            professional_id varchar(64) DEFAULT NULL,
            professional_name varchar(255) NOT NULL,
            professional_registration varchar(100) DEFAULT NULL,
            date date NOT NULL,
            time varchar(20) DEFAULT NULL,
            location varchar(150) DEFAULT 'Sede PJB Castanhal',
            status varchar(30) DEFAULT 'agendado',
            priority varchar(30) DEFAULT 'normal',
            chief_complaint text NOT NULL,
            technical_notes text DEFAULT NULL,
            prescriptions_or_actions text DEFAULT NULL,
            referrals text DEFAULT NULL,
            return_date date DEFAULT NULL,
            tags text DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_beneficiary_att (beneficiary_id),
            KEY idx_area (area),
            KEY idx_status (status)
        ) $charset_collate;";
        dbDelta($sql_attendances);

        // 14. Tabela de Movimentações Financeiras, Doações Monetárias & Despesas
        $table_financial = $wpdb->prefix . 'pjb_financial_transactions';
        $sql_financial = "CREATE TABLE $table_financial (
            id varchar(64) NOT NULL,
            type varchar(30) NOT NULL,
            category varchar(100) NOT NULL,
            description varchar(255) NOT NULL,
            amount decimal(10,2) NOT NULL,
            date date NOT NULL,
            payment_method varchar(50) NOT NULL,
            donor_or_supplier varchar(255) NOT NULL,
            document_number varchar(100) DEFAULT NULL,
            status varchar(30) DEFAULT 'confirmado',
            campaign_id varchar(64) DEFAULT NULL,
            cost_center varchar(100) DEFAULT 'Geral / Manutenção',
            attachment_url longtext DEFAULT NULL,
            attachment_name varchar(255) DEFAULT NULL,
            receipt_number varchar(100) DEFAULT NULL,
            registered_by varchar(150) DEFAULT 'Administração PJB',
            notes text DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_type_date (type, date),
            KEY idx_status (status),
            KEY idx_payment_method (payment_method),
            KEY idx_campaign (campaign_id)
        ) $charset_collate;";
        dbDelta($sql_financial);

        // Popula dados semente se o banco estiver limpo
        self::seed_initial_data();

        update_option('pjb_db_version', PJB_DB_VERSION);
    }

    private static function seed_initial_data() {
        global $wpdb;

    }
}

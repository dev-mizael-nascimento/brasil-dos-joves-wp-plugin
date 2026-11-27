<?php
if (!defined('ABSPATH')) {
    exit;
}

class PJB_Database {
    // ========================================================
    // BENEFICIÁRIOS & JOVENS
    // ========================================================
    public static function format_beneficiary($row) {
        if (!$row) return null;
        $row = (array) $row;
        return array(
            'id' => $row['id'],
            'name' => $row['name'],
            'cpf' => $row['cpf'],
            'rg' => $row['rg'] ?? '',
            'birthDate' => $row['birth_date'],
            'age' => (int) $row['age'],
            'gender' => $row['gender'] ?? 'Não informado',
            'phone' => $row['phone'],
            'email' => $row['email'] ?? '',
            'address' => array(
                'street' => $row['street'] ?? 'Não informado',
                'number' => $row['number'] ?? 'S/N',
                'neighborhood' => $row['neighborhood'],
                'city' => $row['city'] ?? 'Castanhal',
                'state' => $row['state'] ?? 'PA',
                'cep' => $row['cep'] ?? '68740-000',
            ),
            'guardianName' => $row['guardian_name'] ?? '',
            'guardianPhone' => $row['guardian_phone'] ?? '',
            'nis' => $row['nis'] ?? '',
            'cadUnico' => (bool) $row['cad_unico'],
            'familyIncome' => (float) $row['family_income'],
            'familyMembersCount' => (int) $row['family_members_count'],
            'perCapitaIncome' => (float) $row['per_capita_income'],
            'schoolingStatus' => $row['schooling_status'] ?? 'Não informado',
            'publicSchoolName' => $row['public_school_name'] ?? '',
            'vulnerabilityLevel' => $row['vulnerability_level'],
            'notes' => $row['notes'] ?? '',
            'registrationDate' => $row['registration_date'],
            'status' => $row['status'] ?? 'ativo',
            'serviceInterest' => $row['service_interest'] ?? (preg_match('/\[(?:Interesse|Serviço):\s*([^|\]]+)/i', $row['notes'] ?? '', $m) ? trim($m[1]) : ''),
            'preferredShift' => $row['preferred_shift'] ?? (preg_match('/\[(?:Turno):\s*([^|\]]+)/i', $row['notes'] ?? '', $m2) ? trim($m2[1]) : ''),
        );
    }

    public static function search_beneficiaries($filters = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_beneficiaries';

        $where = array('1=1');
        $params = array();

        if (!empty($filters['query'])) {
            $like = '%' . $wpdb->esc_like(trim($filters['query'])) . '%';
            $where[] = "(name LIKE %s OR cpf LIKE %s OR phone LIKE %s)";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if (!empty($filters['neighborhood']) && $filters['neighborhood'] !== 'todos') {
            $where[] = "neighborhood = %s";
            $params[] = $filters['neighborhood'];
        }

        if (!empty($filters['vulnerability']) && $filters['vulnerability'] !== 'todas') {
            $where[] = "vulnerability_level = %s";
            $params[] = $filters['vulnerability'];
        }

        if (!empty($filters['status']) && $filters['status'] !== 'todos') {
            $where[] = "status = %s";
            $params[] = $filters['status'];
        }

        $sql = "SELECT * FROM $table WHERE " . implode(' AND ', $where) . " ORDER BY registration_date DESC";
        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        $results = $wpdb->get_results($sql, ARRAY_A);
        return array_map(array(__CLASS__, 'format_beneficiary'), $results ?: array());
    }

    public static function get_beneficiary_by_id($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_beneficiaries';
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %s", $id), ARRAY_A);
        return self::format_beneficiary($row);
    }

    public static function get_beneficiary_by_cpf($cpf) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_beneficiaries';
        $clean = preg_replace('/\D/', '', $cpf);
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') = %s OR cpf = %s LIMIT 1",
                $clean,
                $cpf
            ),
            ARRAY_A
        );
        return self::format_beneficiary($row);
    }

    public static function create_beneficiary($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_beneficiaries';

        $id = !empty($data['id']) ? $data['id'] : 'ben_' . wp_generate_uuid4();
        $family_income = (float) ($data['familyIncome'] ?? 0);
        $family_members = max(1, (int) ($data['familyMembersCount'] ?? 1));
        $per_capita = round($family_income / $family_members, 2);

        // Nível de vulnerabilidade automático se não definido
        $vuln = $data['vulnerabilityLevel'] ?? null;
        if (empty($vuln) || $vuln === 'pendente') {
            if ($per_capita <= 218 || !empty($data['nis'])) {
                $vuln = 'critica';
            } elseif ($per_capita <= 700) {
                $vuln = 'alta';
            } elseif ($per_capita <= 1412) {
                $vuln = 'media';
            } else {
                $vuln = 'baixa';
            }
        }

        $address = $data['address'] ?? array();

        $record = array(
            'id' => $id,
            'name' => sanitize_text_field($data['name']),
            'cpf' => sanitize_text_field($data['cpf']),
            'rg' => sanitize_text_field($data['rg'] ?? ''),
            'birth_date' => sanitize_text_field($data['birthDate'] ?? '2008-01-01'),
            'age' => (int) ($data['age'] ?? 16),
            'gender' => sanitize_text_field($data['gender'] ?? 'Não informado'),
            'phone' => sanitize_text_field($data['phone']),
            'email' => sanitize_email($data['email'] ?? ''),
            'street' => sanitize_text_field($address['street'] ?? 'Não informado'),
            'number' => sanitize_text_field($address['number'] ?? 'S/N'),
            'neighborhood' => sanitize_text_field($address['neighborhood'] ?? 'Centro'),
            'city' => sanitize_text_field($address['city'] ?? 'Castanhal'),
            'state' => sanitize_text_field($address['state'] ?? 'PA'),
            'cep' => sanitize_text_field($address['cep'] ?? '68740-000'),
            'guardian_name' => sanitize_text_field($data['guardianName'] ?? ''),
            'guardian_phone' => sanitize_text_field($data['guardianPhone'] ?? ''),
            'nis' => sanitize_text_field($data['nis'] ?? ''),
            'cad_unico' => !empty($data['cadUnico']) ? 1 : 0,
            'family_income' => $family_income,
            'family_members_count' => $family_members,
            'per_capita_income' => $per_capita,
            'schooling_status' => sanitize_text_field($data['schoolingStatus'] ?? 'Não informado'),
            'public_school_name' => sanitize_text_field($data['publicSchoolName'] ?? ''),
            'vulnerability_level' => $vuln,
            'notes' => (function() use ($data) {
                $n = sanitize_textarea_field($data['notes'] ?? '');
                if (!empty($data['serviceInterest']) && strpos($n, '[Interesse:') === false) {
                    $s = !empty($data['preferredShift']) ? ' | Turno: ' . sanitize_text_field($data['preferredShift']) : '';
                    $t = '[Interesse: ' . sanitize_text_field($data['serviceInterest']) . $s . ']';
                    $n = $n ? $n . "\n" . $t : $t;
                }
                return $n;
            })(),
            'registration_date' => current_time('mysql'),
            'status' => sanitize_text_field($data['status'] ?? 'ativo'),
        );

        $inserted = $wpdb->insert($table, $record);
        if (!$inserted) {
            return false;
        }

        return self::get_beneficiary_by_id($id);
    }

    public static function update_beneficiary($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_beneficiaries';

        $existing = self::get_beneficiary_by_id($id);
        if (!$existing) return false;

        $fields = array();
        if (isset($data['name'])) $fields['name'] = sanitize_text_field($data['name']);
        if (isset($data['cpf'])) $fields['cpf'] = sanitize_text_field($data['cpf']);
        if (isset($data['rg'])) $fields['rg'] = sanitize_text_field($data['rg']);
        if (isset($data['birthDate'])) $fields['birth_date'] = sanitize_text_field($data['birthDate']);
        if (isset($data['age'])) $fields['age'] = (int) $data['age'];
        if (isset($data['gender'])) $fields['gender'] = sanitize_text_field($data['gender']);
        if (isset($data['phone'])) $fields['phone'] = sanitize_text_field($data['phone']);
        if (isset($data['email'])) $fields['email'] = sanitize_email($data['email']);
        if (isset($data['status'])) $fields['status'] = sanitize_text_field($data['status']);
        if (isset($data['vulnerabilityLevel'])) $fields['vulnerability_level'] = sanitize_text_field($data['vulnerabilityLevel']);
        if (isset($data['notes'])) $fields['notes'] = sanitize_textarea_field($data['notes']);
        if (isset($data['guardianName'])) $fields['guardian_name'] = sanitize_text_field($data['guardianName']);
        if (isset($data['guardianPhone'])) $fields['guardian_phone'] = sanitize_text_field($data['guardianPhone']);
        if (isset($data['nis'])) $fields['nis'] = sanitize_text_field($data['nis']);
        if (isset($data['cadUnico'])) $fields['cad_unico'] = !empty($data['cadUnico']) ? 1 : 0;
        if (isset($data['schoolingStatus'])) $fields['schooling_status'] = sanitize_text_field($data['schoolingStatus']);
        if (isset($data['publicSchoolName'])) $fields['public_school_name'] = sanitize_text_field($data['publicSchoolName']);

        if (isset($data['familyIncome']) || isset($data['familyMembersCount'])) {
            $income = isset($data['familyIncome']) ? (float) $data['familyIncome'] : $existing['familyIncome'];
            $members = isset($data['familyMembersCount']) ? max(1, (int) $data['familyMembersCount']) : $existing['familyMembersCount'];
            $fields['family_income'] = $income;
            $fields['family_members_count'] = $members;
            $fields['per_capita_income'] = round($income / $members, 2);
        }

        if (isset($data['address']) && is_array($data['address'])) {
            $addr = $data['address'];
            if (isset($addr['street'])) $fields['street'] = sanitize_text_field($addr['street']);
            if (isset($addr['number'])) $fields['number'] = sanitize_text_field($addr['number']);
            if (isset($addr['neighborhood'])) $fields['neighborhood'] = sanitize_text_field($addr['neighborhood']);
            if (isset($addr['city'])) $fields['city'] = sanitize_text_field($addr['city']);
            if (isset($addr['state'])) $fields['state'] = sanitize_text_field($addr['state']);
            if (isset($addr['cep'])) $fields['cep'] = sanitize_text_field($addr['cep']);
        }

        if (!empty($fields)) {
            $wpdb->update($table, $fields, array('id' => $id));
        }

        return self::get_beneficiary_by_id($id);
    }

    public static function delete_beneficiary($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_beneficiaries';
        $deleted = $wpdb->delete($table, array('id' => $id));
        return (bool) $deleted;
    }

    // ========================================================
    // CURSOS PROFISSIONALIZANTES
    // ========================================================
    public static function format_course($row) {
        if (!$row) return null;
        $row = (array) $row;
        return array(
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'] ?? '',
            'category' => $row['category'] ?? 'Qualificação',
            'workloadHours' => (int) $row['workload_hours'],
            'targetAgeMin' => (int) ($row['target_age_min'] ?? 14),
            'targetAgeMax' => (int) ($row['target_age_max'] ?? 29),
            'totalVacancies' => (int) ($row['total_vacancies'] ?? 30),
            'activeVacancies' => (int) ($row['active_vacancies'] ?? 30),
            'status' => $row['status'] ?? 'aberto',
            'instructorName' => $row['instructor_name'] ?? 'Instrutor PJB',
            'minSchooling' => $row['min_schooling'] ?? 'Livre',
            'syllabus' => $row['syllabus'] ?? '',
        );
    }

    public static function get_courses() {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_courses';
        $rows = $wpdb->get_results("SELECT * FROM $table ORDER BY name ASC", ARRAY_A);
        return array_map(array(__CLASS__, 'format_course'), $rows ?: array());
    }

    public static function get_course_by_id($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_courses';
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %s", $id), ARRAY_A);
        return self::format_course($row);
    }

    public static function create_course($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_courses';
        $id = !empty($data['id']) ? $data['id'] : 'crs_' . wp_generate_uuid4();

        $total_vacancies = (int) ($data['totalVacancies'] ?? 30);
        $record = array(
            'id' => $id,
            'name' => sanitize_text_field($data['name']),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'category' => sanitize_text_field($data['category'] ?? 'Qualificação'),
            'workload_hours' => (int) ($data['workloadHours'] ?? 40),
            'target_age_min' => (int) ($data['targetAgeMin'] ?? 14),
            'target_age_max' => (int) ($data['targetAgeMax'] ?? 29),
            'total_vacancies' => $total_vacancies,
            'active_vacancies' => (int) ($data['activeVacancies'] ?? $total_vacancies),
            'status' => sanitize_text_field($data['status'] ?? 'aberto'),
            'instructor_name' => sanitize_text_field($data['instructorName'] ?? 'Instrutor PJB'),
            'min_schooling' => sanitize_text_field($data['minSchooling'] ?? 'Livre'),
            'syllabus' => isset($data['syllabus']) ? sanitize_textarea_field($data['syllabus']) : null,
        );

        $inserted = $wpdb->insert($table, $record);
        return $inserted ? self::get_course_by_id($id) : false;
    }

    public static function update_course($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_courses';

        $fields = array();
        if (isset($data['name'])) $fields['name'] = sanitize_text_field($data['name']);
        if (isset($data['description'])) $fields['description'] = sanitize_textarea_field($data['description']);
        if (isset($data['category'])) $fields['category'] = sanitize_text_field($data['category']);
        if (isset($data['workloadHours'])) $fields['workload_hours'] = (int) $data['workloadHours'];
        if (isset($data['targetAgeMin'])) $fields['target_age_min'] = (int) $data['targetAgeMin'];
        if (isset($data['targetAgeMax'])) $fields['target_age_max'] = (int) $data['targetAgeMax'];
        if (isset($data['totalVacancies'])) $fields['total_vacancies'] = (int) $data['totalVacancies'];
        if (isset($data['activeVacancies'])) $fields['active_vacancies'] = (int) $data['activeVacancies'];
        if (isset($data['status'])) $fields['status'] = sanitize_text_field($data['status']);
        if (isset($data['instructorName'])) $fields['instructor_name'] = sanitize_text_field($data['instructorName']);
        if (isset($data['minSchooling'])) $fields['min_schooling'] = sanitize_text_field($data['minSchooling']);
        if (isset($data['syllabus'])) $fields['syllabus'] = sanitize_textarea_field($data['syllabus']);

        if (!empty($fields)) {
            $wpdb->update($table, $fields, array('id' => $id));
        }

        return self::get_course_by_id($id);
    }

    public static function delete_course($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_courses';
        return (bool) $wpdb->delete($table, array('id' => $id));
    }

    // ========================================================
    // TURMAS
    // ========================================================
    public static function format_class($row) {
        if (!$row) return null;
        $row = (array) $row;
        $enrolled = json_decode($row['enrolled_students'] ?? '[]', true) ?: array();
        return array(
            'id' => $row['id'],
            'courseId' => $row['course_id'],
            'courseName' => $row['course_name'],
            'code' => $row['code'],
            'schedule' => $row['schedule'],
            'room' => $row['room'] ?? 'Sala PJB Castanhal',
            'startDate' => $row['start_date'],
            'endDate' => $row['end_date'],
            'maxStudents' => (int) $row['max_students'],
            'enrolledStudentIds' => $enrolled,
            'status' => $row['status'] ?? 'ativa',
        );
    }

    public static function get_classes() {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_classes';
        $rows = $wpdb->get_results("SELECT * FROM $table ORDER BY start_date DESC", ARRAY_A);
        return array_map(array(__CLASS__, 'format_class'), $rows ?: array());
    }

    public static function get_class_by_id($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_classes';
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %s", $id), ARRAY_A);
        return self::format_class($row);
    }

    public static function create_class($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_classes';
        $id = !empty($data['id']) ? $data['id'] : 'cls_' . wp_generate_uuid4();

        $enrolled = isset($data['enrolledStudentIds']) ? wp_json_encode($data['enrolledStudentIds']) : '[]';

        $record = array(
            'id' => $id,
            'course_id' => sanitize_text_field($data['courseId']),
            'course_name' => sanitize_text_field($data['courseName']),
            'code' => sanitize_text_field($data['code']),
            'schedule' => sanitize_text_field($data['schedule']),
            'room' => sanitize_text_field($data['room'] ?? 'Sala PJB Castanhal'),
            'start_date' => sanitize_text_field($data['startDate']),
            'end_date' => sanitize_text_field($data['endDate']),
            'max_students' => (int) ($data['maxStudents'] ?? 25),
            'enrolled_students' => $enrolled,
            'status' => sanitize_text_field($data['status'] ?? 'ativa'),
        );

        $inserted = $wpdb->insert($table, $record);
        return $inserted ? self::get_class_by_id($id) : false;
    }

    public static function update_class($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_classes';

        $fields = array();
        if (isset($data['code'])) $fields['code'] = sanitize_text_field($data['code']);
        if (isset($data['schedule'])) $fields['schedule'] = sanitize_text_field($data['schedule']);
        if (isset($data['room'])) $fields['room'] = sanitize_text_field($data['room']);
        if (isset($data['startDate'])) $fields['start_date'] = sanitize_text_field($data['startDate']);
        if (isset($data['endDate'])) $fields['end_date'] = sanitize_text_field($data['endDate']);
        if (isset($data['maxStudents'])) $fields['max_students'] = (int) $data['maxStudents'];
        if (isset($data['status'])) $fields['status'] = sanitize_text_field($data['status']);
        if (isset($data['enrolledStudentIds'])) $fields['enrolled_students'] = wp_json_encode($data['enrolledStudentIds']);

        if (!empty($fields)) {
            $wpdb->update($table, $fields, array('id' => $id));
        }

        return self::get_class_by_id($id);
    }

    public static function delete_class($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_classes';
        return (bool) $wpdb->delete($table, array('id' => $id));
    }

    public static function enroll_student($class_id, $student_id_or_ids) {
        $class = self::get_class_by_id($class_id);
        if (!$class) return false;

        $enrolled = is_array($class['enrolledStudentIds']) ? $class['enrolledStudentIds'] : array();
        $ids = is_array($student_id_or_ids) ? $student_id_or_ids : array($student_id_or_ids);
        $changed = false;

        foreach ($ids as $sid) {
            $sid = sanitize_text_field(strval($sid));
            if (!empty($sid) && !in_array($sid, $enrolled)) {
                $enrolled[] = $sid;
                $changed = true;
            }
        }

        if ($changed) {
            self::update_class($class_id, array('enrolledStudentIds' => array_values($enrolled)));
        }
        return self::get_class_by_id($class_id);
    }

    public static function remove_student($class_id, $student_id) {
        $class = self::get_class_by_id($class_id);
        if (!$class) return false;

        $enrolled = is_array($class['enrolledStudentIds']) ? $class['enrolledStudentIds'] : array();
        $enrolled = array_values(array_filter($enrolled, function ($id) use ($student_id) {
            return $id !== $student_id;
        }));

        self::update_class($class_id, array('enrolledStudentIds' => $enrolled));
        return self::get_class_by_id($class_id);
    }

    public static function get_class_students($class_id) {
        $class = self::get_class_by_id($class_id);
        if (!$class) return array();

        $enrolled_ids = is_array($class['enrolledStudentIds']) ? $class['enrolledStudentIds'] : array();
        if (empty($enrolled_ids)) return array();

        $students = array();
        foreach ($enrolled_ids as $sid) {
            $ben = self::get_beneficiary_by_id($sid);
            if ($ben) {
                $students[] = $ben;
            }
        }
        return $students;
    }

    // ========================================================
    // FREQUÊNCIA / CHAMADA
    // ========================================================
    public static function get_attendance_by_class($class_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_attendances';
        $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE class_id = %s ORDER BY date DESC", $class_id), ARRAY_A);

        return array_map(function ($r) {
            return array(
                'id' => $r['id'],
                'classId' => $r['class_id'],
                'date' => $r['date'],
                'topic' => $r['topic'],
                'records' => json_decode($r['records'] ?? '[]', true) ?: array(),
            );
        }, $rows ?: array());
    }

    public static function record_attendance($class_id, $date, $topic, $records) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_attendances';

        $id = 'att_' . wp_generate_uuid4();
        $record = array(
            'id' => $id,
            'class_id' => sanitize_text_field($class_id),
            'date' => sanitize_text_field($date),
            'topic' => sanitize_text_field($topic ?: 'Aula regular'),
            'records' => wp_json_encode($records),
        );

        $wpdb->insert($table, $record);
        return array(
            'id' => $id,
            'classId' => $class_id,
            'date' => $date,
            'topic' => $topic,
            'records' => $records,
        );
    }

    public static function get_student_stats($student_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_attendances';
        $rows = $wpdb->get_results("SELECT * FROM $table", ARRAY_A);

        $total_classes = 0;
        $attended_classes = 0;

        foreach ($rows as $row) {
            $records = json_decode($row['records'] ?? '[]', true) ?: array();
            foreach ($records as $rec) {
                if (($rec['studentId'] ?? '') === $student_id) {
                    $total_classes++;
                    if (!empty($rec['present'])) {
                        $attended_classes++;
                    }
                }
            }
        }

        $rate = $total_classes > 0 ? round(($attended_classes / $total_classes) * 100, 1) : 100.0;
        return array(
            'totalClasses' => $total_classes,
            'attendedClasses' => $attended_classes,
            'attendanceRate' => $rate,
            'consecutiveAbsences' => 0,
        );
    }

    // ========================================================
    // CERTIFICADOS OFICIAIS
    // ========================================================
    public static function format_certificate($row) {
        if (!$row) return null;
        $row = (array) $row;
        return array(
            'id' => $row['id'],
            'code' => $row['code'],
            'studentId' => $row['student_id'],
            'studentName' => $row['student_name'],
            'studentCpf' => $row['student_cpf'],
            'courseId' => $row['course_id'],
            'courseName' => $row['course_name'],
            'workloadHours' => (int) $row['workload_hours'],
            'issueDate' => $row['issue_date'],
            'verificationHash' => $row['verification_hash'],
            'qrCodeUrl' => $row['qr_code_url'],
            'issuedBy' => $row['issued_by'] ?? 'Coordenação Geral PJB',
            'syllabus' => $row['syllabus'] ?? '',
        );
    }

    public static function get_certificates() {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_certificates';
        $rows = $wpdb->get_results("SELECT * FROM $table ORDER BY issue_date DESC", ARRAY_A);
        return array_map(array(__CLASS__, 'format_certificate'), $rows ?: array());
    }

    public static function get_certificate_by_code($code) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_certificates';
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE code = %s", $code), ARRAY_A);
        return self::format_certificate($row);
    }

    public static function get_certificates_by_cpf($cpf) {
        global $wpdb;
        $clean_cpf = preg_replace('/\D/', '', $cpf);
        if (empty($clean_cpf)) {
            return array();
        }
        $table = $wpdb->prefix . 'pjb_certificates';
        $all = $wpdb->get_results("SELECT * FROM $table ORDER BY issue_date DESC", ARRAY_A);
        $filtered = array_filter($all ?: array(), function ($row) use ($clean_cpf) {
            $student_cpf = preg_replace('/\D/', '', $row['student_cpf'] ?? '');
            return $student_cpf === $clean_cpf;
        });
        return array_values(array_map(array(__CLASS__, 'format_certificate'), $filtered));
    }

    public static function get_certificates_by_student($student_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_certificates';
        $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE student_id = %s", $student_id), ARRAY_A);
        return array_map(array(__CLASS__, 'format_certificate'), $rows ?: array());
    }

    public static function issue_certificate($student_id, $course_id, $issued_by = 'Coordenação Geral PJB') {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_certificates';

        $student = self::get_beneficiary_by_id($student_id);
        $course = self::get_course_by_id($course_id);

        if (!$student || !$course) {
            return false;
        }

        $id = 'crt_' . wp_generate_uuid4();
        $code = 'PJB-' . date('Y') . '-' . strtoupper(substr(wp_generate_password(8, false), 0, 8));
        $issue_date = date('Y-m-d');

        $sig = PJB_Crypto::generate_signature(array(
            'code' => $code,
            'studentId' => $student['id'],
            'courseId' => $course['id'],
            'issueDate' => $issue_date,
            'workloadHours' => $course['workloadHours'],
        ));

        // URL canônica para validação pública do certificado (compatível com domínios personalizados e sem #)
        $custom_val_url = get_option('pjb_custom_validation_url', '');
        if (!empty($custom_val_url)) {
            $base_val = rtrim($custom_val_url, '/');
            $verify_url = (strpos($base_val, '?') !== false)
                ? $base_val . '&code=' . urlencode($code)
                : $base_val . '?code=' . urlencode($code);
        } else {
            $verify_url = home_url('/validar?code=' . urlencode($code));
        }
        $qr_api = 'https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=' . urlencode($verify_url);

        $record = array(
            'id' => $id,
            'code' => $code,
            'student_id' => $student['id'],
            'student_name' => $student['name'],
            'student_cpf' => $student['cpf'],
            'course_id' => $course['id'],
            'course_name' => $course['name'],
            'workload_hours' => $course['workloadHours'],
            'issue_date' => $issue_date,
            'verification_hash' => $sig['hash'],
            'qr_code_url' => $qr_api,
            'issued_by' => sanitize_text_field($issued_by),
            'syllabus' => !empty($course['syllabus']) ? $course['syllabus'] : null,
        );

        $wpdb->insert($table, $record);
        return self::get_certificate_by_code($code);
    }

    // ========================================================
    // DOAÇÕES & CESTAS BÁSICAS
    // ========================================================
    public static function format_donation($row) {
        if (!$row) return null;
        $row = (array) $row;
        return array(
            'id' => $row['id'],
            'receiptNumber' => $row['receipt_number'],
            'type' => $row['type'],
            'category' => $row['category'],
            'itemDescription' => $row['item_description'],
            'quantity' => (float) $row['quantity'],
            'unit' => $row['unit'] ?? 'unidade',
            'partnerName' => $row['partner_name'] ?? '',
            'beneficiaryId' => $row['beneficiary_id'] ?? '',
            'beneficiaryName' => $row['beneficiary_name'] ?? '',
            'campaignId' => $row['campaign_id'] ?? '',
            'date' => $row['date'],
            'registeredBy' => $row['registered_by'] ?? 'Equipe PJB',
            'notes' => $row['notes'] ?? '',
        );
    }

    public static function get_donations() {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_donations';
        $rows = $wpdb->get_results("SELECT * FROM $table ORDER BY date DESC", ARRAY_A);
        return array_map(array(__CLASS__, 'format_donation'), $rows ?: array());
    }

    public static function get_donations_by_beneficiary($beneficiary_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_donations';
        $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE beneficiary_id = %s ORDER BY date DESC", $beneficiary_id), ARRAY_A);
        return array_map(array(__CLASS__, 'format_donation'), $rows ?: array());
    }

    public static function get_inventory_summary() {
        $donations = self::get_donations();
        $inventory = array(
            'foodBaskets' => 0,
            'foodKilos' => 0,
            'schoolKits' => 0,
            'clothingItems' => 0,
            'financialBalance' => 0,
        );

        foreach ($donations as $d) {
            $multiplier = ($d['type'] === 'entrada') ? 1 : -1;
            $qty = $d['quantity'] * $multiplier;

            if ($d['category'] === 'cesta_basica') {
                $inventory['foodBaskets'] += $qty;
            } elseif ($d['category'] === 'alimento') {
                $inventory['foodKilos'] += $qty;
            } elseif ($d['category'] === 'material_didatico') {
                $inventory['schoolKits'] += $qty;
            } elseif ($d['category'] === 'uniforme') {
                $inventory['clothingItems'] += $qty;
            } elseif ($d['category'] === 'financeiro') {
                $inventory['financialBalance'] += $qty;
            }
        }

        // Garante que não mostre saldo de estoque negativo
        $inventory['foodBaskets'] = max(0, $inventory['foodBaskets']);
        $inventory['foodKilos'] = max(0, $inventory['foodKilos']);
        $inventory['schoolKits'] = max(0, $inventory['schoolKits']);
        $inventory['clothingItems'] = max(0, $inventory['clothingItems']);

        return $inventory;
    }

    public static function create_donation($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_donations';

        $id = !empty($data['id']) ? $data['id'] : 'don_' . wp_generate_uuid4();
        $receipt = 'REC-' . date('Y') . '-' . strtoupper(substr(wp_generate_password(6, false), 0, 6));

        $record = array(
            'id' => $id,
            'receipt_number' => $receipt,
            'type' => sanitize_text_field($data['type']),
            'category' => sanitize_text_field($data['category']),
            'item_description' => sanitize_text_field($data['itemDescription']),
            'quantity' => (float) $data['quantity'],
            'unit' => sanitize_text_field($data['unit'] ?? 'unidade'),
            'partner_name' => sanitize_text_field($data['partnerName'] ?? ''),
            'beneficiary_id' => sanitize_text_field($data['beneficiaryId'] ?? ''),
            'beneficiary_name' => sanitize_text_field($data['beneficiaryName'] ?? ''),
            'campaign_id' => sanitize_text_field($data['campaignId'] ?? ''),
            'date' => sanitize_text_field($data['date'] ?? date('Y-m-d')),
            'registered_by' => sanitize_text_field($data['registeredBy'] ?? 'Equipe PJB'),
            'notes' => sanitize_textarea_field($data['notes'] ?? ''),
        );

        $inserted = $wpdb->insert($table, $record);
        if (!$inserted) return false;

        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %s", $id), ARRAY_A);
        return self::format_donation($row);
    }

    public static function delete_donation($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_donations';
        return (bool) $wpdb->delete($table, array('id' => $id));
    }

    // ========================================================
    // CAMPANHAS SOLIDÁRIAS
    // ========================================================
    public static function get_campaigns() {
        global $wpdb;
        $table_c = $wpdb->prefix . 'pjb_campaigns';
        $table_d = $wpdb->prefix . 'pjb_donations';
        $rows = $wpdb->get_results("
            SELECT c.*, 
                   COALESCE((
                       SELECT SUM(d.quantity) 
                       FROM $table_d d 
                       WHERE d.campaign_id = c.id AND d.type = 'entrada'
                   ), 0) AS current_progress
            FROM $table_c c 
            ORDER BY c.start_date DESC
        ", ARRAY_A);

        return array_map(function ($r) {
            $tags = array();
            if (!empty($r['tags'])) {
                $decoded = json_decode($r['tags'], true);
                if (is_array($decoded)) {
                    $tags = $decoded;
                } elseif (is_string($r['tags'])) {
                    $tags = array_filter(array_map('trim', explode(',', $r['tags'])));
                }
            }
            return array(
                'id' => $r['id'],
                'title' => $r['title'],
                'description' => $r['description'] ?? '',
                'category' => $r['category'] ?? 'Alimentação',
                'targetGoal' => (float) ($r['target_goal'] ?? 0),
                'currentProgress' => (float) ($r['current_progress'] ?? 0),
                'unit' => $r['unit'] ?? 'Unidades',
                'startDate' => $r['start_date'],
                'endDate' => $r['end_date'],
                'location' => $r['location'] ?? 'Sede PJB Castanhal',
                'status' => $r['status'] ?? 'em_andamento',
                'tags' => $tags,
            );
        }, $rows ?: array());
    }

    public static function get_campaign_by_id($id) {
        $camps = self::get_campaigns();
        foreach ($camps as $c) {
            if ($c['id'] === $id) return $c;
        }
        return null;
    }

    public static function create_campaign($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_campaigns';
        $id = !empty($data['id']) ? $data['id'] : 'cmp_' . wp_generate_uuid4();

        $tags_json = null;
        if (isset($data['tags'])) {
            $tags_json = is_array($data['tags']) ? wp_json_encode(array_values($data['tags'])) : sanitize_text_field($data['tags']);
        }

        $record = array(
            'id' => $id,
            'title' => sanitize_text_field($data['title']),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'category' => sanitize_text_field($data['category'] ?? 'Alimentação'),
            'target_goal' => (float) $data['targetGoal'],
            'unit' => sanitize_text_field($data['unit'] ?? 'Unidades'),
            'start_date' => sanitize_text_field($data['startDate'] ?? date('Y-m-d')),
            'end_date' => sanitize_text_field($data['endDate'] ?? date('Y-m-d', strtotime('+30 days'))),
            'location' => sanitize_text_field($data['location'] ?? 'Sede PJB Castanhal'),
            'status' => sanitize_text_field($data['status'] ?? 'em_andamento'),
            'tags' => $tags_json,
        );

        $inserted = $wpdb->insert($table, $record);
        if (!$inserted) return false;

        return self::get_campaign_by_id($id);
    }

    public static function update_campaign($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_campaigns';
        $fields = array();

        if (isset($data['title'])) $fields['title'] = sanitize_text_field($data['title']);
        if (isset($data['description'])) $fields['description'] = sanitize_textarea_field($data['description']);
        if (isset($data['category'])) $fields['category'] = sanitize_text_field($data['category']);
        if (isset($data['targetGoal'])) $fields['target_goal'] = (float) $data['targetGoal'];
        if (isset($data['unit'])) $fields['unit'] = sanitize_text_field($data['unit']);
        if (isset($data['startDate'])) $fields['start_date'] = sanitize_text_field($data['startDate']);
        if (isset($data['endDate'])) $fields['end_date'] = sanitize_text_field($data['endDate']);
        if (isset($data['location'])) $fields['location'] = sanitize_text_field($data['location']);
        if (isset($data['status'])) $fields['status'] = sanitize_text_field($data['status']);
        if (isset($data['tags'])) {
            $fields['tags'] = is_array($data['tags']) ? wp_json_encode(array_values($data['tags'])) : sanitize_text_field($data['tags']);
        }

        if (empty($fields)) {
            return self::get_campaign_by_id($id);
        }

        $wpdb->update($table, $fields, array('id' => $id));
        return self::get_campaign_by_id($id);
    }

    public static function delete_campaign($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_campaigns';
        return (bool) $wpdb->delete($table, array('id' => $id));
    }

    // ========================================================
    // AÇÕES ESPORTIVAS, CULTURAIS, MÚSICA & JOGOS
    // ========================================================
    public static function format_cultural_activity($row, $participants = array(), $supporters = array()) {
        if (!$row) return null;
        $row = (array) $row;

        $tags = array();
        if (!empty($row['tags'])) {
            $dec = json_decode($row['tags'], true);
            if (is_array($dec)) {
                $tags = $dec;
            } elseif (is_string($row['tags'])) {
                $tags = array_filter(array_map('trim', explode(',', $row['tags'])));
            }
        }

        return array(
            'id' => $row['id'],
            'title' => $row['title'],
            'modality' => $row['modality'],
            'type' => $row['type'] ?? 'cultura',
            'description' => $row['description'] ?? '',
            'targetAgeMin' => (int) ($row['target_age_min'] ?? 6),
            'targetAgeMax' => (int) ($row['target_age_max'] ?? 29),
            'location' => $row['location'] ?? 'Sede PJB Castanhal',
            'schedule' => $row['schedule'] ?? '',
            'startDate' => $row['start_date'],
            'endDate' => $row['end_date'] ?? null,
            'status' => $row['status'] ?? 'planejado',
            'maxParticipants' => (int) ($row['max_participants'] ?? 30),
            'instructorName' => $row['instructor_name'] ?? 'Equipe PJB',
            'tags' => $tags,
            'achievementsSummary' => $row['achievements_summary'] ?? '',
            'createdAt' => $row['created_at'] ?? gmdate('Y-m-d H:i:s'),
            'participants' => $participants,
            'supporters' => $supporters,
        );
    }

    public static function get_cultural_activities($filters = array()) {
        global $wpdb;
        $table_a = $wpdb->prefix . 'pjb_cultural_activities';
        $table_p = $wpdb->prefix . 'pjb_cultural_participants';
        $table_s = $wpdb->prefix . 'pjb_cultural_supporters';

        $where = array('1=1');
        $params = array();

        if (!empty($filters['type']) && $filters['type'] !== 'todos') {
            $where[] = "type = %s";
            $params[] = $filters['type'];
        }
        if (!empty($filters['status']) && $filters['status'] !== 'todos') {
            $where[] = "status = %s";
            $params[] = $filters['status'];
        }
        if (!empty($filters['modality']) && $filters['modality'] !== 'todas') {
            $where[] = "modality = %s";
            $params[] = $filters['modality'];
        }

        $sql = "SELECT * FROM $table_a WHERE " . implode(' AND ', $where) . " ORDER BY start_date DESC";
        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        $rows = $wpdb->get_results($sql, ARRAY_A);
        if (!$rows) return array();

        // Agrupa participantes e apoiadores
        $all_parts = $wpdb->get_results("SELECT * FROM $table_p ORDER BY registration_date DESC", ARRAY_A) ?: array();
        $parts_by_act = array();
        foreach ($all_parts as $p) {
            $parts_by_act[$p['activity_id']][] = array(
                'id' => $p['id'],
                'activityId' => $p['activity_id'],
                'beneficiaryId' => $p['beneficiary_id'] ?? null,
                'beneficiaryName' => $p['beneficiary_name'],
                'beneficiaryCpf' => $p['beneficiary_cpf'] ?? '',
                'beneficiaryPhone' => $p['beneficiary_phone'] ?? '',
                'role' => $p['role'] ?? 'participante',
                'status' => $p['status'] ?? 'confirmado',
                'achievement' => $p['achievement'] ?? '',
                'notes' => $p['notes'] ?? '',
                'registrationDate' => $p['registration_date'],
            );
        }

        $all_sups = $wpdb->get_results("SELECT * FROM $table_s", ARRAY_A) ?: array();
        $sups_by_act = array();
        foreach ($all_sups as $s) {
            $sups_by_act[$s['activity_id']][] = array(
                'id' => $s['id'],
                'activityId' => $s['activity_id'],
                'name' => $s['name'],
                'type' => $s['type'] ?? 'empresa_parceira',
                'contact' => $s['contact'] ?? '',
                'contribution' => $s['contribution'],
                'notes' => $s['notes'] ?? '',
            );
        }

        $result = array();
        foreach ($rows as $r) {
            $act_id = $r['id'];
            $parts = $parts_by_act[$act_id] ?? array();
            $sups = $sups_by_act[$act_id] ?? array();
            $result[] = self::format_cultural_activity($r, $parts, $sups);
        }

        return $result;
    }

    public static function get_cultural_activity_by_id($id) {
        global $wpdb;
        $table_a = $wpdb->prefix . 'pjb_cultural_activities';
        $table_p = $wpdb->prefix . 'pjb_cultural_participants';
        $table_s = $wpdb->prefix . 'pjb_cultural_supporters';

        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_a WHERE id = %s", $id), ARRAY_A);
        if (!$row) return null;

        $parts_raw = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_p WHERE activity_id = %s ORDER BY registration_date DESC", $id), ARRAY_A) ?: array();
        $participants = array_map(function ($p) {
            return array(
                'id' => $p['id'],
                'activityId' => $p['activity_id'],
                'beneficiaryId' => $p['beneficiary_id'] ?? null,
                'beneficiaryName' => $p['beneficiary_name'],
                'beneficiaryCpf' => $p['beneficiary_cpf'] ?? '',
                'beneficiaryPhone' => $p['beneficiary_phone'] ?? '',
                'role' => $p['role'] ?? 'participante',
                'status' => $p['status'] ?? 'confirmado',
                'achievement' => $p['achievement'] ?? '',
                'notes' => $p['notes'] ?? '',
                'registrationDate' => $p['registration_date'],
            );
        }, $parts_raw);

        $sups_raw = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_s WHERE activity_id = %s", $id), ARRAY_A) ?: array();
        $supporters = array_map(function ($s) {
            return array(
                'id' => $s['id'],
                'activityId' => $s['activity_id'],
                'name' => $s['name'],
                'type' => $s['type'] ?? 'empresa_parceira',
                'contact' => $s['contact'] ?? '',
                'contribution' => $s['contribution'],
                'notes' => $s['notes'] ?? '',
            );
        }, $sups_raw);

        return self::format_cultural_activity($row, $participants, $supporters);
    }

    public static function create_cultural_activity($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_cultural_activities';
        $id = !empty($data['id']) ? $data['id'] : 'act_' . wp_generate_uuid4();

        $tags_json = null;
        if (isset($data['tags'])) {
            $tags_json = is_array($data['tags']) ? wp_json_encode(array_values($data['tags'])) : sanitize_text_field($data['tags']);
        }

        $record = array(
            'id' => $id,
            'title' => sanitize_text_field($data['title']),
            'modality' => sanitize_text_field($data['modality']),
            'type' => sanitize_text_field($data['type'] ?? 'cultura'),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'target_age_min' => isset($data['targetAgeMin']) ? (int) $data['targetAgeMin'] : 6,
            'target_age_max' => isset($data['targetAgeMax']) ? (int) $data['targetAgeMax'] : 29,
            'location' => sanitize_text_field($data['location'] ?? 'Sede PJB Castanhal'),
            'schedule' => sanitize_text_field($data['schedule'] ?? 'Horário regular'),
            'start_date' => sanitize_text_field($data['startDate'] ?? date('Y-m-d')),
            'end_date' => !empty($data['endDate']) ? sanitize_text_field($data['endDate']) : null,
            'status' => sanitize_text_field($data['status'] ?? 'planejado'),
            'max_participants' => isset($data['maxParticipants']) ? (int) $data['maxParticipants'] : 30,
            'instructor_name' => sanitize_text_field($data['instructorName'] ?? 'Equipe PJB'),
            'tags' => $tags_json,
            'achievements_summary' => sanitize_textarea_field($data['achievementsSummary'] ?? ''),
            'created_at' => current_time('mysql'),
        );

        $inserted = $wpdb->insert($table, $record);
        if (!$inserted) return false;

        return self::get_cultural_activity_by_id($id);
    }

    public static function update_cultural_activity($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_cultural_activities';
        $fields = array();

        if (isset($data['title'])) $fields['title'] = sanitize_text_field($data['title']);
        if (isset($data['modality'])) $fields['modality'] = sanitize_text_field($data['modality']);
        if (isset($data['type'])) $fields['type'] = sanitize_text_field($data['type']);
        if (isset($data['description'])) $fields['description'] = sanitize_textarea_field($data['description']);
        if (isset($data['targetAgeMin'])) $fields['target_age_min'] = (int) $data['targetAgeMin'];
        if (isset($data['targetAgeMax'])) $fields['target_age_max'] = (int) $data['targetAgeMax'];
        if (isset($data['location'])) $fields['location'] = sanitize_text_field($data['location']);
        if (isset($data['schedule'])) $fields['schedule'] = sanitize_text_field($data['schedule']);
        if (isset($data['startDate'])) $fields['start_date'] = sanitize_text_field($data['startDate']);
        if (isset($data['endDate'])) $fields['end_date'] = !empty($data['endDate']) ? sanitize_text_field($data['endDate']) : null;
        if (isset($data['status'])) $fields['status'] = sanitize_text_field($data['status']);
        if (isset($data['maxParticipants'])) $fields['max_participants'] = (int) $data['maxParticipants'];
        if (isset($data['instructorName'])) $fields['instructor_name'] = sanitize_text_field($data['instructorName']);
        if (isset($data['tags'])) {
            $fields['tags'] = is_array($data['tags']) ? wp_json_encode(array_values($data['tags'])) : sanitize_text_field($data['tags']);
        }
        if (isset($data['achievementsSummary'])) $fields['achievements_summary'] = sanitize_textarea_field($data['achievementsSummary']);

        if (!empty($fields)) {
            $wpdb->update($table, $fields, array('id' => $id));
        }

        return self::get_cultural_activity_by_id($id);
    }

    public static function delete_cultural_activity($id) {
        global $wpdb;
        $table_a = $wpdb->prefix . 'pjb_cultural_activities';
        $table_p = $wpdb->prefix . 'pjb_cultural_participants';
        $table_s = $wpdb->prefix . 'pjb_cultural_supporters';

        $wpdb->delete($table_p, array('activity_id' => $id));
        $wpdb->delete($table_s, array('activity_id' => $id));
        return (bool) $wpdb->delete($table_a, array('id' => $id));
    }

    public static function add_cultural_participant($activity_id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_cultural_participants';
        $id = 'part_' . wp_generate_uuid4();

        // Evita duplicidade
        if (!empty($data['beneficiaryId'])) {
            $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE activity_id = %s AND beneficiary_id = %s", $activity_id, $data['beneficiaryId']));
            if ($existing) {
                return self::get_cultural_activity_by_id($activity_id);
            }
        }

        $record = array(
            'id' => $id,
            'activity_id' => $activity_id,
            'beneficiary_id' => !empty($data['beneficiaryId']) ? sanitize_text_field($data['beneficiaryId']) : null,
            'beneficiary_name' => sanitize_text_field($data['beneficiaryName']),
            'beneficiary_cpf' => !empty($data['beneficiaryCpf']) ? sanitize_text_field($data['beneficiaryCpf']) : null,
            'beneficiary_phone' => !empty($data['beneficiaryPhone']) ? sanitize_text_field($data['beneficiaryPhone']) : null,
            'role' => sanitize_text_field($data['role'] ?? 'participante'),
            'status' => sanitize_text_field($data['status'] ?? 'confirmado'),
            'achievement' => !empty($data['achievement']) ? sanitize_text_field($data['achievement']) : null,
            'notes' => !empty($data['notes']) ? sanitize_textarea_field($data['notes']) : null,
            'registration_date' => sanitize_text_field($data['registrationDate'] ?? date('Y-m-d')),
        );

        $wpdb->insert($table, $record);
        return self::get_cultural_activity_by_id($activity_id);
    }

    public static function update_cultural_participant($activity_id, $participant_id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_cultural_participants';
        $fields = array();

        if (isset($data['role'])) $fields['role'] = sanitize_text_field($data['role']);
        if (isset($data['status'])) $fields['status'] = sanitize_text_field($data['status']);
        if (isset($data['achievement'])) $fields['achievement'] = sanitize_text_field($data['achievement']);
        if (isset($data['notes'])) $fields['notes'] = sanitize_textarea_field($data['notes']);

        if (!empty($fields)) {
            $wpdb->update($table, $fields, array('id' => $participant_id, 'activity_id' => $activity_id));
        }

        return self::get_cultural_activity_by_id($activity_id);
    }

    public static function remove_cultural_participant($activity_id, $participant_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_cultural_participants';
        $wpdb->delete($table, array('id' => $participant_id, 'activity_id' => $activity_id));
        return self::get_cultural_activity_by_id($activity_id);
    }

    public static function add_cultural_supporter($activity_id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_cultural_supporters';
        $id = 'sup_' . wp_generate_uuid4();

        $record = array(
            'id' => $id,
            'activity_id' => $activity_id,
            'name' => sanitize_text_field($data['name']),
            'type' => sanitize_text_field($data['type'] ?? 'empresa_parceira'),
            'contact' => !empty($data['contact']) ? sanitize_text_field($data['contact']) : null,
            'contribution' => sanitize_text_field($data['contribution']),
            'notes' => !empty($data['notes']) ? sanitize_textarea_field($data['notes']) : null,
        );

        $wpdb->insert($table, $record);
        return self::get_cultural_activity_by_id($activity_id);
    }

    public static function remove_cultural_supporter($activity_id, $supporter_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_cultural_supporters';
        $wpdb->delete($table, array('id' => $supporter_id, 'activity_id' => $activity_id));
        return self::get_cultural_activity_by_id($activity_id);
    }

    // ========================================================
    // SAÚDE & JURÍDICO - PROFISSIONAIS VOLUNTÁRIOS
    // ========================================================
    public static function format_attendance_professional($row) {
        if (!$row) return null;

        $specific_dates = array();
        if (!empty($row['specific_dates'])) {
            $decoded = json_decode($row['specific_dates'], true);
            $specific_dates = is_array($decoded) ? $decoded : array_filter(array_map('trim', explode(',', $row['specific_dates'])));
        }

        $available_days = array(1, 2, 3, 4, 5);
        if (isset($row['available_days']) && $row['available_days'] !== '') {
            $available_days = array_map('intval', explode(',', $row['available_days']));
        }

        $available_hours = array();
        if (!empty($row['available_hours'])) {
            $decoded_hours = json_decode($row['available_hours'], true);
            $available_hours = is_array($decoded_hours) ? $decoded_hours : array_filter(array_map('trim', explode(',', $row['available_hours'])));
        }

        return array(
            'id' => $row['id'],
            'name' => $row['name'],
            'area' => $row['area'],
            'specialty' => $row['specialty'],
            'registrationNumber' => $row['registration_number'],
            'phone' => $row['phone'],
            'email' => $row['email'] ?? '',
            'organization' => $row['organization'] ?? 'Voluntário PJB',
            'shiftsDescription' => $row['shifts_description'] ?? '',
            'scheduleType' => $row['schedule_type'] ?? 'weekly',
            'specificDates' => $specific_dates,
            'availableDays' => $available_days,
            'shiftStartTime' => $row['shift_start_time'] ?? '14:00',
            'shiftEndTime' => $row['shift_end_time'] ?? '18:00',
            'slotDurationMinutes' => !empty($row['slot_duration_minutes']) ? intval($row['slot_duration_minutes']) : 30,
            'availableHours' => $available_hours,
            'active' => (bool) $row['active'],
            'notes' => $row['notes'] ?? '',
            'createdAt' => $row['created_at'],
        );
    }

    public static function get_attendance_professionals($filters = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_attendance_professionals';
        $where = array('1=1');
        $params = array();

        if (!empty($filters['area']) && $filters['area'] !== 'todas' && $filters['area'] !== 'todos') {
            $where[] = "area = %s";
            $params[] = $filters['area'];
        }
        if (isset($filters['active']) && $filters['active'] !== '') {
            $where[] = "active = %d";
            $params[] = $filters['active'] ? 1 : 0;
        }

        $sql = "SELECT * FROM $table WHERE " . implode(' AND ', $where) . " ORDER BY name ASC";
        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        $rows = $wpdb->get_results($sql, ARRAY_A);
        if (!$rows) return array();

        return array_map(array(__CLASS__, 'format_attendance_professional'), $rows);
    }

    public static function get_attendance_professional_by_id($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_attendance_professionals';
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %s", $id), ARRAY_A);
        return self::format_attendance_professional($row);
    }

    public static function create_attendance_professional($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_attendance_professionals';
        $id = !empty($data['id']) ? $data['id'] : 'prof_' . wp_generate_uuid4();

        $specific_dates = isset($data['specificDates']) && is_array($data['specificDates']) ? wp_json_encode($data['specificDates']) : null;
        $available_days = isset($data['availableDays']) && is_array($data['availableDays']) ? implode(',', $data['availableDays']) : '1,2,3,4,5';
        $available_hours = isset($data['availableHours']) && is_array($data['availableHours']) ? wp_json_encode($data['availableHours']) : null;

        $record = array(
            'id' => $id,
            'name' => sanitize_text_field($data['name']),
            'area' => sanitize_text_field($data['area'] ?? 'saude'),
            'specialty' => sanitize_text_field($data['specialty']),
            'registration_number' => sanitize_text_field($data['registrationNumber'] ?? ($data['registration_number'] ?? '')),
            'phone' => sanitize_text_field($data['phone']),
            'email' => !empty($data['email']) ? sanitize_email($data['email']) : null,
            'organization' => sanitize_text_field($data['organization'] ?? 'Voluntário PJB'),
            'shifts_description' => sanitize_text_field($data['shiftsDescription'] ?? ($data['shifts_description'] ?? '')),
            'schedule_type' => sanitize_text_field($data['scheduleType'] ?? 'weekly'),
            'specific_dates' => $specific_dates,
            'available_days' => $available_days,
            'shift_start_time' => sanitize_text_field($data['shiftStartTime'] ?? '14:00'),
            'shift_end_time' => sanitize_text_field($data['shiftEndTime'] ?? '18:00'),
            'slot_duration_minutes' => !empty($data['slotDurationMinutes']) ? intval($data['slotDurationMinutes']) : 30,
            'available_hours' => $available_hours,
            'active' => isset($data['active']) ? ($data['active'] ? 1 : 0) : 1,
            'notes' => !empty($data['notes']) ? sanitize_textarea_field($data['notes']) : null,
            'created_at' => current_time('mysql'),
        );

        $inserted = $wpdb->insert($table, $record);
        if (!$inserted) return false;

        return self::get_attendance_professional_by_id($id);
    }

    public static function update_attendance_professional($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_attendance_professionals';

        $record = array();
        if (isset($data['name'])) $record['name'] = sanitize_text_field($data['name']);
        if (isset($data['area'])) $record['area'] = sanitize_text_field($data['area']);
        if (isset($data['specialty'])) $record['specialty'] = sanitize_text_field($data['specialty']);
        if (isset($data['registrationNumber'])) $record['registration_number'] = sanitize_text_field($data['registrationNumber']);
        if (isset($data['registration_number'])) $record['registration_number'] = sanitize_text_field($data['registration_number']);
        if (isset($data['phone'])) $record['phone'] = sanitize_text_field($data['phone']);
        if (isset($data['email'])) $record['email'] = sanitize_email($data['email']);
        if (isset($data['organization'])) $record['organization'] = sanitize_text_field($data['organization']);
        if (isset($data['shiftsDescription'])) $record['shifts_description'] = sanitize_text_field($data['shiftsDescription']);
        if (isset($data['shifts_description'])) $record['shifts_description'] = sanitize_text_field($data['shifts_description']);
        if (isset($data['scheduleType'])) $record['schedule_type'] = sanitize_text_field($data['scheduleType']);
        if (isset($data['specificDates'])) $record['specific_dates'] = is_array($data['specificDates']) ? wp_json_encode($data['specificDates']) : null;
        if (isset($data['availableDays'])) $record['available_days'] = is_array($data['availableDays']) ? implode(',', $data['availableDays']) : $data['availableDays'];
        if (isset($data['shiftStartTime'])) $record['shift_start_time'] = sanitize_text_field($data['shiftStartTime']);
        if (isset($data['shiftEndTime'])) $record['shift_end_time'] = sanitize_text_field($data['shiftEndTime']);
        if (isset($data['slotDurationMinutes'])) $record['slot_duration_minutes'] = intval($data['slotDurationMinutes']);
        if (isset($data['availableHours'])) $record['available_hours'] = is_array($data['availableHours']) ? wp_json_encode($data['availableHours']) : null;
        if (isset($data['active'])) $record['active'] = $data['active'] ? 1 : 0;
        if (isset($data['notes'])) $record['notes'] = sanitize_textarea_field($data['notes']);

        if (!empty($record)) {
            $wpdb->update($table, $record, array('id' => $id));
        }

        return self::get_attendance_professional_by_id($id);
    }

    public static function delete_attendance_professional($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_attendance_professionals';
        return (bool) $wpdb->delete($table, array('id' => $id));
    }

    // ========================================================
    // SAÚDE & JURÍDICO - ATENDIMENTOS E PRONTUÁRIOS
    // ========================================================
    public static function format_attendance($row) {
        if (!$row) return null;

        $tags = array();
        if (!empty($row['tags'])) {
            $decoded = json_decode($row['tags'], true);
            $tags = is_array($decoded) ? $decoded : array_map('trim', explode(',', $row['tags']));
        }

        return array(
            'id' => $row['id'],
            'protocolNumber' => $row['protocol_number'],
            'area' => $row['area'],
            'specialty' => $row['specialty'],
            'beneficiaryId' => $row['beneficiary_id'] ?? null,
            'beneficiaryName' => $row['beneficiary_name'],
            'beneficiaryCpf' => $row['beneficiary_cpf'] ?? '',
            'beneficiaryPhone' => $row['beneficiary_phone'] ?? '',
            'beneficiaryBirthDate' => $row['beneficiary_birth_date'] ?? '',
            'professionalId' => $row['professional_id'] ?? null,
            'professionalName' => $row['professional_name'],
            'professionalRegistration' => $row['professional_registration'] ?? '',
            'date' => $row['date'],
            'time' => $row['time'] ?? '',
            'location' => $row['location'] ?? 'Sede PJB Castanhal',
            'status' => $row['status'] ?? 'agendado',
            'priority' => $row['priority'] ?? 'normal',
            'chiefComplaint' => $row['chief_complaint'],
            'technicalNotes' => $row['technical_notes'] ?? '',
            'prescriptionsOrActions' => $row['prescriptions_or_actions'] ?? '',
            'referrals' => $row['referrals'] ?? '',
            'returnDate' => $row['return_date'] ?? '',
            'tags' => $tags,
            'createdAt' => $row['created_at'],
            'updatedAt' => $row['updated_at'] ?? $row['created_at'],
        );
    }

    public static function get_attendances($filters = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_service_attendances';
        $where = array('1=1');
        $params = array();

        if (!empty($filters['area']) && $filters['area'] !== 'todas' && $filters['area'] !== 'todos') {
            $where[] = "area = %s";
            $params[] = $filters['area'];
        }
        if (!empty($filters['status']) && $filters['status'] !== 'todos') {
            $where[] = "status = %s";
            $params[] = $filters['status'];
        }
        if (!empty($filters['specialty']) && $filters['specialty'] !== 'todas') {
            $where[] = "specialty = %s";
            $params[] = $filters['specialty'];
        }
        if (!empty($filters['beneficiaryId'])) {
            $where[] = "beneficiary_id = %s";
            $params[] = $filters['beneficiaryId'];
        }
        if (!empty($filters['search'])) {
            $search_like = '%' . $wpdb->esc_like($filters['search']) . '%';
            $where[] = "(beneficiary_name LIKE %s OR protocol_number LIKE %s OR professional_name LIKE %s OR chief_complaint LIKE %s)";
            $params[] = $search_like;
            $params[] = $search_like;
            $params[] = $search_like;
            $params[] = $search_like;
        }

        $sql = "SELECT * FROM $table WHERE " . implode(' AND ', $where) . " ORDER BY date DESC, created_at DESC";
        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        $rows = $wpdb->get_results($sql, ARRAY_A);
        if (!$rows) return array();

        return array_map(array(__CLASS__, 'format_attendance'), $rows);
    }

    public static function get_attendance_by_id($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_service_attendances';
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %s", $id), ARRAY_A);
        return self::format_attendance($row);
    }

    public static function create_attendance($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_service_attendances';
        $id = !empty($data['id']) ? $data['id'] : 'att_' . wp_generate_uuid4();

        // Gera protocolo sequencial se ausente
        $protocol = !empty($data['protocolNumber']) ? $data['protocolNumber'] : null;
        if (!$protocol) {
            $prefix = (isset($data['area']) && $data['area'] === 'juridico') ? 'PJB-JUR' : 'PJB-SAU';
            $year = date('Y');
            $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table") + 1;
            $protocol = sprintf("%s-%s-%04d", $prefix, $year, $count);
        }

        $tags = isset($data['tags']) && is_array($data['tags']) ? wp_json_encode($data['tags']) : (is_string($data['tags'] ?? '') ? $data['tags'] : '[]');

        $record = array(
            'id' => $id,
            'protocol_number' => $protocol,
            'area' => sanitize_text_field($data['area'] ?? 'saude'),
            'specialty' => sanitize_text_field($data['specialty']),
            'beneficiary_id' => !empty($data['beneficiaryId']) ? sanitize_text_field($data['beneficiaryId']) : null,
            'beneficiary_name' => sanitize_text_field($data['beneficiaryName']),
            'beneficiary_cpf' => !empty($data['beneficiaryCpf']) ? sanitize_text_field($data['beneficiaryCpf']) : null,
            'beneficiary_phone' => !empty($data['beneficiaryPhone']) ? sanitize_text_field($data['beneficiaryPhone']) : null,
            'beneficiary_birth_date' => !empty($data['beneficiaryBirthDate']) ? sanitize_text_field($data['beneficiaryBirthDate']) : null,
            'professional_id' => !empty($data['professionalId']) ? sanitize_text_field($data['professionalId']) : null,
            'professional_name' => sanitize_text_field($data['professionalName']),
            'professional_registration' => !empty($data['professionalRegistration']) ? sanitize_text_field($data['professionalRegistration']) : null,
            'date' => sanitize_text_field($data['date'] ?? date('Y-m-d')),
            'time' => !empty($data['time']) ? sanitize_text_field($data['time']) : null,
            'location' => sanitize_text_field($data['location'] ?? 'Sede PJB Castanhal'),
            'status' => sanitize_text_field($data['status'] ?? 'agendado'),
            'priority' => sanitize_text_field($data['priority'] ?? 'normal'),
            'chief_complaint' => sanitize_textarea_field($data['chiefComplaint']),
            'technical_notes' => !empty($data['technicalNotes']) ? sanitize_textarea_field($data['technicalNotes']) : null,
            'prescriptions_or_actions' => !empty($data['prescriptionsOrActions']) ? sanitize_textarea_field($data['prescriptionsOrActions']) : null,
            'referrals' => !empty($data['referrals']) ? sanitize_textarea_field($data['referrals']) : null,
            'return_date' => !empty($data['returnDate']) ? sanitize_text_field($data['returnDate']) : null,
            'tags' => $tags,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        );

        $inserted = $wpdb->insert($table, $record);
        if (!$inserted) return false;

        return self::get_attendance_by_id($id);
    }

    public static function update_attendance($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_service_attendances';

        $record = array('updated_at' => current_time('mysql'));
        if (isset($data['area'])) $record['area'] = sanitize_text_field($data['area']);
        if (isset($data['specialty'])) $record['specialty'] = sanitize_text_field($data['specialty']);
        if (isset($data['beneficiaryId'])) $record['beneficiary_id'] = sanitize_text_field($data['beneficiaryId']);
        if (isset($data['beneficiaryName'])) $record['beneficiary_name'] = sanitize_text_field($data['beneficiaryName']);
        if (isset($data['beneficiaryCpf'])) $record['beneficiary_cpf'] = sanitize_text_field($data['beneficiaryCpf']);
        if (isset($data['beneficiaryPhone'])) $record['beneficiary_phone'] = sanitize_text_field($data['beneficiaryPhone']);
        if (isset($data['beneficiaryBirthDate'])) $record['beneficiary_birth_date'] = sanitize_text_field($data['beneficiaryBirthDate']);
        if (isset($data['professionalId'])) $record['professional_id'] = sanitize_text_field($data['professionalId']);
        if (isset($data['professionalName'])) $record['professional_name'] = sanitize_text_field($data['professionalName']);
        if (isset($data['professionalRegistration'])) $record['professional_registration'] = sanitize_text_field($data['professionalRegistration']);
        if (isset($data['date'])) $record['date'] = sanitize_text_field($data['date']);
        if (isset($data['time'])) $record['time'] = sanitize_text_field($data['time']);
        if (isset($data['location'])) $record['location'] = sanitize_text_field($data['location']);
        if (isset($data['status'])) $record['status'] = sanitize_text_field($data['status']);
        if (isset($data['priority'])) $record['priority'] = sanitize_text_field($data['priority']);
        if (isset($data['chiefComplaint'])) $record['chief_complaint'] = sanitize_textarea_field($data['chiefComplaint']);
        if (isset($data['technicalNotes'])) $record['technical_notes'] = sanitize_textarea_field($data['technicalNotes']);
        if (isset($data['prescriptionsOrActions'])) $record['prescriptions_or_actions'] = sanitize_textarea_field($data['prescriptionsOrActions']);
        if (isset($data['referrals'])) $record['referrals'] = sanitize_textarea_field($data['referrals']);
        if (isset($data['returnDate'])) $record['return_date'] = sanitize_text_field($data['returnDate']);
        if (isset($data['tags'])) {
            $record['tags'] = is_array($data['tags']) ? wp_json_encode($data['tags']) : sanitize_text_field($data['tags']);
        }

        $wpdb->update($table, $record, array('id' => $id));
        return self::get_attendance_by_id($id);
    }

    public static function update_attendance_status($id, $status) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_service_attendances';
        $valid = array('agendado', 'em_espera', 'em_atendimento', 'concluido', 'retorno_pendente', 'cancelado');
        if (!in_array($status, $valid, true)) {
            return false;
        }

        $wpdb->update($table, array(
            'status' => sanitize_text_field($status),
            'updated_at' => current_time('mysql'),
        ), array('id' => $id));

        return self::get_attendance_by_id($id);
    }

    public static function delete_attendance($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_service_attendances';
        return (bool) $wpdb->delete($table, array('id' => $id));
    }

    public static function get_attendance_stats() {
        global $wpdb;
        $table_att = $wpdb->prefix . 'pjb_service_attendances';
        $table_prof = $wpdb->prefix . 'pjb_attendance_professionals';

        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_att");
        $saude = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_att WHERE area = 'saude'");
        $juridico = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_att WHERE area = 'juridico'");
        $emEspera = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_att WHERE status = 'em_espera'");
        $emAtendimento = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_att WHERE status = 'em_atendimento'");
        $concluidos = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_att WHERE status = 'concluido'");
        $agendados = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_att WHERE status = 'agendado'");
        $retornoPendente = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_att WHERE status = 'retorno_pendente'");
        $voluntariosAtivos = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_prof WHERE active = 1");

        return array(
            'total' => $total,
            'saude' => $saude,
            'juridico' => $juridico,
            'emEspera' => $emEspera,
            'emAtendimento' => $emAtendimento,
            'concluidos' => $concluidos,
            'agendados' => $agendados,
            'retornoPendente' => $retornoPendente,
            'voluntariosAtivos' => $voluntariosAtivos,
        );
    }

    // ========================================================
    // NOTIFICAÇÕES & HISTÓRICO
    // ========================================================
    public static function get_notifications() {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_notifications';
        $rows = $wpdb->get_results("SELECT * FROM $table ORDER BY timestamp DESC LIMIT 50", ARRAY_A);

        return array_map(function ($r) {
            return array(
                'id' => $r['id'],
                'recipientPhone' => $r['recipient_phone'],
                'recipientName' => $r['recipient_name'],
                'type' => $r['type'],
                'channel' => $r['channel'],
                'message' => $r['message'],
                'status' => $r['status'],
                'beneficiaryId' => $r['beneficiary_id'] ?? null,
                'timestamp' => $r['timestamp'],
            );
        }, $rows ?: array());
    }

    public static function log_notification($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_notifications';

        $id = 'notif_' . wp_generate_uuid4();
        $record = array(
            'id' => $id,
            'recipient_phone' => sanitize_text_field($data['recipientPhone']),
            'recipient_name' => sanitize_text_field($data['recipientName']),
            'type' => sanitize_text_field($data['type'] ?? 'custom'),
            'channel' => sanitize_text_field($data['channel'] ?? 'whatsapp'),
            'message' => sanitize_textarea_field($data['message']),
            'status' => sanitize_text_field($data['status'] ?? 'sent'),
            'beneficiary_id' => sanitize_text_field($data['beneficiaryId'] ?? ''),
            'timestamp' => current_time('mysql'),
        );

        $wpdb->insert($table, $record);
        return $record;
    }

    // ========================================================
    // PAINEL DE ESTATÍSTICAS GERAIS (DASHBOARD STATS)
    // ========================================================
    public static function get_stats_summary() {
        global $wpdb;
        $beneficiaries = self::search_beneficiaries();
        $classes = self::get_classes();
        $certificates = self::get_certificates();
        $donations = self::get_donations();
        $campaigns = self::get_campaigns();
        $inventory = self::get_inventory_summary();

        $total_beneficiaries = count($beneficiaries);
        $total_minors = 0;
        $total_cad_unico = 0;
        $active_beneficiaries = 0;
        $critical_vuln = 0;
        $high_vuln = 0;
        $vuln_counts = array('critica' => 0, 'alta' => 0, 'media' => 0, 'baixa' => 0);
        $neighborhoods = array();

        foreach ($beneficiaries as $b) {
            if ($b['age'] < 18) $total_minors++;
            if (!empty($b['cadUnico'])) $total_cad_unico++;
            if (($b['status'] ?? '') === 'ativo') $active_beneficiaries++;

            $lvl = $b['vulnerabilityLevel'] ?? 'baixa';
            if ($lvl === 'critica') $critical_vuln++;
            if ($lvl === 'alta') $high_vuln++;
            if (isset($vuln_counts[$lvl])) {
                $vuln_counts[$lvl]++;
            }

            $nh = $b['address']['neighborhood'] ?? 'Centro';
            $neighborhoods[$nh] = ($neighborhoods[$nh] ?? 0) + 1;
        }

        $table_courses = $wpdb->prefix . 'pjb_courses';
        $table_classes = $wpdb->prefix . 'pjb_classes';
        $table_certs = $wpdb->prefix . 'pjb_certificates';

        $total_courses = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_courses");
        $active_classes = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_classes WHERE status = 'ativa'");
        $total_certs = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_certs");

        // Alunos únicos matriculados em turmas ativas
        $enrolled_ids = array();
        foreach ($classes as $c) {
            if (($c['status'] ?? '') === 'ativa') {
                foreach ($c['enrolledStudentIds'] as $sid) {
                    $enrolled_ids[$sid] = true;
                }
            }
        }
        $active_students_count = count($enrolled_ids);

        // Alunos que já possuem certificado
        $certified_students = array();
        foreach ($certificates as $cert) {
            $certified_students[$cert['studentId']] = true;
        }

        return array(
            'totalBeneficiaries' => $total_beneficiaries,
            'activeBeneficiaries' => $active_beneficiaries,
            'criticalVulnerability' => $critical_vuln,
            'highVulnerability' => $high_vuln,
            'totalCourses' => $total_courses,
            'activeClasses' => $active_classes,
            'totalCertificates' => $total_certs,
            'averageAttendanceRate' => 0,
            'inventory' => $inventory,
            'activeCampaignsCount' => count(array_filter($campaigns, function($c) { return ($c['status'] ?? '') === 'em_andamento'; })),
            'neighborhoodsCount' => $neighborhoods,
            // Retrocompatibilidade
            'totalMinors' => $total_minors,
            'totalCadUnico' => $total_cad_unico,
            'activeStudentsCount' => $active_students_count,
            'certifiedStudentsCount' => count($certified_students),
            'overallAttendanceRate' => 0,
            'foodBasketsDelivered' => $inventory['totalSaidas'] ?? 0,
            'vulnerabilityCount' => $vuln_counts,
        );
    }

    // ========================================================
    // BACKUP & MIGRAÇÃO DE DADOS
    // ========================================================
    public static function export_backup() {
        global $wpdb;

        $beneficiaries = self::get_beneficiaries();
        $courses = self::get_courses();
        $classes = self::get_classes();

        $table_att = $wpdb->prefix . 'pjb_attendances';
        $rows_att = $wpdb->get_results("SELECT * FROM $table_att ORDER BY date ASC", ARRAY_A);
        $attendances = array_map(function ($r) {
            return array(
                'id' => $r['id'],
                'classId' => $r['class_id'],
                'date' => $r['date'],
                'topic' => $r['topic'],
                'records' => json_decode($r['records'] ?? '[]', true) ?: array(),
            );
        }, $rows_att ?: array());

        $certificates = self::get_certificates();
        $campaigns = self::get_campaigns();
        $donations = self::get_donations();
        $notifications = self::get_notifications();
        $settings = get_option('pjb_settings', array());

        $currentUser = wp_get_current_user();
        $userName = $currentUser && $currentUser->user_login ? $currentUser->user_login : 'admin';

        update_option('pjb_last_backup_date', current_time('mysql'));

        return array(
            'system' => 'Projeto Brasil dos Jovens (PJB)',
            'format' => 'pjb-database-backup',
            'version' => PJB_VERSION,
            'exportDate' => gmdate('c'),
            'exportedBy' => $userName,
            'environment' => 'wordpress',
            'stats' => array(
                'beneficiaries' => count($beneficiaries),
                'courses' => count($courses),
                'classes' => count($classes),
                'attendances' => count($attendances),
                'certificates' => count($certificates),
                'campaigns' => count($campaigns),
                'donations' => count($donations),
                'notifications' => count($notifications),
            ),
            'data' => array(
                'beneficiaries' => $beneficiaries,
                'courses' => $courses,
                'classes' => $classes,
                'attendances' => $attendances,
                'certificates' => $certificates,
                'campaigns' => $campaigns,
                'donations' => $donations,
                'notifications' => $notifications,
                'settings' => $settings,
            ),
        );
    }

    public static function import_backup($payload, $mode = 'merge') {
        global $wpdb;

        if (is_string($payload)) {
            $payload = json_decode($payload, true);
        }

        if (!is_array($payload)) {
            return array('success' => false, 'error' => 'Formato JSON inválido para restauração de backup.');
        }

        $dataset = isset($payload['data']) && is_array($payload['data']) ? $payload['data'] : $payload;

        $table_ben = $wpdb->prefix . 'pjb_beneficiaries';
        $table_crs = $wpdb->prefix . 'pjb_courses';
        $table_cls = $wpdb->prefix . 'pjb_classes';
        $table_att = $wpdb->prefix . 'pjb_attendances';
        $table_cert = $wpdb->prefix . 'pjb_certificates';
        $table_cmp = $wpdb->prefix . 'pjb_campaigns';
        $table_don = $wpdb->prefix . 'pjb_donations';
        $table_not = $wpdb->prefix . 'pjb_notifications';

        if ($mode === 'replace') {
            $wpdb->query("DELETE FROM $table_ben");
            $wpdb->query("DELETE FROM $table_crs");
            $wpdb->query("DELETE FROM $table_cls");
            $wpdb->query("DELETE FROM $table_att");
            $wpdb->query("DELETE FROM $table_cert");
            $wpdb->query("DELETE FROM $table_cmp");
            $wpdb->query("DELETE FROM $table_don");
            $wpdb->query("DELETE FROM $table_not");
        }

        $imported = array(
            'beneficiaries' => 0,
            'courses' => 0,
            'classes' => 0,
            'attendances' => 0,
            'certificates' => 0,
            'campaigns' => 0,
            'donations' => 0,
            'notifications' => 0,
        );

        // 1. Beneficiários
        if (!empty($dataset['beneficiaries']) && is_array($dataset['beneficiaries'])) {
            foreach ($dataset['beneficiaries'] as $b) {
                if (empty($b['name']) || empty($b['cpf'])) continue;
                $id = !empty($b['id']) ? $b['id'] : 'ben_' . wp_generate_uuid4();
                $addr = $b['address'] ?? array();

                $row = array(
                    'id' => $id,
                    'name' => sanitize_text_field($b['name']),
                    'cpf' => sanitize_text_field($b['cpf']),
                    'rg' => sanitize_text_field($b['rg'] ?? ''),
                    'birth_date' => sanitize_text_field($b['birthDate'] ?? ($b['birth_date'] ?? date('Y-m-d'))),
                    'age' => (int) ($b['age'] ?? 0),
                    'gender' => sanitize_text_field($b['gender'] ?? 'Não informado'),
                    'phone' => sanitize_text_field($b['phone'] ?? ''),
                    'email' => sanitize_email($b['email'] ?? ''),
                    'street' => sanitize_text_field($addr['street'] ?? ($b['street'] ?? 'Não informado')),
                    'number' => sanitize_text_field($addr['number'] ?? ($b['number'] ?? 'S/N')),
                    'neighborhood' => sanitize_text_field($addr['neighborhood'] ?? ($b['neighborhood'] ?? 'Centro')),
                    'city' => sanitize_text_field($addr['city'] ?? ($b['city'] ?? 'Castanhal')),
                    'state' => sanitize_text_field($addr['state'] ?? ($b['state'] ?? 'PA')),
                    'cep' => sanitize_text_field($addr['cep'] ?? ($b['cep'] ?? '68740-000')),
                    'guardian_name' => sanitize_text_field($b['guardianName'] ?? ($b['guardian_name'] ?? '')),
                    'guardian_phone' => sanitize_text_field($b['guardianPhone'] ?? ($b['guardian_phone'] ?? '')),
                    'nis' => sanitize_text_field($b['nis'] ?? ''),
                    'cad_unico' => !empty($b['cadUnico']) || !empty($b['cad_unico']) ? 1 : 0,
                    'family_income' => (float) ($b['familyIncome'] ?? ($b['family_income'] ?? 0)),
                    'family_members_count' => (int) ($b['familyMembersCount'] ?? ($b['family_members_count'] ?? 1)),
                    'per_capita_income' => (float) ($b['perCapitaIncome'] ?? ($b['per_capita_income'] ?? 0)),
                    'schooling_status' => sanitize_text_field($b['schoolingStatus'] ?? ($b['schooling_status'] ?? 'Cursando')),
                    'public_school_name' => sanitize_text_field($b['publicSchoolName'] ?? ($b['public_school_name'] ?? '')),
                    'vulnerability_level' => sanitize_text_field($b['vulnerabilityLevel'] ?? ($b['vulnerability_level'] ?? 'media')),
                    'notes' => sanitize_textarea_field($b['notes'] ?? ''),
                    'registration_date' => sanitize_text_field($b['registrationDate'] ?? ($b['registration_date'] ?? date('Y-m-d'))),
                    'status' => sanitize_text_field($b['status'] ?? 'ativo'),
                );

                $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_ben WHERE id = %s OR cpf = %s", $id, $row['cpf']));
                if ($exists) {
                    $wpdb->update($table_ben, $row, array('id' => $exists));
                } else {
                    $wpdb->insert($table_ben, $row);
                }
                $imported['beneficiaries']++;
            }
        }

        // 2. Cursos
        if (!empty($dataset['courses']) && is_array($dataset['courses'])) {
            foreach ($dataset['courses'] as $c) {
                if (empty($c['name'])) continue;
                $id = !empty($c['id']) ? $c['id'] : 'crs_' . wp_generate_uuid4();
                $row = array(
                    'id' => $id,
                    'name' => sanitize_text_field($c['name']),
                    'description' => sanitize_textarea_field($c['description'] ?? ''),
                    'category' => sanitize_text_field($c['category'] ?? 'Qualificação'),
                    'workload_hours' => (int) ($c['workloadHours'] ?? ($c['workload_hours'] ?? 40)),
                    'target_age_min' => (int) ($c['targetAgeMin'] ?? ($c['target_age_min'] ?? 14)),
                    'target_age_max' => (int) ($c['targetAgeMax'] ?? ($c['target_age_max'] ?? 29)),
                    'total_vacancies' => (int) ($c['totalVacancies'] ?? ($c['total_vacancies'] ?? 30)),
                    'active_vacancies' => (int) ($c['activeVacancies'] ?? ($c['active_vacancies'] ?? 30)),
                    'status' => sanitize_text_field($c['status'] ?? 'aberto'),
                    'instructor_name' => sanitize_text_field($c['instructorName'] ?? ($c['instructor_name'] ?? 'PJB Castanhal')),
                );

                $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_crs WHERE id = %s", $id));
                if ($exists) {
                    $wpdb->update($table_crs, $row, array('id' => $id));
                } else {
                    $wpdb->insert($table_crs, $row);
                }
                $imported['courses']++;
            }
        }

        // 3. Turmas
        if (!empty($dataset['classes']) && is_array($dataset['classes'])) {
            foreach ($dataset['classes'] as $cls) {
                if (empty($cls['courseName']) && empty($cls['course_name'])) continue;
                $id = !empty($cls['id']) ? $cls['id'] : 'cls_' . wp_generate_uuid4();
                $enrolled = isset($cls['enrolledStudentIds'])
                    ? wp_json_encode($cls['enrolledStudentIds'])
                    : (isset($cls['enrolled_students']) ? (is_string($cls['enrolled_students']) ? $cls['enrolled_students'] : wp_json_encode($cls['enrolled_students'])) : '[]');

                $row = array(
                    'id' => $id,
                    'course_id' => sanitize_text_field($cls['courseId'] ?? ($cls['course_id'] ?? '')),
                    'course_name' => sanitize_text_field($cls['courseName'] ?? ($cls['course_name'] ?? '')),
                    'code' => sanitize_text_field($cls['code'] ?? 'TURMA-' . substr($id, -4)),
                    'schedule' => sanitize_text_field($cls['schedule'] ?? 'Turno Regular'),
                    'room' => sanitize_text_field($cls['room'] ?? 'Sala PJB Castanhal'),
                    'start_date' => sanitize_text_field($cls['startDate'] ?? ($cls['start_date'] ?? date('Y-m-d'))),
                    'end_date' => sanitize_text_field($cls['endDate'] ?? ($cls['end_date'] ?? date('Y-m-d', strtotime('+60 days')))),
                    'max_students' => (int) ($cls['maxStudents'] ?? ($cls['max_students'] ?? 25)),
                    'enrolled_students' => is_string($enrolled) ? $enrolled : wp_json_encode($enrolled),
                    'status' => sanitize_text_field($cls['status'] ?? 'ativa'),
                );

                $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_cls WHERE id = %s", $id));
                if ($exists) {
                    $wpdb->update($table_cls, $row, array('id' => $id));
                } else {
                    $wpdb->insert($table_cls, $row);
                }
                $imported['classes']++;
            }
        }

        // 4. Presenças / Diários de Chamada
        if (!empty($dataset['attendances']) && is_array($dataset['attendances'])) {
            foreach ($dataset['attendances'] as $att) {
                $classId = $att['classId'] ?? ($att['class_id'] ?? '');
                $date = $att['date'] ?? date('Y-m-d');
                if (empty($classId)) continue;
                $id = !empty($att['id']) ? $att['id'] : 'att_' . wp_generate_uuid4();
                $records = isset($att['records'])
                    ? (is_string($att['records']) ? $att['records'] : wp_json_encode($att['records']))
                    : '[]';

                $row = array(
                    'id' => $id,
                    'class_id' => sanitize_text_field($classId),
                    'date' => sanitize_text_field($date),
                    'topic' => sanitize_text_field($att['topic'] ?? 'Aula regular'),
                    'records' => $records,
                );

                $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_att WHERE id = %s OR (class_id = %s AND date = %s)", $id, $classId, $date));
                if ($exists) {
                    $wpdb->update($table_att, $row, array('id' => $exists));
                } else {
                    $wpdb->insert($table_att, $row);
                }
                $imported['attendances']++;
            }
        }

        // 5. Certificados Oficiais
        if (!empty($dataset['certificates']) && is_array($dataset['certificates'])) {
            foreach ($dataset['certificates'] as $cert) {
                $code = $cert['code'] ?? '';
                if (empty($code)) continue;
                $id = !empty($cert['id']) ? $cert['id'] : 'cert_' . wp_generate_uuid4();

                $row = array(
                    'id' => $id,
                    'code' => sanitize_text_field($code),
                    'student_id' => sanitize_text_field($cert['studentId'] ?? ($cert['student_id'] ?? '')),
                    'student_name' => sanitize_text_field($cert['studentName'] ?? ($cert['student_name'] ?? '')),
                    'student_cpf' => sanitize_text_field($cert['studentCpf'] ?? ($cert['student_cpf'] ?? '')),
                    'course_id' => sanitize_text_field($cert['courseId'] ?? ($cert['course_id'] ?? '')),
                    'course_name' => sanitize_text_field($cert['courseName'] ?? ($cert['course_name'] ?? '')),
                    'workload_hours' => (int) ($cert['workloadHours'] ?? ($cert['workload_hours'] ?? 40)),
                    'issue_date' => sanitize_text_field($cert['issueDate'] ?? ($cert['issue_date'] ?? date('Y-m-d'))),
                    'verification_hash' => sanitize_text_field($cert['verificationHash'] ?? ($cert['verification_hash'] ?? '')),
                    'status' => sanitize_text_field($cert['status'] ?? 'valido'),
                    'qr_code_url' => esc_url_raw($cert['qrCodeUrl'] ?? ($cert['qr_code_url'] ?? '')),
                );

                $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_cert WHERE id = %s OR code = %s", $id, $code));
                if ($exists) {
                    $wpdb->update($table_cert, $row, array('id' => $exists));
                } else {
                    $wpdb->insert($table_cert, $row);
                }
                $imported['certificates']++;
            }
        }

        // 6. Campanhas Sociais
        if (!empty($dataset['campaigns']) && is_array($dataset['campaigns'])) {
            foreach ($dataset['campaigns'] as $cmp) {
                if (empty($cmp['title'])) continue;
                $id = !empty($cmp['id']) ? $cmp['id'] : 'cmp_' . wp_generate_uuid4();

                $row = array(
                    'id' => $id,
                    'title' => sanitize_text_field($cmp['title']),
                    'description' => sanitize_textarea_field($cmp['description'] ?? ''),
                    'category' => sanitize_text_field($cmp['category'] ?? 'Alimentação'),
                    'target_goal' => (float) ($cmp['targetGoal'] ?? ($cmp['target_goal'] ?? 100)),
                    'unit' => sanitize_text_field($cmp['unit'] ?? 'Unidades'),
                    'start_date' => sanitize_text_field($cmp['startDate'] ?? ($cmp['start_date'] ?? date('Y-m-d'))),
                    'end_date' => sanitize_text_field($cmp['endDate'] ?? ($cmp['end_date'] ?? date('Y-m-d', strtotime('+30 days')))),
                    'location' => sanitize_text_field($cmp['location'] ?? 'Sede PJB Castanhal'),
                    'status' => sanitize_text_field($cmp['status'] ?? 'em_andamento'),
                );

                $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_cmp WHERE id = %s", $id));
                if ($exists) {
                    $wpdb->update($table_cmp, $row, array('id' => $id));
                } else {
                    $wpdb->insert($table_cmp, $row);
                }
                $imported['campaigns']++;
            }
        }

        // 7. Doações & Estoque
        if (!empty($dataset['donations']) && is_array($dataset['donations'])) {
            foreach ($dataset['donations'] as $don) {
                if (empty($don['itemDescription']) && empty($don['item_description'])) continue;
                $id = !empty($don['id']) ? $don['id'] : 'don_' . wp_generate_uuid4();

                $row = array(
                    'id' => $id,
                    'campaign_id' => sanitize_text_field($don['campaignId'] ?? ($don['campaign_id'] ?? '')),
                    'type' => sanitize_text_field($don['type'] ?? 'entrada'),
                    'item_description' => sanitize_text_field($don['itemDescription'] ?? ($don['item_description'] ?? '')),
                    'quantity' => (float) ($don['quantity'] ?? 1),
                    'unit' => sanitize_text_field($don['unit'] ?? 'Cestas'),
                    'beneficiary_id' => sanitize_text_field($don['beneficiaryId'] ?? ($don['beneficiary_id'] ?? '')),
                    'beneficiary_name' => sanitize_text_field($don['beneficiaryName'] ?? ($don['beneficiary_name'] ?? '')),
                    'date' => sanitize_text_field($don['date'] ?? date('Y-m-d')),
                    'donor_name' => sanitize_text_field($don['donorName'] ?? ($don['donor_name'] ?? '')),
                    'receipt_number' => sanitize_text_field($don['receiptNumber'] ?? ($don['receipt_number'] ?? 'REC-' . substr($id, -6))),
                    'status' => sanitize_text_field($don['status'] ?? 'concluido'),
                );

                $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_don WHERE id = %s", $id));
                if ($exists) {
                    $wpdb->update($table_don, $row, array('id' => $id));
                } else {
                    $wpdb->insert($table_don, $row);
                }
                $imported['donations']++;
            }
        }

        // 8. Notificações
        if (!empty($dataset['notifications']) && is_array($dataset['notifications'])) {
            foreach ($dataset['notifications'] as $not) {
                if (empty($not['recipientPhone']) && empty($not['recipient_phone'])) continue;
                $id = !empty($not['id']) ? $not['id'] : 'notif_' . wp_generate_uuid4();

                $row = array(
                    'id' => $id,
                    'recipient_phone' => sanitize_text_field($not['recipientPhone'] ?? ($not['recipient_phone'] ?? '')),
                    'recipient_name' => sanitize_text_field($not['recipientName'] ?? ($not['recipient_name'] ?? '')),
                    'type' => sanitize_text_field($not['type'] ?? 'custom'),
                    'channel' => sanitize_text_field($not['channel'] ?? 'whatsapp'),
                    'message' => sanitize_textarea_field($not['message'] ?? ''),
                    'status' => sanitize_text_field($not['status'] ?? 'sent'),
                    'beneficiary_id' => sanitize_text_field($not['beneficiaryId'] ?? ($not['beneficiary_id'] ?? '')),
                    'timestamp' => sanitize_text_field($not['timestamp'] ?? current_time('mysql')),
                );

                $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_not WHERE id = %s", $id));
                if ($exists) {
                    $wpdb->update($table_not, $row, array('id' => $id));
                } else {
                    $wpdb->insert($table_not, $row);
                }
                $imported['notifications']++;
            }
        }

        // 9. Configurações (opcional)
        if (!empty($dataset['settings']) && is_array($dataset['settings'])) {
            update_option('pjb_settings', $dataset['settings']);
        }

        return array(
            'success' => true,
            'mode' => $mode,
            'imported' => $imported,
            'timestamp' => gmdate('c'),
        );
    }

    // ========================================================
    // GESTÃO FINANCEIRA & DOAÇÕES MONETÁRIAS
    // ========================================================
    public static function format_financial_transaction($row) {
        if (!$row) return null;
        $row = (array) $row;
        return array(
            'id' => $row['id'],
            'type' => $row['type'],
            'category' => $row['category'],
            'description' => $row['description'],
            'amount' => (float) $row['amount'],
            'date' => $row['date'],
            'paymentMethod' => $row['payment_method'],
            'donorOrSupplier' => $row['donor_or_supplier'],
            'documentNumber' => $row['document_number'] ?? null,
            'status' => $row['status'] ?? 'confirmado',
            'campaignId' => $row['campaign_id'] ?? null,
            'costCenter' => $row['cost_center'] ?? 'Geral / Manutenção',
            'attachmentUrl' => $row['attachment_url'] ?? null,
            'attachmentName' => $row['attachment_name'] ?? null,
            'receiptNumber' => $row['receipt_number'] ?? null,
            'registeredBy' => $row['registered_by'] ?? 'Administração PJB',
            'notes' => $row['notes'] ?? null,
            'createdAt' => $row['created_at'] ?? null,
            'updatedAt' => $row['updated_at'] ?? null,
        );
    }

    public static function get_financial_transactions($filters = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_financial_transactions';

        $where = array('1=1');
        $params = array();

        if (!empty($filters['type']) && $filters['type'] !== 'todos') {
            $where[] = 'type = %s';
            $params[] = sanitize_text_field($filters['type']);
        }

        if (!empty($filters['status']) && $filters['status'] !== 'todos') {
            $where[] = 'status = %s';
            $params[] = sanitize_text_field($filters['status']);
        }

        if (!empty($filters['paymentMethod']) && $filters['paymentMethod'] !== 'todos') {
            $where[] = 'payment_method = %s';
            $params[] = sanitize_text_field($filters['paymentMethod']);
        }

        if (!empty($filters['search'])) {
            $like = '%' . $wpdb->esc_like(sanitize_text_field($filters['search'])) . '%';
            $where[] = '(description LIKE %s OR donor_or_supplier LIKE %s OR document_number LIKE %s OR receipt_number LIKE %s OR category LIKE %s)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $sql = "SELECT * FROM $table WHERE " . implode(' AND ', $where) . " ORDER BY date DESC, created_at DESC";
        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        $rows = $wpdb->get_results($sql, ARRAY_A);
        return array_map(array(__CLASS__, 'format_financial_transaction'), $rows ?: array());
    }

    public static function get_financial_transaction_by_id($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_financial_transactions';
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %s", $id), ARRAY_A);
        return self::format_financial_transaction($row);
    }

    public static function create_financial_transaction($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_financial_transactions';
        $id = !empty($data['id']) ? $data['id'] : 'fin_' . wp_generate_uuid4();

        // Gera número de recibo sequencial oficial se ausente
        $receipt = !empty($data['receiptNumber']) ? $data['receiptNumber'] : null;
        if (!$receipt) {
            $type = $data['type'] ?? 'receita';
            $prefix = ($type === 'receita') ? 'REC' : 'DESP';
            $year = date('Y');
            $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table") + 1;
            $receipt = sprintf("%s-%s-%04d", $prefix, $year, $count);
        }

        $record = array(
            'id' => $id,
            'type' => sanitize_text_field($data['type'] ?? 'receita'),
            'category' => sanitize_text_field($data['category'] ?? 'Geral'),
            'description' => sanitize_text_field($data['description'] ?? ''),
            'amount' => (float) ($data['amount'] ?? 0),
            'date' => sanitize_text_field($data['date'] ?? date('Y-m-d')),
            'payment_method' => sanitize_text_field($data['paymentMethod'] ?? 'pix'),
            'donor_or_supplier' => sanitize_text_field($data['donorOrSupplier'] ?? 'Anônimo'),
            'document_number' => !empty($data['documentNumber']) ? sanitize_text_field($data['documentNumber']) : null,
            'status' => sanitize_text_field($data['status'] ?? 'confirmado'),
            'campaign_id' => !empty($data['campaignId']) ? sanitize_text_field($data['campaignId']) : null,
            'cost_center' => !empty($data['costCenter']) ? sanitize_text_field($data['costCenter']) : 'Geral / Manutenção',
            'attachment_url' => !empty($data['attachmentUrl']) ? $data['attachmentUrl'] : null,
            'attachment_name' => !empty($data['attachmentName']) ? sanitize_text_field($data['attachmentName']) : null,
            'receipt_number' => $receipt,
            'registered_by' => !empty($data['registeredBy']) ? sanitize_text_field($data['registeredBy']) : 'Administração PJB',
            'notes' => !empty($data['notes']) ? sanitize_textarea_field($data['notes']) : null,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        );

        $inserted = $wpdb->insert($table, $record);
        if (!$inserted) return false;

        return self::get_financial_transaction_by_id($id);
    }

    public static function update_financial_transaction($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_financial_transactions';

        $record = array('updated_at' => current_time('mysql'));
        if (isset($data['type'])) $record['type'] = sanitize_text_field($data['type']);
        if (isset($data['category'])) $record['category'] = sanitize_text_field($data['category']);
        if (isset($data['description'])) $record['description'] = sanitize_text_field($data['description']);
        if (isset($data['amount'])) $record['amount'] = (float) $data['amount'];
        if (isset($data['date'])) $record['date'] = sanitize_text_field($data['date']);
        if (isset($data['paymentMethod'])) $record['payment_method'] = sanitize_text_field($data['paymentMethod']);
        if (isset($data['donorOrSupplier'])) $record['donor_or_supplier'] = sanitize_text_field($data['donorOrSupplier']);
        if (isset($data['documentNumber'])) $record['document_number'] = sanitize_text_field($data['documentNumber']);
        if (isset($data['status'])) $record['status'] = sanitize_text_field($data['status']);
        if (isset($data['campaignId'])) $record['campaign_id'] = sanitize_text_field($data['campaignId']);
        if (isset($data['costCenter'])) $record['cost_center'] = sanitize_text_field($data['costCenter']);
        if (array_key_exists('attachmentUrl', $data)) $record['attachment_url'] = $data['attachmentUrl'];
        if (array_key_exists('attachmentName', $data)) $record['attachment_name'] = sanitize_text_field($data['attachmentName']);
        if (isset($data['registeredBy'])) $record['registered_by'] = sanitize_text_field($data['registeredBy']);
        if (isset($data['notes'])) $record['notes'] = sanitize_textarea_field($data['notes']);

        $wpdb->update($table, $record, array('id' => $id));
        return self::get_financial_transaction_by_id($id);
    }

    public static function update_financial_transaction_status($id, $status) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_financial_transactions';

        $wpdb->update($table, array(
            'status' => sanitize_text_field($status),
            'updated_at' => current_time('mysql'),
        ), array('id' => $id));

        return self::get_financial_transaction_by_id($id);
    }

    public static function delete_financial_transaction($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_financial_transactions';
        return (bool) $wpdb->delete($table, array('id' => $id));
    }

    public static function get_financial_stats() {
        global $wpdb;
        $table = $wpdb->prefix . 'pjb_financial_transactions';

        $totalReceitas = (float) $wpdb->get_var("SELECT COALESCE(SUM(amount), 0) FROM $table WHERE type = 'receita' AND status = 'confirmado'");
        $totalDespesas = (float) $wpdb->get_var("SELECT COALESCE(SUM(amount), 0) FROM $table WHERE type = 'despesa' AND status = 'confirmado'");
        $saldoGeral = $totalReceitas - $totalDespesas;

        $currentMonth = date('Y-m');
        $receitasMes = (float) $wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(amount), 0) FROM $table WHERE type = 'receita' AND status = 'confirmado' AND date LIKE %s", $currentMonth . '%'));
        $despesasMes = (float) $wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(amount), 0) FROM $table WHERE type = 'despesa' AND status = 'confirmado' AND date LIKE %s", $currentMonth . '%'));
        $saldoMes = $receitasMes - $despesasMes;

        $totalPix = (float) $wpdb->get_var("SELECT COALESCE(SUM(amount), 0) FROM $table WHERE payment_method = 'pix' AND status = 'confirmado'");
        $pendentesCount = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'pendente'");
        $pendentesTotal = (float) $wpdb->get_var("SELECT COALESCE(SUM(amount), 0) FROM $table WHERE status = 'pendente'");
        $totalDoadores = (int) $wpdb->get_var("SELECT COUNT(DISTINCT donor_or_supplier) FROM $table WHERE type = 'receita' AND status = 'confirmado'");

        // Breakdown por categorias
        $catRows = $wpdb->get_results("SELECT type, category, COALESCE(SUM(amount), 0) as total FROM $table WHERE status = 'confirmado' GROUP BY type, category", ARRAY_A);
        $porCategoria = array('receita' => array(), 'despesa' => array());
        if ($catRows) {
            foreach ($catRows as $crow) {
                $t = $crow['type'];
                if (isset($porCategoria[$t])) {
                    $porCategoria[$t][$crow['category']] = (float) $crow['total'];
                }
            }
        }

        return array(
            'totalReceitas' => $totalReceitas,
            'totalDespesas' => $totalDespesas,
            'saldoGeral' => $saldoGeral,
            'receitasMes' => $receitasMes,
            'despesasMes' => $despesasMes,
            'saldoMes' => $saldoMes,
            'totalPix' => $totalPix,
            'pendentesCount' => $pendentesCount,
            'pendentesTotal' => $pendentesTotal,
            'totalDoadores' => $totalDoadores,
            'porCategoria' => $porCategoria,
        );
    }

    public static function get_backup_summary() {
        global $wpdb;

        $tables = array(
            'beneficiaries' => $wpdb->prefix . 'pjb_beneficiaries',
            'courses' => $wpdb->prefix . 'pjb_courses',
            'classes' => $wpdb->prefix . 'pjb_classes',
            'attendances' => $wpdb->prefix . 'pjb_attendances',
            'certificates' => $wpdb->prefix . 'pjb_certificates',
            'campaigns' => $wpdb->prefix . 'pjb_campaigns',
            'donations' => $wpdb->prefix . 'pjb_donations',
            'notifications' => $wpdb->prefix . 'pjb_notifications',
            'financial' => $wpdb->prefix . 'pjb_financial_transactions',
        );

        $counts = array();
        $totalRecords = 0;
        foreach ($tables as $key => $table) {
            $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");
            $counts[$key] = $count;
            $totalRecords += $count;
        }

        return array(
            'totalRecords' => $totalRecords,
            'counts' => $counts,
            'lastBackupDate' => get_option('pjb_last_backup_date', 'Nenhum backup registrado'),
            'databaseVersion' => PJB_VERSION,
        );
    }
}


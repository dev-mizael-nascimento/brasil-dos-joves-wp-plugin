<?php
if (!defined('ABSPATH')) {
    exit;
}

class PJB_AI_Service {
    private static function get_api_key() {
        if (defined('GEMINI_API_KEY') && !empty(GEMINI_API_KEY)) {
            return GEMINI_API_KEY;
        }
        return get_option('pjb_gemini_api_key', '');
    }

    private static function get_model() {
        return get_option('pjb_gemini_model', 'gemini-2.5-flash');
    }

    private static function call_gemini($prompt) {
        $api_key = self::get_api_key();
        if (empty($api_key)) {
            return null;
        }

        $model = self::get_model();
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . urlencode($model) . ':generateContent?key=' . urlencode($api_key);
        $body = wp_json_encode(array(
            'contents' => array(
                array(
                    'parts' => array(
                        array('text' => $prompt)
                    )
                )
            )
        ));

        $response = wp_remote_post($url, array(
            'headers' => array('Content-Type' => 'application/json'),
            'body'    => $body,
            'timeout' => 20,
        ));

        if (is_wp_error($response)) {
            return null;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            return null;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
    }

    public static function generate_social_assessment($beneficiary, $classes = array(), $donations = array()) {
        $classes_str = implode(', ', array_map(function ($c) { return $c['courseName']; }, $classes)) ?: 'Nenhum';
        $donations_str = implode(', ', array_map(function ($d) { return $d['itemDescription']; }, $donations)) ?: 'Nenhum';

        $prompt = "Você é o Assistente Social do Projeto Brasil dos Jovens (PJB) em Castanhal, Pará.
Elabore um parecer social institucional para o jovem abaixo:
- Nome: {$beneficiary['name']}
- Idade: {$beneficiary['age']} anos
- CPF: {$beneficiary['cpf']}
- Bairro: {$beneficiary['address']['neighborhood']}, Castanhal/PA
- Renda Familiar Declarada: R$ " . number_format($beneficiary['familyIncome'], 2, ',', '.') . " (Membros: {$beneficiary['familyMembersCount']})
- CadÚnico: " . ($beneficiary['cadUnico'] ? 'Sim' : 'Não informado') . "
- Escolaridade: {$beneficiary['schoolingStatus']}
- Cursos no PJB: $classes_str
- Apoios recebidos: $donations_str

Estruture um parecer técnico com diagnóstico sociofamiliar, situação educacional e recomendações da coordenação social.";

        $ai_text = self::call_gemini($prompt);
        if ($ai_text) {
            return array(
                'assessment' => $ai_text,
                'priorityScore' => $beneficiary['vulnerabilityLevel'] === 'critica' ? 95 : ($beneficiary['vulnerabilityLevel'] === 'alta' ? 80 : 50),
                'recommendedActions' => array(
                    'Inclusão prioritária no programa de cestas de alimentos',
                    'Acompanhamento e frequência na capacitação técnica do PJB',
                    'Orientação familiar junto ao CRAS de referência do bairro em Castanhal',
                ),
                'isAiGenerated' => true,
            );
        }

        // Parecer institucional em caso de ausência da chave da IA
        $assessment = "PARECER SOCIAL INSTITUCIONAL - PROJETO BRASIL DOS JOVENS\n" .
            "Castanhal - PA | Data: " . date('d/m/Y') . "\n\n" .
            "1. IDENTIFICAÇÃO E DIAGNÓSTICO\n" .
            "Beneficiário: {$beneficiary['name']} (CPF: {$beneficiary['cpf']})\n" .
            "Residência: Bairro {$beneficiary['address']['neighborhood']}, Castanhal/PA.\n" .
            "Composição familiar: {$beneficiary['familyMembersCount']} pessoas.\n" .
            "Renda familiar per capita estimada: R$ " . number_format($beneficiary['perCapitaIncome'], 2, ',', '.') . ".\n\n" .
            "2. SITUAÇÃO ESCOLAR E PARTICIPAÇÃO NO PJB\n" .
            "Estudante na condição de {$beneficiary['schoolingStatus']} ({$beneficiary['publicSchoolName']}).\n" .
            "Participação nas ações socioeducativas promovidas no polo Castanhal.\n\n" .
            "3. RECOMENDAÇÕES TÉCNICAS\n" .
            "- Atendimento prioritário para apoio alimentar e material didático;\n" .
            "- Acompanhamento pedagógico para assiduidade nos cursos do PJB;\n" .
            "- Apoio e orientação institucional para acesso aos direitos socioassistenciais.";

        return array(
            'assessment' => $assessment,
            'priorityScore' => $beneficiary['vulnerabilityLevel'] === 'critica' ? 90 : 70,
            'recommendedActions' => array(
                'Apoio assistencial com cesta básica no ciclo vigente',
                'Incentivo à assiduidade e conclusão no curso de formação',
                'Acompanhamento do núcleo familiar pelo polo Castanhal',
            ),
            'isAiGenerated' => false,
        );
    }

    public static function generate_grant_report($metrics, $campaigns = array(), $donations = array()) {
        $prompt = "Você é o Diretor de Relações Institucionais do Projeto Brasil dos Jovens (PJB) em Castanhal, Pará.
Elabore um relatório executivo de impacto social com as métricas:
- Total de jovens atendidos: {$metrics['totalBeneficiaries']}
- Menores de idade em vulnerabilidade: {$metrics['totalMinors']}
- Alunos qualificados/concluintes: {$metrics['certifiedStudentsCount']}
- Cestas e mantimentos distribuídos: {$metrics['foodBasketsDelivered']}
Redija um relatório formal para apresentação a patrocinadores e editais.";

        $ai_text = self::call_gemini($prompt);
        if ($ai_text) {
            return array('report' => $ai_text, 'isAiGenerated' => true);
        }

        $report = "RELATÓRIO EXECUTIVO DE IMPACTO SOCIAL • PROJETO BRASIL DOS JOVENS (PJB)\n" .
            "Polo Regional: Castanhal / Pará | Emissão: " . date('d/m/Y') . "\n\n" .
            "1. RESUMO EXECUTIVO\n" .
            "O Projeto Brasil dos Jovens atua diretamente no enfrentamento à vulnerabilidade social e na capacitação profissional de adolescentes e jovens em Castanhal/PA.\n\n" .
            "2. INDICADORES CONSOLIDADOS\n" .
            "- Jovens e famílias cadastradas na base: {$metrics['totalBeneficiaries']}\n" .
            "- Menores de 18 anos assistidos: {$metrics['totalMinors']}\n" .
            "- Alunos certificados em cursos profissionalizantes: {$metrics['certifiedStudentsCount']}\n" .
            "- Taxa média de assiduidade pedagógica: {$metrics['overallAttendanceRate']}%\n\n" .
            "3. IMPACTO COMUNITÁRIO\n" .
            "Atuação descentralizada com presença nos bairros periféricos de Castanhal (Jaderlândia, Ianetama, Apeú, Bom Jesus e adjacências), promovendo inclusão produtiva e cidadania.";

        return array('report' => $report, 'isAiGenerated' => false);
    }

    public static function generate_grant_proposal($params) {
        $title = $params['projectTitle'] ?? 'Capacitação e Futuro Jovem';
        $amount = number_format((float) ($params['requestedAmount'] ?? 120000), 2, ',', '.');
        $students = $params['targetStudents'] ?? 120;
        $months = $params['durationMonths'] ?? 12;

        $prompt = "Elabore uma proposta técnica e plano de trabalho para edital de fomento social da ONG Projeto Brasil dos Jovens (PJB) em Castanhal/PA.
- Título do Projeto: $title
- Valor Solicitado: R$ $amount
- Duração: $months meses
- Meta de Atendidos: $students jovens
Apresente: Justificativa, Objetivos, Metodologia, Cronograma e Orçamento Sintético.";

        $ai_text = self::call_gemini($prompt);
        if ($ai_text) {
            return array('proposal' => $ai_text, 'isAiGenerated' => true);
        }

        $proposal = "PROPOSTA DE PROJETO SOCIAL • EDITAL DE FOMENTO\n" .
            "Projeto: $title\n" .
            "Proponente: Projeto Brasil dos Jovens (PJB) - Castanhal/PA\n" .
            "Público-alvo: $students jovens em situação de vulnerabilidade social\n" .
            "Duração: $months meses | Valor total solicitado: R$ $amount\n\n" .
            "1. JUSTIFICATIVA E CONTEXTO LOCAL\n" .
            "O município de Castanhal, polo do nordeste paraense, concentra alta demanda por qualificação profissional para inserção de jovens no primeiro emprego formal. O PJB atua como ponte transformadora entre a periferia e as oportunidades de mercado.\n\n" .
            "2. OBJETIVOS ESPECÍFICOS\n" .
            "- Qualificar profissionalmente $students jovens em módulos de informática, administração e atendimento;\n" .
            "- Fornecer apoio socioassistencial (material didático, uniforme e acompanhamento familiar);\n" .
            "- Encaminhar no mínimo 40% dos formandos para vagas de jovem aprendiz ou contratação no comércio e serviços.\n\n" .
            "3. METODOLOGIA E ATIVIDADES\n" .
            "Aulas práticas com 75% de frequência obrigatória, mentoria de carreira e suporte social contínuo.";

        return array('proposal' => $proposal, 'isAiGenerated' => false);
    }
}

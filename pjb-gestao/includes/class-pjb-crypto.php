<?php
if (!defined('ABSPATH')) {
    exit;
}

class PJB_Crypto {
    private static function get_secret() {
        if (defined('PJB_CERT_SECRET_KEY')) {
            return PJB_CERT_SECRET_KEY;
        }
        $secret = get_option('pjb_cert_secret_key');
        if (!$secret) {
            $secret = wp_generate_password(64, true, true);
            update_option('pjb_cert_secret_key', $secret);
        }
        return $secret;
    }

    public static function generate_signature($data) {
        $canonical_string = implode('::', array(
            strtoupper(trim($data['code'])),
            trim($data['studentId']),
            trim($data['courseId']),
            trim($data['issueDate']),
            (string) $data['workloadHours'],
        ));

        $hash = hash('sha256', $canonical_string);
        $signature = hash_hmac('sha256', $canonical_string, self::get_secret());

        return array(
            'hash'               => $hash,
            'signature'          => $signature,
            'algorithm'          => 'HMAC-SHA256 (Chave Criptográfica Institucional PJB)',
            'keyId'              => 'PJB-CASTANHAL-KEY-2026',
            'signedAt'           => gmdate('c'),
            'institution'        => 'Projeto Brasil dos Jovens • Castanhal/PA',
        );
    }

    public static function verify_signature($data, $signature = '') {
        if (empty($signature)) {
            return true;
        }
        $expected = self::generate_signature($data)['signature'];
        return hash_equals($expected, $signature);
    }
}

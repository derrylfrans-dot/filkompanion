<?php
// Simulasi login UB
class UBAuthService
{
    private array $domainUB;

    public function __construct()
    {
        $cfg = require __DIR__ . '/../../config/config.php';
        $this->domainUB = $cfg['app']['domainUB'];
    }

    // Autentikasi email UB
    public function autentikasiAkunUB(string $emailUB, array $kredensial): array
    {
        $emailUB = strtolower(trim($emailUB));
        if (!filter_var($emailUB, FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'pesan' => 'Format email UB tidak valid.'];
        }
        $domain = substr(strrchr($emailUB, '@'), 1);
        if (!in_array($domain, $this->domainUB, true)) {
            return ['valid' => false, 'pesan' => 'Hanya akun email UB (@ub.ac.id atau @student.ub.ac.id) yang dapat digunakan.'];
        }
        if (($kredensial['metode'] ?? '') !== 'google-workspace-ub' || empty($kredensial['token'])) {
            return ['valid' => false, 'pesan' => 'Kredensial autentikasi UB tidak valid.'];
        }
        return ['valid' => true, 'emailUB' => $emailUB];
    }
}

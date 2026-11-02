<?php
// Sesi dan CSRF
class Auth
{
    public static function mulaiSesi(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('FILKOMPANIONSESSID');
            session_start([
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
                'use_strict_mode' => true,
            ]);
        }
    }

    // Simpan sesi login
    public static function masuk(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['pengguna'] = [
            'userId'       => $user['userId'],
            'nama'         => $user['nama'],
            'emailUB'      => $user['emailUB'],
            'rolePengguna' => $user['rolePengguna'],
        ];
    }

    public static function keluar(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function pengguna(): ?array
    {
        return $_SESSION['pengguna'] ?? null;
    }

    public static function tokenCsrf(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    public static function cekCsrf(?string $token): bool
    {
        return is_string($token) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
    }
}

<?php
// Koneksi PDO
class Database
{
    private static ?PDO $koneksi = null;

    public static function koneksi(): PDO
    {
        if (self::$koneksi === null) {
            $cfg = require __DIR__ . '/../../config/config.php';
            $db  = $cfg['db'];
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $db['host'],
                $db['port'],
                $db['name'],
                $db['charset']
            );
            self::$koneksi = new PDO($dsn, $db['user'], $db['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            self::$koneksi->exec("SET time_zone = '+07:00'");
        }
        return self::$koneksi;
    }
}

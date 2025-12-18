<?php
// app/config/database.php

class DatabaseConfig {
    const HOST = 'localhost';
    const USERNAME = 'root';
    const PASSWORD = '';
    const DATABASE = 'bt_log_transportes';
    const CHARSET = 'utf8mb4';
    
    public static function getDSN() {
        return "mysql:host=" . self::HOST . ";dbname=" . self::DATABASE . ";charset=" . self::CHARSET;
    }
}
?>
#!/usr/bin/env php
<?php

$host = getenv('DB_HOST') ?: 'mysql';
$port = getenv('DB_PORT') ?: '3306';
$user = getenv('DB_USERNAME') ?: 'root';
$pass = getenv('DB_PASSWORD') ?: '';
$maxAttempts = (int) (getenv('DB_WAIT_ATTEMPTS') ?: 60);
$sleepSeconds = (int) (getenv('DB_WAIT_SLEEP') ?: 2);

for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
    try {
        new PDO("mysql:host={$host};port={$port}", $user, $pass, [
            PDO::ATTR_TIMEOUT => 3,
        ]);
        fwrite(STDOUT, "Database connection established.\n");
        exit(0);
    } catch (Throwable $e) {
        fwrite(STDOUT, "Waiting for database ({$attempt}/{$maxAttempts})...\n");
        sleep($sleepSeconds);
    }
}

fwrite(STDERR, "Database not reachable after {$maxAttempts} attempts.\n");
exit(1);

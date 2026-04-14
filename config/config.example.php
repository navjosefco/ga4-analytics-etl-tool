<?php
/**
 * Global Configuration & Connection Factory
 */

define('GA4_PROPERTY_ID', '123456789'); 
define('JSON_KEY_FILE', __DIR__ . '/../tu-archivo-de-llaves.json');

define('DB_HOST', 'localhost');
define('DB_NAME', 'analytics_db');
define('DB_USER', 'root');
define('DB_PASS', '');

require_once __DIR__ . '/../vendor/autoload.php';

use Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;

function getGaClient() {
    return new BetaAnalyticsDataClient(['credentials' => JSON_KEY_FILE]);
}

function getPdo() {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    return new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
}

/**
 * Helper para paginación masiva de la API de GA4
 */
function fetchAllRows($client, $request) {
    $allRows = [];
    $limit = 50000;
    $offset = 0;
    $request->setLimit($limit);

    while (true) {
        $request->setOffset($offset);
        $response = $client->runReport($request);
        $rows = $response->getRows();
        if (count($rows) === 0) break;
        foreach ($rows as $row) { $allRows[] = $row; }
        $offset += $limit;
        if ($offset >= $response->getRowCount()) break;
    }
    return $allRows;
}

function gaDateToSql($gaDate) {
    return substr($gaDate, 0, 4) . '-' . substr($gaDate, 4, 2) . '-' . substr($gaDate, 6, 2);
}

function getDateRange($argv) {
    if (isset($argv[1]) && $argv[1] === 'historico') {
        return ['start' => '2024-01-01', 'end' => 'yesterday', 'modo' => 'HISTORICO'];
    }
    return ['start' => '2daysAgo', 'end' => 'yesterday', 'modo' => 'DIARIO'];
}
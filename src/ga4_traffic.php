<?php
/**
 * GA4 Traffic ETL - Extracción optimizada de tráfico web
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once __DIR__ . '/helpers.php';

use Google\Analytics\Data\V1beta\RunReportRequest;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\Dimension;

[$start, $end, $modo] = array_values(getDateRange($argv));
$pdo = getPdo();
$client = getGaClient();

$request = (new RunReportRequest())
    ->setProperty('properties/' . GA4_PROPERTY_ID)
    ->setDateRanges([new DateRange(['start_date' => $start, 'end_date' => $end])])
    ->setDimensions([
        new Dimension(['name' => 'date']),
        new Dimension(['name' => 'sessionDefaultChannelGroup']),
        new Dimension(['name' => 'deviceCategory']),
        new Dimension(['name' => 'pagePath']),
    ])
    ->setMetrics([
        new Metric(['name' => 'sessions']),
        new Metric(['name' => 'screenPageViews']),
        new Metric(['name' => 'transactions']),
        new Metric(['name' => 'totalRevenue']),
    ]);

$rows = fetchAllRows($client, $request);

$sql = "INSERT INTO ga4_traffic (date, channel, device, resort_id, sessions, pageviews, transactions, revenue)
        VALUES (:date, :channel, :device, :resort, :sessions, :pvs, :trans, :rev)
        ON DUPLICATE KEY UPDATE sessions = VALUES(sessions), revenue = VALUES(revenue)";

$stmt = $pdo->prepare($sql);
$pdo->beginTransaction();

foreach ($rows as $row) {
    $d = $row->getDimensionValues();
    $m = $row->getMetricValues();

    $stmt->execute([
        ':date'     => gaDateToSql($d[0]->getValue()),
        ':channel'  => $d[1]->getValue(),
        ':device'   => $d[2]->getValue(),
        ':resort'   => resortIdFromUrl($d[3]->getValue()),
        ':sessions' => (int)$m[0]->getValue(),
        ':pvs'      => (int)$m[1]->getValue(),
        ':trans'    => (int)$m[2]->getValue(),
        ':rev'      => (float)$m[3]->getValue(),
    ]);
}
$pdo->commit();
echo "ETL Tráfico: Sincronización exitosa.\n";
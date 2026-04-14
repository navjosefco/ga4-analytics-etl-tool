<?php
/**
 * GA4 Marketing ETL - Atribución de campañas Google Ads
 */

require_once __DIR__ . '/../config/config.php';

use Google\Analytics\Data\V1beta\RunReportRequest;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\Dimension;

$range = getDateRange($argv);
$client = getGaClient();
$pdo = getPdo();

$request = (new RunReportRequest())
    ->setProperty('properties/' . GA4_PROPERTY_ID)
    ->setDateRanges([new DateRange(['start_date' => $range['start'], 'end_date' => $range['end']])])
    ->setDimensions([
        new Dimension(['name' => 'date']),
        new Dimension(['name' => 'sessionGoogleAdsCampaignName']),
    ])
    ->setMetrics([
        new Metric(['name' => 'advertiserAdCost']),
        new Metric(['name' => 'advertiserAdClicks']),
        new Metric(['name' => 'transactions']),
        new Metric(['name' => 'totalRevenue']),
    ]);

$response = $client->runReport($request);

$sql = "INSERT INTO ga4_marketing (date, campaign, cost, clicks, revenue, roas)
        VALUES (:date, :camp, :cost, :clicks, :rev, :roas)
        ON DUPLICATE KEY UPDATE cost = VALUES(cost), revenue = VALUES(revenue)";

$stmt = $pdo->prepare($sql);
$pdo->beginTransaction();

foreach ($response->getRows() as $row) {
    $d = $row->getDimensionValues();
    $m = $row->getMetricValues();
    
    $cost = (float)$m[0]->getValue();
    $rev  = (float)$m[3]->getValue();
    $roas = ($cost > 0) ? ($rev / $cost) : 0;

    $stmt->execute([
        ':date'   => gaDateToSql($d[0]->getValue()),
        ':camp'   => $d[1]->getValue(),
        ':cost'   => $cost,
        ':clicks' => (int)$m[1]->getValue(),
        ':rev'    => $rev,
        ':roas'   => $roas
    ]);
}
$pdo->commit();
echo "ETL Marketing: Sincronización finalizada.\n";
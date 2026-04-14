<?php
/**
 * GA4 Funnel ETL - Conversión por pasos del proceso de reserva
 */

require_once __DIR__ . '/../config/config.php';

use Google\Analytics\Data\V1beta\RunReportRequest;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\Dimension;

$range = getDateRange($argv);
$client = getGaClient();

$request = (new RunReportRequest())
    ->setProperty('properties/' . GA4_PROPERTY_ID)
    ->setDateRanges([new DateRange(['start_date' => $range['start'], 'end_date' => $range['end']])])
    ->setDimensions([
        new Dimension(['name' => 'date']),
        new Dimension(['name' => 'eventName']),
    ])
    ->setMetrics([new Metric(['name' => 'eventCount'])]);

$response = $client->runReport($request);

// Agregación en memoria para construir la fila del funnel
$matrix = [];
foreach ($response->getRows() as $row) {
    $fecha = gaDateToSql($row->getDimensionValues()[0]->getValue());
    $event = $row->getDimensionValues()[1]->getValue();
    $count = (int)$row->getMetricValues()[0]->getValue();

    if (!isset($matrix[$fecha])) {
        $matrix[$fecha] = ['start' => 0, 'view' => 0, 'payment' => 0, 'done' => 0];
    }

    switch($event) {
        case 'session_start':   $matrix[$fecha]['start'] = $count; break;
        case 'view_item':       $matrix[$fecha]['view'] = $count; break;
        case 'begin_checkout':  $matrix[$fecha]['payment'] = $count; break;
        case 'purchase':        $matrix[$fecha]['done'] = $count; break;
    }
}

// Inserción masiva
$pdo = getPdo();
$stmt = $pdo->prepare("INSERT INTO ga4_funnel (date, step_start, step_view, step_payment, step_confirm) 
                       VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE step_confirm = VALUES(step_confirm)");

$pdo->beginTransaction();
foreach ($matrix as $date => $s) {
    $stmt->execute([$date, $s['start'], $s['view'], $s['payment'], $s['done']]);
}
$pdo->commit();
echo "ETL Funnel: Sincronización finalizada.\n";
<?php
/**
 * Business Logic Helpers - Saneamiento de datos
 */

function resortIdFromUrl($url) {
    if (str_contains($url, 'hotel-alfa')) return 'HOTEL_A';
    if (str_contains($url, 'resort-beta')) return 'RESORT_B';
    return 'GLOBAL_BRAND';
}

function resortIdFromCampaign($campaignName) {
    $name = strtoupper($campaignName);
    if (str_contains($name, 'ALFA')) return 'HOTEL_A';
    if (str_contains($name, 'BETA')) return 'RESORT_B';
    return 'GENERIC_AD';
}
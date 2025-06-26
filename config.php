<?php
/**
 * Configuration File for the Facebook CAPI Handler.
 */

// =============================================================================
// Конфигурация Facebook
// =============================================================================
define('FB_PIXEL_ID', '1213877270042268');
define('FB_ACCESS_TOKEN', 'EAAVNPZCdalIoBO5UcTaU9tUJaiEs4sqZCDHLcSQ0PWC10Ld2ynoq11nL6zfGTEq34wmQLHf5rPvxPtIuWs6NU6f4eZCHfm5hEfeK4Iou37gwZBxwD8naLUFx7BWAyTij5OnrNElPxtQDSbFKBXlg0fzoNwmtkZCBtrIe4g3B7GfS7TH9zZAJN48sdP6p095CVYvwZDZD');
define('FB_GRAPH_API_VERSION', 'v23.0');

// =============================================================================
// Сопоставление данных
// =============================================================================

// Карта статусов Keitaro на события Facebook.
define('STATUS_TO_EVENT_MAP', [
    'lead' => 'Lead',
    'sale' => 'Purchase',
]);

/**
 * Карта параметров из URL Keitaro на поля Facebook Advanced Matching.
 * Эти данные будут хешироваться и использоваться для повышения качества сопоставления.
 */
define('KEITARO_TO_FB_ADVANCED_MATCHING_MAP', [
    'city'    => 'ct',
    'region'  => 'st',
    'country_code' => 'country',
    // Если вы будете передавать email, имя или телефон, добавьте их сюда.
    // 'email' => 'em',
    // 'phone' => 'ph',
    // 'fname' => 'fn',
]);

/**
 * Список параметров, которые будут добавлены в 'custom_data'.
 * Это все остальные полезные данные из Keitaro, которые вы хотите видеть в Facebook.
 */
define('KEITARO_CUSTOM_DATA_PARAMS', [
    'source',
    'creative_id',
    'ad_campaign_id',
    'sub_id_1',
    'sub_id_2',
    'sub_id_3',
    'sub_id_4',
    'sub_id_5',
    'cost',
]);
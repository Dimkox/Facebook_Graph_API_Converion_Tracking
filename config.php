<?php
/**
 * Configuration File for the Facebook CAPI Handler.
 * v4.0: Added phone number mapping.
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

define('STATUS_TO_EVENT_MAP', [
    'lead' => 'Lead',
    'sale' => 'Purchase',
]);

/**
 * Карта параметров из URL Keitaro на поля Facebook Advanced Matching.
 * Эти данные будут автоматически хешироваться.
 */
define('KEITARO_TO_FB_ADVANCED_MATCHING_MAP', [
    'city'         => 'ct',      // Город
    'region'       => 'st',      // Штат/Регион
    'country_code' => 'country', // Код страны
    'lang'         => 'lc',      // Язык пользователя
    'sub_id_15'    => 'ph',      // НОВОЕ: Номер телефона (будет хешироваться)
]);

/**
 * Список пользовательских параметров для передачи в 'custom_data'.
 */
define('KEITARO_CUSTOM_DATA_PARAMS', [
    'utm_source', 'utm_campaign', 'creative_id', 'utm_placement',
    'campaign_id', 'adset_id', 'adset_name', 'ad_name', 'cost',
    'sub_id_12', 'sub_id_13', 'sub_id_14', // sub_id_15 теперь используется выше
]);
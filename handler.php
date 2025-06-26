<?php
/**
 * Ultimate Facebook Conversions API Handler for Keitaro
 *
 * Processes S2S postbacks with a full set of parameters from traffic logs
 * for maximum Event Match Quality and detailed custom analytics.
 */

require_once __DIR__ . '/config.php';

/**
 * Class FacebookCapiHandler
 * Encapsulates all logic for Facebook Conversions API.
 */
class FacebookCapiHandler
{
    private $pixelId;
    private $accessToken;
    private $apiVersion;

    public function __construct(string $pixelId, string $accessToken, string $apiVersion)
    {
        $this->pixelId = $pixelId;
        $this->accessToken = $accessToken;
        $this->apiVersion = $apiVersion;
    }

    public function sendEvent(array $params): array
    {
        if (empty($params['fbclid']) || empty($params['status'])) {
            return $this->formatResponse(400, false, 'Required "fbclid" or "status" are missing.');
        }
        $eventName = STATUS_TO_EVENT_MAP[$params['status']] ?? null;
        if (!$eventName) {
            return $this->formatResponse(400, false, "Unsupported status: {$params['status']}.");
        }
        
        $eventData = $this->buildEventPayload($eventName, $params);
        $result = $this->executeApiRequest($eventName, $eventData);

        // Логируем транзакцию
        $this->logTransaction($params, $eventData, $result);

        return $result;
    }

    private function buildEventPayload(string $eventName, array $params): array
    {
        $currentTime = time();
        $userData = ['fbc' => sprintf('fb.1.%d.%s', $currentTime, $params['fbclid'])];
        $customData = [];

        // Шаг 1: Обработка полей для Advanced Matching (с хешированием).
        // Этот цикл автоматически подхватит новый параметр 'lang'.
        foreach (KEITARO_TO_FB_ADVANCED_MATCHING_MAP as $keitaroKey => $fbKey) {
            if (!empty($params[$keitaroKey])) {
                $userData[$fbKey] = hash('sha256', strtolower($params[$keitaroKey]));
            }
        }
        
        if (!empty($params['ip'])) $userData['client_ip_address'] = $params['ip'];
        if (!empty($params['user_agent'])) $userData['client_user_agent'] = $params['user_agent'];

        foreach (KEITARO_CUSTOM_DATA_PARAMS as $key) {
            if (!empty($params[$key])) {
                $customData[$key] = $params[$key];
            }
        }
        
        $eventData = [
            'event_name' => $eventName,
            'event_time' => $currentTime,
            'action_source' => 'website',
            'event_id' => uniqid('event_', true),
            'user_data' => $userData,
        ];
        
        if ($eventName === 'Purchase' && !empty($params['payout'])) {
            $customData['value'] = (float)$params['payout'];
            $customData['currency'] = strtoupper($params['currency'] ?? 'USD');
        }

        if (!empty($customData)) {
            $eventData['custom_data'] = $customData;
        }
        
        return $eventData;
    }

    private function executeApiRequest(string $eventName, array $eventData): array
    {
        $apiUrl = sprintf('https://graph.facebook.com/%s/%s/events', $this->apiVersion, $this->pixelId);
        $payload = http_build_query(['data' => json_encode([$eventData]), 'access_token' => $this->accessToken]);
        
        $ch = curl_init();
        curl_setopt_array($ch, [CURLOPT_URL => $apiUrl, CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload]);
        $responseBody = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $isSuccess = ($httpCode >= 200 && $httpCode < 300);
        $message = $isSuccess ? "Event '{$eventName}' sent." : "Failed to send event '{$eventName}'.";
        
        return $this->formatResponse($isSuccess ? 200 : 502, $isSuccess, $message, $eventData, json_decode($responseBody, true));
    }
    
    private function formatResponse(int $httpCode, bool $success, string $message, ?array $sentData = null, ?array $fbResponse = null): array
    {
        http_response_code($httpCode);
        return ['success' => $success, 'message' => $message, 'sent_data' => $sentData, 'fb_response' => $fbResponse];
    }
    
    private function logTransaction(array $request, array $sentData, array $result): void
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s T'),
            'status' => $result['success'] ? 'SUCCESS' : 'ERROR',
            'incoming_request' => $request,
            'sent_to_facebook' => $sentData,
            'facebook_response' => $result['fb_response'] ?? null,
        ];
        file_put_contents(LOG_FILE, json_encode($logEntry, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}

// =============================================================================
// Точка входа и обработка запроса
// =============================================================================

define('LOG_FILE', __DIR__ . '/capi_log.txt');

try {
    $handler = new FacebookCapiHandler(FB_PIXEL_ID, FB_ACCESS_TOKEN, FB_GRAPH_API_VERSION);
    $result = $handler->sendEvent($_GET);
    header('Content-Type: application/json');
    unset($result['sent_data']);
    echo json_encode($result);
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal Server Error.', 'error' => $e->getMessage()]);
}
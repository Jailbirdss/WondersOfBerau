<?php
header('Content-Type: application/json; charset=utf-8');

error_reporting(0);
ini_set('display_errors', 0);

if (ob_get_level()) {
    ob_end_clean();
}
ob_start();

require_once __DIR__ . '/../config.php';

function get_deepl_api_key()
{
    return env('DEEPL_API_KEY', '');
}

function isTranslatorEnabled()
{
    global $conn;
    $query = mysqli_query($conn, "SELECT setting_value FROM settings WHERE setting_key = 'translator_enabled'");
    if ($query && mysqli_num_rows($query) > 0) {
        $setting = mysqli_fetch_assoc($query);
        return $setting['setting_value'] == '1';
    }
    return true;
}


function translateText($text, $targetLang, $sourceLang = '')
{
    $apiKey = get_deepl_api_key();
    if ($apiKey === '') {
        return array('error' => 'DeepL API key belum dikonfigurasi');
    }

    $url = 'https://api-free.deepl.com/v2/translate';

    $data = array(
        'auth_key' => $apiKey,
        'text' => $text,
        'target_lang' => strtoupper($targetLang)
    );

    if (!empty($sourceLang)) {
        $data['source_lang'] = strtoupper($sourceLang);
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return array('error' => 'cURL Error: ' . $error);
    }

    if ($httpCode !== 200) {
        return array('error' => 'HTTP Error: ' . $httpCode, 'response' => $response);
    }

    $result = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return array('error' => 'JSON Parse Error: ' . json_last_error_msg());
    }

    return $result;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['check_status'])) {
    ob_clean(); // Clear any buffered output
    echo json_encode(array(
        'enabled' => isTranslatorEnabled()
    ));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_clean(); // Clear any buffered output

    if (!isTranslatorEnabled()) {
        echo json_encode(array(
            'success' => false,
            'error' => 'Fitur penerjemah bahasa saat ini dinonaktifkan oleh administrator',
            'disabled' => true
        ));
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['text']) || !isset($input['target_lang'])) {
        echo json_encode(array(
            'success' => false,
            'error' => 'Parameter text dan target_lang wajib diisi'
        ));
        exit;
    }

    $text = $input['text'];
    $targetLang = $input['target_lang'];
    $sourceLang = isset($input['source_lang']) ? $input['source_lang'] : '';

    $langMap = array(
        'id' => 'ID',
        'en' => 'EN',
        'ja' => 'JA',
        'ko' => 'KO',
        'zh' => 'ZH',
        'es' => 'ES',
        'fr' => 'FR',
        'de' => 'DE'
    );

    $targetLang = isset($langMap[$targetLang]) ? $langMap[$targetLang] : strtoupper($targetLang);

    $result = translateText($text, $targetLang, $sourceLang);

    ob_clean(); // Clear before output
    if (isset($result['error'])) {
        echo json_encode(array(
            'success' => false,
            'error' => $result['error'],
            'details' => isset($result['response']) ? $result['response'] : null
        ));
    } else if (isset($result['translations']) && count($result['translations']) > 0) {
        echo json_encode(array(
            'success' => true,
            'translated_text' => $result['translations'][0]['text'],
            'detected_source_language' => isset($result['translations'][0]['detected_source_language'])
                ? $result['translations'][0]['detected_source_language']
                : null
        ));
    } else {
        echo json_encode(array(
            'success' => false,
            'error' => 'Terjemahan gagal, response tidak valid',
            'response' => $result
        ));
    }
    exit;
}

ob_clean();
echo json_encode(array(
    'success' => false,
    'error' => 'Method not allowed. Use POST request.'
));
exit;
?>
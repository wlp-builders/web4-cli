<?php
/**
 * Function to send a POST request with files and custom headers.
 *
 * @param string $url The endpoint URL.
 * @param array $headers The headers to include in the request.
 * @param array $postFields The fields to include in the POST request.
 * @return string The response from the server.
 */
function web4_multi_upload_repo($url, $headers, $postFields) {
    // Initialize cURL
    $ch = curl_init();

    // Set the cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // Execute the request
    $response = curl_exec($ch);

    // Check for errors
    if ($response === false) {
        $error = 'cURL error: ' . curl_error($ch);
        curl_close($ch);
        throw new Exception($error);
    }

    // Close the cURL session
    curl_close($ch);

    return $response;
}

/*
// URL and data
$url = 'http://wlpv3-196.local/';
$headers = [
    'DID: did:web4:wlpv3-196.local#sig'
];
$postFields = [
    'data' => json_encode(['repo__upload' => []]),
    'zip_file' => new CURLFile('/var/www/wlpv3-web4/tests/test.zip'),
    'sig_file' => new CURLFile('/var/www/wlpv3-web4/tests/test.sig.json'),
];

// Execute the function and print the response
try {
    $response = web4_multi_upload_repo($url, $headers, $postFields);
    echo 'Response: ' . $response;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}*/

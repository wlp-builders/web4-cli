<?php
/**
 * Unified Function for Web4 Repo Search Operation
 *
 * Description: This function sends a search request to a repository API and retrieves the response.
 *
 * @param string $repo The repository URL.
 * @param string $keyword The keyword to search in the repository.
 * @param array $headers The headers for the request.
 * @return string The API response or an error message.
 */
function web4_get_nonce($repo, $did, $headers) {
    // Prepare the data to send in the request
    $data = ['web4__get_nonce' => [$did]];

    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $repo);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    // Execute the request
    $response = curl_exec($ch);

    if ($response === false) {
        $error = 'cURL error: ' . curl_error($ch);
        curl_close($ch);
        throw new Exception($error);
    }

    // Close cURL session
    curl_close($ch);

    return json_decode($response,true);
}

/*
// Example usage
$repo = 'http://wlpv3-196.local/';
$keyword = 'plug';
$headers = [
    'DID: did:web4:wlpv3-196.local#sig',
    'Content-Type: application/json'
];

// Execute the function
$result = web4_repo_search($repo, $keyword, $headers);

// Output the result
echo $result;
//*/
?>

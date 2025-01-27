<?php
/**
 * Unified Function for Web4 Repo Download ZIP Operation
 *
 * Description: This single function sends a request to a repository API, retrieves the response, 
 * and writes it to a file in the specified directory.
 *
 * @param string $repo The repository URL.
 * @param array $data The data to send in the request.
 * @param array $headers The headers for the request.
 * @param string $install_path The installation path where files will be saved.
 * @param string $type The type of the directory (e.g., 'plugins' or 'themes').
 * @return string The path to the saved file or an error message.
 */
function web4_repo_download_zip_operation($repo, $data, $headers, $install_path, $type) {
    // Send HTTP POST request using cURL
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $repo);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $response = curl_exec($ch);

    if ($response === false) {
        $error = 'cURL error: ' . curl_error($ch);
        curl_close($ch);
        return $error;
    }

    curl_close($ch);
  

    // Determine the directory based on the type
    $target_dir = rtrim($install_path, '/'); //. "/wp-content/install/{$type}/";

    // Ensure the directory exists
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // Generate a cryptographically secure 8-byte random string (hex) and create the filename
    $random_hex = bin2hex(random_bytes(8));
    $file_name = $target_dir.'/' .basename($type). "_{$random_hex}.zip";

    // Write the response to the file
    file_put_contents($file_name, $response);

    return $file_name;
}

/*
// Example usage
$repo = 'http://wlpv3-196.local/';
$data = [
    'repo__download' => ['Private Document Tracker','wlpv3-196.local']
];
$headers = [
    'DID: did:web4:wlpv3-196.local#sig',
    'Content-Type: application/json'
];
$type = 'plugins';
$install_path = '/var/www/wlp146.local/wp-content/install/'.$type;
$download = 'Private Document Tracker';

// Execute the function
$result = web4_repo_download_zip_operation($repo, $data, $headers, $install_path, $type, $download);

// Output the result
echo time().$result;
//*/
?>

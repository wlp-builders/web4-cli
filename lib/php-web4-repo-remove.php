<?php

function web4_repo_remove($repo, $name, $headers) {
    // Prepare the data to send in the request
    $data = ['repo__remove' => [$name]];

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
	var_dump(['url',$repo]);
	var_dump(['input',$data,$headers]);
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

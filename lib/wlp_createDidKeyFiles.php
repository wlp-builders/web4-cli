<?php

// Function to create PHP files for each base64-encoded secret
function createDidKeyFiles($docReturn, $directory) {
    // Ensure the directory exists
    if (!is_dir($directory)) {
        mkdir($directory, 0777, true); // Create directory if it doesn't exist
    }

    // Function to create individual PHP file with the base64-encoded secret
    function createKeyFile($filename, $keyValue, $directory) {
        $content = "<?php\n" . "define('$filename', '$keyValue');\n";
        file_put_contents($directory . '/' . $filename . '.php', $content);
    }

    // Create PHP files for each base64-encoded secret in the docReturn array
    foreach($docReturn['secrets'] as $key => $value) {
        createKeyFile($key, $docReturn['secrets'][$key], $directory);
    }
}

/*
// test
// Example docReturn array (replace with actual function or data)
$docReturn = [
    'e2e_secret_base64' => base64_encode('your_box_key1_secret_value_here'),
    'sig_secret_base64' => base64_encode('your_sign_key1_secret_value_here'),
    'donate_secret_base64' => base64_encode('your_sign_key2_secret_value_here'),
    'donate_b_secret_base64' => base64_encode('your_sign_key3_secret_value_here'),
];

// Directory to save the key files
$directory = './web4-keys/';

// Call the function to create the PHP files
createKeyFiles($docReturn, $directory);
*/

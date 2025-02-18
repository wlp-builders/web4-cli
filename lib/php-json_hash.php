<?php
// ex input: $data=['key'=>'value'] , $hashType ex. sha3-512
function json_hash($data,$hashType) {
    // Ensure it's a pure associative array (if object, convert to array)
    if (is_object($data)) {
        $data = json_decode(json_encode($data), true);
    }
    
    // Recursively sort the array to maintain a consistent order
    ksort_recursive($data);
    
    // Encode to JSON with consistent options
    $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    
    // ex. Return SHA3-512 hash
    return hash($hashType, $json);
}

// Recursive sorting function
function ksort_recursive(&$array) {
    if (is_array($array)) {
        ksort($array);
        foreach ($array as &$value) {
            if (is_array($value)) {
                ksort_recursive($value);
            }
        }
    }
}

/*
// Example usage
$data = (object)["b" => 22, "a" => 1, "nested" => ["z" => 5, "x" => 3]];
echo json_hash($data,'sha3-512');
//*/
<?php
require_once 'php-getDidParts.php';
require_once 'WLP256Signature2024.php';
require_once 'php-json_hash.php';

function wlp_did_document_retrieve_and_verify($domain, $did_document_url) {
    // Fetch the JSON data
    $jsonData = file_get_contents($did_document_url);
    //var_dump($jsonData);
    if ($jsonData === false) {
        return ["error" => "Error fetching data from $did_document_url"];
    }

    // Decode the JSON into an associative array
    $data = json_decode($jsonData, true);

    if ($data === null) {
        return ["error" => "Error decoding JSON: " . json_last_error_msg()];
    }
  
    
    //var_dump($data);
    return parseAndVerifyDidDocument($data,$domain);
}

// helper function
function parseAndVerifyDidDocument($did_document_data,$domain) { 
  $didWitHashtag = 'did:web4:'.$domain.'#sig';
  log_message(json_encode(['didWitHashtag',$didWitHashtag]));
  $proofSignature = $did_document_data['proof']['signature']['signed'];
  log_message(json_encode(['proofSignature',$proofSignature]));

  // Double check if we use the right supported type
  if($did_document_data['proof']['type'] != "WEB4Signature2025") {
     throw new Exception('Unsupported proof type '.$did_document_data['proof']['type'].', expected WEB4Signature2025');
  }
  

  $verificationMethods = $did_document_data['verificationMethod'];
  foreach($verificationMethods as $v) {
      if($v['id'] === $didWitHashtag) {
        $verificationMethod = $v;

        // Clone the array and remove proof signature and pow
        $clonedArray = $did_document_data;
        unset($clonedArray['proof']);
        unset($clonedArray['pow']);
        
        log_message(json_encode(['clonedArray',$clonedArray]));
        log_message(json_encode(['sizeof clonedArray',strlen(json_encode($clonedArray))]));
        log_message(json_encode(['sizeof did_document_data',strlen(json_encode($did_document_data))]));
        // recompute signed hash according to WEB4Signature2025 specs (simply sha3-512 the  json encoded object without proof)
        $hash = json_hash($clonedArray,'sha3-512');		


        // get public key 
        $publicKey = base64_decode($verificationMethod['publicKeyBase64']);
        
        if($v['type'] === 'Ed25519VerificationKey2018') {
          $valid = wlp256_verify($proofSignature, $publicKey);
          if(false == $valid) {
            return ["valid"=>false,"error" =>'Proof signature not valid'];
          }
          if($valid['input-hash-sha3-512'] != $hash) {
            log_message(json_encode(['2 hashes:',$hash,$valid['input-hash-sha3-512']]));
            return ["valid"=>false,"error" =>'Hash is not valid'];
          }
        } else {
          throw new Error('unsupported verificationMethod type');
        }
        
        return ["valid"=>true,"data"=>$did_document_data];
      } // end if
    } // end for

    // Return the modified data
    return ["valid"=>false];
}

/*
// Example usage
$didWithHashtag = "did:wlp:wlp1.local#sig";
$fullUrl = "http://wlp1.local/.well-known/did.json";
$result = wlp_did_document_retrieve_and_verify($didWithHashtag, $fullUrl);
if (isset($result['error'])) {
    echo $result['error'] . "\n";
} else {
  // good
  var_dump(json_encode($result, JSON_PRETTY_PRINT) . "\n");
}
//*/

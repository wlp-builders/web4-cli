<?php
require_once 'wlp_did.php';
require_once 'wlp256.php';
require_once 'php-encodings.php';
require_once 'php-generateSecp256k1Keypair.php';
require_once 'WLP256Signature2024.php';
require_once 'wlp_anti_spam_pow.php';

function wlp_did_create_document($domain, $fileName) {
  // Generate encryption keys 
  $box_keys1 = wlp_did_generate_box_keys();
  
  // Generate signing keys 
  $sign_keys1 = wlp_did_generate_sign_keys();


  // Generate signing keys for publishing, signing software etc
  $sign_keys_publishing = wlp_did_generate_sign_keys();

  // donate Ed25599 keypair for stellar, solana, ex.
  $sign_keys2 = wlp_did_generate_sign_keys();

  // donate-b Secp256k1 keypair for btc,eth, ex.
  $sign_keys3 = generateSecp256k1Keypair();
  $didDocument = wlp_did_create_document_full($box_keys1['public'], $sign_keys1['public'],$sign_keys_publishing['public'], $sign_keys2['public'],$sign_keys3['public'],$sign_keys1['secret'],$domain, $fileName);

  $sign_keys_publishing;
  
  return [
    "didDocument"=>$didDocument,
    'secrets'=>[
      'e2e_secret_base64'=>rtrim(base64_encode($box_keys1['secret']),'='),
      'sig_secret_base64'=>rtrim(base64_encode($sign_keys1['secret']),'='),
      'sig_publish_secret_base64'=>rtrim(base64_encode($sign_keys_publishing['secret']),'=')
    ]];
}
  
/*
output keys description:
            "sig" => "Used for signing, authentication, and assertion purposes. The 'sig' private key must stay on the server.",
            "e2e" => "Used for receiving encrypted messages. The 'e2e' private key may be removed and stored locally."
*/
// $publicKeyForEncryption base64, $publicKeyForSigning base64, $domain example.com, $fileName = './did.json';
  function wlp_did_create_document_full($publicKeyForEncryption, $publicKeyForSigning,$publicKeyForPublishing,$publicKeyForDonations,$publicKeyForDonationsB,$secretKeyForSigning, $domain, $fileName) {
    // Use the domain as the controller
    $controllerId = "https://" . rtrim($domain, "/");
    $did = 'did:web4:'.$domain;

    // Construct the DID Document
    $didDocument = [
        "@context" => "https://web4.builders/didproto",
        "id" => $did,
        "created" => time(),
        "latestVersion" => $controllerId.'/.well-known/did.json',
        "verificationMethod" => [
            [
                "id" => $did."#sig",
                "type" => "Ed25519VerificationKey2018",
                "publicKeyBase64" => rtrim(base64_encode($publicKeyForSigning),'='),
            ],
            [
              "id" => $did."#sig-publish",
              "type" => "Ed25519VerificationKey2018",
              "publicKeyBase64" => rtrim(base64_encode($publicKeyForPublishing),'='),
            ],
            [
                "id" => $did."#e2e",
                "type" => "X25519KeyAgreementKey2019",
                "publicKeyBase64" => rtrim(base64_encode($publicKeyForEncryption),'='),
                "encryption_algorithm" => "crypto_box",
            ],
      //$publicKeyForDonationsB
        ]
    ];

    // prepare proof by signing the did document without "proof" key
    $sig_result_obj = WLP256Signature2024($didDocument,$did."#sig",$secretKeyForSigning);

    // Generate proof of work - wlp_anti_spam_pow
    $pow = generateProofOfWork(json_encode($didDocument),'444');
    $pow["type"] = "WEB4Hash2025";
    $pow['@context'] = "https://web4.builders/WEB4Hash2025";

    // add proof signature + pow proof
    $didDocument['proof'] = $sig_result_obj;
    $didDocument['pow'] = $pow;

    
    
    // Convert the DID Document to JSON format
    $didDocumentJson = json_encode($didDocument, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    if ($didDocumentJson === false) {
        throw new Exception("Error encoding DID Document to JSON: " . json_last_error_msg());
    }

    //var_dump(['didDocumentJson',$didDocumentJson]);
    //var_dump(['fileName',$fileName]);
    // Save the JSON encoded DID Document to the specified file
    if (file_put_contents($fileName, $didDocumentJson) === false) {
        throw new Exception("Error writing DID Document to file: $fileName");
    }

    chmod($fileName, 0770);

    return ($didDocument);
}


/*
// test
try {

    $domain = "wlp1.local";                                      // Your domain
    $dir='/var/www/wlp1.local/';
    mkdir($dir.'/.well-known');
    $fileName = $dir."/.well-known/did.json";                     // Output file name
    $result = wlp_did_create_document($domain, $fileName);
    var_dump($result);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
//*/

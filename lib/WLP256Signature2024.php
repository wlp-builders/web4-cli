<?php
require_once 'wlp256.php';
require_once 'php-json_hash.php';

if(!function_exists('WLP256Signature2024')){
	function WLP256Signature2024($input,$didWithHashtag,$secretKeyForSigning,$time_end=null,$receiverDomain=null) {
	  $hash = json_hash($input,'sha3-512');		
	  $exp = false; // no expiration

	  if($time_end) {
		$created = $time_end; // to set same as nonce last time
	  } else {
		  $created = time();
	  }
	  $payload = [
	    "input-hash-sha3-512"=>$hash,
	    "created"=>$created,
	    "senderDid"=>$didWithHashtag
	  ];
	  if($receiverDomain){
		$payload['receiverDomain'] = $receiverDomain;
	  }
	  $signed = wlp256_sign($payload, $secretKeyForSigning,$exp);
	  $payload['input-hash'] = $payload['input-hash-sha3-512'];
	  unset($payload['input-hash-sha3-512']);
	  return [
	    "@context"=>"https://web4.builders/WEB4Signature2025",
	    "type"=>"WEB4Signature2025",
	    "signature" => ["signed"=>$signed,"payload" => $payload],
	  ];
	}
	function WLP256Signature2024_verify($newDoc,$publicKeyForSigning) {
	    $proofSignature = $newDoc['signed'];
	    return false != wlp256_verify($proofSignature, $publicKeyForSigning);
	}
}
/*
// test

// Introducing
// Meritocratic Governance for Everyone
// With 'Send Domain Award' 

// the main idea here is that anyone / highly trusted parties 
// can award you points specific to your expertise in action
// then those with more points get more voting power in these areas
$obj = [
  "sender" => "https://wlp.builders/.well-known/did.json",
  "receiver"=>"your-awesome-domain.tld",
  "award" => ["cybersecurity"=>20],
  "reason" => "Gave great tip for JWT brute-force security.",
];

// the keypair  used normally is linked to the .well-known/did.json
$sign_keys1 = wlp_did_generate_sign_keys(); // demo
$didWithHashtag = 'did:wlp:wlp.builders#donate';
$signature = WLP256Signature2024(json_encode($obj),$didWithHashtag,$sign_keys1['secret']);
$newDoc = $obj;
$newDoc['proof'] = $signature['payload'];
$newDoc['proof']['signed'] = $signature['signed'];
//var_dump($newDoc);// this can be published+verified anywhere

var_dump(WLP256Signature2024_verify($newDoc,$sign_keys1['public']));
//*/

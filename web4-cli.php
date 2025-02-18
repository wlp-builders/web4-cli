<?php
define('WEB4_CLI_NO_POW_TEST',1);
//define('WEB4_CLI_DUMP_POSTDATA_TEST',1);

// only load DID lib if not already loaded
if(!function_exists('WLP256Signature2024')) {
	require_once __DIR__.'/lib/WLP256Signature2024.php';  
	require_once __DIR__.'/lib/wlp_anti_spam_pow.php'; 
	require_once __DIR__.'/lib/wlp_did_create_document.php';
	require_once __DIR__.'/lib/wlp_createDidKeyFiles.php';
}

// other essentials
require_once __DIR__.'/lib/php-web4_multi_upload_repo.php';
require_once __DIR__.'/lib/php-web4_repo_download_zip_operation.php';
require_once __DIR__.'/lib/php-web4-post.php';
require_once __DIR__.'/lib/php-web4-get-nonce.php';
require_once __DIR__.'/lib/php-web4-repo-remove.php';


function loadEnvFromCurrentDir() {
    $envFilePath = getcwd() . '/.env-web4'; // Get the current working directory and append /.env

    if (!file_exists($envFilePath)) {
	    return [];
        //throw new Exception('Env file not found');
    }

    $variables = [];
    $lines = file($envFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; // Skip comments
        }

        [$name, $value] = array_map('trim', explode('=', $line, 2));
        $variables[$name] = $value;
    }

    return $variables;
}


function printUsage() {
    echo "Usage:\n";
    echo "  --mode=sign_file --file=<file_path> --secretKeyFile=<secret_key_file> --didWithHashtag=<did_with_hashtag>\n";
    echo "  --mode=sign_and_publish --repositoryUrlsFile=<repos.txt> --file=<file_path> --secretKeyFile=<secret_key_file> --didWithHashtag=<did_with_hashtag>\n";
    echo "  --mode=verify_sigfile --publicKeyBase64ForSigning=<public_key_base64> --file=<file_path.sig>\n";
    echo "\n";
    echo "Modes:\n";
    echo "  repo_search      - Search for files on repository.\n";
    echo "  repo_download    - Download signed file from repository.\n";
    echo "  sign_file        - Sign a file using a given secret key and DID.\n";
    echo "  sign_and_publish - Sign a file and upload both file + .sig file to a web4 compliant repo server.\n";
    echo "  verify_file      - Verify a sig.json file with a public key and DID.\n";
    echo "\n";
}

function loadSecretFromFile($secretKeyFile) {
    if (file_exists($secretKeyFile)) {
        include_once $secretKeyFile;
        $constantName = strtolower(basename($secretKeyFile, '.php'));
        if (defined($constantName)) {
            return base64_decode(constant($constantName));
        } else {
            echo "Error: Constant $constantName is not defined in $secretKeyFile.\n";
            exit(1);
        }
    } else {
        echo "Error: Secret key file $secretKeyFile does not exist.\n";
        exit(1);
    }
}

function hashFile($filePath) {
    $fileContent = file_get_contents($filePath);
    if ($fileContent === false) {
        echo "Error: Could not read the file.\n";
        exit(1);
    }
    return hash('sha3-512', $fileContent);
}

function signTextMode($filePath, $secretKeyFile, $didWithHashtag) {
    $fileContent = trim(file_get_contents($filePath),true);
    $fileContent = str_replace(PHP_EOL,'\n',$fileContent);
    $hashContent = json_encode(["text"=>$fileContent]);
    $secretKeyForSigning = loadSecretFromFile($secretKeyFile);
    $sigData = WLP256Signature2024($hashContent, $didWithHashtag, $secretKeyForSigning);

    // Generate proof of work - wlp_anti_spam_pow
    if(defined('WEB4_CLI_NO_POW_TEST')) {
	    $pow = generateProofOfWork(json_encode($sigData),'444');
	    $pow["type"] = "WEB4Hash2025";
	    $sigData['pow'] = $pow;
    }

    return '<div class="the_content">'.$fileContent.'</div><h5>Signature:</h5><pre>'.(json_encode($sigData, JSON_PRETTY_PRINT)).'</pre>';
}

function generateDidMode($options) {

	$required_params = ['domain','outputFolder'];
	foreach($required_params as $param) {
		if(!isset($options[$param])) {
			throw new Exception($param." parameter missing");
		}
		
	}

	// wlp create structure output
	$outputFolder = $options['outputFolder'];

	if(!file_exists($outputFolder)) {
		mkdir($outputFolder, 0777, true);
	}


	$domain = $options['domain'];
	$wellKnownFolder = $options['outputFolder'].'/.well-known';
	if(!file_exists($wellKnownFolder)) {
		mkdir($wellKnownFolder,0777,true);
	}
	$fileName = $options['outputFolder'].'/.well-known/did.json';
	$docReturn = wlp_did_create_document($domain, $fileName);
	createDidKeyFiles($docReturn, $outputFolder . '/web4-keys');
	$output = $docReturn;
	// todo keys proba
	return(json_encode($output, JSON_PRETTY_PRINT));
	
}

function signFileMode($options) { 

	$required_params = ['receiverDomain','repositoryUrl'];
	foreach($required_params as $param) {
		if(!isset($options[$param])) {
			throw new Exception($param." parameter missing");
		}
		
	}
	
    $nonceInfo = getNonceMode($options);
	extract($options);

    $end_timestamp = $nonceInfo['end_timestamp'];
    
    $fileHash = hashFile($filePath);
    $secretKeyForSigning = loadSecretFromFile($secretKeyFile);
    $input = ["nonceInfo"=>$nonceInfo,"fileHash"=>$fileHash];
    $sigData = WLP256Signature2024($input, $didWithHashtag, $secretKeyForSigning,$end_timestamp,$receiverDomain);

    // Generate proof of work - wlp_anti_spam_pow
    if(!defined("WEB4_CLI_NO_POW_TEST")) {
    $pow = generateProofOfWork(json_encode($sigData),'444');
    $pow["type"] = "WEB4Hash2025";
    $sigData['pow'] = $pow;
    }

    // add input for next POST request and debugging
    $sigData["input"]=$input;
    return $sigData;
}

function verifySigFileMode($options) {
	$required_params = ['didWithHashtag'];
	foreach($required_params as $param) {
		if(!isset($options[$param])) {
			throw new Exception($param." parameter missing");
		}
		
	}

    extract($options);
    $sigData = json_decode(file_get_contents($filePath), true);
    $publicKeyForSigning = base64_decode($publicKeyBase64ForSigning);

    var_dump($sigData);
    if ($sigData['payload']['senderDid'] != $didWithHashtag) {
        return "Error: Mismatch in domain/did key.\n";
        exit(1);
    }

    $isVerified = WLP256Signature2024_verify($sigData, $publicKeyForSigning);
    if ($isVerified) {
        return "The signature is valid.\n";
    } else {
        return "The signature is NOT valid.\n";
    }
}

function repoRemoveMode($options){
	$required_params = ['name','didWithHashtag','secretKeyFile'];
	foreach($required_params as $param) {
		if(!isset($options[$param])) {
			throw new Exception($param." parameter missing");
		}
		
	}
	extract($options);
    $secretKeyForSigning = loadSecretFromFile($secretKeyFile);

    $urls = loadUrls($options);
    $allResults = [];
    foreach($urls as $url) {
	$repo = trim($url);
	$headers = [
	    'DID: '.$didWithHashtag,
	    'Content-Type: application/json'
	];


	$nonceOptions = ['repositoryUrl'=>$url,'didWithHashtag'=>$didWithHashtag];
	$nonceInfo = getNonceMode($nonceOptions);
	$postData = ['fn'=>['repo__remove'=>$name],'nonceInfo'=>$nonceInfo];
    // sign post data v1
    $postData['proof'] = WLP256Signature2024($postData,$didWithHashtag,$secretKeyForSigning,$nonceInfo['end_timestamp'],$receiverDomain);


	if(defined('WEB4_CLI_DUMP_POSTDATA_TEST')) {
	var_dump($postData);
	}

	// Execute the function
	$results = web4_post($repo, $postData, $headers);
	$allResults[] = $results;
    }

// Output the result
return $allResults;


}
function getNonceMode($options){
	$required_params = ['didWithHashtag','repositoryUrl'];
	foreach($required_params as $param) {
		if(!isset($options[$param])) {
			throw new Exception($param." parameter missing");
		}
		
	}
	extract($options);

    $url = $repositoryUrl;
	// Example usage
	$repo = trim($url);
	$headers = [
	    'DID: '.$didWithHashtag,
	    'Content-Type: application/json'
	];

	$postData = ['fn'=>['web4__get_nonce'=>[$didWithHashtag]]];

    	$errors = [];
	// Execute the function
	var_dump('nonce post data and repo: '.json_encode(['url'=>$url,'postData'=>$postData]));
	$result = web4_post($repo,$postData,$headers);
	var_dump('nonce result: '.json_encode($result));
	return $result;


}

function searchMode($options){
	$required_params = ['didWithHashtag','query','secretKeyFile'];
	foreach($required_params as $param) {
		if(!isset($options[$param])) {
			throw new Exception($param." parameter missing");
		}
		
	}
	extract($options);

    $urls = loadUrls($options);
    $allResults = [];
    foreach($urls as $url) {
		$options['receiverDomain'] = str_replace('https://','',str_replace('http://','',$url));
		$receiverDomain = $options['receiverDomain'];
	$nonceOptions = ['repositoryUrl'=>$url,'didWithHashtag'=>$didWithHashtag];
	$nonceInfo = getNonceMode($nonceOptions);
	//var_dump(['nonceInfo',$nonceInfo]);

	$keyword = $options['query'];
	// Example usage
	$repo = trim($url);
	$headers = [
	    'DID: '.$didWithHashtag,
	    'Content-Type: application/json'
	];
    $secretKeyForSigning = loadSecretFromFile($secretKeyFile);
    $postData = ['fn'=>['repo__search'=>[$keyword]],'nonceInfo'=>$nonceInfo];
    
    // sign post data v1
    $postData['proof'] = WLP256Signature2024($postData,$didWithHashtag,$secretKeyForSigning,$nonceInfo['end_timestamp'],$receiverDomain);

	if(defined('WEB4_CLI_DUMP_POSTDATA_TEST')) {
		var_dump($postData);
	}

    $errors = [];
	// Execute the function
	$results = web4_post($repo, $postData, $headers);
	if($results) {
        if(isset($results['error'])) {
            $errors[] = ["url"=>$url, "error"=>$results['error']];
        } else {

           foreach($results as $result) {
                $result['source'] = $url;
                $allResults[] = $result;
            }
        }
	}
    }

// Output the result
return ["results"=>$allResults,"errors"=>$errors];


}

function loadUrls($options) {

    $urls = [];
    if(isset($options['repositoriesTxt'])) {
	    $urlsText = trim($options['repositoriesTxt']);
	    $urls = explode(PHP_EOL,$urlsText);
    } else {
	    $repositoryUrlsFile = $options['repositoryUrlsFile'];
	    $urlsText = trim(file_get_contents($repositoryUrlsFile));
	    $urls = explode(PHP_EOL,$urlsText);
    }
    return $urls;
}

function downloadFileMode($options){
	$required_params = ['downloadId','secretKeyFile','didWithHashtag','outputFolder'];
	foreach($required_params as $param) {
		if(!isset($options[$param])) {
			throw new Exception($param." parameter missing");
		}
		
	}

    // upload both .zip and .sig to URLS defined in repositoryUrlsFile
	//$download_id, $version, $secretKeyFile, $didWithHashtag,$repositoryUrlsFile) {
    extract($options);
    $secretKeyForSigning = loadSecretFromFile($secretKeyFile);

    $urls = loadUrls($options);
    foreach($urls as $url) {
	$options['receiverDomain'] = str_replace('https://','',str_replace('http://','',$url));
	$receiverDomain = $options['receiverDomain'];
	// Execute the function and print the response
	try {
	$repo = trim($url);
	$nonceOptions = ['repositoryUrl'=>$url,'didWithHashtag'=>$didWithHashtag];
	$nonceInfo = getNonceMode($nonceOptions);
	$postData = [
		'fn'=>['repo__download' => [$downloadId]],
		'nonceInfo'=>$nonceInfo,
	];
    // sign post data v1
    $postData['proof'] = WLP256Signature2024($postData,$didWithHashtag,$secretKeyForSigning,$nonceInfo['end_timestamp'],$receiverDomain);


	if(defined('WEB4_CLI_DUMP_POSTDATA_TEST')) {
		var_dump($postData);
	}

	$headers = [
	    'DID: '.$didWithHashtag,
	    'Content-Type: application/json'
	];
	$type = 'plugins';
	$install_path = $outputFolder.'/'.$type;

	// Execute the function
	$response = web4_repo_download_zip_operation($repo, $postData, $headers, $install_path, $type);

	    return ['output_path'=>$response];
	    break; // return if download completed
	} catch (Exception $e) {
	    echo 'Error: ' . $e->getMessage();
	}
}

}

function signFileAndPublishMode($options) {
	$required_params = ['filePath','secretKeyFile','didWithHashtag','receiverDomain'];
	foreach($required_params as $param) {
		if(!isset($options[$param])) {
			throw new Exception($param." parameter missing");
		}
		
	}
	extract($options);
	if(isset($options['repositoryUrlsFile'])) {
    		// upload both .zip and .sig to URLS defined in repositoryUrlsFile
    		$urlsText = trim(file_get_contents($repositoryUrlsFile));
    		$urls = explode(PHP_EOL,$urlsText);

	} elseif(isset($options['repositoryUrl'])){
		$urls = [$options['repositoryUrl']];
	} else {
		throw new Exception('Need repositoryUrl or repositoryUrlsFile option');
	}
    
    $secretKeyForSigning = loadSecretFromFile($secretKeyFile);
    
    foreach($urls as $url) {
	    $options['repositoryUrl'] = $url;
    $sigfile_content = signFileMode($options);
    // create .sig file
    $sigfile_path = str_replace('.zip','.sig.json',$filePath); 
    file_put_contents($sigfile_path,json_encode($sigfile_content));
	$headers = [
	    'DID: '.$didWithHashtag
	];
$postData = [
    'data'=> ['fn' => ['repo__upload' => []]]
];
    // sign post data v1
    $postData['data']['proof'] = WLP256Signature2024($postData['data'],$didWithHashtag,$secretKeyForSigning,$sigfile_content['input']['nonceInfo']['end_timestamp'],$receiverDomain);

	if(defined('WEB4_CLI_DUMP_POSTDATA_TEST')) {
		var_dump($postData);
	}

    $postData['zip_file'] = new CURLFile($filePath);
    $postData['sig_file'] = new CURLFile($sigfile_path);

// Execute the function and print the response
try {
    $response = web4_multi_upload_repo($url, $headers, $postData);
    echo $response.PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
}

}


function runMain($options) {
    if (!isset($options['mode'])) {
        echo "Error: Missing --mode.\n";
        printUsage();
        exit(1);
    }

    switch ($options['mode']) {
        case 'get_nonce':
		try {
		    $output = getNonceMode($options);
		    echo json_encode($output);
		} catch(Exception $e) {
			echo $e->getMessage().PHP_EOL;
			printUsage();
			exit(1);
		 }
            break;
        case 'generate_did':
		try {
			$docReturn = generateDidMode($options);
			$outputFolder = $options['outputFolder'];
			echo $docReturn;
		} catch(Exception $e) {
			echo $e->getMessage().PHP_EOL;
			printUsage();
			exit(1);
		 }
            break;
        case 'repo_remove':
		try {
			$docReturn = repoRemoveMode($options);
			echo json_encode($docReturn);
		} catch(Exception $e) {
			echo $e->getMessage().PHP_EOL;
			printUsage();
			exit(1);
		 }
            break;
        case 'repo_search':
		try {
		    $output = searchMode($options);
		    echo json_encode($output);
		} catch(Exception $e) {
			echo $e->getMessage().PHP_EOL;
			printUsage();
			exit(1);
		 }
            break;
        case 'download_file':
		try {
		    $output = downloadFileMode($options);
		} catch(Exception $e) {
			echo $e->getMessage().PHP_EOL;
			printUsage();
			exit(1);
		 }
            break;
        case 'sign_and_publish':
		try {
		    $output = signFileAndPublishMode($options);
		} catch(Exception $e) {
			echo $e->getMessage().PHP_EOL;
                	printUsage();
                	exit(1);
		}
            break;
        case 'sign_file':
		try {
	    echo json_encode(signFileMode($options));
		} catch(Exception $e) {
			echo $e->getMessage().PHP_EOL;
			printUsage();
			exit(1);
		 }
            break;

        case 'verify_sigfile':
		try {
	    echo verifySigFileMode($options);
		} catch(Exception $e) {
			echo $e->getMessage().PHP_EOL;
			printUsage();
			exit(1);
		 }
            break;

        default:
            echo "Error: Invalid mode specified.\n";
            printUsage();
            exit(1);
    }
}

if (php_sapi_name() === 'cli' 
    && isset($argc) 
    && strpos(realpath($argv[0]), realpath(dirname(__FILE__))) === 0) {


    // first check for interactive shorthands --generate	
// Parse command-line arguments
$options = getopt('', ['generate']);

// Check if --publish is specified
if (isset($options['generate'])) {
    echo "Interactive mode enabled (--generate)\n";

    // Prompt for domain
    echo "Enter domain (e.g., mydomain.com): ";
    $domain = trim(fgets(STDIN));

    // Prompt for output folder
    echo "Enter output folder (e.g., /path/to/output): ";
    $outputFolder = trim(fgets(STDIN));

    // Validate inputs
    if (!$domain || !$outputFolder) {
        echo "All inputs are required! Please try again.\n";
        exit(1);
    }

    echo "You entered:\n";
    echo "  Domain: $domain\n";
    echo "  Output Folder: $outputFolder\n";
    $options['domain'] = $domain;
    $options['outputFolder'] = $outputFolder;

    echo generateDidMode($options);
    echo PHP_EOL.PHP_EOL.'Great! Next step: upload '.$outputFolder.'/.well-known/did.json to '.$domain. '(for example via sftp)';

    die();
}


// try to load .env-web4 file
try {
    $envVars = loadEnvFromCurrentDir();
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
$options = getopt("", ["mode:", "filePath:", "secretKeyFile:", "didWithHashtag:", "publicKeyBase64ForSigning:","repositoryUrlsFile:","query:","downloadId:","version:",'domain:','outputFolder:','receiverDomain:','name:','repositoryUrl:']);

// Merge command line options with environment variables, giving precedence to command line options.
$finalVars = array_merge($envVars, array_filter($options));

runMain($finalVars);
}

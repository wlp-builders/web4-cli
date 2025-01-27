<?php
define('TEST_MODE',1);

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
require_once __DIR__.'/lib/php-web4-search-operation.php';


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
    echo "  --generate	- Generates your DID.json and WEB4 keys via interactive mode. \n";
    echo "Usage Full:\n";
    echo "  --mode=generate_did --domain=<domain.tld> --outputFolder=<folder> \n";
    #echo "  --mode=sign_file --file=<file_path> --secretKeyFile=<secret_key_file> --didWithHashtag=<did_with_hashtag>\n";
    #echo "  --mode=sign_and_publish --repositoryUrlsFile=<repos.txt> --file=<file_path> --secretKeyFile=<secret_key_file> --didWithHashtag=<did_with_hashtag>\n";
    #echo "  --mode=verify_sigfile --publicKeyBase64ForSigning=<public_key_base64> --file=<file_path.sig>\n";
    echo "\n";
    echo "Modes:\n";
    echo "  generate_did     - Generate DID.json file and WEB4 keys.\n";
    #echo "  repo_search      - Search for files on repository.\n";
    #echo "  repo_download    - Download signed file from repository.\n";
    ##echo "  sign_file        - Sign a file using a given secret key and DID.\n";
    #echo "  sign_and_publish - Sign a file and upload both file + .sig file to a web4 compliant repo server.\n";
    #echo "  verify_file      - Verify a sig.json file with a public key and DID.\n";
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
    if(!TEST_MODE) {
	    $pow = generateProofOfWork(json_encode($sigData),'444');
	    $pow["type"] = "WEB4Hash2025";
	    $sigData['pow'] = $pow;
    }

    return '<div class="the_content">'.$fileContent.'</div><h5>Signature:</h5><pre>'.(json_encode([
	    'proof' => $sigData,
    ], JSON_PRETTY_PRINT)).'</pre>';
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
	$domain = $options['domain'];
	$fileName = $options['outputFolder'].'/did.json';
	$docReturn = wlp_did_create_document($domain, $fileName);
	createDidKeyFiles($docReturn, $outputFolder . '/web4-keys');
	$output = $docReturn;
	// todo keys proba
	return(json_encode($output, JSON_PRETTY_PRINT));
	
}

function signFileMode($filePath, $secretKeyFile, $didWithHashtag) {
    $fileHash = hashFile($filePath);
    $secretKeyForSigning = loadSecretFromFile($secretKeyFile);
    $skipSha3Hash = true;
    $sigData = WLP256Signature2024($fileHash, $didWithHashtag, $secretKeyForSigning, $skipSha3Hash);

    // Generate proof of work - wlp_anti_spam_pow
    if(!TEST_MODE) {
    $pow = generateProofOfWork(json_encode($sigData),'444');
    $pow["type"] = "WEB4Hash2025";
    $sigData['pow'] = $pow;
    }

    return(json_encode(['proof' => $sigData], JSON_PRETTY_PRINT));
}

function verifySigFileMode($filePath, $publicKeyBase64ForSigning, $didWithHashtag) {
    $sigData = json_decode(file_get_contents($filePath), true);
    $publicKeyForSigning = base64_decode($publicKeyBase64ForSigning);

    if ($sigData['proof']['payload']['did'] != $didWithHashtag) {
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

function searchMode($options){
    $urls = loadUrls($options);
    $allResults = [];
    foreach($urls as $url) {
	$keyword = $options['query'];
	// Example usage
	$repo = trim($url);
	$headers = [
	    'DID: did:web4:wlpv3-196.local#sig',
	    'Content-Type: application/json'
	];

	// Execute the function
	$results = web4_repo_search($repo, $keyword, $headers);
	foreach($results as $result) {
		$result['source'] = $url;
		$allResults[] = $result;
	}
    }

// Output the result
return $allResults;


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
    // upload both .zip and .sig to URLS defined in repositoryUrlsFile
	//$download_id, $version, $secretKeyFile, $didWithHashtag,$repositoryUrlsFile) {
    $download_id = $options['downloadId'];
    $secretKeyFile = $options['secretKeyFile'];
    $domain = $options['domain'];
    $didWithHashtag = 'did:web4:'.$domain.'#sig';

    $urls = loadUrls($options);
    foreach($urls as $url) {
     // $url = 'http://wlpv3-196.local/';

// Execute the function and print the response
try {

$repo = trim($url);
//$repo = 'http://wlpv3-196.local/';
$data = [
    'repo__download' => [$download_id]
];
$headers = [
    'DID: did:web4:wlpv3-196.local#sig',
    'Content-Type: application/json'
];
$type = 'plugins';
$install_path = WEB4_INSTALL_PATH.'/'.$type;

// Execute the function
$response = web4_repo_download_zip_operation($repo, $data, $headers, $install_path, $type);

    echo json_encode(['output_path'=>$response]);
    break; // return if download completed
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
}

}

function signFileAndPublishMode($filePath, $secretKeyFile, $didWithHashtag,$repositoryUrlsFile) {
    

    $sigfile_content = signFileMode($filePath, $secretKeyFile, $didWithHashtag);
    
    // create .sig file
    $sigfile_path = '/var/www/wlpv3-web4/tests/test.sig.json';
    file_put_contents($sigfile_path,$sigfile_content);
    
    // upload both .zip and .sig to URLS defined in repositoryUrlsFile
    $urlsText = trim(file_get_contents($repositoryUrlsFile));
    $urls = explode(PHP_EOL,$urlsText);
    foreach($urls as $url) {
     // $url = 'http://wlpv3-196.local/';
$headers = [
    'DID: '.$didWithHashtag
];
$postFields = [
    'data' => json_encode(['repo__upload' => []]),
    'zip_file' => new CURLFile($filePath),
    'sig_file' => new CURLFile($sigfile_path),
];

// Execute the function and print the response
try {
    $response = web4_multi_upload_repo($url, $headers, $postFields);
    echo $response;
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
        case 'repo_search':
            if (isset($options['query'])) {
		    $output = searchMode($options);
		    echo json_encode($output);
            } else {
                echo "Error: Missing parameters for sign_and_publish.\n";
                printUsage();
                exit(1);
            }
            break;
        case 'download_file':
	    if(!isset($options['downloadId'])) {
                echo "Error: Missing parameter downloadId.\n";
                printUsage();
                exit(1);
	    }
            if (isset($options['repositoryUrlsFile']) && isset($options['secretKeyFile']) && isset($options['didWithHashtag'])) {
		    $output = downloadFileMode($options);
            } else {
                echo "Error: Missing parameters for sign_and_publish.\n";
                printUsage();
                exit(1);
            }
            break;
        case 'sign_and_publish':
            if (isset($options['repositoryUrlsFile']) && isset($options['file']) && isset($options['secretKeyFile']) && isset($options['didWithHashtag'])) {
		    $output = signFileAndPublishMode($options);
            } else {
                echo "Error: Missing parameters for sign_and_publish.\n";
                printUsage();
                exit(1);
            }
            break;
        case 'sign_file':
            if (isset($options['file']) && isset($options['secretKeyFile']) && isset($options['didWithHashtag'])) {
		    echo signFileMode($options);
            } else {
                echo "Error: Missing parameters for signing.\n";
		 if(!isset($options['file'])) echo 'Missing: --file'.PHP_EOL;	
		 if(!isset($options['secretKeyFile'])) echo 'Missing: --secretKeyFile'.PHP_EOL;	
		 if(!isset($options['didWithHashtag'])) echo 'Missing: --didWithHashtag'.PHP_EOL;	
		
                printUsage();
                exit(1);
            }
            break;

        case 'verify_sigfile':
            if (isset($options['file']) && isset($options['didWithHashtag']) && isset($options['publicKeyBase64ForSigning'])) {
		    echo verifySigFileMode($options);
            } else {
                echo "Error: Missing parameters for verification.\n";
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


if (php_sapi_name() == "cli") {
	
	

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
    echo PHP_EOL.PHP_EOL.'Great! Next step: upload '.$outputFolder.'/did.json to '.$domain. '(for example via sftp)';

    die();
}
// try to load .env-web4 file
try {
    $envVars = loadEnvFromCurrentDir();
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}

$options = getopt("", ["mode:", "file:", "secretKeyFile:", "didWithHashtag:", "publicKeyBase64ForSigning:","repositoryUrlsFile:","query:","downloadId:","version:",'domain:','outputFolder:']);

// Merge command line options with environment variables, giving precedence to command line options.
$finalVars = array_merge($envVars, array_filter($options));

runMain($finalVars);
}

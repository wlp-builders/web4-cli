OUTPUT=`php ../web4-cli.php --filePath='test-data.txt' --secretKeyFile='data/test4.local/web4-keys/sig_publish_secret_base64.php' --didWithHashtag='did:web4:test4.local#sig-publish' --mode='sign_file' --receiverDomain='web4-repo.every.yoga'`

echo $OUTPUT
echo $OUTPUT > test.sig.json


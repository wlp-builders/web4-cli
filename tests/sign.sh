OUTPUT=`php web4-cli.php --file='tests/test-data.txt' --secretKeyFile='tests/data/sig_secret_base64.php' --didWithHashtag='did:web4:wlp193.local#sig' --mode='sign_file'`
echo $OUTPUT
echo $OUTPUT > tests/test.sig.json


RECEIVER_DOMAIN=`basename $(realpath ../../../../../../)`
echo $RECEIVER_DOMAIN
php ../../web4-cli.php --filePath='../data/wlp-markdown-1.0.1.zip' --receiverDomain=$RECEIVER_DOMAIN --repositoryUrl="http://$RECEIVER_DOMAIN"

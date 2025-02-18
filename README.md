# web4-cli
 Login with your website. Sign actions and files with your .well-known/did.json. Decentralization & Democracy for Everyone. https://web4.builders. 

## Join Web4 in 3 Easy Steps

## 1. Generate DID
```
git clone https://github.com/wlp-builders/web4-cli
cd web4-cli
php web4-cli.php --generate
```

## 2. Publish your DID to your website
```
cd outputFolder
sftp root@yoursite.tld
cd /var/www/yoursite.tld
mkdir .well-known
cd .well-known 
put did.json
```

## 3. Test & Celebrate! 
Visit your website.tld/.well-known/did.json to verify it.

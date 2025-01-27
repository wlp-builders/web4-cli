# web4-cli
 Login with your website. Sign actions and files with your .well-known/did.json. Decentralization & Democracy for Everyone. https://web4.builders. 

## Join Web4 in Easy Steps
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

## 3. Celebrate! 
New updates/modes will come soon, so be sure to pull the repository in a few days.
```
cd web4-cli
git pull
```

###

## Usage
```
php web4-cli.php 

Usage:
  --generate	- Generates your DID.json and WEB4 keys via interactive mode. 
Usage Full:
  --mode=generate_did --domain=<domain.tld> --outputFolder=<folder> 

Modes:
  generate_did     - Generate DID.json file and WEB4 keys.
```

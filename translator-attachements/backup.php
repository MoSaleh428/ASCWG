<?php

function encryptString($plaintext, $password) {
//This is s3cr3t
}


function decryptString($ciphertext, $password) {
    $ciphertext = base64_decode($ciphertext);
    echo $ciphertext;
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
echo $DecryptVal;
echo "test";
echo $DecryptVal;
unserialize($DecryptVal);

?>

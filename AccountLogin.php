<?php

include 'Credentials.php';
include 'Protection.php';
include 'EncryptionKeys.php';

foreach($_POST as $element)
{
    checkForbiddenPhrase($INTERNAL_AUTH_KEY, $element);
}

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: ");
}


$postAuthKey1=$conn->real_escape_string($_POST["postAuthKey"]);
$emailId=$conn->real_escape_string($_POST["emailId"]);
$otp=$conn->real_escape_string($_POST["otp"]);


$key_name="postAuthKey";


$response=array();
$response['success']=false;
$response['validAuth']=false;
$response['validEmail']=false;
$response['validOtp']=false;

$stmt3=$conn->prepare("SELECT value FROM Auth_Keys WHERE name =?");
$stmt3->bind_param("s", $key_name);

$stmt3->execute();
$stmt3->bind_result($postAuthKey2);


if($stmt3->fetch() && $postAuthKey1==$postAuthKey2)
{
    $stmt3->close();
    $response['validAuth']=true;
    
    $stmt=$conn->prepare("SELECT COUNT(email_id), otp FROM Account WHERE email_id=? AND status=0");
    $stmt->bind_param("s", $emailId);
    $stmt->execute();
    $stmt->bind_result($count, $realOtp);
    $stmt->fetch();
    $stmt->close();

    if($count==1)
    {
        $count=-1;
        $response['validEmail']=true;

        $otp=encrypt($INTERNAL_AUTH_KEY, $otp, $keySet);

        if($otp==$realOtp)
        {
            $response['validOtp']=true;

            $stmt=$conn->prepare("UPDATE Account SET status=1 WHERE email_id=?");
            $stmt->bind_param("s",$emailId);
            $stmt->execute();
            $stmt->fetch();
            $stmt->close();

            $response['success']=true;
        }
        
        
        $otp1=mt_rand(1000, mt_rand(1001,9999));        
        $otp1=encrypt($INTERNAL_AUTH_KEY, $otp1, $keySet);
    
		$stmt3=$conn->prepare("UPDATE Account SET otp=? WHERE email_id=?");
		$stmt3->bind_param("ss",$otp1,$emailId);
		$stmt3->execute();
		$stmt3->close();
    }
}
$conn->close();

echo json_encode($response);


?>
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
$pollId=$conn->real_escape_string($_POST["pollId"]);
$emailId=$conn->real_escape_string($_POST["emailId"]);
$endCode=$conn->real_escape_string($_POST["endCode"]);

$key_name="postAuthKey";

$response=array();
$response['success']=false;
$response['validAuth']=false;
$response['validAccount']=false;
$response['validPoll']=false;
$response['validEndCode']=false;

$stmt=$conn->prepare("SELECT value FROM Auth_Keys WHERE name =?");
$stmt->bind_param("s", $key_name);
$stmt->execute();
$stmt->bind_result($postAuthKey2);

if($stmt->fetch() && $postAuthKey1==$postAuthKey2)
{
    $stmt->close();
	$response['validAuth']=true;
	
	$stmt2=$conn->prepare("SELECT COUNT(email_id) FROM Account WHERE email_id=? AND status=1");
	$stmt2->bind_param("s",$emailId);
	$stmt2->execute();
	$stmt2->bind_result($count);
	
	if($stmt2->fetch() && $count==1)
	{
		$count=-1;
		$stmt2->close();
		
		$response['validAccount']=true;
		
		$stmt3=$conn->prepare("SELECT COUNT(id),end_code FROM Poll WHERE id=? AND account_id=? AND status=1");
		$stmt3->bind_param("ds",$pollId,$emailId);
		$stmt3->execute();
		$stmt3->bind_result($count2,$endCode2);
		
		if($stmt3->fetch() && $count2==1)
		{
			$count2=-1;
			$stmt3->close();
			
			$response['validPoll']=true;
			$encryptedCode=encrypt($INTERNAL_AUTH_KEY,$endCode,$keySet);
			
			if($endCode2==$encryptedCode)
			{
				$response['validEndCode']=true;
				
				$stmt4=$conn->prepare("UPDATE Poll SET status=2 WHERE id=? AND account_id=?");
				$stmt4->bind_param("ds",$pollId,$emailId);
				$stmt4->execute();
				$stmt4->fetch();
				$stmt4->close();
				
				$response['success']=true;
			}
        }
        else
			$stmt3->close();
	}
	else
		$stmt2->close();
}
else
	$stmt->close();

$conn->close();
echo json_encode($response);	

?>
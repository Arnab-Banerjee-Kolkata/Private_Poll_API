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

$key_name="postAuthKey";

$response=array();
$response['success']=false;
$response['validAuth']=false;

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
		
		$stmt3=$conn->prepare("UPDATE Account SET status=0 WHERE email_id=? AND status=1");
		$stmt3->bind_param("s",$emailId);
		$stmt3->execute();
		$stmt3->fetch();
		$stmt3->close();
		
		$response['success']=true;
	}
	else
		$stmt2->close();
}
else
	$stmt->close();

$conn->close();
echo json_encode($response);	
	
?>
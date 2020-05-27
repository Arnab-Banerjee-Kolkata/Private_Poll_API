<?php

include 'Credentials.php';
include 'Protection.php';
include 'EncryptionKeys.php';


// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: ");
}


$postAuthKey1=$conn->real_escape_string($_POST["postAuthKey"]);
$emailId=$conn->real_escape_string($_POST["emailId"]);
$currentPassword=$conn->real_escape_string($_POST["currentPassword"]);
$newPassword=$conn->real_escape_string($_POST["newPassword"]);

$key_name="postAuthKey";

$response=array();
$response['success']=false;
$response['validAuth']=false;
$response['validEmail']=false;
$response['validCurrentPassword']=false;
$response['validNewPassword']=false;


$stmt=$conn->prepare("SELECT value FROM Auth_Keys WHERE name =?");
$stmt->bind_param("s", $key_name);
$stmt->execute();
$stmt->bind_result($postAuthKey2);

if($stmt->fetch() && $postAuthKey1==$postAuthKey2)
{
    $stmt->close();
    $response['validAuth']=true;
	
	$stmt2=$conn->prepare("SELECT COUNT(email_id),password FROM Account WHERE email_id=? AND status=1");
	$stmt2->bind_param("s",$emailId);
	$stmt2->execute();
	$stmt2->bind_result($count,$password);
	
	if($stmt2->fetch() && $count==1)
	{
		$count=-1;
		$stmt2->close();
		$response['validEmail']=true;
		
		if($password==$currentPassword)
		{
			$response['validCurrentPassword']=true;
			
			$uppercase=preg_match('@[A-Z]@', $newPassword);	//New password validation
			$lowercase=preg_match('@[a-z]@', $newPassword);
			$number=preg_match('@[0-9]@', $newPassword);
			$specialChars=preg_match('@[^\w]@', $newPassword);
			
			if($uppercase && $lowercase && $number && $specialChars && strlen($newPassword) >= 8 && strlen($newPassword) <= 20)
			{
				$response['validNewPassword']=true;
				
				$stmt3=$conn->prepare("UPDATE Account SET password=? WHERE email_id=? AND status=1");
				$stmt3->bind_param("ss",$newPassword,$emailId);
				$stmt3->execute();
				$stmt3->fetch();
				$stmt3->close();
				
				$response['success']=true;
			}
		}
	}
	else
		$stmt2->close();
}
else
	$stmt->close();

$conn->close();
echo json_encode($response);
	
?>
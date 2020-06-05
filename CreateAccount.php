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
$password=$conn->real_escape_string($_POST["password"]);

$key_name="postAuthKey";

$response=array();
$response['success']=false;
$response['validAuth']=false;
$response['validEmail']=false;
$response['validPassword']=false;


$stmt=$conn->prepare("SELECT value FROM Auth_Keys WHERE name =?");
$stmt->bind_param("s", $key_name);
$stmt->execute();
$stmt->bind_result($postAuthKey2);

if($stmt->fetch() && $postAuthKey1==$postAuthKey2)
{
    $stmt->close();
    $response['validAuth']=true;
	
	$stmt3=$conn->prepare("SELECT COUNT(email_id) FROM Account WHERE email_id=?");
	$stmt3->bind_param("s",$emailId);
	$stmt3->execute();
	$stmt3->bind_result($count);
	$stmt3->fetch();
	
	if($count==0 && filter_var($emailId, FILTER_VALIDATE_EMAIL))		//email validation
	{
		$count=-1;
		$stmt3->close();
		$response['validEmail']=true;
		
		$uppercase=preg_match('@[A-Z]@', $password);	//password validation
		$lowercase=preg_match('@[a-z]@', $password);
		$number=preg_match('@[0-9]@', $password);
		$specialChars=preg_match('@[^\w]@', $password);
		
		if($uppercase && $lowercase && $number && $specialChars && strlen($password) >= 8 && strlen($password) <= 20)
		{
			$response['validPassword']=true;

            $stmt2=$conn->prepare("INSERT INTO Account(email_id,status,password) VALUES(?,1,?)");
            $stmt2->bind_param("ss",$emailId,$password);
            $stmt2->execute();
            $stmt2->fetch();
            $stmt2->close();

			$response['success']=true;
		}
	}
	else
		$stmt3->close();
}

else
	$stmt->close();

$conn->close();
echo json_encode($response);
	
?>

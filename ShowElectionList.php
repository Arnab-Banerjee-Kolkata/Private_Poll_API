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

$key_name="postAuthKey";

$response=array();
$response['success']=false;
$response['validAuth']=false;
$response['validEmail']=false;

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
		$response['validEmail']=true;
		
		$stmt3=$conn->prepare("SELECT id,entry_code,end_code,type,max_votes,status,topic FROM Poll WHERE account_id=?");
		$stmt3->bind_param("s",$emailId);
		$stmt3->execute();
		$stmt3->bind_result($id,$entryCode,$endCode,$type,$maxVotes,$status,$topic);
		
		$createdList=array();
		
		while($stmt3->fetch())
		{
			$details=array();
			
			$details['id']=$id;
			$details['entryCode']=$entryCode;
			$details['endCode']=$endCode;
			$details['type']=$type;
			$details['maxVotes']=$maxVotes;
			$details['status']=$status;
			$details['topic']=$topic;
			
			array_push($createdList,$details);
		}
		$stmt3->close();
		
		$response['createdList']=$createdList;
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
	
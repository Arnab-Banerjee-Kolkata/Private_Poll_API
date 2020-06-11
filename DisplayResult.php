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
$pollId=$conn->real_escape_string($_POST["pollId"]);
$emailId=$conn->real_escape_string($_POST["emailId"]);

$key_name="postAuthKey";

$response=array();
$response['success']=false;
$response['validAuth']=false;
$response['validPoll']=false;
$response['validUser']=false;


$stmt=$conn->prepare("SELECT value FROM Auth_Keys WHERE name =?");
$stmt->bind_param("s", $key_name);
$stmt->execute();
$stmt->bind_result($postAuthKey2);

if($stmt->fetch() && $postAuthKey1==$postAuthKey2)
{
    $stmt->close();
    $response['validAuth']=true;
	
	$stmt2=$conn->prepare("SELECT COUNT(id),account_id FROM Poll WHERE id=? AND status=2");
	$stmt2->bind_param("d",$pollId);
	$stmt2->execute();
	$stmt2->bind_result($count,$creator);
	
	if($stmt2->fetch() && $count==1)
	{
		$count=-1;
		$stmt2->close();
		$response['validPoll']=true;
		
		$stmt4=$conn->prepare("SELECT email_id FROM Voted_List WHERE poll_id=?");
		$stmt4->bind_param("d",$pollId);
		$stmt4->execute();
		$stmt4->bind_result($voter);
		$stmt4->fetch();
		
		if($emailId==$creator || $emailId==$voter)
		{
			$stmt4->close();
			$response['validUser']=true;
			
			$stmt3=$conn->prepare("SELECT option_name,vote_count FROM Polling_Options WHERE poll_id=?");
			$stmt3->bind_param("d",$pollId);
			$stmt3->execute();
			$stmt3->bind_result($optionName,$voteCount);
			
			$results=array();
			
			while($stmt3->fetch())
			{
				$options=array();
				
				$options['optionName']=$optionName;
				$options['voteCount']=$voteCount;
				
				array_push($results,$options);
			}
			$stmt3->close();
			
			$response['results']=$results;
			$response['success']=true;
		}
		else
			$stmt4->close();
	}
	else
		$stmt2->close();
}
else
	$stmt->close();

$conn->close();
echo json_encode($response);
	
?>

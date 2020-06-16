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
$entryCode=$conn->real_escape_string($_POST["entryCode"]);

$key_name="postAuthKey";

$response=array();
$response['success']=false;
$response['validAuth']=false;
$response['validPoll']=false;
$response['validEntryCode']=false;

$stmt=$conn->prepare("SELECT value FROM Auth_Keys WHERE name =?");
$stmt->bind_param("s", $key_name);
$stmt->execute();
$stmt->bind_result($postAuthKey2);

if($stmt->fetch() && $postAuthKey1==$postAuthKey2)
{
    $stmt->close();
    $response['validAuth']=true;
	
	$stmt2=$conn->prepare("SELECT COUNT(id),entry_code FROM Poll WHERE id=? AND status=1");
	$stmt2->bind_param("d",$pollId);
	$stmt2->execute();
	$stmt2->bind_result($count,$entryCode2);
	
	if($stmt2->fetch() && $count==1)
	{
		$count=-1;
		$stmt2->close();
		$response['validPoll']=true;
		
		if($entryCode==$entryCode2)
		{
			$response['validEntryCode']=true;
			
			$stmt3=$conn->prepare("SELECT option_id,option_name FROM Polling_Options WHERE poll_id=?");
			$stmt3->bind_param("d",$pollId);
			$stmt3->execute();
			$stmt3->bind_result($optionId,$optionName);
			
			$votingPanel=array();
			
			while($stmt3->fetch())
			{
				$options=array();
				
				$options['optionName']=$optionName;
				$options['voteCount']=$voteCount;
				
				array_push($votingPanel,$options);
			}
			$stmt3->close();			
			
			$response['votingPanel']=$votingPanel;
			$response['success']=true;
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
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
$pollId=$conn->real_escape_string($_POST["pollId"]);

$key_name="postAuthKey";

$response=array();
$response['success']=false;
$response['validAuth']=false;
$response['validEmail']=false;
$response['validPoll']=false;


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
		
		$stmt3=$conn->prepare("SELECT COUNT(id),status,max_votes,topic,entry_code,end_code FROM Poll WHERE account_id=? AND id=?");
		$stmt3->bind_param("sd",$emailId,$pollId);
		$stmt3->execute();
		$stmt3->bind_result($count2,$status,$maxVotes,$topic,$entryCode,$endCode);
		
		if($stmt3->fetch() && $count2==1)
		{
			$count2=-1;
			$stmt3->close();
			$response['validPoll']=true;
			
			$details=array();
			
			$response['maxVotes']=$maxVotes;
			
			if($status==2)
			{
				$details=array();
				
				$stmt4=$conn->prepare("SELECT option_id,option_name,vote_count FROM Polling_Options WHERE poll_id=?");
				$stmt4->bind_param("s",$pollId);
				$stmt4->execute();
				$stmt4->bind_result($optionId,$optionName,$voteCount);
				
				while($stmt4->fetch())
				{
                    			$options=array();
                    
					$options['optionId']=$optionId;
					$options['optionName']=$optionName;
					$options['voteCount']=$voteCount;
					
					array_push($details,$options);
				}
				$response['options']=$details;

				$stmt4->close();
			}
			$response['status']=$status;
			$response['topic']=$topic;
			
			if($status>=0 && $status<2)
			{
				$response['entryCode']=$entryCode;
				$response['endCode']=$endCode;
			}
			
			$response['success']=true;
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

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
$pollOptionId=$conn->real_escape_string($_POST["pollOptionId"]);

$key_name="postAuthKey";

$response=array();
$response['success']=false;
$response['validAuth']=false;
$response['validPoll']=false;
$response['validPollOptionId']=false;

$stmt=$conn->prepare("SELECT value FROM Auth_Keys WHERE name =?");
$stmt->bind_param("s", $key_name);
$stmt->execute();
$stmt->bind_result($postAuthKey2);

if($stmt->fetch() && $postAuthKey1==$postAuthKey2)
{
    $stmt->close();
    $response['validAuth']=true;
	
	$stmt2=$conn->prepare("SELECT COUNT(id),max_votes FROM Poll WHERE id=? AND status=1");
	$stmt2->bind_param("d",$pollId);
	$stmt2->execute();
	$stmt2->bind_result($count,$maxVotes);
	
	if($stmt2->fetch() && $count==1)
	{
		$count=-1;
		$stmt2->close();
		$response['validPoll']=true;
		
		$stmt3=$conn->prepare("SELECT COUNT(option_id),vote_count FROM Polling_Options WHERE option_id=? AND poll_id=?");
		$stmt3->bind_param("dd",$pollOptionId,$pollId);
		$stmt3->execute();
		$stmt3->bind_result($count2,$voteCount);
		
		
		if($stmt3->fetch() && $count2==1)
		{
			$count2=-1;
			$stmt3->close();
			$response['validPollOptionId']=true;
			
			++$voteCount;
			
			$stmt4=$conn->prepare("UPDATE Polling_Options SET vote_count=? WHERE option_id=? AND poll_id=?");
			$stmt4->bind_param("ddd",$voteCount,$pollOptionId,$pollId);
			$stmt4->execute();
			$stmt4->fetch();
			$stmt4->close();
			
			$stmt6=$conn->prepare("SELECT SUM(vote_count) FROM Polling_Options WHERE poll_id=?");
			$stmt6->bind_param("d",$pollId);
			$stmt6->execute();
			$stmt6->bind_result($totalVoteCount);
			$stmt6->fetch();
			$stmt6->close();
			
			if($maxVotes==$totalVoteCount)
			{
				$stmt5=$conn->prepare("UPDATE Poll SET status=2 WHERE id=?");
				$stmt5->bind_param("d",$pollId);
				$stmt5->execute();
				$stmt5->fetch();
				$stmt5->close();
				
				$response['electionCompleted']=true;
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
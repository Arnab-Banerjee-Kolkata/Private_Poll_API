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
$maxVotes=$conn->real_escape_string($_POST["maxVotes"]);
$topic=$conn->real_escape_string($_POST["topic"]);

$i=0;
foreach($_POST['options'] as $element)
{
    $options[$i++]=$conn->real_escape_string($element);
}

$key_name="postAuthKey";

$response=array();
$response['success']=false;
$response['validAuth']=false;
$response['validEmail']=false;
$response['validMaxVotes']=false;
$response['validOptions']=false;

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
		
		if($maxVotes>=1 or $maxVotes==null)
		{
			$response['validMaxVotes']=true;
			
			$temparr=array_map('strtoupper',$options);
            
            if(count($temparr) == count(array_unique($temparr)) && !empty($options))
            {
                $response['validOptions']=true;
			
			//Poll insertion starts here
			$entryCode=generateOtp($INTERNAL_AUTH_KEY);
			$endCode=generateOtp($INTERNAL_AUTH_KEY); 
			
			$stmt3=$conn->prepare("INSERT INTO Poll(entry_code,end_code,max_votes,status,account_id,topic) VALUES(?,?,?,0,?,?)");
			$stmt3->bind_param("ssdss",$entryCode,$endCode,$maxVotes,$emailId,$topic);
			$stmt3->execute();
			$stmt3->fetch();
			$stmt3->close();
			
			$stmt4=$conn->prepare("SELECT LAST_INSERT_ID()");//get Poll id here
			$stmt4->execute();
			$stmt4->bind_result($pollId);
			$stmt4->fetch();
			$stmt4->close();
			
			$response['pollId']=$pollId;
			$response['entryCode']=$entryCode;
			$response['endCode']=$endCode;
			
			$temp=0;
			
			while($options[$temp])
			{
				$stmt5=$conn->prepare("INSERT INTO Polling_Options(option_name,poll_id,vote_count) VALUES(?,?,0)");
				$stmt5->bind_param("sd",$options[$temp],$pollId);
				$stmt5->execute();
				$stmt5->fetch();
				$stmt5->close();
				
				$temp++;
			}
			
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
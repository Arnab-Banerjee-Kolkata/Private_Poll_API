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
$response['validPoll']=false;
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
		
		$stmt3=$conn->prepare("SELECT COUNT(id) FROM Poll WHERE id=? AND account_id=? AND status=0");
		$stmt3->bind_param("ds",$pollId,$emailId);
		$stmt3->execute();
		$stmt3->bind_result($count2);
		
		if($stmt3->fetch() && $count2==1)
		{
			$count2=-1;
			$stmt3->close();
			$response['validPoll']=true;
			
			if($maxVotes>=1 or $maxVotes==null)
			{
				
			$response['validMaxVotes']=true;
				
			$temparr=array_map('strtoupper',$options);
            
            if(count($temparr) == count(array_unique($temparr)) && !empty($options))
            {
                $response['validOptions']=true;

		    	$stmt4=$conn->prepare("DELETE FROM Polling_Options WHERE poll_id=?");
		    	$stmt4->bind_param("d",$pollId);
		    	$stmt4->execute();
		    	$stmt4->fetch();
		    	$stmt4->close();

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

                	if($maxVotes==0)
				$maxVotes=null;
                    
	    		$stmt6=$conn->prepare("UPDATE Poll SET topic=?,max_votes=? WHERE id=?");
		    	$stmt6->bind_param("sdd",$topic,$maxVotes,$pollId);
			$stmt6->execute();
	    		$stmt6->fetch();
		    	$stmt6->close();
    
	    		$response['success']=true;
		    }
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

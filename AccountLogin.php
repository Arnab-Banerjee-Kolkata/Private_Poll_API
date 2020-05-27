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

$stmt3=$conn->prepare("SELECT value FROM Auth_Keys WHERE name =?");
$stmt3->bind_param("s", $key_name);

$stmt3->execute();
$stmt3->bind_result($postAuthKey2);


if($stmt3->fetch() && $postAuthKey1==$postAuthKey2)
{
    $stmt3->close();
    $response['validAuth']=true;
    
    $stmt=$conn->prepare("SELECT COUNT(email_id), password FROM Account WHERE email_id=? AND status=0");
    $stmt->bind_param("s", $emailId);
    $stmt->execute();
    $stmt->bind_result($count, $password2);
    $stmt->fetch();
    $stmt->close();

    if($count==1)
    {
        $count=-1;
        $response['validEmail']=true;

        if($password==$password2)
        {
            $response['validPassword']=true;

            $stmt=$conn->prepare("UPDATE Account SET status=1 WHERE email_id=?");
            $stmt->bind_param("s",$emailId);
            $stmt->execute();
            $stmt->fetch();
            $stmt->close();

            $response['success']=true;
        }
    }
}
$conn->close();

echo json_encode($response);

?>

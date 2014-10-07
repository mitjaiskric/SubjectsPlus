<?php

// NOTE the email is turned off!

// Report all PHP errors
ini_set('display_errors',1); 
error_reporting(E_ALL);

$subsubcat = "";
$subcat = "";
$page_title = "Update Staff Info";
include("../includes/header.php");

// try to avoid someone hitting this page by mistake
if (!isset($_GET["sendnow"]) || $_GET["sendnow"] != "1") {
  print "not going to send this just now";
  
  exit;
}

// set up our email
$sent_from = "f.urtecho@miami.edu";

ini_set("SMTP", $email_server);
ini_set("sendmail_from", $sent_from);

    $subject = "Please Confirm Staff Information";
$header  = 'MIME-Version: 1.0' . "\n";
$header .= 'Content-type: text/html; charset=iso-8859-1' . "\n";
    $header .= "Return-Path: $sent_from\n";
    $header .= "From:  $sent_from\n";
    

$q1 = 'SELECT lname as "Last Name", fname as "First Name", title as "Job Title", job_classification as "Classification", tel as "Work Phone #", 
  email as "Email Address", room_number as "Room #", 
  emergency_contact_name as "Emergency Contact", emergency_contact_relation as "Emergency Contact (Relationship)", emergency_contact_phone as "Emergency Contact (Phone #)", 
  street_address as "Address", city as "City", state as "State", zip as "Zip", home_phone as "Home Phone #", cell_phone as "Cell Phone #"
  FROM staff_detailed
  WHERE active = 1';

// AND email IN ("agdarby@miami.edu", "mabraham@miami.edu")
          
$r1 = MYSQL_QUERY($q1);

$num_rows = mysql_num_rows($r1);

if ($num_rows != 0) {

  while ($myrow = mysql_fetch_array($r1, MYSQL_ASSOC)) {
    $send_to = $myrow["Email Address"];
    //$send_to = "agdarby@gmail.com";
    $message = "<html><body><p>Please send me any changes you may have regarding your Emergency Contact Information by <strong>April 30</strong>.  Hurricane Season is coming up soon and we need to make sure we have everyone's updated information in our system.<p>
        <p>Please review your personal information below and let me know if this information is all correct.  If there are any changes please be sure to send me your corrections and <span style=\"background-color: yellow;\">highlight them</span> so that I know which information I need to update.  If all of the information is correct, please type an X next to \"All information is correct\".</p><p>ALL INFORMATION IS CORRECT _____ </p>";
    foreach ($myrow as $key => $value) {


      $message .= "<strong>" . $key . "</strong>: " . $value . "<br />";
    }
    
    $message .= "<br />\n\n<p>Remember:  <span style=\"background-color: yellow;\">Highlight your corrections!</span></p>";
    $message .= "</body></html>";
    
    print $message . $send_to;
        // begin assembling actual message

    //$success = mail($send_to, "$subject", $message, $header);
    // The below is just for testing purposes
    if ($success) {
      
      print "mail sent to $send_to";
    } else {
      print "mail didn't go to $send_to";
    }
  }
}

?>

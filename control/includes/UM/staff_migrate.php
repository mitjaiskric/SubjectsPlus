<?php

/*
 * This just prints out a bunch of sql statements that may then be run
 * uncomment the file bit to create directories for each
 */

$subcat = "records";
$subsubcat = "index.php";
$page_title = "Browse Items";

include("../includes/header.php");

//////////////
// ptag migration
//////////////

$querierUser = new sp_Querier();
$qUser = "SELECT staff_id, ptags FROM staff_detailed ORDER BY staff_id";
$userArray = $querierUser->getResult($qUser);

foreach ($userArray as $value) {
  print "UPDATE staff SET ptags = '" . $value["ptags"] . "' WHERE staff_id = '" . $value["staff_id"] . "';<br />";
}
exit;

try {
  $dbc = new sp_DBConnector($uname, $pword, "internal_directory", $hname);
} catch (Exception $e) {
  echo $e;
}

$querierUser = new sp_Querier();
$qUser = "SELECT employee.Employee_ID, Last_Name, First_Name, Position, Position_No, Classification, Room_No, Street, City, State, Zip, Supervisor_ID, Dept_ID, Priority, Email, Home_Phone, Work_Phone, Intercom, Cell_Phone, Fax, E_Contact_Name, E_Contact_Relation, E_Contact_Phone 
  FROM employee, employee_phone_number 
  WHERE employee.Employee_ID = employee_phone_number.Employee_ID 
  ORDER BY Last_Name";

$userArray = $querierUser->getResult($qUser);

//print_r($userArray);


foreach ($userArray as $value) {
  
  $tel = explode("-", $value["Work_Phone"]);
  $dept_id = str_replace("L-", "", $value["Dept_ID"]);
  
  
  $statement = "INSERT INTO staff (staff_id, lname, fname, title, tel, department_id, staff_sort, email, user_type_id, active, position_number, job_classification, room_number, supervisor_id, emergency_contact_name, emergency_contact_relation, emergency_contact_phone, street_address, city, state, zip, home_phone, cell_phone, fax, intercom) 
      VALUES (\"" . $value["Employee_ID"] . "\", 
        \"" . $value["Last_Name"] . "\", 
      \"" . $value["First_Name"] . "\", 
      \"" . $value["Position"] . "\", 
      '" . $tel[1]. "-" .$tel[2] . "', 
      '" . $dept_id . "', 
      \"" . $value["Priority"] . "\", 
      '" . $value["Email"] . "', 
      1, 
      1,
      \"" . $value["Position_No"] . "\", 
      \"" . $value["Classification"] . "\", 
      \"" . $value["Room_No"] . "\", 
      \"" . $value["Supervisor_ID"] . "\",       
      \"" . $value["E_Contact_Name"] . "\", 
      \"" . $value["E_Contact_Relation"] . "\", 
      \"" . $value["E_Contact_Phone"] . "\",       
      \"" . $value["Street"] . "\", 
      \"" . $value["City"] . "\", 
      \"" . $value["State"] . "\", 
      \"" . $value["Zip"] . "\",       
      \"" . $value["Home_Phone"] . "\", 
      \"" . $value["Cell_Phone"] . "\", 
      \"" . $value["Fax"] . "\", 
      \"" . $value["Intercom"] . "\"            
      )";
  
      //$querierInsertStaff = new sp_Querier();
      //$insertArray = $querierInsertStaff->getResult($statement);

  /*$record = new sp_Staff($_POST["staff_id"], "post");
  $record->insertRecord();*/
  print $statement . ";<br />";
  
          // create folder

        /*
            $user_folder = explode("@", $value["Email"]);
            $path = "../../assets/users/_" . $user_folder[0];
            mkdir($path);

            // And copy over the generic headshot image
            $nufile = $path . "/headshot.jpg";
            $copier = copy("../../assets/images/headshot.jpg", $nufile);
       
        
  print "create folder for $user_folder[0]<br />";
         
         */
}
?>

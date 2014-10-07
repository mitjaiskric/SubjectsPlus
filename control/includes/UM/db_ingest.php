<?php

// to update:
// 1.  wipe tables:  location, location_title, rank, title
// 2.  Make sure new alternate_title field is in place:
// ALTER TABLE `title` ADD `alternate_title` VARCHAR( 255 ) NOT NULL AFTER `title` 
// 3.  Make sure "primary sources" added to all_ctags var in config.php
// 
// Setting the Content-Type header with charset

header('Content-type: text/html; charset=UTF-8');

$subcat = "records";
$subsubcat = "index.php";
$page_title = "Browse Items";

include("../includes/header.php");

$records = file('DisplayList.txt');
$del_records = file('RemoveList.txt');
$staff_owner_id = '381';

getAllSubs($records);
ingestData($records);
deleteData($del_records);

function ingestData($records) {
  foreach ($records as $line_num => $line) {
    $this_line = explode("|", $line);

    // Fields
    $erm_id = trim($this_line[0]);

    // Clean up titles
    $erm_title = trim($this_line[1]);
    $erm_title = utf8_encode($erm_title);

    // Alternate Title info
    $erm_alt_title = trim($this_line[2]);
    $erm_alt_title = utf8_encode($erm_alt_title);

    // use only second description?
    $erm_description = trim($this_line[3]);
    $erm_description = explode("^", $erm_description);
    $erm_description = $erm_description[0];
    $erm_description = utf8_encode($erm_description);

    $erm_url = trim($this_line[4]);
    // remove proxy if present

    $erm_url = preg_replace('/https:\/\/iiiprxy\.library\.miami\.edu\/login\?url=/', '', $erm_url);

    $erm_trial = trim($this_line[5]);
    $erm_access = trim($this_line[6]);
    $erm_subjects = trim($this_line[7]);
    $erm_labels = trim($this_line[8]);
    $erm_note = trim($this_line[9]);
    $erm_is_trial = trim($this_line[10]);

    // add our trial to the lables field
    if ($erm_is_trial == "t") {
      $erm_labels .= "^Database_Trial";
    }

    //print $erm_subjects;
    // put subjects into array
    $erm_subjects = explode("^", $erm_subjects);

    $erm_labels = explode("^", $erm_labels);

    // insert into records
    $updater = modifydb($erm_id, $erm_title, $erm_description, $erm_url, $erm_trial, $erm_access, $erm_subjects, $erm_labels, $erm_note, $erm_alt_title);
  }
}

function modifydb($erm_id, $erm_title, $erm_description, $erm_url, $erm_trial, $erm_access, $erm_subjects, $erm_labels, $erm_note, $erm_alt_title) {

  $_POST["title_id"] = "";
  $_POST["title"] = $erm_title;
  $_POST["alternate_title"] = $erm_alt_title;
  $_POST["description"] = $erm_description;
  $_POST["prefix"] = "";

  // data stored in location table
  $_POST["location_id"] = array(""); // array
  $_POST["location"] = array("$erm_url"); // array
  $_POST["call_number"] = array("$erm_id"); // array
  $_POST["format"] = array("1"); // array INT

  if ($erm_access != "UM Restricted") {
    $_POST["access_restrictions"] = array("1");
  } else {
    $_POST["access_restrictions"] = array("2");
  }

  if ($erm_note != "") {
    $_POST["display_note"] = array("$erm_note"); // array
  } else {
    $_POST["display_note"] = array("$erm_trial"); // array
  }

  $_POST["eres_display"] = array("Y"); // array
  $ingest_ctags = ""; // array
  // Set up some empty arrays
  $_POST["subject"] = array();
  $_POST["rank"] = array();
  $_POST["source"] = array();
  $_POST["description_override"] = array();

  // data stored in rank table
  $i = 0;
  foreach ($erm_subjects as $value) {
    // look up subject_id -- aargh!
    $shortie = preg_replace("/[^A-Za-z0-9]/", "", $value);
    $q = "SELECT subject_id from subject where shortform = '$shortie'";
    $r = MYSQL_QUERY($q);

    // if we have a match, populate the $_POST array values to be read by the
    // sp_Record functions

    if ($r) {
      $this_subject_id = mysql_fetch_row($r);

      $_POST["subject"][$i] = $this_subject_id[0];
      $_POST["rank"][$i] = 1;
      $_POST["source"][$i] = 1;
      $_POST["description_override"][$i] = "";
      $i++;
    }
  }

  $_POST["ctags"] = "";

  // Let's check if "new databases" is a subject; if so, we'll make this a ctag
  if (in_array("New Databases", $erm_subjects)) {
    $ingest_ctags = array("New_Databases");
  } else {
    $ingest_ctags = array();
  }
  

  foreach ($erm_labels as $value) {

    // remove any goofy final semi-colons
    $value = preg_replace('/;$/', "", $value);

    switch ($value) {
      case "Full-Text Database":
      case "contains full text":
      case "Database (Contains Full Text)":
      case "Books (Contains Full Text)":
      case "Full-Text": // preferred ?
        $ingest_ctags[] = "full_text";
        break;
      case "Government Documents":
        $ingest_ctags[] = "Government_Documents";
        break;
      case "Images":
      case "Image Database":
      case "Image": // preferred
        $ingest_ctags[] = "images";
        break;
      case "News":
      case "News & Newspapers":
        $ingest_ctags[] = "News_and_Newspapers";
        break;
      case "Papers": // preferred
        $ingest_ctags[] = "Papers";
        break;

      case "Primary Sources":
      case "Primary Source Documents": // preferred
        $ingest_ctags[] = "Primary_Source_Documents";
        break;
      case "Proceedings": // preferred
        $ingest_ctags[] = "Proceedings";
        break;
      case "Reference": // preferred
        $ingest_ctags[] = "Reference";
        break;
      case "Standards": // preferred
        $ingest_ctags[] = "Standards";
        break;
      case "A & I Database":
      case "Abstract/Citation Database":
      case "Indexes & Abstracts":
      case "Abstract/Citation/Index": // preferred?

        $ingest_ctags[] = "Abstract/Citation/Index";
        break;

      case "E-books":
      case "E-Text":
      case "E-Text Collection":
      case "Electronic Books":
      case "Electronic Texts":
      case "E-Book": // preferred?
      case "E-Book Collection": // preferred?
        $ingest_ctags[] = "E-Books";
        break;
      case "E-Music":
      case "Audio": // preferred?
        $ingest_ctags[] = "audio";
        break;
      case "Music Scores": // preferred?
        $ingest_ctags[] = "Music_Scores";
        break;
      case "Maps": // pref
        $ingest_ctags[] = "Maps";
        break;
      case "E-Video":
      case "Video": // PREF
        $ingest_ctags[] = "video";
        break;
      case "Mobile Enabled": //pref
        $ingest_ctags[] = "Mobile_Enabled";
        break;
      case "Statistics":
      case "Statistics & Data": // pref
      case "Data Set":
        $ingest_ctags[] = "Statistics_and_Data";
        break;
      case "Dissertations_and_Theses": // preferred ?
        $ingest_ctags[] = "thesis";
        break;
      case "E-Journal Collection":
      case "E-Journal":
        $ingest_ctags[] = "E-Journals";
        break;
      case "Database_Trial":
        $ingest_ctags[] = "Database_Trial";
        break;
    }
  }

  // de-dupe
  $ingest_ctags = array_unique($ingest_ctags);

  $data = "";

  foreach ($ingest_ctags as $value) {
    $data .= "$value|";
  }
  // remove final pipe
  $data = preg_replace('/\|$/', "", $data);

  $_POST["ctags"] = array("$data");

  $qcheck = "SELECT title_id, location.location_id FROM location, location_title WHERE location.location_id = location_title.location_id AND call_number = '$erm_id'";
  print $qcheck;
  $rcheck = MYSQL_QUERY($qcheck);

  if ($rcheck) {
    $myrow = mysql_fetch_row($rcheck);

    $num_rows = mysql_num_rows($rcheck);

    if ($num_rows == 0) {
      print "NO RECORD FOUND:  Do Insert!";
      $item = new sp_Record("", "post");
      $item->insertRecord(1);
      print $item->deBug();
    } else {
      $_POST["title_id"] = $myrow[0];
      $_POST["location_id"] = array($myrow[1]);

      //print "title = " . $myrow[0] . "-- location_id = " . $myrow[1];
      print "MATCHING RECORD FOUND:  Do Update!<p>";
      $item = new sp_Record("", "post");
      $item->updateRecord(1);
      print $item->deBug();
      print "<p></p>";
    }
  }
}

function getAllSubs($records, $insert = "") {
  global $staff_owner_id;

  $all_erm_subjects = array();
  // loop through .txt file
  foreach ($records as $line_num => $line) {
    $this_line = explode("|", $line);
    $erm_subjects = trim($this_line[7]);
    $erm_subjects = explode("^", $erm_subjects);

    if (!empty($erm_subjects)) {
      $all_erm_subjects = array_merge($all_erm_subjects, $erm_subjects);
    }
  }

  // Now work with this array
  foreach ($all_erm_subjects as $value) {
    // edit out items that begin "Database" and empty ones
    if (!preg_match("/^Database/", $value) && $value != "") {
      $new_subs[] = $value;

      $_POST["subject_id"] = "";
      $_POST["subject"] = $value;
      $shortie = preg_replace("/[^A-Za-z0-9]/", "", $value);
      $_POST["shortform"] = $shortie;
      $_POST["active"] = 1;
      $_POST["type"] = "Subject";
      $_POST["extra"] = '70';


      // data stored in staff_subject table
      $_POST["staff_id"] = array($staff_owner_id);

      $record = new sp_Guide("", "post");
      $record->insertRecord();
      print $record->deBug();
    }
  }

  $new_subs = array_unique($new_subs);
  sort($new_subs);
  print_r($new_subs);
}

function deleteData($records) {
  foreach ($records as $line_num => $line) {
    $this_line = explode("|", $line);

    // Fields
    $erm_id = trim($this_line[0]);

    $qcheck = "SELECT title_id, location.location_id FROM location, location_title WHERE location.location_id = location_title.location_id AND call_number = '$erm_id'";
    print $qcheck . "<br />";

    $rcheck = MYSQL_QUERY($qcheck);

    if ($rcheck) {
      $myrow = mysql_fetch_row($rcheck);

      $num_rows = mysql_num_rows($rcheck);

      if ($num_rows != 0) {
        print "we shall delete $erm_id";
        $record = new sp_Record($myrow[0], "delete");
        $record->deleteRecord();
        $record->deBug();
        // Show feedback
        $feedback = $record->getMessage();
      }
    }
  }
}

?>

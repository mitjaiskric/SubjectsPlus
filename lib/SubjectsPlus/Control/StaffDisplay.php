<?php
   namespace SubjectsPlus\Control;
/**
 *   @file
 *   @brief creates the staff listing
 *
 *   @author adarby, d lowder
 *   @date
 *   @todo
 */
    
use PDO;
    
    
class StaffDisplay {

  function writeTable($qualifier, $get_assoc_subs = 1, $print_display = 0) {

    global $tel_prefix;
    global $mod_rewrite;

    // sanitize submission
    $selected = scrubData($qualifier);

    switch ($qualifier) {
      case "Faculty Profiles":
        $q = "select lname, fname, title, tel, email, staff_id, ptags
			FROM staff
			WHERE active = 1
            AND ptags like '%librarian%'
			order by lname, fname";

        $r = $db->query($q);

        $items = "<table width=\"98%\" class=\"item_listing\">";

        $row_count = 0;
        $colour1 = "oddrow";
        $colour2 = "evenrow";
        $current_dept = "";

        foreach ($r as $myrow) {

          $row_colour = ($row_count % 2) ? $colour1 : $colour2;

          $lname = $myrow["0"];
          $fname = $myrow["1"];
          $title = $myrow["2"];
          $tel = $myrow["3"];
          $email = $myrow["4"];
          $name_id = explode("@", $email);
          $staff_id = $myrow["5"];
          $ptags = $myrow["6"];

          if ($get_assoc_subs == 1) {
            // Grab our subjects, if any
            $assoc_subjects = self::getAssocSubjects($staff_id, $ptags);
          } else {
            $assoc_subjects = "";
          }

          if ($mod_rewrite == 1) {
            $link_to_details = "staff/" . $name_id[0];
          } else {
            $link_to_details = "staff_details.php?name=" . $name_id[0];
          }

          $items .= "<tr class=\"$row_colour\">
		<td style=\"width: 20%\" align=\"left\" class=\"$row_colour\"><span class=\"staff_contact\">";
          if ($print_display != 1) {
            $items .= "<a href=\"$link_to_details\">$lname, $fname</a>";
          } else {
            $items .= "$lname, $fname";
          }
          
          $items .= "</span></td>
			<td style=\"width: 40%\" align=\"left\" class=\"$row_colour\">$title $assoc_subjects</td>
			<td align=\"left\" class=\"$row_colour\">$tel_prefix$tel </td>
			<td class=\"$row_colour\"><a href=\"mailto:$email\">$email</a></td></tr>";

          $row_count++;
        }

        $items .= "</table>";


        break;
      case "By Department":
        $q = "select distinct d.department_sort, staff.staff_sort, name, lname, fname, title, staff.tel, staff.email, d.department_id, d.telephone, staff.staff_id, staff.ptags
			FROM department d, staff
			WHERE d.department_id = staff.department_id
			AND user_type_id = '1'
            AND active = 1
			order by department_sort, d.name,  staff_sort desc, lname";

        $db = new Querier;
        $r = $db->query($q);

        $items = "<table width=\"98%\" class=\"item_listing\">";

        $row_count = 0;
        $colour1 = "oddrow";
        $colour2 = "evenrow";
        $current_dept = "";

           foreach ($r as $myrow) {

          $row_colour = ($row_count % 2) ? $colour1 : $colour2;

          $dept_name = $myrow["2"];
          $lname = $myrow["3"];
          $fname = $myrow["4"];
          $title = $myrow["5"];
          $tel = $myrow["6"];
          $email = $myrow["7"];
          $dept_id = $myrow["8"];
          $dept_tel = $myrow["9"];
          $name_id = explode("@", $email);

          $staff_id = $myrow["10"];
          $ptags = $myrow["11"];

          if ($get_assoc_subs == 1) {
            // Grab our subjects, if any
            $assoc_subjects = self::getAssocSubjects($staff_id, $ptags);
          } else {
            $assoc_subjects = "";
          }

          // end subject listing

          if ($mod_rewrite == 1) {
            $link_to_details = "staff/" . $name_id[0];
          } else {
            $link_to_details = "staff_details.php?name=" . $name_id[0];
          }

          if ($current_dept != $dept_id) {
            $items .= "<tr><td align=\"center\" colspan=\"4\"><a name=\"$dept_id\"></a><h2 class=\"dept_header\">$dept_name&nbsp; &nbsp;" . $tel_prefix . $dept_tel . "</h2></td></tr>";
          }

          $items .= "<tr class=\"$row_colour\">
		<td style=\"width: 20%\" align=\"left\" class=\"$row_colour\"><span class=\"staff_contact\">";
          
          // Here we stick in their headshot; comment out if you don't want; maybe later this should be an admin parameter
          $items .= getHeadshot($email, '');

          if ($print_display != 1) {
            $items .= "<a href=\"$link_to_details\">$lname, $fname</a>";
          } else {
            $items .= "$lname, $fname";
          }
          
          $items .= "</span></td>
			<td style=\"width: 40%\" align=\"left\" class=\"$row_colour\">$title $assoc_subjects</td>
			<td align=\"left\" class=\"$row_colour\">$tel_prefix$tel </td>
			<td class=\"$row_colour\"><a href=\"mailto:$email\">$email</a></td></tr>";

          $row_count++;
          $current_dept = $dept_id;
        }


        $items .= "</table>";

        break;
      case "Subject Librarians A-Z":

        $q = "select distinct lname, fname, title, tel, email, staff.staff_id
                from staff, staff_subject ss, subject su
                where staff.staff_id = ss.staff_id
                AND ss.subject_id = su.subject_id
                AND staff.active = 1
                AND type = 'Subject'
                AND su.active = '1'
                AND user_type_id = '1'
                AND shortform != 'NewDatabases'
                order by lname, fname";
        $db = new Querier;
        $r = $db->query($q);

        $items = "<table width=\"98%\" class=\"item_listing\">
			<tr>
                            <th width=\"300\">" . _("Librarian") . "</th>
                            <th>" . _("Subject Responsibilities") . "</th>
			</tr>";

        $row_count = 0;
        $colour1 = "oddrow";
        $colour2 = "evenrow";

          foreach ($r as $myrow) {
          $row_colour = ($row_count % 2) ? $colour1 : $colour2;

          $items .= "<tr class=\"$row_colour\">\n
					<td width=\"400\">";
          $items .= showStaff($myrow[4], '', '', 1);
          $items .= "</td>\n";
          $items .= "<td>";

          $sub_query = "select subject, shortform from subject, staff_subject
                    WHERE subject.subject_id = staff_subject.subject_id
                    AND staff_id =  '$myrow[5]'
                    AND type = 'Subject'
                    AND active = '1'
                    AND shortform != 'NewDatabases'
                    ORDER BY subject";

          /* Select all active records (this is based on a db connection made above) */

          $sub_result = $db->query($sub_query);

          $num_rows = (count($sub_result) - 1);

          // Loop through all items, sticking commas in between

          $subrowcount = 0;

          foreach ($sub_result as $subrow) {

            if ($mod_rewrite == 1) {
              $linky = $subrow[1];
            } else {
              $linky = "guide.php?subject=" . $subrow[1];
            }

            $items .= "<a href=\"$linky\">$subrow[0]</a>";
            if ($subrowcount < $num_rows) {
              $items .= ", ";
            }
            $subrowcount++;
          }

          $items .= "</td>\n
					</tr>";

          $row_count++;
        }

        $items .= "</table>";
        break;
      case "Librarians by Subject Specialty":
        $q = "select lname, fname, title, tel, email, subject, staff.staff_id, shortform from
                    staff, staff_subject, subject
			where staff.staff_id = staff_subject.staff_id
			AND staff_subject.subject_id = subject.subject_id
			AND type = 'Subject'
            AND staff.active = 1
            AND subject.active = 1
            AND shortform != 'NewDatabases'
			order by subject, lname, fname";
        $head_fields = array("Subject", "Library Liaison", "Phone", "Email");
        $db = new Querier;
        $r = $db->query($q);
        $items = prepareTH($head_fields);

        $row_count = 0;
        $colour1 = "oddrow";
        $colour2 = "evenrow";
        $subrowsubject = "";

    foreach ($r as $myrow) {
          $full_name = $myrow["lname"] . ", " . $myrow["fname"];
          $title = $myrow["title"];
          $tel = $tel_prefix . $myrow["tel"];
          $email = $myrow["email"];
          $name_id = explode("@", $email);

          if ($subrowsubject == $myrow["subject"]) {
            //$psubject = " ";
            $psubject = $myrow["subject"];
            $row_count--;
          } else {
            $subrowsubject = $myrow["subject"];
            $psubject = $myrow["subject"];
            $shortsub = $myrow["shortform"];
          }

          $row_colour = ($row_count % 2) ? $colour1 : $colour2;

          $items .= "<tr class=\"$row_colour\">\n
					<td>";

          if ($mod_rewrite == 1) {
            $linky = $shortsub;
          } else {
            $linky = "guide.php?subject=" . $shortsub;
          }
          $items .= "<a href=\"$linky\">$psubject</a>";
          $items .= "</td>\n";
          $items .= "<td>";

          if ($mod_rewrite == 1) {
            $linky = "staff_details.php?name=" . $name_id[0];
          } else {
            $linky = "staff_details.php?name=" . $name_id[0];
          }

          $items .= "<a href=\"$linky\">$full_name</a></td>";
          $items .= "<td>";
          $items .= $tel;
          $items .= "</td>\n";
          $items .= "<td>";
          $items .= "<a href=\"mailto:$email\">$email</a>";
          $items .= "</td>\n
					</tr>";

          $row_count++;
        }

        $items .= "</table>";
        break;

      case "A-Z":
      default:

        $q = "SELECT s.staff_id, lname, fname, title, tel, s.email, name, ptags
			FROM staff s
			LEFT JOIN department d on s.department_id = d.department_id
			WHERE user_type_id = '1'
            AND active = 1
			ORDER BY s.lname, s.fname";

        $hf1 = _("Name");
        $hf2 = _("Title");
        $hf3 = _("Phone");
        $hf4 = _("Email");

        $head_fields = array($hf1, $hf2, $hf3, $hf4);

        $db = new Querier;
            $r = $db->query($q,PDO::FETCH_ASSOC);

        $items = prepareTH($head_fields);

        $row_count = 0;
        $colour1 = "oddrow";
        $colour2 = "evenrow";

        foreach ($r as $myrow) {

          $row_colour = ($row_count % 2) ? $colour1 : $colour2;

          $staff_id = $myrow["staff_id"];
          $full_name = $myrow["lname"] . ", " . $myrow["fname"];
          $title = $myrow["title"];
          $tel = $tel_prefix . $myrow["tel"];
          $email = $myrow["email"];
          $name_id = explode("@", $email);
          $department = $myrow["name"];
          $ptags = $myrow["ptags"];

          if ($get_assoc_subs == 1) {
            // Grab our subjects, if any
            $assoc_subjects = self::getAssocSubjects($staff_id, $ptags);
          } else {
            $assoc_subjects = "";
          }

          if ($mod_rewrite == 1) {
            $link_to_details = "staff/" . $name_id[0];
          } else {
            $link_to_details = "staff_details.php?name=" . $name_id[0];
          }

          //$headshot = getHeadshot($email, "medium");

          $items .= "
		<tr class=\"zebra $row_colour\">
			<td  class=\"staff-name-row\">";
          
          if ($print_display != 1) {
            $items .= "<a href=\"$link_to_details\" class=\"no_link\">$full_name</a>";
          } else {
            $items .= "$full_name";
          }
          
          $items .= "</td>
			<td class=\"staff-title-row\">$title $assoc_subjects</td>
			<td  class=\"staff-tel-row\">$tel &nbsp;</td>
			<td  class=\"staff-email-row\"><a href=\"mailto:$email\">$email</a></td>
		</tr>";

          $row_count++;
        }

        $items .= "</table>";
        break;
    }




    return $items;
  }

  function searchFor($qualifier) {
    
  }

  function getAssocSubjects($staff_id, $ptags) {
    global $mod_rewrite;
    $assoc_subjects = "";

    // See if they're a librarian, and then check for subjects

    $islib = preg_match('/librarian/', $ptags);

    if ($islib == 1) {
      // UM hack in query
      $q2 = "SELECT subject, shortform 
              FROM subject, staff_subject 
              WHERE subject.subject_id = staff_subject.subject_id
              AND staff_subject.staff_id = $staff_id
              AND subject.active = 1
              AND shortform != 'NewDatabases'
              ORDER BY subject";
      //print $q2;
        $db = new Querier;
      $r2 = $db->query($q2);

      foreach ($r2 as $myrow2) {

        if ($mod_rewrite == 1) {
          $link_to_guide = $myrow2[1];
        } else {
          $link_to_guide = "guide.php?subject=" . $myrow2[1];
        }

        $assoc_subjects .= "<a href=\"$link_to_guide\">$myrow2[0]</a>, ";
      }
    }

    if ($assoc_subjects != "") {
      $assoc_subjects = rtrim($assoc_subjects, ", ");
      $assoc_subjects = "<br /><span class=\"smaller\">$assoc_subjects</span>";
    } else {
      $assoc_subjects = "";
    }
    return $assoc_subjects;
  }

}

?>
<?php

/**
 *   @file record_bits.php
 *   @brief Inserting elements via .load into record.php
 *
 *   @author adarby
 *   @date
 *   @todo scrub post vars
 */
$subsubcat = "";
$subcat = "records";
$page_title = "Record Bits include";
$header = "noshow";

use SubjectsPlus\Control\Querier;
use SubjectsPlus\Control\DBConnector;
use SubjectsPlus\Control\Dropdown;
use SubjectsPlus\Control\Record;
use SubjectsPlus\Control\LinkChecker;
use SubjectsPlus\Control\Mailer;
use SubjectsPlus\Control\MailMessage;

include("../includes/header.php");

// Connect to database


//print_r($_POST);

switch ($_POST["type"]) {
    case "location":

        $record = new Record();
        $record->buildLocation();

        break;
    case "add_subject":

        $subject_name = Truncate($_POST["our_sub_text"], 25, '');
        $source_name = Truncate($_POST["our_source_text"], 15, '');

        echo "<div class=\"selected_item_wrapper\"><div class=\"selected_item\" id=\"root-" . $_POST["our_source_id"] . "\"><input type=\"hidden\" name=\"rank[]\" value=\"0\" /><input type=\"hidden\" name=\"subject[]\" value=\"" . $_POST["our_sub_id"] . "\" /><input type=\"hidden\" id=\"hidden_source-" . $_POST["our_sub_id"] . "-" . $_POST["our_source_id"] . "\" name=\"source[]\" value=\"" . $_POST["our_source_id"] . "\" />" . $subject_name . "<span class=\"small_extra\"> " . $source_name . " </span><br /><textarea class=\"desc_override desc-area\" name=\"description_override[]\" rows=\"4\" cols=\"35\"></textarea></div><div class=\"selected_item_options\"><img src=\"$IconPath/delete.png\" class=\"delete_sub\" alt=\"delete\" border=\"0\"> <img src=\"$IconPath/source_override_inactive.png\" class=\"source_override\" id=\"source_override-" . $_POST["our_sub_id"] . "-" . $_POST["our_source_id"] . "\" alt=\"add source\" border=\"0\"> <img src=\"$IconPath/note_override_inactive.png\" class=\"note_override\" alt=\"delete\" border=\"0\"></div></div>";

        break;
    case "source_override":

        // load list of sources
        $querierSource = new Querier();
        $qSource = "select source_id, source from source order by source";
        $defsourceArray = $querierSource->query($qSource);

        $sourceMe = new Dropdown("source_override[]", $defsourceArray, $_POST["our_source_id"]);
        $source_string = $sourceMe->display();

        echo "<span class=\"record-source-override\">" . _("Source Override") . "<br />$source_string <img src=\"$IconPath/list-add.png\" class=\"add_source\" id=\"add_source_id-" . $_POST["our_subject_id"] . "-" . $_POST["our_source_id"] . "\" alt=\"" . _("add source override") . "\" title=\"" . _("add source override") . "\" border=\"0\">
        <img src=\"$IconPath/delete.png\" class=\"cancel_add_source\" id=\"cancel_add_source_id-" . $_POST["our_subject_id"] . "-" . $_POST["our_source_id"] . "\" alt=\"" . _("never mind") . "\" title=\"" . _("never mind") . "\" border=\"0\"></span>";

        break;
    case "new_record_label":
        switch ($_POST["format_type_id"]) {
            case 1:
                $label_text = _("Location (Enter URL)");
                break;
            case 2:
                $label_text = _("Location (Enter Call Number)");
                break;
            case 3:
                $label_text = _("Location (Enter Persistent Catalog URL--include http://)");
                break;
        }

        print $label_text;
        break;
    case "check_title":

        break;
    case "check_url":

        // check link
    	if( isset($_POST['useProxy']) && $_POST['useProxy'] == 'TRUE' )
    		$lobjLinkChecker = new LinkChecker($proxyURL, 5, FALSE);
    	else
    		$lobjLinkChecker = new LinkChecker('', 5, FALSE);

    	$lobjError = $lobjLinkChecker->checkUrl($_REQUEST["checkurl"]);

        if ($lobjError['message'] == "") {
            $feedback = _("This URL looks OK to me");
            print "<img src=\"$IconPath/accept.png\" alt=\"check url\" border=\"0\" id=\"check_url\" alt=\"$feedback\" title=\"$feedback\" >";
        } else {
            $feedback = _("This URL looks dodgy; better check it");
            print "<img src=\"$IconPath/exclamation_octagon_fram.png\" alt=\"check url\" border=\"0\" id=\"check_url\" alt=\"$feedback\" title=\"$feedback\" />";
        }

        break;
    case "recommend_delete":
        $del_record = $CpanelPath . "records/record.php?record_id=" . $_POST["our_id"];
        $message_body = "<p>" . _("The following record is recommended for delete") . ":</p>
            <p><a href=\"$del_record\">$del_record</a></p>";
        $messageParams = array('from' => $_SESSION['email'],
            'to' => $administrator_email,
            'subjectLine' => _("SubjectsPlus: Record Delete Recommendation"),
            'content' => $message_body);
        $message = new MailMessage($messageParams);
        $mailer = new Mailer();
        $mailer->send($message);
        echo "<div class=\"rec_delete_confirm\">" . _("Delete request sent to ") . "$administrator_email</div>";
        break;
}
?>
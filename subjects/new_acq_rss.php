<?php

header("Content-Type: application/rss+xml; charset=UTF-8");

//include("../control/includes/config.php");
//include("../control/includes/functions.php");

date_default_timezone_set('US/Eastern');

// $connnection = 'mysql:host=127.0.0.1;port=3310;dbname=new_titles';
// $uname = "webuser01";
// $pword = "w3bpas2";

function getSub(array $s, $sub, $sql)
{
	$sql =  " AND acq_call REGEXP '" . $s[$sub] . "'";
	return $sql;
}

/*
 * get_week_range accepts numeric $month, $day, and $year values.
 * It will find the first sunday and the last saturday of the week for the
 * given day, and return them as YYYY-MM-DD
 *
 * @param month: numeric value of the month (01 - 12)
 * @param day  : numeric value of the day (01 - 31)
 * @param year : four-digit value of the year (2008)
 * @return     : array('first' => sunday of week, 'last' => saturday of week);
 */
function get_week_range($day='', $month='', $year='') {
// default empties to current values
  if (empty($day))
    $day = date('d');
  if (empty($month))
    $month = date('m');
  if (empty($year))
    $year = date('Y');


  $weekday = date('w', mktime(0, 0, 0, $month, $day, $year));
  $sunday = $day - $weekday;
  $sunday = $sunday - 7;
  $start_week = date('Y-m-d', mktime(0, 0, 0, $month, $sunday, $year));
  $start_day = date('d', mktime(0, 0, 0, $month, $sunday, $year));
  $end_week = date('Y-m-d', mktime(0, 0, 0, $month, $sunday + 6, $year));
  $end_day = date('d', mktime(0, 0, 0, $month, $sunday + 6, $year));


  if (!empty($start_week) && !empty($end_week)) {
    return array('first' => $start_week, 'last' => $end_week, 'sday' => $start_day, 'eday' => $end_day);
  }
  // otherwise there was an error :'(
  return false;
}

$open = <<<XMLHEAD
<?xml version="1.0" encoding="UTF-8"?>
<?xml-stylesheet type="text/css" href="../_src/css/rss.css" ?>
<rss version="2.0">
	<channel>
	<link>http://library.miami.edu/</link>
XMLHEAD;

// current month - testing

// $date_start = date("Ymd", strtotime(date('m') . '/01/' . date('Y') . ' 00:00:00'));
// $date_end  = date("Ymd", strtotime('-1 second', strtotime('+1 month', strtotime(date('m') . '/01/' . date('Y') . '00:00:00'))));

// last month - production

$date_start = date('Ymd', (strtotime('last month', strtotime(date('m/01/y')))));
$date_end =  date('Ymd', (strtotime('this month -1 hour', strtotime(date('m/01/y')))));


$view = isset($_GET['view']) ? $_GET['view'] : null;
$acq  = isset($_GET['acq']) ? $_GET['acq'] : "";
$sub  = isset($_GET['sub']) ? $_GET['sub'] : "";
$t = isset($_GET['t']) ? $_GET['t'] : "";

// set categories and regex's
$category = array(
      'All Titles',
      'Architecture Library',
      'Bestsellers',
      'Cuban Heritage Collection',
      'Government Documents',
      'Graphic Novels',
      'Juvenile Collection',
      'Marine Library',
      'Reference Collection',
      'Special Collections',
      'Video & DVD Collection',
      'Music Libray - Books, Dissertations, Theses',
      'Music Library - Jazz CD\'s',
      'Music Library - Scores',
      'Music Library - Sound Recordings'
      );

// sub-catergory regex's
  $subcategory = array('Africana' => '^E29\.N3|E184\.[567]|E185\.[1-9]',
      'Anthropology' => '^G[NRT]',
      'Architecture' => '^NA',
      'Art & Art History' => '^N\d|N[B-E|K|X]',
      'Biology' => '^Q[HKLMPR]',
      'Business' => '^H[EFGJ]',
      'Chemistry' => '^QD',
      'Classics' => '^DE|DF1|DF2|DT5[6789]\.|DT6[123456789]\.|DT73|DT[789][456789]\.|DT1[12345][12345]\.|DG[123]|PA[12346]',
      'Communication' => '^P8[7-9]\D|P9\d[\D\s]|PN199\d\D|PN4[6-8]\d\d\D|PN4[01]\d\d\D|PN5\d\d\d\D|TR',
      'Computer Science' => '^QA76\D',
      'Economics' => '^H[BCD]',
      'Education' => '^L',
      'Engineering' => '^T[ACDEFGHJKLNPS]',
      'English Literature' => '^PN4[3-5]\d\d\D|P[ERSZ]|P[123][0127][1-9]\D|PN841|PN[1-7]\d\d\D|PN1[013]\d\d\D|PN6[01]\d\d\D',
      'Environmental Science' => '^GE|GF|HC79\D|KF3775|QH[12]\d\d\D|QH54[0-9]\D|QK9[0-8]\d\D|S589\D7|S9[0-7][0-2]|SD4[12][1-8]|TD',
      'Foreign Languages & Literature' => '^PN8[05]\d\D|P[CDQT]',
      'Geography & Regional Studies' => '^G\d|G[AB]|GF|geograph|geo',
      'Geology' => '^QE',
      'Health Science' => '^R|QP\d|QR|QM',
      'History' => '^\d|C[BCEJNRST]|CD\d|D|E|F|U|V[ABCDEFGH]',
      'International Studies' => '^([J][A|C|F|K-Z])|D8[3-5][0-9]|D10\d\d\D|D[ST][1-9]\d\d\D|KZ|E183\D[7-9]|E804\D|J[1-9]\d\d\D|D[DPQ]2\d\d\D|DG5[78]\d\D|# = DK2[6-9]\d\D|DR|DS[4-9]\d\D|DS1[12]\d\D|DS6[1-6]\D|F114\d\D|F[1-3]\d\d\d\D',
      'Judaic Studies' => '^BM|S119|DS1[12345]1',
      'Latin American Studies' => '^F1[2456789][013456789][0123456789]\.|F2[012345678][013456789][0123456789]\.|F3[0234567][013456789][0123456789]\.|JL1[123456789][123456789][123456789]\.|LE[789]\.|LE1[123]\.|LE2[34567][123456789]\.|KG3.\|KH|HN110\.5|HN120\.|HN14[0123456789]\.|HN15[037]\.|HN16[03]\.|HN17[0235]\.|HN18[34]\.|HN190\.|HN203\.|HN210\.|HN21[789]\.|HN220\.|HN253\.|HN26[013]\.|HN270\.|HN28[23]\.|HN30[01234567]\.|HN31[037]\.|HN320\.|HN33[0123]\.|HN34[01]\.|HN350\.|HN37[01]\.',
      'Mathematics' => '^QA',
      'Nursing' => '^RT|RA',
      'Philosophy' => '^B\d|B[CDHJ]',
      'Physics & Astronomy' => '^Q[BC]',
      'Political Science' => '^J\d|J[ACFKLNQV]|political|politics',
      'Psychology' => '^BF|RC|[Pp]sych|[Mm]ental|[Bb]ehavioral|[Nn]euro',
      'Religious Studies' => '^B[LMPQRSTVX]|religion|religious|spiritual|[Cc]hurch|Protestant|Reformation',
      'Sociology' => '^H[MNQSTVX]|H\d|RA|[Ss]ociolog',
      'Statistics' => '^HA',
      'Theatre Arts' => '^[pP]lays|[dD]rama|[Tt]heatre|PN3[012]\d\d|PN2\d\d\d|PN1[568]\d\d\DPN6120\.\D|PN611[0-9]\.[5-9]\D|GT1741|GV1[5-7]\d\d\D/',
      'Womens Studies' => '^[wW]om.n.|[fF]emale|[fF]eminist |[lL]esbian.|[gG]ender');

	// get the subcategory names and allow access via index
	$subName = array_keys($subcategory);

$current_week = get_week_range(date('d'), date('m'), date('Y'));

  $wkTitle = array('TITLE', 'AUTHOR', 'CALL NUMBER');

if($view !== null)
{
	$dbh = new PDO('mysql:host=127.0.0.1;port=3310;dbname=new_titles', 'webuser01', 'w3bpas2');

	$sql = "SELECT * FROM acq_weekly WHERE acq_start = '" . str_replace('-', '', $current_week['first']) . "' AND acq_end = '" . str_replace('-', '', $current_week['last']) . "'";

	if($view == 0) // TITLE
	{
		$sql .= " ORDER BY acq_title ASC";
	}

	// if($view == 1) // AUTHOR -             ####### SORT BUG?
	// {
		// $sql .= " ORDER BY acq_author";
	// }

	if($view == 1) // CALL NUMBER
	{
		$sql .= " ORDER BY acq_call ASC";
	}

	$sth = $dbh->prepare($sql);
	$sth->execute();

	$wdata = $sth->fetchAll();

	echo $open;

	echo "\t\t<title><![CDATA[New weekly acquisitions titles sorted by " . $wkTitle[$view] . "]]></title>\n";
	echo "\t\t<description><![CDATA[" .  $wkTitle[$view] . "]]></description>\n";
	echo "\t\t<lastBuildDate><![CDATA[" . date("D, d M Y H:i:s O") . "]]></lastBuildDate>\n";
	echo "\t\t<pubDate><![CDATA[" . date("D, d M Y H:i:s O") . "]]></pubDate>\n";

	if($view == 0) // TITLE
	{
		foreach($wdata as $row)
		{
			echo "\t\t<item>\n";
			echo "\t\t\t<title><![CDATA[" . $row['acq_title'] . "]]></title>\n";
			echo "\t\t\t<description><![CDATA[" . htmlentities($row['acq_imprint']) . "]]></description>\n";
			echo "\t\t\t<link><![CDATA[http://ibisweb.miami.edu/search/o?SEARCH=" . $row['acq_oclc'] . "]]></link>\n";
			echo "\t\t\t<guid><![CDATA[http://ibisweb.miami.edu/search/o?SEARCH=" . $row['acq_oclc'] . "]]></guid>\n";
			echo "\t\t</item>";
		}

		$dbh = null;
	}

	if($view == 1) // AUTHOR
	{
		foreach($wdata as $row)
		{
			echo "\t\t<item>\n";
			echo "\t\t\t<title><![CDATA[" . htmlentities($row['acq_author']) . "]]></title>\n";
			echo "\t\t\t<description><![CDATA[" . htmlentities($row['acq_imprint']) . "]]></description>\n";
			echo "\t\t\t<link><![CDATA[http://ibisweb.miami.edu/search/o?SEARCH=" . $row['acq_oclc'] . "]]></link>\n";
			echo "\t\t\t<guid><![CDATA[http://ibisweb.miami.edu/search/o?SEARCH=" . $row['acq_oclc'] . "]]></guid>\n";
			echo "\t\t</item>";
		}

		$dbh = null;
	}

	if($view == 2) // CALL NUMBER
	{
		foreach($wdata as $row)
		{
			echo "\t\t<item>\n";
			echo "\t\t\t<title><![CDATA[" . htmlentities($row['acq_call']) . "]]></title>\n";
			echo "\t\t\t<description><![CDATA[" . htmlentities($row['acq_imprint']) . "]]></description>\n";
			echo "\t\t\t<link><![CDATA[http://ibisweb.miami.edu/search/o?SEARCH=" . $row['acq_oclc'] . "]]></link>\n";
			echo "\t\t\t<guid><![CDATA[http://ibisweb.miami.edu/search/o?SEARCH=" . $row['acq_oclc'] . "]]></guid>\n";
			echo "\t\t</item>";
		}

		$dbh = null;
	}

	if(empty($wdata)) // NO RECORDS FOR THE WEEK
	{
			echo "\t\t<item>\n";
			echo "\t\t\t<title><![CDATA[" . 'No additions for this week' . "]]></title>\n";
			echo "\t\t\t<description><![CDATA[" . 'Please check back next week!' . "]]></description>\n";
			echo "\t\t\t<link><![CDATA[http://library.miami.edu]]></link>\n";
			echo "\t\t\t<guid><![CDATA[http://ibisweb.miami.edu]]></guid>\n";
			echo "\t\t</item>";
	}

}


// if view is null then its monthly
  if ($view == null)
  {
    $dbh = new PDO('mysql:host=127.0.0.1;port=3310;dbname=new_titles', 'webuser01', 'w3bpas2');

    $key = 'acq_author';
    $sort = 'ASC';

    $sql = "SELECT * FROM acq_monthly WHERE acq_start = $date_start AND acq_end = $date_end AND acq_searchId = 0";

    switch ((int) $acq) {
      case 0:
        // Master List
        $sql = "SELECT * FROM acq_monthly WHERE acq_start = $date_start AND acq_end = $date_end AND acq_searchId = 0";

        if ($sub !== null) {
          switch ((int) $sub) {
            case 0:
              $sql .= getSub($subcategory, 'Africana', $sql) . " ORDER BY $key $sort";
              break;
            case 1:
              $sql .= getSub($subcategory, 'Anthropology', $sql) . " ORDER BY $key $sort";
              break;
            case 2:
              $sql .= getSub($subcategory, 'Architecture', $sql) . " ORDER BY $key $sort";
              break;
            case 3:
              $sql .= getSub($subcategory, 'Art & Art History', $sql) . " ORDER BY $key $sort";
              break;
            case 4:
              $sql .= getSub($subcategory, 'Biology', $sql) . " ORDER BY $key $sort";
              break;
            case 5:
              $sql .= getSub($subcategory, 'Business', $sql) . " ORDER BY $key $sort";
              break;
            case 6:
              $sql .= getSub($subcategory, 'Chemistry', $sql) . " ORDER BY $key $sort";
              break;
            case 7:
              $sql .= getSub($subcategory, 'Classics', $sql) . " ORDER BY $key $sort";
              break;
            case 8:
              $sql .= getSub($subcategory, 'Communication', $sql) . " ORDER BY $key $sort";
              break;
            case 9:
              $sql .= getSub($subcategory, 'Computer Science', $sql) . " ORDER BY $key $sort";
              break;
            case 10:
              $sql .= getSub($subcategory, 'Economics', $sql) . " ORDER BY $key $sort";
              break;
            case 11:
              $sql .= getSub($subcategory, 'Education', $sql) . " ORDER BY $key $sort";
              break;
            case 12:
              $sql .= getSub($subcategory, 'Engineering', $sql) . " ORDER BY $key $sort";
              break;
            case 13:
              $sql .= getSub($subcategory, 'English Literature', $sql) . " ORDER BY $key $sort";
              break;
            case 14:
              $sql .= getSub($subcategory, 'Environmental Science', $sql) . " ORDER BY $key $sort";
              break;
            case 15:
              $sql .= getSub($subcategory, 'Foreign Languages & Literature', $sql) . " ORDER BY $key $sort";
              break;
            case 16:
              $sql .= getSub($subcategory, 'Geography & Regional Studies', $sql) . " ORDER BY $key $sort";
              break;
            case 17:
              $sql .= getSub($subcategory, 'Geology', $sql) . " ORDER BY $key $sort";
              break;
            case 18:
              $sql .= getSub($subcategory, 'Health Science', $sql) . " ORDER BY $key $sort";
              break;
            case 19:
              $sql .= getSub($subcategory, 'History', $sql) . " ORDER BY $key $sort";
              break;
            case 20:
              $sql .= getSub($subcategory, 'International Studies', $sql) . " ORDER BY $key $sort";
              break;
            case 21:
              $sql .= getSub($subcategory, 'Judaic Studies', $sql) . " ORDER BY $key $sort";
              break;
            case 22:
              $sql .= getSub($subcategory, 'Latin American Studies', $sql) . " ORDER BY $key $sort";
              break;
            case 23:
              $sql .= getSub($subcategory, 'Mathematics', $sql) . " ORDER BY $key $sort";
              break;
            case 24:
              $sql .= getSub($subcategory, 'Nursing', $sql) . " ORDER BY $key $sort";
              break;
            case 25:
              $sql .= getSub($subcategory, 'Philosophy', $sql) . " ORDER BY $key $sort";
              break;
            case 26:
              $sql .= getSub($subcategory, 'Physics & Astronomy', $sql) . " ORDER BY $key $sort";
              break;
            case 27:
              $sql .= getSub($subcategory, 'Political Science', $sql) . " ORDER BY $key $sort";
              break;
            case 28:
              $sql .= getSub($subcategory, 'Psychology', $sql) . " ORDER BY $key $sort";
              break;
            case 29:
              $sql .= getSub($subcategory, 'Religious Studies', $sql) . " ORDER BY $key $sort";
              break;
            case 30:
              $sql .= getSub($subcategory, 'Sociology', $sql) . " ORDER BY $key $sort";
              break;
            case 31:
              $sql .= getSub($subcategory, 'Statistics', $sql) . " ORDER BY $key $sort";
              break;
            case 32:
              $sql .= getSub($subcategory, 'Theatre Arts', $sql) . " ORDER BY $key $sort";
              break;
            case 33:
              $sql .= getSub($subcategory, 'Womens Studies', $sql) . " ORDER BY $key $sort";
              break;
          }
        }
        break;
      case 1:
        // Architecture Library
        $sql .= " AND acq_location = 'a' OR acq_location = 'aad'";
        break;
      case 2:
        // Contemporary Fiction
        $sql .= " AND acq_location = 'gbp'";
        break;
      case 3:
        // Cuban Heritage Collection
        $sql .= " AND acq_location = 'c'";
        break;
      case 4:
        // Government Documents
        $sql .= " AND acq_location = 'gg' AND acq_location != 'wwwg'";
        break;
      case 5:
        // Graphic Novels
        $sql .= " AND acq_location = 'gbg'";
        break;
      case 6:
        // Juvenile Collection
        $sql .= " AND acq_location = 'gbj'";
        break;
      case 7:
        // Marine Library
        $sql .= " AND acq_location = 'r' AND acq_location != 'rbb'";
        break;
      case 8:
        // Reference Collection
        $sql .= " AND acq_location = 'gr'";
        break;
      case 9:
        // Special Collections
        $sql .= " AND acq_location = 's'";
        break;
      case 10:
        // Video & DVD Collection
        $sql .= " AND acq_location = 'gad' AND acq_location != 'gav'";
        break;
      case 11:
        // Music Library - Books, Dissertations, Theses
        $sql .= " AND acq_material ='a' AND acq_itemLoc = 'eb' OR acq_itemLoc = 'ebo' OR acq_itemLoc = 'er'";
        break;
      case 12:
        // Music Library - Jazz CD's

        # word boundries captures whole words IE art can match 'quartet', 'bart' w/ word boundries only 'art' is a match
        $sql .= " AND (acq_material ='h' OR acq_material ='j') AND (acq_itemLoc = 'eac' OR acq_itemLoc = 'ev3o') AND (acq_subject REGEXP '[[:<:]]jazz[[:>:]]|[[:<:]]blues[:>:]]|[[:<:]]big band[[:>:]]')";

        if ($t !== NULL) {
          $sql .= " ORDER BY acq_title ASC";
        } else {
          $sql .= " ORDER BY acq_author ASC";
        }

        break;
      case 13:
        // Music Library - Scores
        $sql .= " AND acq_location = 'e' AND acq_material = 'c'";

        if ($t !== NULL) {
          $sql .= " ORDER BY acq_title ASC";
        } else {
          $sql .= " ORDER BY acq_author ASC";
        }

        break;
      case 14:
        // Music Library - Sound Recordings
        $sql .= " AND acq_location = 'e' AND (acq_material = 'd' OR acq_material = 'g' OR acq_material = 'h' OR acq_material = 'i' OR acq_material = 'j' OR acq_material = 'v')";

        if ($t !== NULL) {
          $sql .= " ORDER BY acq_title ASC";
        } else {
          $sql .= " ORDER BY acq_author ASC";
        }
        break;

      default:
        $sql = null;
        break;
    }


	if (!is_null($sql))
	{
		$sth = $dbh->prepare($sql);
		$sth->execute();
		$row = $sth->fetchAll();

		if ($sub == null)
		{
			// main category
			$title = $category[(int)$acq];
		}
		elseif($sub !== null)
		{
			// New Acquisitions + subcategory
			$title = 'New Acquisitions - ' . $subName[(int)$sub];
		}

		if (!empty($row))
		{
			echo $open;

			echo "\t\t<title><![CDATA[" . $title . "]]></title>\n";
			echo "\t\t<description><![CDATA[" . $title . "]]></description>\n";
			echo "\t\t<lastBuildDate><![CDATA[" . date('Y-m-d h:i:sA') . "]]></lastBuildDate>\n";
			echo "\t\t<pubDate><![CDATA[" . date('Y-m-d h:i:sA') . "]]></pubDate>\n";

			foreach ($row as $field)
			{
				if ($field['acq_oclc'] !== null && $t == null)
				{
					echo "\t\t<item>\n";
					echo "\t\t\t<title><![CDATA[" . htmlentities($field['acq_title']) . "]]></title>\n";
					echo "\t\t\t<description><![CDATA[" . htmlentities($field['acq_imprint']) . "]]></description>\n";
					echo "\t\t\t<link><![CDATA[http://ibisweb.miami.edu/search/o?SEARCH=" . $field['acq_oclc'] . "]]></link>\n";
					echo "\t\t</item>";
				}

				if ($field['acq_oclc'] !== null && $t == 1)
				{
					echo "\t\t<item>\n";
					echo "\t\t\t<title><![CDATA[" . htmlentities($field['acq_title']) . "]]></title>\n";
					echo "\t\t\t<description><![CDATA[" . htmlentities($field['acq_imprint']) . "]]></description>\n";
					echo "\t\t\t<link><![CDATA[http://ibisweb.miami.edu/search/o?SEARCH=" . $field['acq_oclc'] . "]]></link>\n";
					echo "\t\t</item>";
           		}

			}
		}
		elseif (empty($row)) // NO RECORDS FOR MONTH
		{
			echo $open;

			echo "\t\t<title><![CDATA[" . $title . "]]></title>\n";
			echo "\t\t<description><![CDATA[" . $title . "]]></description>\n";
			echo "\t\t<lastBuildDate><![CDATA[" . date('Y-m-d h:i:sA') . "]]></lastBuildDate>\n";
			echo "\t\t<pubDate><![CDATA[" . date('Y-m-d h:i:sA') . "]]></pubDate>\n";

			echo "\t\t<item>\n";
			echo "\t\t\t<title><![CDATA[" . 'No additions for this month' . "]]></title>\n";
			echo "\t\t\t<description><![CDATA[" . 'Please check back next month!' . "]]></description>\n";
			echo "\t\t\t<link><![CDATA[http://library.miami.edu]]></link>\n";
			echo "\t\t</item>";
		}

		// close connection
		$dbh = null;
	}
	else
	{
		// close connection
		$dbh = null;
	}
}

$close = <<<XMLFOOT
	</channel>
</rss>
XMLFOOT;

echo $close;
?>
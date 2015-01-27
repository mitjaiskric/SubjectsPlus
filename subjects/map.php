<?php
// notice breaks style by inserting CDATA
error_reporting(1);

$lstrShelf = '';

//store call number in local variable
if (isset($_GET['call']))
{
	//remove all extra characters and only save the first valid section of the call
	//number. A452. 5f. T3fd -> A452.

	$callFull = strtoupper($_GET['call']);
	$callFull = preg_replace('/BROWSE SHELF|Browse Shelf/', '', $callFull);
	$callFull = (trim(rawurldecode($callFull)));

	$callFull = preg_replace('/[^(\x20-\x7F)]*/', '', $callFull);

	$lobjSplit = explode('.', $callFull);

	$call = trim($lobjSplit[0]);

	if(strstr($call, ' '))
	{
		$lobjSplit = explode(' ', $call);

		$call = trim($lobjSplit[0]);
	}

}

//store location in local variable
if (isset($_GET['l']))
{
	$location = $_GET['l'];

	$location = preg_replace("/^\pZ+|\pZ+$/u", "", rawurldecode($location));
}


// show hide rows - currently not used
if (isset($_GET['h']))
{
	$h = $_GET['h'];
}

// isbn - currently not used
if (isset($_GET['i']))
{
	$isbn = $_GET['i'];
	$isbn = preg_match('/^([\d]+)/', $isbn, $match);
	$isbn = $match[0];
}

// map location to floor
function getFloor($dbh, $location)
{
	//if the location is anywhere in the CHC (Cuban Heritage Collection),
	//replace location with 'CHC'
	if(strpos($location, 'CHC') === 0)
	{
		$location = 'CHC';
	}

	//if the location is anywhere in the Special Collections
	//replace location with 'Special Collections'
	if(strstr($location, 'Spec Coll'))
	{
		$location = 'Special Collections';
	}

	//query SQL database to get floor that location is in
	$sql = 'SELECT `floor` FROM location WHERE `locationName` LIKE "%' . trim($location) . '%"';

	$sth = $dbh->prepare($sql);
	$sth->execute();
	$floor = $sth->fetchColumn();

	return $floor;
}

/**
 * getDescription() - return description of location from databse
 * when location requires sections and not rows as in the stacks.
 *
 * @param resource $lobjDB
 * @param string $location
 * @return string
 */
function getDescription($lobjDB, $location)
{
	//if the location is anywhere in the CHC (Cuban Heritage Collection),
	//replace location with 'CHC'
	if(strpos($location, 'CHC') === 0)
	{
		$location = 'CHC';
	}

	//if the location is anywhere in the Special Collections
	//replace location with 'Special Collections'
	if(strstr($location, 'Spec Coll'))
	{
		$location = 'Special Collections';
	}

	$lstrQuery = 'SELECT `sectionDesc` FROM location WHERE `locationName` LIKE "%' . trim($location) . '%"';

	$lobjStatementH = $lobjDB->prepare($lstrQuery);
	$lobjStatementH->execute();
	$lstrDescription = $lobjStatementH->fetchColumn();

	return $lstrDescription;
}

// break call number into two parts
function splitCallNumber($callNumber)
{
	$matches = array();
	preg_match('/^([A-Za-z]+)(\d+)$/', $callNumber, $matches);
	// $matches[0] contains whole $callNumber
	return array($matches[1], $matches[2]);
}

// pad call number to allow correct comparison
// rightpad characters with spaces and leftpad numbers with zeros
function padCallNumbers(array $callNumbers)
{
	$characters = array();
	$length 	= 0;
	$numbers	= array();
	$result 	= array();

	foreach ($callNumbers as $value)
	{
		list($character, $number) = splitCallNumber($value);
		$characters[] 			= $character;
		$numbers[]				= $number;
	}

	// Pad the characters with spaces so that they're both the same length
	$length = max(array_map('strlen', $characters));

	foreach ($characters as &$currentCharacters)
	{
		$currentCharacters = str_pad($currentCharacters, $length, ' ', STR_PAD_RIGHT);
	}

	// Left-pad the numbers with 0s so that they're both the same length
	$length = max(array_map('strlen', $numbers));

	foreach ($numbers as &$currentNumbers)
	{
		$currentNumbers = str_pad($currentNumbers, $length, '0', STR_PAD_LEFT);
	}

	foreach ($callNumbers as $index => $value)
	{
		$result[] = $characters[$index] . $numbers[$index];
	}

	return $result;
}

// check to see if call number to see if it falls in between the row after setting upper/lower boundaries
function isCallNumberBetween($callNumber, $A, $B, $lboolTop, $lboolHalf, $lboolOverBook)
{
	list($lower, $upper, $callNumber) = setBounds($A, $B, $callNumber, $lboolTop, $lboolHalf, $lboolOverBook);

	if (!empty($lower) && !empty($upper))
	{
		//check whether the range goes around (i.e. TT835 to B1)
		if((ord($upper) - ord($lower)) >= 0)
		{
			//if the range doesn't go around, must meet both conditions
			return (($lower <= $callNumber) && ($callNumber <= $upper));
		}else{
			//it the range does go around, must meet only one condition
			return (($lower <= $callNumber) || ($callNumber <= $upper));
		}
	}
	else
	{
		return null;
	}
}

// set upper and lower limits/boundaries for the row
function setBounds($columnA, $columnB, $callNumber, $lboolTop, $lboolHalf, $lboolOverBook)
{
	//if shelf is half oversized and half regular. Set Bounds accordingly
	if($lboolHalf)
	{
		if($lboolOverBook)
		{
			if($lboolTop)
			{
				list($A, $B, $callNumber) 	= padCallNumbers(array($columnA, 'A0', $callNumber));
			}else
			{
				list($A, $B, $callNumber) 	= padCallNumbers(array('A0', $columnB, $callNumber));
			}
		}else
		{
			if($lboolTop)
			{
				list($A, $B, $callNumber) 	= padCallNumbers(array('Z9999', $columnB, $callNumber));
			}else
			{
				list($A, $B, $callNumber) 	= padCallNumbers(array($columnA, 'Z9999', $callNumber));
			}
		}
	}else
	{
		list($A, $B, $callNumber) 	= padCallNumbers(array($columnA, $columnB, $callNumber));
	}

	if(!$lboolTop)
	{
		// A lower B upper
		return array($A, $B, $callNumber);
	}
	elseif($lboolTop)
	{
		//  B lower A upper
		return array($B, $A, $callNumber);
	}
	elseif($A == $B)
	{
		// set A lower B upper
		return array($A, $B, $callNumber);
	}
}

/**
 * isBookOversized() - checks whether the book is in the Oversized section
 *
 * @param string $lstrLocation
 * @return boolean
 */
function isBookOversized($lstrLocation)
{
	if(strstr($lstrLocation, 'Oversize') || stristr($lstrLocation, 'Folio'))
	{
		return true;
	}

	return false;
}

/**
 * isStacks() - determines if the location is from stacks
 *
 * @param string $lstrLocation
 * @return boolean
 */
function isStacks($lstrLocation)
{
	if($lstrLocation == 'stacks')
	{
		return true;
	}else{
		return false;
	}
}

/**
 * isReferDesk() - determines if location is reference stack
 *
 * @param string $lstrLocation
 * @return boolean
 */
function isReferDesk($lstrLocation)
{
	if($lstrLocation == 'Reference Desk')
	{
		return true;
	}else{
		return false;
	}
}

/**
 * changeForOtherMezzanine() - if it is one ofthe other sections for mezzanine
 * change location variable to invoke the other section
 *
 * @param string $lstrLocation
 * @return void
 */
function changeForOtherMezzanine(&$lstrLocation)
{
	$lobjMezzArray = array('Richter Mezzanine', 'Richter Mezz. Oversize', 'Richter Mezzanine - Reference', 'Richter Mezz. - IGO', 'Richter Mezzanine Books');

	if(in_array($lstrLocation, $lobjMezzArray))
	{
		$lstrLocation = 'Mezzanine Other';
	}
}

//setup username and password for database
$user = 'librarywebu';
$psswd = '7cogB1iM';

//edit to work with localhost
if($_SERVER['HTTP_HOST'] == '127.0.0.1' || $_SERVER['HTTP_HOST'] == '129.171.178.49:8020')
{
	$dbh = new PDO('mysql:host=127.0.0.1;port=3306;dbname=svg', $user, $psswd);
}else{
	$dbh = new PDO('mysql:host=127.0.0.1;port=3308;dbname=svg', $user, $psswd);
}

//get the floor that location is in
$floor = getFloor($dbh, $location);

//get all possible rows or sections for respective floor
$sql = 'Select * FROM shelves WHERE `floor`= \'' . $floor . '\' ORDER BY `rows` DESC';

$sth = $dbh->prepare($sql);
$sth->execute();
$sdata = $sth->fetchAll();

//initialize variable that states whether stacks or not (default stacks)
$isStacks = true;

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="content-type" content="text/html;charset=utf-8" />
		<meta name = "viewport" content = "width=device-width, height=1400, initial-scale=1, maximum-scale=1.5, minimum-scale=0.01">
		<title>map</title>

		<link rel="stylesheet" type="text/css" href="_src/css/svg.css" />

		<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.1/jquery.min.js"></script>
		<script type="text/javascript">

			var _gaq = _gaq || [];
			_gaq.push(['_setAccount', 'UA-15217512-1']);
			_gaq.push(['_trackPageview']);

			(function() {
				var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
				ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
				var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
			})();

		</script>
		<script type="text/javascript">
			/*
			ctx: context
			x: Upper left corner's X coordinate
			y: Upper left corner's Y coordinate
			w: Rectangle's width
			h: Rectangle's height
			r: Corner radius
			*/
			function fillRoundedRect(ctx, x, y, w, h, r)
			{
				ctx.beginPath();

				ctx.moveTo(x+r, y);

				ctx.lineTo(x+w-r, y);

				ctx.quadraticCurveTo(x+w, y, x+w, y+r);

				ctx.lineTo(x+w, y+h-r);

				ctx.quadraticCurveTo(x+w, y+h, x+w-r, y+h);

				ctx.lineTo(x+r, y+h);

				ctx.quadraticCurveTo(x, y+h, x, y+h-r);

				ctx.lineTo(x, y+r);

				ctx.quadraticCurveTo(x, y, x+r, y);

			 	ctx.fillStyle = '#F88017';
				ctx.globalAlpha = 0.72;

				ctx.fill();

			}

			jQuery(document).ready(function()
			{
				$(window).load(function(){
				var floorimage = new Image();

				floorimage.onload = function(){
					jQuery('#canvas1').attr('width',floorimage.width);
					jQuery('#canvas1').attr('height',floorimage.height);
					jQuery('#canvas2').attr('width',floorimage.width);
					jQuery('#canvas2').attr('height',floorimage.height);

					var ctx1 = jQuery('#canvas1')[0].getContext("2d");
					ctx1.drawImage(floorimage,0,0);
					var ctx2 = jQuery('#canvas2')[0].getContext("2d");
			<?php
//foreach possible row or section, test criterias to determine whether that row
//or section should be highlighted 

foreach($sdata as $field)
{

	//determine whether this location is in stacks
	if(isStacks($field['location']))
	{
		//store properties in local variables
		$lboolHalf = (boolean) $field['half'];

		$lboolOverShelf = (boolean) $field['oversized'];

		$lboolOverBook = isBookOversized($_GET['l']);

		$inRange = isCallNumberBetween($call, str_replace(' ', '', $field['callNumberA']), str_replace(' ', '', $field['callNumberB']), (boolean) $field['top'], $lboolHalf, $lboolOverBook);

		// generate jquery if row or section meets criterias
		if (($inRange && $lboolOverShelf && $lboolOverBook) || ($inRange && !$lboolOverShelf && !$lboolOverBook) || ($inRange && $lboolHalf))
		{
			//append rows that match in variable
			$lstrShelf .= $field['rows'] . ',';

			//echo all javascript to highlight row or section
			echo "\n\t\t\t\tfillRoundedRect(ctx2,{$field['positionx']},{$field['positiony']},{$field['width']},{$field['height']},4);";

			echo "\n\n\t\t\t\tvar Output1 = ('<div id=\"r{$field['rows']}\" style=\"z-index: 5; position: absolute; top: {$field['positiony']}px; left: {$field['positionx']}px; width:"
					. " {$field['width']}px; height: {$field['height']}px;background-color: orange; opacity: 0;\">&nbsp;</div>');";

			echo "\n\n\t\t\t\tvar Output2 = Output1 + ('<div id=\"r{$field['rows']}pu\" class=\"rowpup\" style=\"display: none; position: absolute; top: {$field['positiony']}px; left:"
					. "{$field['positionx']}px;\"><span style=\"\">"
					. "Row {$field['rows']}</span></div>');";

			echo "\n\n\t\t\t\tjQuery(Output2).appendTo('#canvascontainer');";

			//make sure popup is not above page
			$lintYposition = ($field['positiony'] - 35);

			//adjust position if y position is above page
			if($lintYposition < 0)
			{
				$lintYposition = $field['positiony'] + 5 + $field['height'];
			}


			echo "\n\n\t\t\t\tjQuery('#r{$field['rows']}').on('click', function(){\n\n\t\t\t\t\t" . "$('.rowpup').hide();\n\n\t\t\t\t\tjQuery('#r{$field['rows']}pu').attr('style','position: absolute; top: " .
				$lintYposition . "px; left: " . ($field['positionx'] - ((110 - $field['width'])/2)) . "px; width: 110px; height: 30px;')"
				. ";\n\n\t\t\t\t});";
		}
	}else{
		//not in stacks
		$isStacks = false;

		//determine whether location contains only a reference desk
		$isReferenceDesk = isReferDesk($field['location']);

		//run location through function so that if it is in Mezzanine, the location
		//will be edited for Mezzanine floor
		changeForOtherMezzanine($location);

		//if the section's location matched location of book and not in reference
		//desk, write javascript to highlight section
		if(stristr($location, $field['location']) && !$isReferenceDesk)
		{
			//get section description
			$lstrShelf = getDescription($dbh, $location) . ' ';

			echo "\n\t\t\t\tfillRoundedRect(ctx2,{$field['positionx']},{$field['positiony']},{$field['width']},{$field['height']},4);";

			echo "\n\n\t\t\t\tvar Output1 = ('<div id=\"s{$field['rows']}\" style=\"z-index: 5; position: absolute; top: {$field['positiony']}px; left: {$field['positionx']}px; width:"
					. " {$field['width']}px; height: {$field['height']}px;background-color: orange; opacity: 0;\">&nbsp;</div>');";

			echo "\n\n\t\t\t\tvar Output2 = Output1 + ('<div id=\"s{$field['rows']}pu\" class=\"sectionpup\" style=\"display: none; position: absolute; top: {$field['positiony']}px; left:"
					. "{$field['positionx']}px;\"><span style=\"\">"
					. "Check this Section</span></div>');";

			echo "\n\n\t\t\t\tjQuery(Output2).appendTo('#canvascontainer');";

			//make sure popup is not above page
			$lintYposition = ($field['positiony'] - 35);

			//adjust position if y position is above page
			if($lintYposition < 0)
			{
				$lintYposition = $field['positiony'] + 5 + $field['height'];
			}

			//make sure popup is not too left
			$lintXposition = ($field['positionx'] - ((180 - $field['width'])/2));

			//adjust position if x position is left of page
			if($lintXposition < 0)
			{
				$lintXposition = 0;
			}


			echo "\n\n\t\t\t\tjQuery('#s{$field['rows']}').on('click', function(){\n\n\t\t\t\t\t" . "$('.sectionpup').hide();\n\n\t\t\t\t\tjQuery('#s{$field['rows']}pu').attr('style','position: absolute; top: " .
				$lintYposition . "px; left: " . $lintXposition . "px; width: 180px; height: 30px;')"
				. ";\n\n\t\t\t\t});";

			echo "\n\n\t\t\t\tjQuery('#s{$field['rows']}').click();";
		}

		//if it is a reference desk section
		if($isReferenceDesk)
		{
			//get section description
			$lstrShelf = getDescription($dbh, $location) . ' ';

			echo "\n\t\t\t\tfillRoundedRect(ctx2,{$field['positionx']},{$field['positiony']},{$field['width']},{$field['height']},4);";

			echo "\n\n\t\t\t\tvar Output1 = ('<div id=\"s{$field['rows']}\" style=\"z-index: 5; position: absolute; top: {$field['positiony']}px; left: {$field['positionx']}px; width:"
					. " {$field['width']}px; height: {$field['height']}px;background-color: orange; opacity: 0;\">&nbsp;</div>');";

			echo "\n\n\t\t\t\tvar Output2 = Output1 + ('<div id=\"s{$field['rows']}pu\" class=\"askpup\" style=\"display: none; position: absolute; top: {$field['positiony']}px; left:"
					. "{$field['positionx']}px;\"><span style=\"\">"
					. "Ask here</span></div>');";

			echo "\n\n\t\t\t\tjQuery(Output2).appendTo('#canvascontainer');";

			//make sure popup is not above page
			$lintYposition = ($field['positiony'] - 35);

			//adjust position if y position is above page
			if($lintYposition < 0)
			{
				$lintYposition = $field['positiony'] + 5 + $field['height'];
			}

			//make sure popup is not too left
			$lintXposition = ($field['positionx'] - ((110 - $field['width'])/2));

			//adjust position if x position is left of page
			if($lintXposition < 0)
			{
				$lintXposition = 0;
			}


			echo "\n\n\t\t\t\tjQuery('#s{$field['rows']}').on('click', function(){\n\n\t\t\t\t\t" . "$('.sectionpup').hide();\n\n\t\t\t\t\tjQuery('#s{$field['rows']}pu').attr('style','position: absolute; top: " .
				$lintYposition . "px; left: " . $lintXposition . "px; width: 110px; height: 30px;')"
				. ";\n\n\t\t\t\t});";

			echo "\n\n\t\t\t\tjQuery('#s{$field['rows']}').click();";
		}
	}

}
//close javscript function and display correct image
echo "\n\t\t\t\t}";
echo "\n\n\t\tfloorimage.src = '_src/{$floor}_floor.png';\n\n";
?>
		//if mobile device
		if(jQuery(window).width() < 800)
		{
			//display Back button
			jQuery(".backbuttoncontainer").css('display', 'block');

			//zoom out to show more of map in mobile
            $("html").css("-moz-transform", "Scale(" + 0.5 + ")");
            $("html").css("-moz-transform-origin", "0 0");
			$("html").css("-o-transform", "Scale(" + 0.5 + ")");
            $("html").css("-o-transform-origin", "0 0");
			$("html").css("-ms-transform", "Scale(" + 0.5 + ")");
            $("html").css("-ms-transform-origin", "0 0");
			$("html").css("-webkit-transform", "Scale(" + 0.5 + ")");
            $("html").css("-webkit-transform-origin", "0 0");
		}

		});});

		</script>

	</head>

<body>
<div id="canvascontainer">
<canvas id="canvas1" style="position: absolute; top: 0px; left: 0px;"></canvas>
<canvas id="canvas2" style="position: absolute; top: 0px; left: 0px;"></canvas>
</div>
<?php

$opt = array($dbh, $floor, $location);
$dbh = null;

// strip invisible unicode characters from URL
$callFull = preg_replace("/^\pZ+|\pZ+$/u", "", rawurldecode($callFull));

// replace spaces with %20
$callSearch = preg_replace('/\s/', "%20", $callFull);

// search for call number
$url = 'http://catalog.library.miami.edu/search/c?' . $callSearch;

$userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_3) AppleWebKit/534.55.3 (KHTML, like Gecko) Version/5.1.5 Safari/534.55.3';

//setup curl to gather data based on call number
$ch = curl_init();
$timeout = 30;

curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

// returned call number search data
$data = curl_exec($ch);

curl_close($ch);

// find row containing title from result data and put in $title
preg_match('/<td class="bibInfoData">\s+<strong>(.*?)<\/strong><\/td><\/tr>/', $data, $title);

$lstrTitle = $title[0];

//if Title is too long, cut it down and add ellipsis
if(strlen($lstrTitle) > 90)
{
	$lstrEditedTitle = preg_replace('/<td class="bibInfoData">\s+<strong>(.*?)<\/strong><\/td><\/tr>/', '${1}',$lstrTitle);

	$lstrEditedTitle = substr($lstrEditedTitle, 0, 85);
	$lstrEditedTitle .= '...';

	$lstrTitle = '<td class="bibInfoData"><strong>' . $lstrEditedTitle . '</strong></td></tr>';
}

//remove last character in shelf description
$lstrShelf = substr_replace($lstrShelf ,"",-1);

// create div containing floor number, call number, title, and location
if($isStacks)
{
	//if info div is for row stacks
	$info  = '<div class="floor_map_floor">&nbsp;</div>
			  <div class="floor_map_callnum">' . $callFull . '</div>
			  <div class="floor_map_title" >' . strip_tags($lstrTitle) . '</div>
			  <div class="floor_map_location">' . $location . ' <span class="row_highlight">on row #' . $lstrShelf . '</span></div>';
}else{
	if($isReferenceDesk)
	{
		//if info div is for a reference desk
		$lstrShelf = trim($lstrShelf);

		$info  = '<div class="floor_map_floor">&nbsp;</div>
				  <div class="ns floor_map_callnum ">' . $callFull . '</div>
				  <div class="floor_map_title" >' . strip_tags($lstrTitle) . '</div>
				  <div class="floor_map_location">' . $location . ' <span class="row_highlight"> - Ask at the ' . $lstrShelf . '</span></div>';
	}else{
		//if info div is for a section
		$lstrShelf = trim($lstrShelf);

		//if blank say "in this Section"
		if($lstrShelf == '')
		{
			$lstrShelf = 'this';
		}

		$info  = '<div class="floor_map_floor">&nbsp;</div>
				  <div class="ns floor_map_callnum ">' . $callFull . '</div>
				  <div class="floor_map_title" >' . strip_tags($lstrTitle) . '</div>
				  <div class="floor_map_location">' . $location . ' <span class="row_highlight">in ' . $lstrShelf . ' Section</span></div>';
	}

}

?>
	<div id="top" class="floor_map">
		<?php echo $info; ?>
	</div>
	<div id="Back" class="backbuttoncontainer">
		<a href="javascript: history.back();">Back</a>
	</div>
</body>
</html>

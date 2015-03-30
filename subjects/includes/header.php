<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?php print $page_title; ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="Description" content="<?php if (isset($description)) {print $description;} ?>" />
<meta name="Keywords" content="<?php if (isset($keywords)) {print $keywords;} ?>" />
<meta name="Author" content="" />

<link type="text/css" media="screen" rel="stylesheet" href="<?php print $AssetPath; ?>css/shared/pure-min.css">
<link type="text/css" media="screen" rel="stylesheet" href="<?php print $AssetPath; ?>css/shared/grids-responsive-min.css">
<link type="text/css" media="screen" rel="stylesheet" href="<?php print $AssetPath; ?>css/public/default.css">
<link type="text/css" media="print" rel="stylesheet" href="<?php print $AssetPath; ?>css/public/print.css">

<?php 
// Load our jQuery libraries + some css
if (isset($use_jquery)) { print generatejQuery($use_jquery);
}

if (!isset ($noheadersearch)) { 
    
    $search_form = '
            <div class="autoC" id="autoC">
                <form id="sp_admin_search" class="pure-form" method="post" action="' . getSubjectsURL() . 'search.php">
                <input type="text" placeholder="Search" autocomplete="off" name="searchterm" size="" id="sp_search" class="ui-autocomplete-input autoC"><span role="status" aria-live="polite" class="ui-helper-hidden-accessible"></span>
                <input type="submit" alt="Išči" name="submitsearch" id="topsearch_button" class="pure-button pure-button-topsearch" value="Išči">
                </form>
            </div>    ';
} else {
    $search_form = '';
}

// We've got a variable for those who wish to keep the old styles
$v2styles = TRUE;
?>
</head>

<body>
<div id="wrap">

<div id="header"> 
    <div id="header_inner_wrap">
        <div class="pure-g">
            <div class="pure-u-1 pure-u-md-1-5">
                <a href="http://vodici.pef.uni-lj.si"><img src="<?php print $AssetPath; ?>images/public/logo.png" alt="Home Page" /></a>
                
            </div>
            <div class="pure-u-1 pure-u-md-4-5">
                <?php if (isset($v2styles)) { print "<h1>$page_title</h1>"; } ?>
				<!--PeF menu-->
				<br />
				<div id="headlinks"><a href="../">Vsi vodiči</a> | <a href="http://www.pef.uni-lj.si/knjiznica">Novice</a> | <a href="http://ucilnica.pef.uni-lj.si/course/index.php?categoryid=85">Knjižnica v Spletni učilnici</a> | <a href="../subjects/guide.php?subject=komentar">Komentar</a> | <a href="../subjects/guide.php?subject=pomoč">Pomoč</a> | <a href="../subjects/vodici.xml"><img src="../assets/images/icons/feed.png" alt="RSS" height="8" /></a>
				</div>
            </div>
        </div>
    </div>
</div> <!--end #header-->

<div class="wrapper-full">
    <div class="pure-g">
        <div class="pure-u-1">
            <?php if (!isset($v2styles)) { print "<h1>$page_title</h1>"; } ?>
            <div id="content_roof"></div> <!-- end #content_roof -->
            <div id="shadowkiller"></div> <!--end #shadowkiller-->
        
            <div id="body_inner_wrap">

<?php
/**
 *   @file video.php
 *   @brief Display the videos
 *
 *   @author adarby
 *   @date feb 2012
 */

use SubjectsPlus\Control\Querier;

include("../control/includes/config.php");
include("../control/includes/functions.php");
include("../control/includes/autoloader.php");

// If you have a theme set, but DON'T want to use it for this page, comment out the next line
if (isset($subjects_theme)  && $subjects_theme != "") { include("themes/$subjects_theme/search_results.php"); exit;}

$use_jquery = array("colorbox");

$page_title = _("Search Results");
$description = _("Search Results.");

// Intro text
$intro = "<p>Search Results</p>";
$display = "<br class=\"clear\" />";


// Clean up user submission
if (isset($_GET["video_id"])) {
  $extra_sql = "and video_id = '" . scrubData($_GET["video_id"], "integer") . "'";
}

if (isset($_GET["tag"])) {
  if (in_array($_GET["tag"], $all_vtags)) {
    $pretty_tag = ucfirst($_GET["tag"]);
    $extra_sql = "and vtags like '%" . $_GET["tag"] . "%'";
  }
}



////////////////////////////
// Now we are finally read to display the page
////////////////////////////

include("includes/header.php");
?>
<br />
<div class="pure-g">
<div class="pure-u-1 pure-u-md-2-3">
    <div class="pluslet">
        <div class="titlebar">
            <div class="titlebar_text"><?php print $page_title; ?></div>
        </div>
        <div class="pluslet_body">
            <br />
      <?php print $intro; ?>
      <br />
      <?php print $display; ?>
        </div>
    </div>
</div>
<div class="pure-u-1 pure-u-md-1-3">
    <div class="pluslet">
        <div class="titlebar">
            <div class="titlebar_text"></div>
        </div>
        <div class="pluslet_body"><p>Looking for movies to check out?  See <a href="">The Place Where We List Movies</a>.</p></div>
    </div>
    <!-- start pluslet -->
    <div class="pluslet">
        <div class="titlebar">
            <div class="titlebar_text"></div>
        </div>
        <div class="pluslet_body"></div>
    </div>
    <!-- end pluslet -->
    <br />

</div>
</div>
<br />

<?php
///////////////////////////
// Load footer file
///////////////////////////

include("includes/footer.php");
?>

<script type="text/javascript" language="javascript">
  $(document).ready(function(){


    // show db details
    $(".details_details").on("click", function() {
      
      $(this).parent().find(".list_bonus").toggle()
    });

    $(".ajax").colorbox({iframe:true, innerWidth:640, innerHeight:480});


    function stripeR(container) {
      $(".zebra").not(":hidden").filter(":even").addClass("evenrow");
      $(".zebra").not(":hidden").filter(":odd").addClass("oddrow");
    }

    function unStripeR () {
      $(".zebra").removeClass("evenrow");
      $(".zebra").removeClass("oddrow");
    }


  });
</script>
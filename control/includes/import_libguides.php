<?php  
header("Content-Type: text/plain");
error_reporting(1);
ini_set('display_errors', 1);
include('../includes/autoloader.php');
include('../includes/config.php');
include('../includes/functions.php');


use SubjectsPlus\Control\Querier;
use SubjectsPlus\Control\LibGuidesImport;





$libguides_importer = new LibGuidesImport;
//$is_imported = $libguides_importer->guide_dupe($_POST['libguide'][0]);

//echo $_POST['libguide'][0];


$libguides_importer->setGuideID($_POST['libguide']);

$libguides_xml = $libguides_importer->load_libguides_links_xml('libguides.xml');
$libguides_xml = $libguides_importer->load_libguides_xml('libguides.xml');

$libguides_importer->import_libguides($libguides_xml);











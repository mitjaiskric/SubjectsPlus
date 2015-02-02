<?php
/**
 * Created by PhpStorm.
 * User: robertsc
 * Date: 2/2/15
 * Time: 11:37 AM
 */
require_once("autoloader.php");
require_once("config.php");
use SubjectsPlus\Control\LibGuidesSubjectImport;

$guideImporter = new LibGuidesSubjectImport();
$guideImporter->run();
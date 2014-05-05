<?php
namespace SubjectsPlus\Control;

/**
 *   @file Search.php
 *   @brief Searches across the different content types in SubjectPlus (guides, pluslets, etc)
 *
 *   @author little9
 *   @date May Day 2014
 *   @todo
 */

use SubjectsPlus\Control\Querier;

class Search {



  private $_search;

  public function setSearch($_search) {
    $this->_search = $_search;
  }

  public function getSearch() {
    $db = new Querier;
    $quoted_search = $db->quote('%' . $this->_search . '%');
    return $quoted_search;
    
  }

  public function getResults() {

    $sql = "SELECT subject_id AS 'id', subject AS 'matching_text', description as 'additional_text', 'Subject Guide' as 'content_type' FROM subject 
WHERE description LIKE" . $this->getSearch() . "
OR subject LIKE " . $this->getSearch() . "
OR keywords LIKE " . $this->getSearch() . "
UNION 
SELECT pluslet_id AS 'id', title AS 'matching_text', body as 'additional_text', 'Pluslet' AS 'content_type' FROM pluslet 
WHERE title LIKE " . $this->getSearch() . "
OR body LIKE " . $this->getSearch() . "
UNION
SELECT faq_id AS 'id', question AS 'matching_text', answer as 'additional_text','FAQ' as 'content_type' FROM faq 
WHERE question LIKE " . $this->getSearch() . "
OR answer LIKE " . $this->getSearch() . "
OR keywords LIKE " . $this->getSearch() . "
UNION
SELECT talkback_id AS 'id', question AS 'matching_text' , answer as 'additional_text', 'Talkback' as 'content_type' FROM talkback 
WHERE question LIKE " . $this->getSearch() . "
OR answer LIKE " . $this->getSearch() . "
UNION
SELECT staff_id AS 'id', email AS 'matching_text' , fname as 'additional_text', 'Staff' as 'content_type' FROM staff 
WHERE fname LIKE " . $this->getSearch() . "
OR lname LIKE " . $this->getSearch() . "
OR email LIKE " . $this->getSearch() . "
OR tel LIKE " . $this->getSearch() . "
UNION
SELECT department_id AS 'id', name AS 'matching_text' , telephone as 'additional_text', 'Department' as 'content_type' FROM department 
WHERE name LIKE " . $this->getSearch() . "
OR telephone LIKE " . $this->getSearch() . "
UNION
SELECT video_id AS 'id', title AS 'matching_text' , description as 'additional_text', 'Video' as 'content_type' FROM video 
WHERE title LIKE " . $this->getSearch() . "
OR description LIKE " . $this->getSearch() . "
OR vtags LIKE " . $this->getSearch();

$db = new Querier;
$results = $db->query($sql);

return $results;

}




}



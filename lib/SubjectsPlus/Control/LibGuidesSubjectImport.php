<?php
/**
 * Created by PhpStorm.
 * User: robertsc
 * Date: 1/21/15
 * Time: 3:20 PM
 */

namespace SubjectsPlus\Control;



class LibGuidesSubjectImport {

  private $_api_subject_url = "http://api.libguides.com/api_subjects.php?iid=155&incempty=false";

  private $_api_categories_guides_url = "http://api.libguides.com/api_subjects.php?iid=155&incempty=false&guides=true";

  private $_to_address = "charlesbrownroberts@miami.edu";

  private $_from_address = "charlesbrownroberts@miami.edu";

  private $_subjectsplus_guide_admin_url = "https://sp.library.miami.edu/control/guides/guide.php?subject_id=";



  public function __construct() {
    $this->db = new Querier();
  }

  /**
   * @return string
   */
  public function getApiSubjectUrl() {
    return $this->_api_subject_url;
  }

  /**
   * @param string $api_subject_url
   */
  public function setApiSubjectUrl($api_subject_url) {
    $this->_api_subject_url = $api_subject_url;
  }

  /**
   * @return string
   */
  public function getApiCategoriesGuidesUrl() {
    return $this->_api_categories_guides_url;
  }

  /**
   * @param string $api_categories_guides_url
   */
  public function setApiCategoriesGuidesUrl($api_categories_guides_url) {
    $this->_api_categories_guides_url = $api_categories_guides_url;
  }

  /**
   * @return string
   */
  public function getToAddress() {
    return $this->_to_address;
  }

  /**
   * @param string $to_address
   */
  public function setToAddress($to_address) {
    $this->_to_address = $to_address;
  }

  /**
   * @return string
   */
  public function getFromAddress() {
    return $this->_from_address;
  }

  /**
   * @param string $from_address
   */
  public function setFromAddress($from_address) {
    $this->_from_address = $from_address;
  }


  /**
   * @param null $api_href
   * @return mixed
   */
  protected function get_curl_string($api_href = NULL) {
    // create curl resource
    $ch = curl_init();

    // set url
    curl_setopt($ch, CURLOPT_URL, $api_href);

    //return the transfer as a string
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);


    if(!curl_exec($ch)) {
      //let someone know insert failed - send a msg
      $subject_line = "LibGuides API Failed ";
      $msg = curl_error($ch);

      // close curl resource to free up system resources
      curl_close($ch);

      $this->mailmsg($this->getToAddress(), $this->getFromAddress(), $subject_line, $msg);

      exit();
    } else {
      // $curlStr contains the output string
      $curlStr = curl_exec($ch);

      // close curl resource to free up system resources
      curl_close($ch);

      return $curlStr;
    }

  }


  /**
   * @param $curlString
   * @return array
   */
  protected function libguides_cats_guides_curl_2_array($curlString) {

    $doc = new \DOMDocument();
    $doc->loadHTML($curlString);

    $links_array = array();
    $array = $doc->getElementsByTagName("a");

    foreach($array as $item) {

      if($item->hasAttribute("class")) {
        $class = $item->getAttribute("class");
      } else {
        $class = "guide";
      }

      $href =  $item->getAttribute("href");
      $subject = trim(preg_replace("/[\r\n]+/", " ", $item->nodeValue));
      $links_array[] = array(
          'href' => $href,
          'subject' => $subject,
          'class' => $class
      );

    }

    return $links_array;
  }


  /**
   * @param $guide_url
   * @return array|null
   */
  protected function libguides_guide_dupe_check($guide_url) {
    //initially set to false
    $exists = false;

    //check by count
    $check = $this->db->query("SELECT COUNT(*) FROM subject WHERE redirect_url = '$guide_url'");

    //if equal to or greater than 1 set to true
    if($check[0][0] >= '1') {
      $exists = true;
    }

    return $exists;
  }

  /**
   * @param $url
   * @return array|null
     */
  protected function get_guide_id_by_url($url) {
    if(!is_null($url)) {
      $row = $this->db->query("SELECT subject_id FROM subject WHERE redirect_url = '$url'");
      return $row;
    }
  }


  /**
   * @param null $value
   * @return mixed
   */
  protected function make_subject_shortform($value = null) {
    // Remove all but alphanumeric characters and accent marks and strip the apostrophes and spaces
    $shortform = preg_replace("/[^\p{L}a-zA-Z0-9]+/u", "_", str_replace("'", "", $value ));
    return $shortform;
  }


  /**
   * @param array $data
   * @return null|string
   */
  protected function insert_guide($data = array()) {
    //set initial return
    $new_guide_id = null;

    //set vars with payload
    $subject      = $data['subject'];
    $active       = $data['active'];
    $redirect_url = $data['redirect_url'];
    $header       = $data['header'];
    $type         = $data['type'];

    //create shortform
    $shortform = $this->make_subject_shortform($subject);

    //insert into subject table
    if($this->db->exec("INSERT INTO subject (subject, shortform, active, redirect_url, header, type)
                      VALUES (" . $this->db->quote($subject) . ","
        . $this->db->quote($shortform) . ","
        . $this->db->quote($active) . ","
        . $this->db->quote($redirect_url) . ","
        . $this->db->quote($header). ","
        . $this->db->quote($type) . ")")) {

      //insert success - retrieve last inserted id
      $new_guide_id = $this->db->last_id();
    } else {

      //let someone know insert failed - send a msg
      $subject_line = "LibGuides Insert Failed";
      $content_line = $subject."  ".$redirect_url;
      $content_line .= print_r($this->db->errorInfo());
      $this->mailmsg($this->getToAddress(), $this->getFromAddress(), $subject_line, $content_line);

    }
    //return last inserted id
    return $new_guide_id;
  }


  /**
   * @param null $parent_id
   * @param null $child_id
   * @return bool
   */
  protected function subject_subject_dupe_check($parent_id = null, $child_id = null) {
    //initially set to false
    $exists = false;

    //check by count
    $check = $this->db->query("SELECT COUNT(*) FROM subject_subject WHERE subject_parent = '$parent_id' AND subject_child = '$child_id'");

    //if equal to or greater than 1 set to true
    if($check[0][0] >=  '1') {
      $exists = true;
    }

    return $exists;
  }

  /**
   * @param null $parent_id
   * @param null $child_id
   * @return null|string
     */
  protected function insert_subject_subject($parent_id = null, $child_id = null) {
    $id = null;

      //insert into subject table
    if($this->db->exec("INSERT INTO subject_subject (subject_parent, subject_child)
                      VALUES (" . $this->db->quote($parent_id) . "," . $this->db->quote($child_id) . ")")) {
      //insert success - retrieve last inserted id
      $id = $this->db->last_id();
    } else {
      //let someone know insert failed - send a msg
      $subject_line = "LibGuides Parent/Child Insert Failed";
      $content_line = "parent_id => ".$parent_id." child_id => ".$child_id;

      $errors = array();
      foreach($this->db->errorInfo() as $e) {
        $errors[] = $e;
      }

      $content_line .= $errors;
      $this->mailmsg($this->getToAddress(), $this->getFromAddress(), $subject_line, $content_line);

    }

    return $id;
  }

  /**
   * @return array
   */
  public function get_subject_subject() {
    $rows = $this->db->query("SELECT subject_subject.subject_parent, subject_subject.subject_child, subject.subject FROM subject_subject
INNER JOIN subject ON subject_subject.subject_parent=subject.subject_id");
    return $rows;

  }

  /**
   * @param null $to
   * @param null $from
   * @param null $subject
   * @param null $content
   * @throws Exception
   */
  protected function mailmsg($to = null, $from = null, $subject = null, $content = null){
    //params for sending mail
    $mail_params['to'] = $to;
    $mail_params['from'] = $from;
    $mail_params['subjectLine'] = $subject;
    $mail_params['content'] = $content;

    if(!is_null($mail_params)) {
      //create the mail msg
      $mail_msg = new MailMessage($mail_params);
      //send the mail
      $mailer = new Mailer();
      $mailer->send($mail_msg);
    }

  }


  /**
   * @return array|null
   */
  protected function prepare_payload_for_import() {
    $payload = null;

    //get the api url for retrieving libguides categories and guides
    $api_url = $this->getApiCategoriesGuidesUrl();

    //return the curl string from libguides
    if($curlString = $this->get_curl_string($api_url)) {
      //convert curl string to array
      $payload = $this->libguides_cats_guides_curl_2_array($curlString);
    }

    return $payload;
  }


  /**
   * @param null $payload
   */
  protected function insert_libguide_into_subjectsplus($payload = null) {

    //new guide or guides exist
    if(!is_null($payload)) {

      //loop thru categories and guides from api
      foreach ($payload as $item) {

        //set payload
        $data = array();
        $data['subject'] = $item['subject'];
        $data['active'] = 0;
        $data['redirect_url'] = $item['href'];
        $data['header'] = 'um';

        //classify guide as a category, topic, or subject
        if ($item['class'] == 'subject_link_a') {
          $data['type'] = 'Category';
        } elseif ($item['class'] == 'guide') {
          $data['type'] = 'Topic';
        } else {
          $data['type'] = 'Topic';
        }

        //check to see if guide exists
        $if_guide_exists = $this->libguides_guide_dupe_check($item['href']);

        //guide does not exist
        if ($if_guide_exists === false) {

          //insert new guide
          $new_guide_id = $this->insert_guide($data);

          //inform staff of success or failure
          if ($new_guide_id === null) {

            //let someone know insert failed - send a msg
            $subject_line = "LibGuide Insert Failed";
            $content_line = $data['subject']." ".$data['redirect_url'];
            //$this->mailmsg($this->getToAddress(), $this->getFromAddress(), $subject_line, $content_line);

          } elseif ($new_guide_id > 0) {

            //set parent child ids for subject_subject table
            if($data['type'] == 'Category') {
              $parent_id = $new_guide_id;
              $child_id = $new_guide_id;
            } elseif($data['type'] == 'Topic') {
              $child_id = $new_guide_id;
            }

            //set subjectsplus admin url for new guide
            $new_guide_url = $this->_subjectsplus_guide_admin_url . $new_guide_id;

            //let someone know about new guide - send a msg
            $subject_line = "New LibGuide Inserted";
            $content_line = "New LibGuide inserted into SubjectsPlus subject table. <br>";
            $content_line .= "<a href='{$data['redirect_url']}'>{$data['subject']}</a> in LibGuides <br>";
            $content_line .= "The guide is set to inactive. Please check the settings. <br>";
            $content_line .= "Also make sure the Shortform is concise. <br>";
            $content_line .= "<a href='{$new_guide_url}'>{$data['subject']}</a> in SubjectsPlus admin";
            //$this->mailmsg($this->getToAddress(), $this->getFromAddress(), $subject_line, $content_line);
          }

        } else {
          //guide already exists in subject table

          //get subject_id from subject table
          $guide_id = $this->get_guide_id_by_url($data['redirect_url']);

          //set the parent and child ids
          if($data['type'] == 'Category') {
            $parent_id = $guide_id[0]['subject_id'];
            $child_id = $guide_id[0]['subject_id'];
          } elseif($data['type'] == 'Topic') {
            $child_id = $guide_id[0]['subject_id'];
          }

        }

        //insert parent and child ids into subject_subject table
        if($this->subject_subject_dupe_check($parent_id, $child_id) === false) {
          $this->insert_subject_subject($parent_id, $child_id);
        }

      }
    }
  }


  /**
   *
   */
  protected function import_new_libguides() {
    //prepare import
    $payload = $this->prepare_payload_for_import();

    //insert new libguides into subjectsplus subject table
    $this->insert_libguide_into_subjectsplus($payload);
  }


  /**
   *
   */
  public function run() {
    $this->import_new_libguides();
  }


//close class
}
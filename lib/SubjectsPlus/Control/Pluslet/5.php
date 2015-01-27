<?php
   namespace SubjectsPlus\Control;
     require_once("Pluslet.php");
/**
 *   @file sp_Pluslet_5
 *   @brief The number corresponds to the ID in the database.  Numbered pluslets are UNEDITABLE clones
 * 		this one displays the Catalog Search Box
 *     YOU WILL NEED TO LOCALIZE THIS!
 *
 *   @author agdarby
 *   @date Feb 2011
 *   @todo 
 */

class Pluslet_5 extends Pluslet {

    public function __construct($pluslet_id, $flag="", $subject_id) {

        parent::__construct($pluslet_id, $flag, $subject_id);

        $this->_editable = FALSE;
        $this->_subject_id = $subject_id;
        $this->_pluslet_id = 5;
        $this->_pluslet_id_field = "pluslet-" . $this->_pluslet_id;
        $this->_title = _("Book Search");
    }

    public function output($action="", $view="public") {

        // public vs. admin
        parent::establishView($view);
        // example form action:  http://icarus.ithaca.edu/cgi-bin/Pwebrecon.cgi?
        $this->_body = '<form action="http://catalog.library.miami.edu/search/" method="get" name="search" id="search">
        <input type="hidden" id="iiiFormHandle_1">
        <select name="searchtype" id="searchtype">
          <option value="X" selected="selected">Keyword</option>
          <option value="t">Title</option>
          <option value="a">Author</option>
          <option value="d">Subject</option>
        </select>
        <input type="hidden" name="SORT" id="SORT" value="D" />
        <input maxlength="75" name="searcharg" size="20" />
        <input type="hidden" id="iiiFormHandle_1"/>
        <input name="Search" type="submit" id="Search" value="Search" />
      </form>
      <p style="margin: 1em  0 0 1em;">See also <a href="http://catalog.library.miami.edu/search/X">advanced search</a></span></p>
            ';



        parent::assemblePluslet();

        return $this->_pluslet;
    }

}

?>
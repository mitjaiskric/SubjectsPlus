<?php
/**
 * Created by PhpStorm.
 * User: robertsc
 * Date: 1/15/15
 * Time: 1:18 PM
 */

namespace SubjectsPlus\Control;


class LibguidesApi {

    private $subjectsUrl = "http://api.libguides.com/api_subjects.php?iid=155";

    public function __construct() {



    }

    public function getSubjects() {
        // create curl resource
        $ch = curl_init();

        // set url
        curl_setopt($ch, CURLOPT_URL, $this->subjectsUrl);

        //return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        // $curlStr contains the output string
        $curlStr = curl_exec($ch);

        // close curl resource to free up system resources
        curl_close($ch);

        return $curlStr;
    }


    public function curl2Arr($curlStr) {

        $doc = new DOMDocument();
        $doc->loadHTML($curlStr);

        // all links in document
        $links = array();
        $arr = $doc->getElementsByTagName("a"); // DOMNodeList Object
        foreach($arr as $item) { // DOMElement Object
            $href =  $item->getAttribute("href");
            $text = trim(preg_replace("/[\r\n]+/", " ", $item->nodeValue));
            $links[] = array(
                'href' => $href,
                'text' => $text
            );
        }


        return $links;
    }




}
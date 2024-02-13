<?php

class DynamicHtmlForms {
	
    // Get HTML (called by Eleutheria while building document before sending it to browser)
    public static function getHtml ($params=array()) {
        
        $html = "
            bla bla bla

            <label class='label' for='firstname'>First Name</label>
            ~incl(text, name: firstname, placeholder: xxx, readonly: 0)~

            <label class='label' for='dob'>DOB</label>
            ~incl(date, name: dob)~

            <label class='label' for='manager'>Manager</label>
            ~incl(checkbox, name: manager)~
        ";

        if($html === false)
            return array('error', 'THIS PART GOES IN THE DOCUMENT', 'THIS PART GOES IN THE DIB ERROR LOG');

        return $html;
    }

}
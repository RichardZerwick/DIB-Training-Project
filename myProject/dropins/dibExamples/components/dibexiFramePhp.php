<?php

class dibexiFramePhp {

    // see /nav/dibexiFramePhp
    // The following function name and definition is standard for all iframes that use PHP as source (instead of Eleutheria)
    public static function merge($containerName, $clientData, $activeFilter, $echoContent) {
        // For security reasons, specify HTML encoding as UTF-8
        header('Content-Type: text/html; charset=UTF-8');

        list($clientId) = DibApp::clientData('a_p', array('clientId'));

        // Make sure clientId is integer to avoid SQL errors and possible SQL injection attacks.
        if(empty($clientId) || $clientId != (string)(int)$clientId)
            $errMsg = '<span class="errMsg">Please select a client above</span>';

        else {
            
            $sql = "SELECT DISTINCT  c.name, s.first_name, s.last_name, s.staff_code, s.color
                    FROM test_client c 
                        INNER JOIN test_project p ON p.client_id = c.id
                        INNER JOIN test_staff_project sp ON sp.project_id = p.id
                        INNER JOIN test_staff s ON sp.staff_id = s.id
                    WHERE p.client_id = :id
                    ORDER BY s.first_name, s.last_name";
            $rst = Database::execute($sql, array('id' => $clientId));

            if($rst === false) {
                $errMsg = '<span class="errMsg">Database error. Please check that the <b>test_client</b> table exists in the main Dropinbase database</span>';

            } else if(empty($rst)) {
                $errMsg = '<b class="errMsg">The selected client has no projects (test_project table) or staff assigned to those projects (test_staff_project table).
                      <br>Please select another client</b>';

            } else {

                // *** VERY IMPORTANT, strip, escape or check for any characters that could possibly be used by an attacker.
                // The 'golden rule' is NEVER trust any data that orginates from a user.
                // Angular escapes values by default in Dropinbase containers... Eleutheria escapes merge fields in its HTML templates.
                // But here the output of our PHP code is injected directly into the iframe (without going through Angular or Eleutheria) and therefore we have we have to ensure values are safe.

                // *** ALSO note the following security configurations:
                //   (see above:) header('Content-Type: text/html; charset=UTF-8');   
                // And the corresponding code in the HTML $content below:
                //   <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

                // Get the client's name from the first record in the database result
                $name = $rst[0]['name'];
                // Encode any unsafe characters
                $name = htmlspecialchars($name, ENT_QUOTES | ENT_HTML5, 'UTF-8', TRUE);

                // Get list of staff
                $staffList = '';

                foreach($rst as $r) {
                    // Alphanumeric characters and # are all safe where we want to inject the color code in the HTML.
                    $color = trim((string)$r['color']);
                    if(empty($color) || !ctype_alnum(str_replace('#', '', $color)))
                        $color = 'red';

                    $staffName = $r['first_name'] . ' ' .  $r['last_name'] . '(' . $r['staff_code'] . ')';
                    $staffName = htmlspecialchars($staffName, ENT_QUOTES | ENT_HTML5, 'UTF-8', TRUE);

                    $staffList .= '<h4 style="color: ' . $color . '">' . $staffName . '</h4>';
                }
            }
        }

        if(empty($errMsg)) {
            $content = '<h3>Presenting ...</h3>
                        <h2>' . $name . '</h2>' . $staffList;
        } else {
            $content = $errMsg;
        }
        
        $content = '
        <html>
        <head>
            <!-- *** NOTE: Security measure: always specify UTF-8 encoding, which corresponds with the UTF-8 header which we sent to the client above -->
            <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
            <style type="text/css">
                .errMsg {
                    color:red;
                    font-family: Roboto,Helvetica Neue,sans-serif;
                    font-size: 16px;
                }
            </style>
        </head>
        
        <body>
        ' . $content . '
        </body>
        </html>';

        echo $content;
        
        return (empty($errMsg)) ? true : false;
    }

}
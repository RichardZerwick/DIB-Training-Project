<?php 

class ActionController extends Controller {

    /*
        Execute actions in the browser/client 
        Note, you can create your own actions

        The code below demonstrates how to execute the built-in Dropinbase actions from the server
        Actions stored in /dropins/setNgxMaterial/shared/ts/actions
    */

    // /nav/dibexActionSetValue
    public function setValue($containerName, $itemEventId, $clientData) {
        $params = [
            'name' => 'hippo',
            'status' => 'develop'
        ];

        ClientFunctions::addAction($actionList, 'set-value', $params);
        return $this->validResult($actionList);
    }

    // /nav/dibexActionBack
    public function back($containerName, $itemEventId, $clientData) {
        $params = [];
        ClientFunctions::addAction($actionList, 'back', $params);
        return $this->validResult($actionList);
    }

    // /nav/dibexActionActivateTab
    public function activateTab($containerName, $itemEventId, $clientData) {
        $params = [
            'item' => 'tabStaff',
            
        ];

        ClientFunctions::addAction($actionList, 'activate-tab', $params);
        return $this->validResult($actionList);
    }

    // /nav/dibexActionAppendValue
    public function appendValue($containerName, $itemEventId, $clientData) {
        $params = [
            'inputText' => ' hippo',
        ];

        ClientFunctions::addAction($actionList, 'append-value', $params);
        return $this->validResult($actionList);
    }

    // /nav/dibexActionChangeLanguage
    public function changeLanguage($containerName, $itemEventId, $clientData) {
        $params = [
            'lang' => 'af',
        ];

        ClientFunctions::addAction($actionList, 'change-language', $params);
        return $this->validResult($actionList);
    }

    // /nav/dibexActionCloseWindow
    public function closeWindow($containerName, $itemEventId, $clientData) {
        $params = [
            'containerName' => 'dibexPopupWindow',
        ];

        ClientFunctions::addAction($actionList, 'close-window', $params);
        return $this->validResult($actionList);
    }

    // /nav/dibexActionCloseDialog
    public function closeDialog($containerName, $itemEventId, $clientData) {
        $params = [
            'containerName' => 'self',
        ];

        ClientFunctions::addAction($actionList, 'close-dialog', $params);
        return $this->validResult($actionList);
    }

    // /nav/dibexActionItemHandler
    public function itemHandler($containerName, $itemEventId, $clientData) {
        $params = [
            'cardHidden' => ["visible"=>true],
            'btnClientside'=> [
                "style"=>[
                    "height"=>"100px", 
                    "background"=>"orange"
                ]
            ]
        ];
        
        ClientFunctions::addAction($actionList, 'item-handler', $params);
        return $this->validResult($actionList);
    }

    // /nav/dibexActionEmitEvent
    public function emitEvent($containerName, $itemEventId, $clientData) {

        list($word, $maxSpeed, $maxScale, $cycleDuration) = DibApp::clientData('a_s', ['word', 'maxSpeed', 'maxScale', 'cycleDuration']);

        // validation
        if(empty($word) || strlen($word)<3 || strlen($word)>15)
            return $this->invalidResult('Provide a word/phrase of not more than 15 characters, and at least 3 characters');

        if(!is_numeric($maxSpeed) || $maxSpeed > 60 || $maxSpeed < 5)
            return $this->invalidResult('Provide a Max Speed between 5 and 60');

        if(!is_numeric($maxScale) || $maxScale > 7 || $maxScale < 1)
            return $this->invalidResult('Provide a Max Scale between 1 and 7');

        if(!is_numeric($cycleDuration) || $cycleDuration > 15 || $cycleDuration < 1)
            return $this->invalidResult('Provide a Cycle Duration between 1 and 15 (seconds)');

        // Ensure word is alphanumeric (security prevention)
        if(!ctype_alnum(str_replace(' ', '', $word)))
            return $this->invalidResult('Provide a word/phrase consisting of alpha-numeric characters (spaces allowed)');

        $params = [
            'emitName' => 'myEvent',
            'action' => 'textSpin',
            'value'=> [
                "word" => $word,
                "maxSpeed" => $maxSpeed,
                "maxScale" => $maxScale,
                "cycleDuration" => $cycleDuration
            ]
        ];
        
        ClientFunctions::addAction($actionList, 'emit-event', $params);
        return $this->validResult($actionList);
    }

    // /nav/dibexActionLogout
    public function Logout($containerName, $itemEventId, $clientData) {
        $params = [];

        
        ClientFunctions::addAction($actionList, 'Logout', $params);
        return $this->validResult($actionList);
    }

    // /nav/dibexActionMessage
    public function message($containerName, $itemEventId, $clientData) {

        $messageType = DibApp::clientData('a_s', 'messageType');

        $params = [
            'messageTitle'=>'Greetings',
            'messageType'=> $messageType,
            'messageText'=>'Hello ' . DIB::$USER['first_name'],
           // 'messageDelay'=>''
        ];

        ClientFunctions::addAction($actionList, 'message', $params);
        return $this->validResult($actionList);
    }

    // /nav/dibexNavigation
    public function messageWithLink($containerName, $itemEventId, $clientData) {

        $msg = "Have you seen it...?
                <br>
                <a href=\"/nav/dibexActionEmitEvent\">
                    Investigate Further
                </a>";

        /*
        $params = [
            'messageTitle' =>'Greetings',
            'messageType'  => 'dialog',
            'messageText'  => $msg,
        ];

        ClientFunctions::addAction($actionList, 'message', $params);
        return $this->validResult($actionList);
        */

        return $this->validResult(null, $msg, 'dialog');
    }

    // /nav/dibexActionOpenContainer
    public function openContainer($containerName, $itemEventId, $clientData) {
        $params = [
            'containerName'=>'dibexTestClientGrid',
            'portAlias'=>'floatPopup',
            'queryString'=>'',
            'changeUrl'=>'false',
        ];

        ClientFunctions::addAction($actionList, 'open-container', $params);
        return $this->validResult($actionList);
    }

    // /nav/dibexActionOpenUrl
    public function openUrl($containerName, $itemEventId, $clientData) {
        $params = [
            'url'=>'/nav/dibexTestClientGrid/own',
            'target'=>'blank'
        ];
            
        ClientFunctions::addAction($actionList, 'open-url', $params);
        return $this->validResult($actionList);
    }

    // /nav/dibexActionPopup
    public function popup($containerName, $itemEventId, $clientData) {

        // Note Dropinbase provides a special convenience function for popups:

        ClientFunctions::addMsgPopup($actionList, 
            // title:
            'A popup question',
            // message:
            "Do you want to say 'Hello World'", 
            // yes-button action type:
            'itemAlias',
            // yes-button action:
            'btnDoSomething',
            // no-button action type:
            'action',
            // no-button action:
            [
                'submitUrl' => 'shared.action.append-value',
                'params' => [
                    'inputTextArea' => Date('H:i:s') . "\r\n"
                ],
            ],
            // button captions (defaults are 'Yes' and 'Cancel'):
            [
                'yes'=>'Yes', 
                'no'=>'Rather Append Text'
            ]
        );
       
        /*
        // Alternatively the following code will accomplish the same thing:
        $params = [
            "messageTitle" => 'A popup question',
            "messageText" => "Do you want to say 'Hello World'",
            "messageButtons" => json_encode([
                "buttons"=> [
                    [
                        "buttonText"=>"Yes",
                        "actionType"=>"itemAlias",
                        "action"=>"btnDoSomething"
                    ],
                    [
                        "buttonText"=>"Rather Append Text",
                        "actionType"=>"action",
                        "action"=>[
                            "submitUrl"=>"shared.action.append-value",
                            "params"=>[
                                "inputTextArea" => Date('H:i:s') . "\r\n"
                            ]
                        ]
                    ]
                ]
            ])
        ];
 
        ClientFunctions::addAction($actionList, 'popup', $params);
        */
        

        return $this->validResult($actionList);
    }

    // /nav/dibexActionPrompt
    public function prompt($containerName, $itemEventId, $clientData) {

        // NOTE: The prompt below is configured to call this same function with the user's answer
        // We test $clientData['prompt'] to distinquish between the two calls

        if(array_key_exists('prompt', $clientData)) {
            $answer = $clientData['prompt'];
            if(empty($answer))
                return $this->invalidResult('No answer is not a right answer! Please try again.');

            if((int)$answer == 5)
                return $this->validResult(null, 'Bingo!');
            else
                return $this->invalidResult('Jeepers, need some tuition mate!');

        }

        // Note Dropinbase provides a special convenience function for prompts:

        ClientFunctions::addPrompt($actionList,
            // title:
            'Math Test',
            // message:
            "What is 2 + 3? If you cancel you will be prompted again :)",
            // submit action type:
            'submitUrl', 
            // submit action:
            "/dropins/dibExamples/Action/prompt/$containerName/$itemEventId",
            // force an answer:
            false,
            // cancel action type:
            'itemAlias',
            // cancel action:
            'btnServerside'
        );

        /* Note, You could call a different function upon submission,, 
            but then either don't specify $itemEventId as function parameter (if all users of the container may execute all item events on it),
            or have a hidden button that calls it and use submit-action-type 'itemAlias' and submit-action 'NAMEOFTHEBUTTON' (eg btnHiddenSubmit).
        */
        
        return $this->validResult($actionList);
    }

    // /nav/dibexActionReload
    public function reload($containerName, $itemEventId, $clientData) {

        $params = [];

        ClientFunctions::addAction($actionList, 'reload', $params);
        return $this->validResult($actionList);
    }

    // /nav/dibexActionRefreshQueue
    public function refreshQueue($containerName, $itemEventId, $clientData) {

        $queueUid = DibApp::clientData('a_s', 'queueUid');

        $params = [
            'id' => $queueUid,
            'intervalMs' => 1000,
            'pollCount' => 10
        ];

        ClientFunctions::addAction($actionList, 'refresh-queue', $params);
        return $this->validResult($actionList);
    }

    // /nav/dibexActionReloadContainer
    public function reloadContainer($containerName, $itemEventId, $clientData) {
        
        $params = [
            'containerName'=>'dibexActionReloadContainer'
        ];

        ClientFunctions::addAction($actionList, 'reload-container', $params);
        return $this->validResult($actionList);
    }

    // /nav/dibexActionReloadEnv
    public function reloadEnv($containerName, $itemEventId, $clientData) {
        
        $params = [
            'containerName'=>'dibexActionReloadEnv'
        ];

        ClientFunctions::addAction($actionList, 'reload-env', $params);
        return $this->validResult($actionList);
    }

    // /nav/dibexActionRepaint
    public function repaint($containerName, $itemEventId, $clientData) {
        
        $params = [];

        ClientFunctions::addAction($actionList, 'repaint', $params);
        return $this->validResult($actionList);
    }

    // /nav/dibexActionRun
    public function run($containerName, $itemEventId, $clientData) {
        
        $params = [
            'actionType' => 'itemAlias',
            'action' => 'inputText',
            'trigger' => 'changed' // NOTE: internally Dropinbase uses 'changed' instead of 'change'
        ];

        ClientFunctions::addAction($actionList, 'run', $params);
        return $this->validResult($actionList);
    }

    // /nav/dibexActionSelectRow
    public function selectRow($containerName, $itemEventId, $clientData) {
        
        $params = [
            "containerName"=>"dibexActionSelectRow",
            'coloumn'=>'name',
            'value'=>'Pizza Palace'
        ];

        ClientFunctions::addAction($actionList, 'select-row', $params);
        return $this->validResult($actionList);
    }

    // /nav/dibexActionSetCustom
    public function setCustom($containerName, $itemEventId, $clientData) {
        
        $params = [
            'containerName'=>'dibexActionSetCustom',
            'myVar3'=>Date('Y-m-d H:i:s')
        ];

        ClientFunctions::addAction($actionList, 'set-custom', $params);
        return $this->validResult($actionList);
    }

    // /nav/dibexActionSetCustomGlobal
    public function setCustomGlobal($containerName, $itemEventId, $clientData) {
        
        $params = [
            'myGVar3'=>Date('Y-m-d H:i:s')
        ];

        ClientFunctions::addAction($actionList, 'set-custom-global', $params);
        return $this->validResult($actionList);
    }

    // /nav/dibexActionClearCustom
    public function ClearCustom($containerName, $itemEventId, $clientData) {
        $params = [
            'containerName'=>'ALL' // ALL specifies global custom variables
        ];
        
        ClientFunctions::addAction($actionList, 'clear-custom', $params);
        return $this->validResult($actionList);
    }
    
    // /nav/dibexActionSetEnumList
    public function setEnumList($containerName, $itemEventId, $clientData) {

        $list = "A,B,C";

        $params = [
            'itemList'=> $list,
           
        ];
        
        ClientFunctions::addAction($actionList, 'set-enum-list', $params);
        return $this->validResult($actionList);
    }

    // /nav/dibexActionSetSpanHtml
    public function setSpanHtml($containerName, $itemEventId, $clientData) {

        $msg = '<h1>Greetings<h1><span style="color:blue;"><b>Welcome to my piece of world</b></span>';

        $params = [
            'spanItem'=> $msg,
            // can specify other items here
        ];

        ClientFunctions::addAction($actionList, 'set-span-html', $params);
        return $this->validResult($actionList);
    }
    
    // /nav/dibexActionSetTheme
    public function setTheme($containerName, $itemEventId, $clientData) {

        $params = [
            'containerName'=> 'dibexActionSetTheme',
            'themeName'=>'dibTheme',
            //'darkness'=>''
            
        ];

        ClientFunctions::addAction($actionList, 'set-theme', $params);
        return $this->validResult($actionList);
    }

    // /nav/dibexActionSetThemeDarkness
    public function setThemeDarkness($containerName, $itemEventId, $clientData) {

        $params = [
            'containerName'=> 'dibAdmin',
            'darkness'=>'false'
        ];

        ClientFunctions::addAction($actionList, 'set-theme-darkness', $params);
        return $this->validResult($actionList);
    }

    // /nav/dibexActionSubmitUrl
    public function submitUrl($containerName, $itemEventId, $clientData) {

        $params = [
            'submitUrl'=> '/dropins/dibExamples/Events/helloWorld/dibexActionPrompt/dib*kyjzvldx2',
            'containerName'=>'dibexActionPrompt',
            'responseType'=>'actions'
        
        ];

        ClientFunctions::addAction($actionList, 'submit-url', $params);
        return $this->validResult($actionList);
    }

}

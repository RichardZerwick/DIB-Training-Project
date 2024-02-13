<?php 

    // Note, you could let this class extend a base class containing global functions...

    // TableEvents.php specifices that class must be named same as the table
    class test_client {

        // DEMO: see for eg. /nav/dibexEventBasics -> Table Events

        private function checkName($attributes) {
            if(!isset($attributes['name']) || !is_string($attributes['name']))
                return array('error', 'Demo Table-Event triggered. Invalid name, please try again.');

            $name = $attributes['name'];
            if(!ctype_alpha(str_replace(' ', '', $name))) {
                $style = ['view.items.spanTableEventMsg.style'=>array("{'color':'white', 'padding':'5px', 'background-color':'crimson', 'font-family' : 'Roboto', 'font-size' : '13px'}")];
                ClientFunctions::addMethod(DibApp::$clientActions, $style);
                
                return array('error', 'Demo Table-Event triggered, blocking names with non-alphabetical characters.<br>Please amend and try again.');
            }
            return TRUE;
        }

        public function beforeUpdate($containerName, &$attributes, $clientData = null) {
           return $this->checkName($attributes);
        }

        public function beforeCreate($containerName, &$attributes, $clientData = null) {
            return $this->checkName($attributes);
        }

        public function afterDelete($containerName, &$attributes, $clientData = null) {
            // Messages can be added to the response sent to the browser in any code (not only in controllers):
            DibApp::setClientMsg('This message is set in tableEvents -> test_client.php -> afterDelete.');

            // Actions can be added too by adding them to the DibApp::$clientActions array:
            // Eg. DibFunctions::addAction(DibApp::$clientActions, 'set-value', ['myItem' => 'someValue']);

        }

    }
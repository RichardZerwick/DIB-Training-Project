<?php

class PortOverride {

    /**
    * Returns Port information required by the /peff/Api/getPortInfo requests for example containers
    * @param string $containerName the container in question
    * @param string $portAlias the port alias (if any in the request)
    * @param array $info the default port information which must be altered
    * @return array the complete port info array required which includes the altered values
    */

    public static function loadExampleInDocs($containerName, $portAlias, $info) {
        $overrideInfo = [
            'tabPath' => [2916], // DocsHolder-tab on dibAdmin
            'portItemId' => 5074, // divExamples-tab on dibDocs
            'portItemAlias' => null,
            'portItemName' => 'divExamples',
            'parentContainerName' => 'dibDocs',
            'parentContainerPortAlias' => null,
            'baseContainer' => 'dibAdmin',
            'baseDropin' => 'plain',
            'parentPortItemId' => 2916 // DocsHolder-tab on dibAdmin
        ];

        return array_merge($info, $overrideInfo);
    }

}
<?php

class products {
    public function beforeCreate($containerName, $attributes, $clientData){
        var_dump($attributes);
    }
}
<?php

class ContainerType {

    public function addDataToChart($containerName, $clientData=array()) {
        return $this->records(
            array(
                'newValue' => rand(0, 9),
                'newLabel' => Date('H:i:s')
            )
        );
    }

}
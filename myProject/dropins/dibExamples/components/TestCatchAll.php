<?php 

class TestCatchAll {

    public static function TestCatchAll($args, &$class, &$controllerArgs) {
        Log::w($args);

        return TRUE;
    }


}
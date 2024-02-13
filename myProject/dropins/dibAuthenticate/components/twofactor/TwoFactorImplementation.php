<?php 
    /**
     * Two factor base : 
     * An interface to define what functions is required to implement a specified 2 factor authentication
     */
    interface TwoFactorImplementation {
        
        /**
         * beforeAuthLoad Gets called before the screen loads, and can for example share the requirement message the user has to enter.
         */
        function beforeAuthLoad() : void;

        /**
         * infoMessage What message will appear for the user    .
         */
        function infoMessage() : string;

        /**
         * What is the name of this two factor implementation method
         */
        function title() : string;
        
        /**
         * inputLabel you want to display    .
         */
        function inputLabel() : string;

        /**
         * inputLabel you want to display if the user needs to configure this plugi    .
         */
        function configLabel() : string;

        
        /**
         * errorMessage If the user is not authenticated what error message will be displayed to the user.
         */
        function errorMessage() : string;

        /**
         * Takes an argurment of the user input and return if the user was successfully validated
         * @return boolean
         */
        function authenticate($userInput);

        //SECTION START PART 2 - CONFIGURATION OF THE TWO FACTOR METHOD
        /**
         * To configure the two factor method if not set by the user
         * @return array(boolean, message)
         */
        function configure($userInput);


        /**
         * infoMessage What message will appear for the user    .
         */
        function setupMessage() : string;

        /**
         * Check if the two factor implementation is correctly configured
         */
        function isConfigured();

        //SECTION START PART 3 - RESET THE TWO FACTOR METHOD
        /**
         * resetLabel you want to display how the user can reset their password    .
         */
        function forgotLinkText($disabledAllowed) : string;
        
        /**
         * confirm the reset label pin    .
         */
        function resetLabel() : string;
        
        /**
         * confirm the reset label pin    .
         */
        function resetConfirmLabel() : string;
        
        /**
         * Allow the user to reset the specified two factor authentication and define determine if the two factor method should be enabled or not.
         */
        function reset($userInput, $confirmationInput, $enable);
        /**
         * reset message that will appear for the user .
         */
        function resetMessage() : string;

        /**
         * beforeResetLoad Gets called before the screen loads, and can for example share the requirement message the user has to enter.
         */
        function beforeResetLoad() : void;

        /**
         * Returns the reset error message after form validation
         */
        function resetErrorMessage() : string;

    }
?>
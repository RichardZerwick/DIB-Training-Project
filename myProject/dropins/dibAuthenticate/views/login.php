<!doctype html>
<html class="no-js" lang="en">

  <head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, shrink-to-fit=no" />
    <meta http-equiv="x-ua-compatible" content="ie=edge">

    <title><?php echo  DIB::$SITENAME; ?> Login</title>

    <!--  LINKS TO STYLE SHEETS -->
    <!-- build:css /css/main.min.css -->
    <link rel="stylesheet" href="/files/dropins/dibAuthenticate/css/main.css">
    <link rel="stylesheet" href="/files/files/themes/overrides/css/login.css">
    <!-- endbuild -->
    <style >
        .hidden { 
            display:none !important;
        }
    </style>

    <!--  LINKS TO GOOGLE FONTS -->
    <link href='https://fonts.googleapis.com/css?family=Roboto:400,100,100italic,300,300italic,400italic,500,500italic,700,700italic,900,900italic' rel='stylesheet' type='text/css'>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <!-- FAVICON -->
    <link rel="icon" type="image/x-icon" href="/files/files/icons/favicon.ico" />
    
  </head>

  <body>
    <div class="bg"></div>
      <form name="loginForm" id="login-form" autocomplete="off" action="/dropins/dibAuthenticate/Site/login" method="POST">	
    <div class="loginbox">
      <div class="loginbox__container">
        <div class="loginbox__header">
          <div class="logo">
            <img src="/files/files/images/logo.png">
          </div>
          <h2>Welcome Back!</h2>
        </div>
        <div class="loginbox__form">
              <?php

/* 
 * Copyright (C) Dropinbase - All Rights Reserved
 * This code, along with all other code under the root /dropinbase folder, is provided "As Is" and is proprietary and confidential
 * Unauthorized copying or use, of this or any related file, is strictly prohibited
 * Please see the License Agreement at www.dropinbase.com/license for more info
*/
               $error = Log::getString();
                if ($error != '')
                    echo '<p class="validation-error">' . $error . "</p>";
                ?>
          <div class="field">
            <input name="username" autocomplete="off" id="username" type="text" onblur="checkLabelPos(this)" <?php if (isset($username)) echo 'value="' . $username . '"' ?> />
            <label for="username">Username</label>
          </div>
          <div class="field">
            <input name="password" autocomplete="off" id="password" type="password" onblur="checkLabelPos(this)"/>
            <label for="password">Password</label>
            <input type="hidden" name="form_token" id="token" value="<?php echo DIB::$USER['auth_id'] ?>"/> 
            <input class="hidden" name="email1" id="email1" type="text" value=""  />
          </div>
          <div class="field">
            <label class="switch-label" for="rememberMe">
              <label for="rememberMe">Remember me</label>
              <div class="switch">
                <input name="rememberMe" id="rememberMe" value="1" type="checkbox"/>
                <div class="box">
                  <div class="handle"></div>
                </div>
              </div>
            </label>
          </div>
          <div class="field">
            <input type="submit" title="Login" value="Login">
          </div>
          <a class="forgot" href="/nav/register?record=new">Not a user? Register</a>
        </div>
      </div>
      </form>
      <a class="forgot" href="/dropins/dibAuthenticate/Site/forgotpassword">Forgotten Password?</a>
      <a class="forgot"> Copyright <?php echo date('Y'); ?>. All rights reserved. </a>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.slim.min.js"></script>
    <script>
      function checkLabelPos (self) {
        if ($(self).val() !== "") {
          $(self).addClass('filled');
        } else {
          $(self).removeClass('filled');
        }
      };

      var inputs = $('input:not([type="checkbox"])');
      inputs.each(function (i, el) {
        if ($(el).val() !== "") {
          $(el).addClass('filled');
        }
      });
      
        function login() { 
            $("#login-form").submit();
        }

        $("#login-button").on("click", function() {
            login();
        });
        $("input").keyup(function(e) {
            var code = (e.keyCode ? e.keyCode : e.which);
            if (code == 13) {
                login();
                e.preventDefault();
            }
        });
    </script>
  </body>

</html>
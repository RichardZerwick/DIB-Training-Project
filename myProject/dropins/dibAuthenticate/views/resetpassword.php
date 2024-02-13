<!doctype html>
<html class="no-js" lang="en">

  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, shrink-to-fit=no" />
    <meta http-equiv="x-ua-compatible" content="ie=edge">

    <title><?php echo  DIB::$SITENAME; ?> Reset password</title>

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
       <form id="login-form" autocomplete="off" action="/dropins/dibAuthenticate/Site/resetpassword" method="POST">			    
             <div class="loginbox">
      <div class="loginbox__container">
        <div class="loginbox__header">
          <div class="logo">
            <img src="/files/files/images/logo.png">
          </div>
          <h2>Change your password</h2>
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
            <input name="password" autocomplete="off" id="password" type="password" onblur="checkLabelPos(this)" />
            <label for="password">New Password</label>
            <input type="hidden" name="form_token" id="token" value="<?php echo DIB::$USER['auth_id'] ?>" /> 
            <input type="hidden" name="key" id="token" value="<?php echo basename($_SERVER['REQUEST_URI']); ?>" /> 
            <input class="hidden" name="email1" id="email1" type="text" value=""  />
                       
          </div>
          <div class="field">
            <input name="password2" autocomplete="off" id="password2" type="password" onblur="checkLabelPos(this)" />
            <label for="password2">Confirm Password</label>
                       
          </div>
          <div class="field">
            <input type="submit"  value="Reset password">
          </div>
        </div>
      </div>
      </form>
       <a class="forgot">Copyright <?php echo date('Y'); ?>. All rights reserved.</a>
    </div>
    <script
  src="https://code.jquery.com/jquery-3.1.1.slim.min.js"
  integrity="sha256-/SIrNqv8h6QGKDuNoLGA4iret+kyesCkHGzVUUV0shc="
  crossorigin="anonymous"></script>
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
      $("document").ready(function() {
            $("#login-button").on("click", function() {
                $("#login-form").submit();
            });
            $("input").keyup(function(e) {
                var code = (e.keyCode ? e.keyCode : e.which);
                if (code == 13) {
                    $("#login-form").submit();
                    e.preventDefault();
                }
            });
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
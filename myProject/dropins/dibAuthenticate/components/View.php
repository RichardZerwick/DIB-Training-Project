<?php

/* 
 * Copyright (C) Dropinbase - All Rights Reserved
 * This code, along with all other code under the root /dropinbase folder, is provided "As Is" and is proprietary and confidential
 * Unauthorized copying or use, of this or any related file, is strictly prohibited
 * Please see the License Agreement at www.dropinbase.com/license for more info
*/


class View {
	public static function BaseTag() {
		echo '<base href="'.DIB::$BASEURL.'">';
	}
	
	public static function Title($pageName) {
		echo '<title>'.DIB::$SITENAME.' - '.$pageName.'</title>';
	}
	
	public static function IncludeCss($css) {
		echo '<link rel="stylesheet" type="text/css" href="/files/files/themes/classic/css/'.$css.'" />';
	}
	
	public static function IncludeAppScript($js) {
		echo '<script type="text/javascript" src="'.$js.'"></script>';
	}
	
	public static function IncludeScript($js) {
		echo '<script type="text/javascript" src="/files/files/themes/classic/js/'.$js.'"></script>';
	}
}

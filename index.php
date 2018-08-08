<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------

if(version_compare(PHP_VERSION,'5.3.0','<'))  die('require PHP > 5.3.0 !');

define('APP_DEBUG',true);

define('NO_CACHE_RUNTIME',True); 

define('DB_FIELD_CACHE',false);
define('HTML_CACHE_ON',false);//www.phpernote.com/
//define('APP_DEBUG',false);
//define('RUNTIME_PATH', './Runtime/');

define('APP_PATH','/');

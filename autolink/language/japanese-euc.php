<?php
	$filename = dirname(__FILE__) . '/japanese-utf8.php';
	$contents = file_get_contents($filename);
	$contents = mb_convert_encoding($contents, 'eucJP-win', 'UTF-8');
	eval('?>' . $contents);

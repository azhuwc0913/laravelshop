<?php
	/**
	 * Created by PhpStorm.
	 * User: wc
	 * Date: 16/6/2
	 * Time: 下午4:30
	 */

require './sphinxapi.php';

	$sph = new SphinxClient();
	$sph->SetServer ('127.0.0.1', 9312);
	$sph->SetMatchMode (SPH_MATCH_ANY);
	$ret = $sph->Query('iphone');
	echo '<pre>';
	var_dump($ret);
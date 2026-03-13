<!DOCTYPE html>
<html lang="pl" class="no-js webkit safari chrome win">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1, user-scalable=0, minimal-ui">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="app-timezone" content="Europe/Warsaw">
<meta name="backend-base-path" content="//backend">
<meta name="backend-timezone" content="Europe/Warsaw">
<meta name="backend-locale" content="pl">
<link href="<?= (isset($_SERVER['HTTPS']) ? "https":"http")."://".$_SERVER["SERVER_NAME"]?>/modules/system/assets/ui/storm.css?v=1.2.3" rel="stylesheet">
<link href="<?= (isset($_SERVER['HTTPS']) ? "https":"http")."://".$_SERVER["SERVER_NAME"]?>/modules/system/assets/ui/icons.css?v=1.2.3" rel="stylesheet">
<link href="<?= (isset($_SERVER['HTTPS']) ? "https":"http")."://".$_SERVER["SERVER_NAME"]?>/storage/cms/css/custom.css?v=<?php echo filemtime('storage/cms/css/custom.css'); ?>" rel="stylesheet">
<script src="<?= (isset($_SERVER['HTTPS']) ? "https":"http")."://".$_SERVER["SERVER_NAME"]?>/modules/backend/assets/js/vendor/jquery.min.js?v=1.2.3" importance="high"></script>
<script src="<?= (isset($_SERVER['HTTPS']) ? "https":"http")."://".$_SERVER["SERVER_NAME"]?>/modules/system/assets/js/framework.js?v=1.2.3" importance="high"></script>
<script src="<?= (isset($_SERVER['HTTPS']) ? "https":"http")."://".$_SERVER["SERVER_NAME"]?>/modules/system/assets/ui/storm-min.js?v=1.2.3" importance="high"></script>

	<style>

		body {background:#fff;padding:0px;margin:0px}
		.tab {display:table;width:100%;border:1px solid var(--mlcolor)}
		.tab > div {width:100%;display:table-row;font-family:Arial;font-size:12px;}
		.tab > div > div {display:table-cell;padding:5px}
		.tab a {color:var(--mcolor);text-decoration:none}
		.pagination {margin-left:-30px}

		.btn-primary{font-weight:normal;background:var(--mcolor) !important}
		.btn-primary:hover,.btn-default:hover {background:var(--m2color) !important}
		.btn-primary .badge{color:var(--mcolor);background:#ffffff !important}
		
		form input {color:#aaa;border:1px solid #ccc;padding:3px;height:23px;font-size:12px;}
		form select {color:#aaa;border:1px solid #ccc;padding:2px;font-size:12px;}
	 	//ul { padding-bottom:10px; margin-left:-40px;font-family:Arials}
 		//ul  li  {list-style: none;margin-left: 3px;margin-bottom:5px; padding-left:12px;padding-top: 3px;padding-right: 12px;padding-bottom:3px; background:var(--mlcolor); font-size:12px}
		//ul  li a {color:59a1d1;text-decoration:none} 
		.form-control {height:38px !important}

		.select2-container--default .select2-selection--single .select2-selection__rendered {
		    
		    padding-top: 2px !important;
		    
		}
		
	</style>
</head>
<body>
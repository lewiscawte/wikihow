<!-- included anywhere we're about to start using EIU -->
<?
	define('EIU_URL_REV', 12);
?>
<!--<script type='text/javascript' src='/extensions/wikihow/prototype1.8.2/p.js'></script>-->
<!-- <script type="text/javascript" src="/skins/common/upload.js"></script> -->
<!-- <script type="text/javascript" src="/skins/common/wikibits.js"></script> -->
<!--<script type="text/javascript" src="/extensions/wikihow/prototype1.8.2/scriptaculous.js"></script>-->
<!--<script type="text/javascript" src="/extensions/wikihow/prototype1.8.2/slider.js"></script>-->
<script>
	var wgAjaxLicensePreview = false;
	//jQuery.noConflict();
</script>
<link rel="stylesheet" type="text/css" href="/extensions/min/?f=/extensions/wikihow/eiu/easyimageupload.css&<?= EIU_URL_REV ?>">
<script src='http://www.google.com/jsapi?key=<?= $GOOGLE_SEARCH_API_KEY ?>' type='text/javascript'></script>
<script type="text/javascript" src="/extensions/min/?f=/extensions/wikihow/eiu/easyimageupload.js,/extensions/wikihow/common/json2.js&<?= EIU_URL_REV ?>"></script>

<!-- template html -->
<div id="eiu-dialog" title="<?= wfMsg('image-uploader') ?>">
	<div id="eiu-dialog-inner"></div>
</div>

<!-- lang stuff -->
<?php
	$langKeys = array('eiu-network-error', 'eiu-user-name-not-found-error', 'eiu-insert', 'eiu-preview', 'cancel', 'special-easyimageupload', 'added-image', 'next-page-link', 'prev-page-link');
	echo WikiHow_i18n::genJSMsgs($langKeys);
?>


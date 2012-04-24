<?php
/**
 * Groups configuration for default Minify implementation
 * @package Minify
 */

/** 
 * You may wish to use the Minify URI Builder app to suggest
 * changes. http://yourdomain/min/builder/
 **/

return array(

	//XXCHANGED: reuben/wikihow added groups to reduce URL length

	// big web JS
	'whjs' => array(
		'//extensions/wikihow/common/jquery-1.7.1.min.js',
		'//skins/common/highlighter-0.6.js',
		'//skins/common/wikibits.js',
		'//skins/common/stu.js',
		'//extensions/wikihow/common/jquery-ui-slider-dialog-custom/jquery-ui-1.8.13.custom.min.js',
		'//skins/common/swfobject.js',
		'//extensions/wikihow/common/jquery.scrollTo/jquery.scrollTo.js',
		'//skins/common/fb.js',
		'//skins/WikiHow/google_cse_search_box.js',
		'//skins/common/mixpanel.js',
		'//skins/WikiHow/gaWHTracker.js',
	),

	'rcw' => array('//extensions/wikihow/rcwidget.js'),
	'sp' => array('//skins/WikiHow/spotlightrotate.js'),
	'fl' => array('//extensions/wikihow/FollowWidget.js'),
	'slj' => array('//extensions/wikihow/slider/slider.js'),
	'ppj' => array('//extensions/wikihow/gallery/prettyPhoto-3.12/src/jquery.prettyPhoto.js'),
	'ads' => array('//extensions/wikihow/wikihowAds/wikihowAds.js'),
	'thm' => array('//extensions/wikihow/thumbsup/thumbsnotifications.js'),
	'stu' => array(
		'//extensions/wikihow/common/jquery-1.7.1.min.js',
		'//skins/common/stu.js'
	),

	// big web CSS
	'whcss' => array(
		'//skins/WikiHow/new.css',
		'//extensions/wikihow/common/jquery-ui-themes/jquery-ui.css',
	),

	'li' => array('//skins/WikiHow/loggedin.css'),
	'slc' => array('//extensions/wikihow/slider/slider.css'),
	'ppc' => array('//extensions/wikihow/gallery/prettyPhoto-3.12/src/prettyPhoto.css'),

	// mobile JS
	'mjq' => array('//extensions/wikihow/common/jquery-1.7.1.min.js'),
	'mwh' => array('//extensions/wikihow/mobile/mobile.js'),
	'mga' => array('//skins/common/ga.js'),
	'mah' => array('//extensions/wikihow/mobile/add2home/add2home.js'),
	'mqg' => array('//extensions/wikihow/mqg/mqg.js'),
	'cm' => array('//extensions/wikihow/checkmarks/checkmarks.js'),

	// mobile CSS
	'mwhc' => array('//extensions/wikihow/mobile/mobile.css'),
	'mwhf' => array('//extensions/wikihow/mobile/mobile-featured.css'),
	'mwhh' => array('//extensions/wikihow/mobile/mobile-home.css'),
	'mwhr' => array('//extensions/wikihow/mobile/mobile-results.css'),
	'mwha' => array('//extensions/wikihow/mobile/mobile-article.css'),
	'ma2h' => array('//extensions/wikihow/mobile/add2home/add2home.css'),
	'mqgc' => array('//extensions/wikihow/mqg/mqg.css'),
	'mcmc' => array('//extensions/wikihow/checkmarks/checkmarks.css'),

    // 'js' => array('//js/file1.js', '//js/file2.js'),
    // 'css' => array('//css/file1.css', '//css/file2.css'),

    // custom source example
    /*'js2' => array(
        dirname(__FILE__) . '/../min_unit_tests/_test_files/js/before.js',
        // do NOT process this file
        new Minify_Source(array(
            'filepath' => dirname(__FILE__) . '/../min_unit_tests/_test_files/js/before.js',
            'minifier' => create_function('$a', 'return $a;')
        ))
    ),//*/

    /*'js3' => array(
        dirname(__FILE__) . '/../min_unit_tests/_test_files/js/before.js',
        // do NOT process this file
        new Minify_Source(array(
            'filepath' => dirname(__FILE__) . '/../min_unit_tests/_test_files/js/before.js',
            'minifier' => array('Minify_Packer', 'minify')
        ))
    ),//*/
);

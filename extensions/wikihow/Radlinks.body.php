<?
class Radlinks extends UnlistedSpecialPage {

    function __construct() {
        UnlistedSpecialPage::UnlistedSpecialPage( 'Radlinks' );
    }

	function execute ( $par )
	{
	    global $wgRequest, $wgSitename, $wgLanguageCode;
	    global $wgDeferredUpdateList, $wgOut, $wgUser, $wgServer;
	
	    $fname = "wfRadlinks";

		$wgOut->setPageTitle("Ads for " . $wgRequest->getVal('google_kw') );

		$wgOut->addHTML("<script type='text/javascript'>
			function google_ad_request_done(ads) {
				// nothing
			}
			</script>
		");

			
		$wgOut->addHTML("<style type=\"text/css\" media=\"all\">/*<![CDATA[*/ @import \"/extensions/wikihow/radlinks.css\"; /*]]>*/</style>");
		
		$wgOut->addHTML("<h2>Ads for '{$wgRequest->getVal('google_kw')}'</h2>");

		$chans = "5047600031";
		if ($wgRequest->getVal('radPos1') == 'true') {
			$chans .= "+8354837063";
		} else if ($wgRequest->getVal('radPos1') == 'false') {
			$chans .= "+3168052762";
		}
		$inner = wfMsg('custom_rad_links_landing', $chans, $wgRequest->getVal('google_kw'), 
			$wgRequest->getVal('google_rt'),
			str_replace("wiki112.wikidiy.com", "www.wikihow.com", $wgRequest->getVal('google_page_url'))
			);
		$inner = preg_replace('/\<[\/]?pre[^>]*>/', '', $inner);
		$wgOut->addHTML($inner);

		$exp = wfMsg('rad_links_explanation');	
		$exp = preg_replace('/\<[\/]?pre\>/', '', $exp);
		$wgOut->addHTML($exp);
	
	}
}


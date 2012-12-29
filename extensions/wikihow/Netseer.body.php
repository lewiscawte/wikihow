<?
class Netseer extends UnlistedSpecialPage {

    function __construct() {
        UnlistedSpecialPage::UnlistedSpecialPage( 'Netseer' );
    }

    function execute ($par) {
		global $wgOut, $wgRequest, $wgUser;
		$wgOut->addHTML('
<script type="text/javascript">
netseer_ad_width = 800; 
netseer_ad_height = 1000;
netseer_network_id = 1075;
netseer_landing_page_id = 11;
netseer_ad_format = "landing_page";
netseer_partner_search_url = " http://www.wikihow.com/Special:Netseer?";
</script>
<script src="http://search.netseer.com/dsatserving/creatives/scripts/contextlinks.js" type="text/javascript"></script>'
	);
	}
}

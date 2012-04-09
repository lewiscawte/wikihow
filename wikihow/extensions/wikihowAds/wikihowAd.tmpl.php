<script type="text/javascript"><!--

if (!gHideAds) {
    if(<?= ($adId === "intro"?"true":"false") ?> && !fromsearch) {
		//This is the intro section, but not from search, so don't show
	}
    else {
		document.write('<div class="wh_ad_inner" id="wikihowad_<?= $adId ?>"></div>');

		WH.wikihowAds.addUnit('<?= $adId ?>');

		if(WH.wikihowAds.getAdsSet() == false) {
			 google_ad_channel = '<?= $channels ?>' + gchans;
			 document.write('<script type="text/javascript" src="http://pagead2.googlesyndication.com/pagead/show_ads.js"></' + 'script>');
		}
	}
} 

//-->
</script>
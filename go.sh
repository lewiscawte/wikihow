#! /bin/sh

for file in fixBrokenRedirects.php generateOLPCdump.php wikihow_olpc.sh commandLine.inc findDuplicatephotos.php googleSuggestScrounge.php updateNFDs.php updateFACs.php refreshLowRatings.php findInlineImages.php updatePageCounter.php checkGoogleResults.php replaceExternalLinksWithSources.php rebuildSkeys.php monitorPages.php generateUrls.php archiveKudos.php update_facebook.php newCheckGoogleIndex.php subscribedLinks.php fixLargeImages.php findDuplicateImages.php getProxy.php suggestions.txt test.php cleanupSuggestions.php bad_words.txt acronyms.txt suggestLinks.php linkToolExactReplacement.php findBadUsers.php checkGoogleIndex.php getAdwordsKeyphrases.php
do
	echo "copying $file"
	cp  /var/www/html/wiki19/maintenance/$file /var/www/html/wiki112/maintenance
done


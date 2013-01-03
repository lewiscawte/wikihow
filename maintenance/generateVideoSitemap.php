<?
	require_once('commandLine.inc');

	echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">
		';

	function formatParams($ar) {
		$s = "";
		foreach ($ar as $a=>$r) {
			$s .= "\t<$a>" . trim(htmlspecialchars($r)) . "</{$a}>\n";
		}
		return $s;
	}
	$dbr = wfGetDB(DB_SLAVE);

	$sql = "select distinct(vt_id) as vt_id from video_titles where vt_source = 'wonderhowto'";
	$res = $dbr->query($sql);

	while ($row = $dbr->fetchObject($res) ) {
		$s 	= "";
		$vid = new WonderhowtoVideo($row->vt_id);
		$vid->loadFromDB();
		$url = "http://www.wikihow.com" . $vid->getURL();
		$s .= "\n<url>
<loc>$url</loc>
<video:video>\n";

		$vid->getData();
		$vid->parseResults($vid->mContent);

//print_r($vid); exit;
		$params = 
			array(
				'video:title' 			=> stripos("How to", $vid->get('TITLE')) !== false ?$vid->get('TITLE') :  "How to " . $vid->get('TITLE'),
				'video:description' 	=> $vid->getDescription(),
				'video:thumbnail_loc' 	=> $vid->mResults[0]['MEDIA:THUMBNAIL'],
				);

		$vendor = preg_replace("@\n.*@im", "", $vid->mResults[0]['MEDIA:CREDIT']);
		$src = $vid->getVideoSrc();
		if (!$src) 
			continue;
		$params['video:player_loc'] = $src;
		/* only works for 5min.com
		$swf = $vid->getVideo();
		preg_match("@http://www.5min.com/Embeded/[0-9]+/&sid=102@", $swf, $matches);
		$swf = $matches[0];
		$s .= "\t" . '<video:player_loc allow_embed="yes">' . htmlspecialchars($swf) . "</video:player_loc>\n"; 
		*/
		$s .= formatParams($params);	
/**
    <video:video>     
      <video:player_loc allow_embed="yes">http://www.site.com/videoplayer.swf?video=123</video:player_loc>
      <video:rating>4.2</video:rating>
      <video:view_count>12345</video:view_count>
      <video:publication_date>2007-11-05T19:20:30+08:00.</video:publication_date>
      <video:expiration_date>2009-11-05T19:20:30+08:00.</video:expiration_date>
      <video:tag>steak</video:tag>
      <video:tag>meat</video:tag>
      <video:tag>summer</video:tag>
      <video:category>Grilling</video:category>
      <video:family_friendly>yes</video:family_friendly>
      <video:duration>600</video:duration>
    </video:video>
*/

		$s .= "</video:video>
</url>\n";
		echo $s;
	}

	echo "
</urlset>";

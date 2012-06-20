<?
global $IP;
require_once("$IP/includes/SkinTemplate.php");

abstract class MobileHtmlBuilder {
	protected $deviceOpts = null;
	protected $nonMobileHtml = '';
	protected $t = null;
	private static $jsScripts = array();
	private static $jsScriptsCombine = array();
	private $cssScriptsCombine = array();

	public function createByRevision(&$t, &$r) {
		global $wgParser, $wgOut;

		$html = '';
		if(!$t) {
			return $html;
		}

		if ($r) {
			$popts = $wgOut->parserOptions();
			$popts->setTidy(true);
			$html = $wgParser->parse($r->getText(), $t, $popts, true, true, $r->getId());
			$html = $html->mText;
			$popts->setTidy(false);
			$html = $this->createByHtml($t, $html);
		}
		return $html;
	}

	public function createByHtml(&$t, &$nonMobileHtml) {
		if ((!$t || !$t->exists()) && !($this instanceof Mobile404Builder)) {
			return '';
		}

		$this->deviceOpts = MobileWikihow::getDevice();
		$this->t = $t;
		$this->nonMobileHtml = $nonMobileHtml;
		$this->setTemplatePath();
		$this->addCSSLibs();
		$this->addJSLibs();
		return $this->generateHtml();
	}

	private function generateHtml() {
		$html = '';
		$html .= $this->generateHeader();
		$html .= $this->generateBody();
		$html .= $this->generateFooter();
		return $html;
	}

	abstract protected function generateHeader();
	abstract protected function generateBody();
	abstract protected function generateFooter();

	protected function getDefaultHeaderVars() {
		global $wgRequest, $wgLanguageCode;

		$t = $this->t;
		$articleName = $t->getText();
		$action = $wgRequest->getVal('action', 'view');
		$deviceOpts = $this->getDevice();
		$pageExists = $t->exists();
		$randomUrl = '/' . wfMsg('special-randomizer');
		$isMainPage = $articleName == wfMsg('mainpage');
		$titleBar = $isMainPage ? wfMsg('mobile-mainpage-title') : wfMsg('pagetitle', $articleName);
		$canonicalUrl = 'http://' . MobileWikihow::getNonMobileSite() . '/' . $t->getPartialURL();
		$js = $wgLanguageCode == 'en' ? array('mjq', 'stu') : null;

		$headerVars = array(
			'isMainPage' => $isMainPage,
			'title' => $titleBar,
			'css' => $this->cssScriptsCombine,
			'js' => $js,  // only include stu js in header. The rest of the js will get loaded by showDeferredJS called in article.tmpl.php
			'randomUrl' => $randomUrl,
			'deviceOpts' => $deviceOpts,
			'canonicalUrl' => $canonicalUrl,
			'pageExists' => $pageExists,
			'jsglobals' => Skin::makeGlobalVariablesScript(array('skinname' => 'mobile')),
		);
		return $headerVars;
	}

	protected function getDefaultFooterVars() {
		global $wgRequest;
		$t = $this->t;
		$redirMainBase = '/' . wfMsg('special') . ':' . wfMsg('MobileWikihow') . '?redirect-non-mobile=';
		$footerVars = array(
			'showSharing' => !$wgRequest->getVal('share', 0),
			'isMainPage' => $t->getText() == wfMsg('mainpage'),
			'pageUrl' => $t->getFullUrl(),
			'showAds' => false,  //temporarily taking ads out of the footer
			'deviceOpts' => $this->getDevice(),
			'redirMainUrl' => $redirMainBase,
		);

			$footerVars['androidAppUrl'] = 'https://market.android.com/details?id=com.wikihow.wikihowapp';
			$footerVars['androidAppLabel'] = wfMsg('try-android-app');

			$footerVars['iPhoneAppUrl'] = 'http://itunes.apple.com/us/app/wikihow-how-to-diy-survival-kit/id309209200?mt=8';
			$footerVars['iPhoneAppLabel'] = wfMsg('try-iphone-app');

		return $footerVars;
	}

	public static function showDeferredJS() {
		static $displayed = false;
		if (!$displayed) {
			$displayed = true;
			$vars = array(
				'scripts' => self::$jsScripts,
				'scriptsCombine' => self::$jsScriptsCombine,
			);
			return EasyTemplate::html('include-js.tmpl.php', $vars);
		} else {
			return '';
		}
	}

	public static function showBootStrapScript() {
		static $displayed = false;
		if (!$displayed) {
			$displayed = true;
			return '<script>mobileWikihow.startup();</script>';
		} else {
			return '';
		}
	}
	
	protected function setTemplatePath() {
		EasyTemplate::set_path( dirname(__FILE__).'/' );
	}

	protected function getDevice() {
		return $this->deviceOpts;
	}

	protected function addJSLibs() {
		// We separate the lib from JS from the other stuff so that it can
		// be cached.  iPhone caches objects under 25k.
		self::addJS('mwh', true); // wikiHow's mobile JS
		self::addJS('mga', true); // google analytics script
	}

	protected function addCSSLibs() {
		$this->addCSS('mwhc');
	}

	protected function addCSS($script) {
		$this->cssScriptsCombine[] = $script;
	}

	public static function addJS($script, $combine) {
		if ($combine) {
			self::$jsScriptsCombine[] = $script;
		} else {
			self::$jsScripts[] = $script;
		}
	}
}

class MobileArticleBuilder extends MobileBasicArticleBuilder {

	private function addCheckMarkFeatureHtml(&$vars) {
		global $IP;
		require_once("$IP/extensions/wikihow/checkmarks/CheckMarks.class.php");

		CheckMarks::injectCheckMarksIntoSteps($vars['sections']);
		$vars['checkmarks'] = CheckMarks::getCheckMarksHtml();
	}

	protected function addExtendedArticleVars(&$vars) {
		global $wgLanguageCode;

		if ($wgLanguageCode == 'en') {
			$this->addCheckMarkFeatureHtml($vars);
		}
		
		$vars['isTestArticle'] = $this->isTestArticle();
	}

	protected function isTestArticle() {
		$testArticles = array(
			"Help-a-Pregnant-Guinea-Pig",
			"Soothe-Tired-Feet",
			"Host-Your-Own-Website-for-Free",
			"Get-Good-Grades-in-Middle-School",
			"Blow-Dry-Hair",
			"Get-a-Job-in-Another-State",
			"Invest-in-Bonds",
			"Travel-with-a-Cat",
			"Play-Dungeons-and-Dragons",
			"Stop-Wetting-the-Bed",
			"Play-Pool-Like-a-Pro",
			"Stop-Saying-the-Word-\"Dude\"-So-Much",
			"Run-a-Fast-Mile",
			"Solve-a-Magnets-Puzzle",
			"Get-to-Facebook-at-School",
			"Make-\"I-Love-You\"-in-Sign-Language",
			"Make-a-Woman-Happy",
			"Make-3D-Photos",
			"Not-Be-Afraid-of-the-Dark",
			"Make-a-Basic-Homemade-Facial-Scrub",
			"Date-a-Player",
			"Look-Like-Blair-Waldorf",
			"Install-Mods-in-Euro-Truck-Simulator",
			"Use-an-iTunes-Gift-Card",
			"Clean-Gold-Jewelry",
			"Take-a-Screenshot-of-the-Entire-Screen",
			"Grow-Corn-from-Seed",
			"Tame-Frizzy-Hair-Quickly",
			"Roll-up-Long-Sleeves-on-a-Dress-Shirt",
			"Be-Self-Motivated",
			"Treat-Fluid-Retention",
			"Value-Your-Pokemon-Cards",
			"Check-the-Fluids-in-a-Car",
			"Make-a-Juicy-Lemon-Iced-Tea",
			"Get-an-ROTC-Scholarship",
			"Stain-Wood",
			"Fix-a-Dislocated-Shoulder",
			"Make-a-Fly-Trap",
			"Get-an-Industrial-Piercing",
			"Slipstream-Your-SATA-Drivers-Into-a-Windows-XP-Installation-CD-Using-nLite",
			"Get-Your-Crush-(for-Boys-and-Girls)",
			"Invent-a-Crochet-Pattern",
			"Enjoy-Life",
			"Make-a-Chevron-Friendship-Bracelet",
			"Clean-a-Mattress",
			"Have-a-Balanced-Lifestyle",
			"Fell-a-Tree",
			"Clean-a-Keyboard",
			"Use-Extended-Desktop-View-in-Windows-XP",
			"Express-Your-Emotional-Pain-the-Healthy-Way",
			"Use-Senuti",
			"Select-the-Right-Digital-Camera",
			"Make-a-Bird-out-of-a-Plastic-Straw",
			"Grow-African-American-Hair",
			"Change-a-Lock-Cylinder",
			"Safely-Download-Torrents",
			"Do-a-Dive",
			"Dress-and-Look-Like-Effy-Stonem",
			"Get-out-of-a-Bad-Mood-Fast",
			"Chase-Lizards-out-of-Your-House",
			"Make-a-Dutch-Braid",
			"Love-Yourself",
			"Win-Sweepstakes",
			"Clean-an-Oil-Painting",
			"Tell-if-a-Betta-Fish-Is-Sick",
			"Shock-Your-Swimming-Pool",
			"Determine-the-Gender-of-a-Cat",
			"Care-for-a-Pet-Rat",
			"Do-Sprint-Training",
			"Grow-a-Van-Dyke-Beard",
			"Get-Rid-of-Gnats",
			"Apply-Eye-Makeup-for-Deep-Set-Eyes",
			"Polish-Shoes",
			"Decide-if-Blonde-Hair-Is-Right-for-You",
			"Motivate-Staff",
			"Run-up-a-Wall-and-Flip",
			"Write-a-Letter-to-Grandma",
			"Make-Different-Colors-With-Food-Coloring",
			"Come-Up-with-a-Nickname",
			"Keep-a-Baseball-Scorecard",
			"Make-a-Girl-Feel-Special",
			"Deal-With-Bullies",
			"Tie-a-Bandana-Like-a-Headband",
			"Give-a-Thank-You-Speech",
			"Hide-Your-Feelings-from-a-Girl-You-Like",
			"Ballet-Dance",
			"Play-Bloody-Mary",
			"Stop-Liking-the-Guy-You-Can't-Have",
			"Draw-an-Owl",
			"Develop-Color-Film",
			"Balance-Yourself-on-a-Skateboard",
			"SMS-(Internationally)-on-iPhone-for-Free-Using-WhatsApp",
			"Make-Homemade-Baked-Potato-Crisps",
			"Make-Slime-Without-Borax",
			"Treat-a-Sore-Throat-or-Laryngitis",
			"Calculate-the-Beats-Per-Minute-(BPM)-of-a-Song",
			"Love-a-Libra",
			"Make-a-Guy-Jealous-Through-Texting",
			"Get-Your-Ex-Girlfriend-Back",
			"Make-a-ParaCord-Fishtail-Weave-Bracelet",
			"Change-Your-Betta-Fish-Water",
			"Make-a-Teddy-Bear",
			"Act-Around-the-Girl-You-Like",
			"Use-a-Multimeter",
			"Celebrate-Towel-Day",
			"Solve-a-Sudoku",
			"Make-a-Natural-Skin-Peel",
			"Fold-Paper-Into-a-Secret-Note-Square",
			"Ride-a-Bicycle-with-Your-Dog",
			"Reuse-Empty-Pill-Bottles",
			"Scrunch-Your-Hair-Overnight",
			"Burn-Wii-Games-to-Disc",
			"Detect-a-Playgirl",
			"Prevent-Bad-Breath",
			"Be-Funny",
			"Fall-in-Love",
			"Shuffle-and-Deal-Texas-Holdem",
			"Get-Rid-of-Earwigs",
			"Sign-a-Letter",
			"Eat-Healthy-as-a-Vegetarian",
			"Change-Your-Desktop-Background-in-Windows",
			"Act-Like-a-Dominatrix",
			"Write-an-Analytical-Essay",
			"Find-the-Gender-of-a-Name",
			"Thicken-Sauce",
			"Adjust-Euro-Style-Cabinet-Hinges",
			"Recycle-Aluminum-Cans-and-Plastic-Bottles-and-Earn-Cash",
			"Make-a-Boy-Horny",
			"Be-Punctual",
			"Build-a-Saw-Horse",
			"Calculate-the-Day-of-the-Week-in-Excel",
			"Fall-Out-of-Love-with-Your-Best-Friend",
			"Make-a-Good-Pot-of-Coffee",
			"Tell-if-You-Have-Diabetes",
			"Be-Diplomatic",
			"Create-a-Made-Up-Word",
			"Choose-Headphones",
			"Play-a-Player",
			"Make-Cookie-Cutters",
			"Play-Softball",
			"Soften-Hard-Water",
			"Change-the-Font-on-Tumblr",
			"Crack-Your-Knuckles",
			"Create-and-Install-Symbols-on-Microsoft-Word",
			"Sell-Items-on-Craigslist",
			"Configure-X11-in-Linux",
			"Dispose-of-Unused-Medication",
			"Improve-Your-2-Mile-Run-Time",
			"Remove-.Ds_Store-Files-on-Mac-Os-X",
			"Transport-Cats-by-Plane",
			"Get-Through-a-Miscarriage",
			"Deal-with-Emotional-Abuse",
			"Connect-One-Router-to-Another-to-Expand-a-Network",
			"Apply-Mascara",
			"Become-More-Intelligent-Than-You-Are-Now",
			"Make-a-Diet-Coke-and-Mentos-Rocket",
			"Care-for-and-Feed-Salamanders",
			"Change-Computer-BIOS-Settings",
			"Groom-a-Dog",
			"Grow-Bamboo-from-Seed",
			"Look-Like-a-Pornstar",
			"Get-to-Know-a-Girl-at-School-You-Don't-Know",
			"Get-a-Teacher-to-Like-You",
			"Install-Deck-Piers",
			"Do-a-Mind-Reading-Magic-Number-Trick",
			"Be-Happy-Being-Yourself",
			"Prevent-a-Shark-Attack",
			"Make-Your-Own-Acne-Treatment",
			"Paint-the-Interior-of-a-House",
			"Do-Your-Homework-on-Time-if-You're-a-Procrastinator",
			"Find-Perpendicular-Vectors-in-3-Dimensions",
			"Take-an-MP3-File-and-Delete-the-Words-to-Create-Karaoke",
			"Make-a-Hologram",
			"Spot-a-Spy",
			"Be-Happy-with-Who-You-Are",
			"Clean-Your-Fingernails",
			"Make-a-Great-Student-Council-Campaign",
			"Know-How-Many-Hours-to-Run-a-Pool-Filter",
			"Set-up-an-FTP-Server-in-Ubuntu-Linux",
			"Give-Your-Boyfriend-Space",
			"Make-Egg-Rolls",
			"Look-Like-a-Model",
			"Configure-a-Router-to-Use-DHCP",
			"Do-a-Bridge",
			"Change-your-Start-Page-on-Mozilla-Firefox",
			"Make-a-Simple-Animation-in-Macromedia-Flash",
			"Make-Friends-Lists-on-Facebook",
			"Do-Spray-Painting",
			"Get-Your-Girlfriend-to-Play-Video-Games",
			"Change-a-Lock",
			"Erase-Old-Marks-off-a-Dry-Erase-Board",
			"Brake-and-Stop-a-Car-in-the-Shortest-Distance",
			"Play-a-Mage-in-World-of-Warcraft",
			"Make-Chocolate-by-Hand",
			"Make-a-Pop-Up-Card",
			"Be-the-Type-of-Nerd-That-Girls-Love",
			"Cut-Hair",
			"Use-a-French-Press-or-Cafetiere",
			"Make-an-Envelope",
			"Improve-Jpeg-Image-Quality",
			"Play-Phase-10",
			"Pan-Sear-a-Steak",
			"Blind-Bake",
			"Make-Waffles",
			"Write-a-Novel",
			"Observe-Memorial-Day-Weekend",
			"Care-for-Your-Curly-Hair",
			"Make-a-Ghillie-Suit",
			"Stop-Picking-Your-Scabs",
			"Have-Emo-Hair-Without-Going-to-Extremes",
			"Give-a-Performance-Review-of-an-Employee",
			"Clean-Your-Belly-Button",
			"Back-Up-Your-Bookmarks-on-Mozilla-Firefox",
			"Be-the-Alpha-Female",
			"Forge-a-Signature",
			"Have-Great-Hair",
			"Recognize-the-Signs-of-an-Abusive-Man",
			"Find-Your-Passion",
			"Make-Taco-Salad",
			"Make-Strawberry-Lemonade",
			"Improve-Search-Engine-Ranking",
			"Cook-an-Omelette",
			"Flirt-In-Sixth-Grade",
			"Overcome-a-Fear-of-Heights",
			"Record-Sound-Produced-by-Your-Sound-Card",
			"Become-Sexy-(for-Boys-Only)",
			"Play-the-Clarinet",
			"Help-Someone-Having-a-Panic-Attack",
			"Repair-Dodgy-or-Broken-Headphones",
			"Describe-a-Person's-Physical-Appearance",
			"Make-Someone-Tell-the-Truth",
			"Make-a-Baggy-T-Shirt-Fitted",
			"Open-Rigid-Plastic-Clamshell-Packages-Safely",
			"Hook-a-Bowling-Ball",
			"Write-Faster",
			"Deal-With-Blackmail",
			"Tan-a-Hide",
			"Respond-After-a-Kiss",
			"Get-Rid-of-Panic-Attacks",
			"Fold-a-Shirt",
			"Use-Mayonnaise-as-a-Hair-Conditioner",
			"Remove-Ink-from-Clothes",
			"Meditate-for-Beginners",
			"Make-a-Summer-Dress-out-of-a-Bedsheet",
			"Start-a-Conversation-with-a-Stranger",
			"Raise-Your-Own-Crickets",
			"Wash-Your-Clothes",
			"Clean-a-Wedding-Gown",
			"Make-a-Pinwheel",
			"Make-a-Mocha-Coffee-Drink",
			"Clear-All-Files-from-a-Computer-Running-Windows-XP",
			"Install-Subversion-on-Mac-OS-X",
			"Take-Care-of-Your-Skin",
			"Understand-the-Unit-Circle",
			"Wash-a-Backpack",
			"Put-Your-Arm-Around-a-Girl",
			"Apply-Bronzer",
			"Date-a-Co-Worker",
			"Improve-Your-Video-Card's-Gaming-Speed-Without-Replacing-It",
			"Open-Locked-Cars",
			"Know-if-You-Are-Gay",
			"Punch-Harder",
			"Tell-Chinese,-Japanese,-and-Korean--Writing--Apart",
			"Replace-an-iPhone-Battery",
			"Use-FFmpeg",
			"Choose-Healthy-Dog-Food",
			"Propose-to-Your-Boyfriend",
			"Treat-Flea-Bites",
			"Make-Cotton-Candy",
			"Install-Fedora",
			"Dry-Your-Home-Grown-Lavender",
			"Practice-Random-Acts-of-Kindness",
			"Get-a-Construction-Loan-(US)",
			"Soundproof-and-Have-a-Drummer-Friendly-Home",
			"Install-Plastic-Lawn-Edging",
			"Install-Skype-on-a-PC",
			"Choose-Between-a-Car-with-Automatic-or-Manual-Transmission",
			"Realign-Your-Ps3's-Blu-Ray-So-That-a-Disc-Can-Load-and-Eject",
			"Perform-an-Underhand-Volleyball-Serve",
			"Cure-a-Chicken-from-Egg-Bound",
			"Open-Your-Spiritual-Chakras",
			"Boil-Water-in-the-Microwave",
			"Speak-French",
			"Make-Friends-on-Facebook",
			"Fillet-Trout",
			"Stop-Being-a-Condescending-Person",
			"Plan-and-Organize-a-Tour-for-Your-Band",
			"Rebuild-an-Engine",
			"Say-a-Speech-Without-Getting-Nervous",
			"Get-Rid-of-Carpet-Mold",
			"Write-Drum-and-Bass",
			"Become-Famous",
			"Thank-Someone",
			"Improve-Your-IQ",
			"Calculate-Lotto-Odds",
			"Deep-Fry-at-Home",
			"Convert-Kelvin-to-Fahrenheit-or-Celsius",
			"Survive-an-Abduction-or-Hostage-Situation",
			"Treat-a-Dry-Socket",
			"Make-Money-Leis",
			"Blow-Out-Eggs",
			"Repair-a-Concrete-Floor",
			"Write-a-Complaint-Letter-to-a-Company",
			"Learn-Russian",
			"Sweeten-Your-Watermelon",
			"Shotgun-a-Beer",
			"Start-a-Scrapbook",
			"Treat-Asthma-Attacks",
			"Make-a-Girl-Laugh",
			"Jump-Double-Dutch",
			"Design-a-Video-Game",
			"Raise-Butterflies",
			"Be-Random",
			"Make-Lemonade",
			"Work-out-Weight-Watchers-Pro-Points-Allowance",
			"Stop-an-Application-from-Opening-at-Startup-With-Mac-OS-X",
			"Turn-an-Image-Black-and-White-Except-for-One-Color-(Adobe-Photoshop-Elements-5.0)",
			"Get-Evidence-Thrown-out-in-Court",
			"Improve-Your-Tone-on-the-Flute",
			"Spend-Your-Summer-Vacation-Indoors-and-Outdoors",
			"Convert-Any-Toilet-to-a-Low-Flush-Toilet",
			"Stop-Grass-Buildup-Under-Any-Lawn-Mower-Deck",
			"Burn-a-CD",
			"Look-Like-a-Hollister-Model-(for-Girls)",
			"Froth-Milk-for-Cappuccino-Without-Fancy-Tools",
			"Replace-a-Bicycle-Tire",
			"Ask-a-Friend-on-a-Date",
			"Obtain-Money-from-Your-Parents",
			"Apply-Eye-Makeup",
			"Tune-Your-Drums",
			"Make-a-Simple-Weather-Barometer",
			"Kick-a-Soccer-Ball",
			"Have-Fun-While-Studying",
			"Maintain-Romance",
			"Make-a-Man-Fall-in-Love-with-You",
			"Make-Limeade",
			"Lose-Weight-with-Vitamins",
			"Create-Good-Study-Habits-for-Exams",
			"Reblog-Something-on-Tumblr",
			"Date-a-Man-with-Kids",
			"Growl",
			"Make-Guacamole",
			"Create-a-Business-Continuity-Plan",
			"Look-Attractive-(Girls)",
			"Decide-Whether-to-Buy-Stocks-or-Mutual-Funds",
			"Overcome-Boredom-at-School",
			"Play-Capture-the-Flag",
			"Wear-Your-Converse",
			"Burp-on-Demand",
			"Catch-the-Three-Legendary-Birds-in-Pokemon-FireRed-and-LeafGreen",
			"Shave",
			"Help-Your-Dog-Lose-Weight",
			"Date-a-Leo-Woman",
			"Put-Anchor-Screws-in-a-Wall",
			"Make-a-Poodle-Skirt",
			"Draw-a-Soccer-Ball",
			"Scare-People",
			"Make-Carrot-Juice",
			"Remove-Latex-Paint-from-Clothes",
			"Make-Your-Feet-Beautiful",
			"Shave-Your-Face",
			"Impress-a-Guy",
			"Forget-About-Your-Ex-Boyfriend",
			"Get-Rid-of-Large-Pores-and-Blemishes",
			"Pop-a-Key-Back-Onto-a-Dell-Laptop-Keyboard",
			"Dump-a-Nice-Guy",
			"Design-a-Website",
			"Use-the-Sine-Rule",
			"Make-a-Pop-Tab-Bracelet",
			"Stop-Dizziness",
			"Set-up-Your-Tattoo-Machine",
			"Pack-Without-Wrinkles",
			"Find-Old-Friends-Online",
			"Cope-with-a-Stomach-Flu",
			"Stop-Coughing-in-5-Minutes",
			"Write-an-Apology-Letter-to-a-Teacher",
			"Cold-Read",
			"Deal-With-the-Police-when-They-Come-to-Your-Door-at-a-Party",
			"Get-Rid-of-Algae-in-Ponds",
			"Learn-to-Draw-Manga-and-Develop-Your-Own-Style",
			"Exercise-Your-Eyes",
			"Get-a-Lesbian-Girlfriend",
			"Make-Thin-Crust-Pizza",
			"Create-Fire-With-a-Magnifying-Glass",
			"Make-Green-Tea",
			"Edit-Video-With-Avidemux",
			"Level-a-Bumpy-Lawn",
			"Make-Denim-Cut-off-Shorts",
			"Sell-Your-House-Using-a-Lease-Option",
			"Buy-a-Car-Like-a-Dealer",
			"Unclog-a-Slow-Running-Bathroom-Sink-Drain",
			"Move-Cross-Country",
			"Be-a-Bad-Boy",
			"Make-a-Diaper-Cake",
			"Save-a-Laptop-from-Liquid-Damage",
			"Make-Liquid-Castile-Soap",
			"Measure-Your-Neck-Size-and-Sleeve-Length",
			"Become-a-Famous-Singer",
			"Avoid-Colloquial-(Informal)-Writing",
			"Know-if-You're-in-a-Rebound-Relationship",
			"Make-Clothes-for-Your-Doll",
			"Say-Beautiful-Girl-in-Spanish",
			"Cut-PVC-Pipe",
			"Protect-Your-Teeth-from-Acid-Wear",
			"Make-a-Padlock-Shim",
			"Download-and-Open-Torrent-Files",
			"Get-a-Free-Car-if-You-Are-Disabled",
			"Apply-Henna-to-Hair",
			"Use-a-Parking-Valet",
			"Record-Your-Voice-on-a-Windows-Computer",
			"Use-\"Who\"-and-\"Whom\"-Correctly",
			"Look-Like-Hayley-Williams",
			"Walk-in-High-Heels",
			"Get-Your-Horse-to-Work-on-the-Bit",
			"Open-Your-Above-Ground-Swimming-Pool-After-Winter",
			"Cope-With-a-Controlling-Parent",
			"Find-a-Song-You-Know-Nothing-About",
			"Make-French-Toast",
			"Dance",
			"Make-a-Rum-Runner",
			"Free-up-Hard-Disk-Space-on-Windows-Vista",
			"Sharpen-Scissors",
			"Stop-Having-An-Inappropriate-Crush",
			"Swing-a-Golf-Club",
			"Use-Color-Replacement-in-MS-Paint",
			"Cheer-Up-a-Friend-After-a-Breakup",
			"Create-a-Language",
			"Make-Your-Fingernails-Look-Good",
			"Make-Your-Cell-Phone-Battery-Last-Longer",
			"Hack-a-Database",
			"Keep-a-Piercing-from-Rejecting",
			"Guess-Someone's-Astrological-Sign",
			"Use-a-Graphing-Calculator-to-Solve-a-Systems-of-Equations",
			"Cleanse-Your-Body-Naturally",
			"See-if-Someone-Is-Invisible-on-Gmail-Chat",
			"Build-a-Picnic-Table",
			"Prevent-Foot-Blisters",
			"Tell-if-Jade-Is-Real",
			"Build-a-Modeling-Portfolio",
			"Make-a-Living-Wall",
			"Slow-Your-Heart-Rate-Down",
			"Make-a-Roman-Shade",
			"Say-Stop-in-French-Kissing",
			"Throw-a-Going-Away-Party",
			"Make-a-Hospital-Corner",
			"Take-Care-of-Your-Hands-and-Nails",
			"Trade-Forex-Online",
			"Make-a-Bunny-by-Typing-Characters-on-Your-Keyboard",
			"Limit-Your-Alcohol-Intake-to-the-Recommended-Two-or-Less-Servings-Per-Day",
			"Make-a-Tomahawk-Without-a-Forge",
			"Be-Cool-(Teen-Girls)",
			"Catch-Rayquaza-in-Pokemon-Emerald",
			"Live-a-Gossip-Girl-Life",
			"Fix-a-Huge-Argument-with-Your-Girlfriend",
			"Overcome-Jealousy-After-a-Break-Up",
			"Get-Music-off-of-YouTube-to-Make-a-Mix-CD",
			"Use-Question-Marks-Correctly",
			"Plant-an-Avocado-Tree",
			"Make-a-Simple-Presentation-Using-Flash",
			"Make-a-Military-Bun",
			"Perform-an-iPod-Low-Level-Format",
			"Make-Naan-Bread",
			"Find-True-North-Without-a-Compass",
			"Perform-the-Balducci-Levitation",
			"Make-a-Trading-Card-Game",
			"Do-Well-in-an-Interview-With-a-Modeling-Agency",
			"Dry-Nail-Polish-Quickly",
			"Fly-a-Hydra-Jet-in-San-Andreas",
			"Edit-PDF-Files-in-Linux-Using-GIMP",
			"Teach-Your-Kitten-to-be-Calm-and-Relaxed",
			"Remove-Petroleum-Jelly-from-Hair-Using-Olive-Oil-and-Detergent",
			"Connect-Coaxial-Cable-Connectors",
			"Microwave-Pasta",
			"Eliminate-and-Prevent-Green-Algae-in-a-Swimming-Pool",
			"Remove-an-Airlock-from-Your-Hot-Water-System",
			"Convince-a-Very-Hairy-Man-to-Shave-His-Chest",
			"Use-Dowsing-or-Divining-Rods",
			"Make-a-Milkshake",
			"Write-a-Limerick",
			"Perform-Clutch-Wheelies-on-a-Motorcycle",
			"Make-Your-Guy-Friend-Want-to-Date-You",
			"Write-a-Letter-Requesting-Sponsorship",
			"System-Link-Two-or-More-Xbox-or-Xbox-360-Consoles",
			"Care-for-a-Pet-Duck",
			"Balance-Chemical-Equations",
			"Change-Your-Nat-Type-on-Xbox-Live",
			"Use-Your-Xbox-360-Controller-for-Windows",
			"Multitask",
			"Build-a-Homemade-Rube-Goldberg-Machine",
			"Get-Car-Loans-After-Bankruptcy",
			"Remove-Water-from-Ears",
			"Write-the-Last-Sentence-in-a-Paper",
			"Avoid-Peeing-on-the-Tampon-String",
			"Copy-Files-to-an-External-Hard-Drive",
			"Safely-Pierce-Your-Own-Ear",
			"Make-a-Superhero-Costume",
			"View-an-Eclipse",
			"Tie-a-Knot-in-a-Cherry-Stem-With-Your-Tongue",
			"Maintain-Your-Spa-or-Hot-Tub",
			"Type",
			"Write-a-Love-Letter-to-a-Girl-You-Do-Not-Know",
			"Say-Greetings-and-Goodbyes-in-Spanish",
			"Remove-Mildew-Smell-from-Towels",
			"Love-Yourself-First-So-Everything-Else-Falls-Into-Line",
			"Get-Pregnant-if-Your-Partner-Had-a-Vasectomy",
			"Write-Goodbye-Letters",
			"Make-Lye",
			"Create-a-Computer-Game-Using-PowerPoint",
			"Shave-Your-Legs-(Male)",
			"Make-Pizza",
			"Tell-Which-Way-Round-a-Diode-Should-Be",
			"Increase-Your-Chances-of-Winning-a-Lottery",
			"Take-Measurements-(For-Women)",
			"Put-in-an-Above-Ground-Pool",
			"Get-Rid-of-Pimples-with-Baking-Soda",
			"Create-a-Clean-Version-of-a-Song",
			"Care-for-Fresh-Cut-Tulips",
			"Buff-a-Boat",
			"Poach-an-Egg-Using-a-Microwave",
			"Make-Chai-Tea",
			"Research-Effectively-Before-Opening-Your-Own-Bar",
			"Make-Your-Hair-Look-Gothic",
			"Record-Your-Screen-on-Mac",
			"Perform-a-Tracheotomy",
			"Be-a-Better-Stage-Actor",
			"Make-Great-Curry",
			"Get-a-Class-3-Firearms-License",
			"Set-up-a-Marine-Reef-Aquarium",
			"Stop-a-Car-with-No-Brakes",
			"Break-in-a-Pair-of-Sperry-Top-Siders-Quickly",
			"Clean-up-Oil-Spills-in-a-Garage",
			"Stop-Snoring",
			"Have-Beautiful-Silky,-Shiny,-Straight-Hair",
			"Train-a-Dog",
			"Get-Organized-and-Concentrate-on-Your-Work",
			"Treat-a-Back-Spasm",
			"Prevent-Hair-Loss",
			"Use-the-Telephone",
			"Make-a-Girl-Become-Obsessed-with-You",
			"Dress-Harajuku-Style",
			"Make-Flavored-Lip-Gloss",
			"Do-a-Burnout-on-a-Motorcycle",
			"Make-Horchata",
			"Practice-Nudity-in-Your-Family",
			"Hold-a-Fashion-Swap-Party",
			"Impress-a-Girl",
			"Advertise-on-Groupon",
			"Let-Go-of-a-Failed-Relationship",
			"Draw-Blood-from-Those-Hard-to-Hit-Veins",
			"Check-out-a-Used-Car-Before-Buying-It",
			"Spot-Fake-DVDs",
			"Create-a-Paper-Helicopter",
			"Build-Your-Vocabulary",
			"Play-Roms-on-a-Nintendo-DS",
			"Copy-and-Paste-PDF-Content-Into-a-New-File",
			"Select-Shoes-to-Wear-with-an-Outfit",
			"Use-a-Wood-Lathe",
			"Get-a-Hiphop-Record-Deal",
			"Make-a-Big-Nose-Look-Smaller",
			"Open-a-Difficult-Jar",
			"Build-an-Adobe-Wall",
			"Sync-Music-to-Your-iPod",
			"Swim-Butterfly-Stroke",
			"Create-Good-Personalities-for-Your-Characters",
			"Make-Someone-Shut-Up",
			"Become-Left-Handed-when-you-are-Right-Handed",
			"Tell-when-a-Cow-or-Heifer-is-in-Estrus",
			"Have-Fun-at-a-Party-Where-Everyone-Is-Getting-Drunk",
			"Understand-Love-As-a-Chemical-Reaction",
			"Turn-an-Old-Road-Bike-Into-a-Singlespeed",
			"Solve-a-Linear-Diophantine-Equation",
			"Blow-Up-a-Balloon",
			"Change-a-Graphics-Card-(on-Board-Card)",
			"Stay-on-a-Raw-Food-Diet",
			"Know-if-You're-in-Love",
			"Grow-Iris",
			"Make-a-Delta-Wing-Paper-Airplane",
			"Go-from-Introvert-to-Extrovert",
			"Deal-With-Overprotective-Parents",
			"Make-Low-Calorie-Vodka-Drinks",
			"Make-a-Green-Tea-Face-Mask",
			"Store-a-Car",
			"Extract-Mint-Oils-from-Leaves",
			"Blow-Glass",
			"Play-School-at-Home",
			"Get-Over-a-Guy-Who-Doesn't-Like-You",
			"Draw-a-Treble-Clef",
			"Clean-a-Clothes-Dryer-Vent",
			"Become-a-Software-Engineer",
			"Clean-a-Body-Piercing",
			"Make-Dried-Pineapple-Flowers",
			"Give-a-Foot-Massage",
			"Fish",
			"Operate-the-Amazon-Kindle",
			"Fix-a-Stripped-Screw-Hole",
			"Activate-Windows-XP-Without-a-Genuine-Product-Key",
			"Get-Rid-of-Pimple-Redness",
			"Heal-a-Broken-Toe",
			"Get-Any-Pokemon-on-Diamond-or-Pearl",
			"Get-Him-to-Make-a-Move",
			"Get-a-Man-(for-Gay-Men)",
			"Hack-a-Computer",
			"Do-the-Sexy-Walk",
			"Mold-Chocolate-Candy",
			"Recycle-Your-Socks",
			"Overcome-Stage-Fright",
			"Catch-a-Squirrel",
			"Know-if-You-are-Pregnant",
			"Confront-a-Backstabber",
			"Replace-Your-Automobile-Windshield",
			"Get-99-Hunter-in-RuneScape",
			"Become-a-Musician",
			"Grow-a-Clover-Lawn",
			"Make-Mint-Tea",
			"Look-Taller",
			"Cook",
			"Look-like-Avril-Lavigne",
			"Walk-a-Dog",
			"Ripen-Green-Tomatoes",
			"Do-80's-Makeup-and-Hair",
			"Walk-a-Slackline",
			"Accept-Blame-when-You-Deserve-It",
			"Unsubscribe-from-Spam",
			"Make-a-Tight-Turn-Quickly-in-a-Car",
			"Delete-a-Skype-Account",
			"Become-a-Lawyer-in-the-United-States",
			"Dry-Rose-Petals",
			"Lower-High-Blood-Pressure-Without-Using-Medication",
			"Clean-Your-Skin",
			"Power-Nap",
			"Make-Calves-Smaller",
			"Make-Someone-Laugh",
			"Make-Money-As-Oil-Prices-Rise",
			"Wind-a-Yarn-Ball",
			"Look-Like-a-Twilight-Vampire",
			"File-a-Lawsuit",
			"Install-a-DVD-Drive",
			"Setup-VNC-on-Mac-OS-X",
			"Make-Dairy-Free-Ice-Cream",
			"Remove-Eyeliner",
			"Make-a-Paper-People-Chain",
			"Preheat-an-Oven",
			"Make-a-Computer-Run-Better",
			"Make-Ginger-Tea-or-Tisane",
			"Make-a-Screensaver-With-Pictures",
			"Prevent-Hand-Pain-from-Excessive-Writing",
			"Clean-Your-Computer-System",
			"Clean-a-Saxophone",
			"Attract-a-Christian-Girl",
			"Change-the-Oil-in-Your-Car",
			"Run-a-20:00-5K",
			"Care-for-a-Baby-Wild-Rabbit",
			"Learn-Smoking-Tricks",
			"Train-a-Puppy-Not-to-Bite",
			"Grow-a-Sunflower-in-a-Pot",
			"Grill-Salmon",
			"Get-Someone-Committed-to-a-Mental-Hospital",
			"Look-Good-for-School",
			"Create-Salt-Dough",
			"Play-the-Accordion",
			"Freeze-a-Wart-With-Liquid-Nitrogen",
			"Make-a-Fairy-House",
			"Do-the-Cotton-Eyed-Joe-Dance",
			"Wash-Silk-Garments",
			"Become-an-Airline-Pilot",
			"Cope-Without-Friends",
			"Improve-Your-Writing-Skills",
			"Keep-a-Bedroom-Tidy",
			"Be-Friends-with-a-Girl-That-Rejected-You",
			"Deal-With-Emotionally-Abusive-Parents",
			"Enjoy-Your-Job",
			"Look-and-Act-Like-Serena-Van-Der-Woodsen",
			"Uninstall-McAfee-Security-Center",
			"Deal-With-a-Friend's-Death",
			"Sculpt-Using-Polymer-Clay",
			"Respect-Yourself-During-a-Breakup",
			"Guess-a-Password",
			"Make-a-Handkerchief",
			"Get-Your-Credit-Report-for-Free",
			"Roll-a-Coin-on-Your-Knuckles",
			"Live-Cheaply",
			"Make-a-Temporary-Tattoo-with-Nail-Polish",
			"Choose-the-Best-Class-and-Race-for-Yourself-in-World-of-Warcraft",
			"Build-Self-Confidence",
			"Calculate-the-Volume-of-a-Cube",
			"Macrame",
			"Rugby-Tackle-Everyone-That-Runs-at-You",
			"Catch-a-Turtle",
			"Soften-a-Leather-Belt",
			"Become-a-Certified-Event-Planner",
			"Become-a-Celebrity",
			"Load-a-Dishwasher",
			"Win-a-Teenage-Girl's-Heart",
			"Check-Your-Air-Conditioner-Before-Calling-for-Service",
			"Dispute-Your-Cell-Phone-Bill",
			"Write-a-Birthday-Invitation",
			"Tell-The-Difference-Between-Slutty-and-Sexy",
			"Tell-Your-Family-That-You-Are-Gay",
			"Avoid-a-Traffic-Ticket",
			"Make-Homemade-Spaghetti-Sauce",
			"Paint",
			"Make-a-Simple-Solar-Viewer",
			"Treat-a-Foot-Blister",
			"Perform-Yoga-Postures",
			"Pick-a-Lock-Using-a-Paperclip",
			"Make-a-Great-PowerPoint-Presentation",
			"Make-a-Simple-Program-With-Xcode",
			"Make-Your-Wireless-Internet-Connection-Faster-(Comcast)",
			"Get-Rid-of-Foot-Odor",
			"Get-a-Job-After-You've-Been-Fired",
			"Open-up-an-iPod",
			"Tell-if-You-Have-Hit-Puberty-(Boys)",
			"Have-Beautiful-Eyelashes-and-Eyebrows-Using-Castor-Oil",
			"Make-Black-Hair-Curly",
			"Do-an-Ozone-Shock-Treatment-on-a-Vehicle",
			"Have-a-Gynecological-Exam",
			"Record-from-a-Webcam",
			"Start-a-Relationship",
			"Do-a-YouTube-Video",
			"Boil-Chicken-Breasts",
			"Toilet-Paper-a-House",
			"Draw",
			"Invest-Small-Amounts-of-Money-Wisely",
			"Get-Rid-of-a-Stuffy-Nose-Quickly",
			"Stop-an-Engine-from-Overheating",
			"Heal-Armpit-Rash",
			"Behave-Professionally-on-Social-Media",
			"Get-Someone-to-Like-You-for-Real",
			"Create-a-Personal-Website",
			"Shrink-a-Cotton-T-Shirt",
			"Have-Six-Pack-Without-Any-Equipment",
			"Have-Courage",
			"Get-a-Celebrity-to-Reply-to-You-on-Twitter",
			"Get-Bigger-Chest-Muscles-(Pecs)",
			"Plant-Sugar-Cane",
			"Make-a-Peanut-Butter-and-Jelly-Sandwich",
			"Keep-a-Wild-Caught-Toad-As-a-Pet",
			"Make-a-Poster",
			"Prepare-for-the-Job-Interview",
			"Remove-Stickers-from-a-Laptop",
			"Know-if-a-Girl-You-Have-Never-Talked-to-Before-Likes-You",
			"Clean-a-Coffee-Maker",
			"Tell-the-Difference-Between-a-Kayak-and-Canoe",
			"Bump-a-Volleyball",
			"Tell-a-Guy-You-Like-Him",
			"Look-Good-at-the-Beach",
			"Tell-Someone-You-Do-Not-Want-to-Be-Friends",
			"Get-Beautiful,-Glowing-Skin",
			"Decorate-Your-School-Binder",
			"Be-Happy-when-You-Don't-Have-Friends",
			"Make-Pancakes-for-One",
			"Find-a-Lost-Dog",
			"Bleach-Your-Hair-With-Hydrogen-Peroxide",
			"Call-911",
			"Change-Microsoft-Office-Product-Key",
			"Overcome-Your-Fear-of-the-Dentist",
			"Get-Motivated",
			"Be-Cyber-Goth",
			"Make-a-Download-Go-Faster",
			"Start-a-Beauty-Salon",
			"Get-Your-Sims-Abducted-by-Aliens-in-Sims-2",
			"Get-Over-Being-Left-Out",
			"Date",
			"Make-Beef-Jerky",
			"Organize-Your-Backpack",
			"Make-a-Slip-Knot",
			"Dress-and-Look-Like-You-Belong-at-a-Rock-Concert-(for-Girls)",
			"Make-Ramen-Noodles",
			"Impress-Your-Crush",
			"Dreadlock-Straight-Hair",
			"Start-a-Phone-Conversation",
			"Play-With-Pokemon-Cards",
			"Understand-Offside-in-Soccer-(Football)",
			"Spot-Someone-Who-Is-Faking-an-Illness-to-Get-out-of-School",
			"Move-to-the-Netherlands",
			"Make-an-Oatmeal-Bath",
			"Treat-a-Caterpillar-Sting",
			"Become-a-WWE-Superstar",
			"Build-Muscle-at-Home",
			"Extend-the-Battery-Life-of-an-iPad",
			"Make-a-Rubber-Band-Guitar",
			"Sing-Screamo",
			"Clean-the-Steam-Iron-and-Its-Base-Plate",
			"Confront-Friends-Who-Are-Ignoring-You",
			"Fix-a-Vacuum-Cleaner",
			"Give-Yourself-a-Manicure",
			"Build-a-Hot-Tub-Platform",
			"Make-DIY-Wrist-Candy",
			"Become-a-Hand-Model",
			"Be-Confident",
			"Look-Good-Without-Makeup",
			"Handle-a-Dog-Attack",
			"Clean-Windows",
			"Defuse-a-Situation-With-a-Difficult-Customer",
			"Bleach-Hair-Blonde",
			"Make-a-Military-Care-Package",
			"Squelch-Malicious-Gossip",
			"Pull-Out-a-Tooth",
			"Dribble-a-Basketball",
			"Straighten-Men's-Hair",
			"Lose-Weight-Without-Going-Hungry",
			"Help-Stop-Pollution",
			"Play-an-A-Major-Chord-on-the-Guitar",
			"Have-the-Dreams-You-Want",
			"Use-Agar-Agar",
			"Be-a-Scene-Queen",
			"Dedupe-Records-in-Excel-2007",
			"Choose-Comfortable-Underwear",
			"Print-Only-a-Section-of-a-Web-Page,-Document-or-E-mail",
			"Have-Girl-Swag",
			"Identify-Common-Poisonous-Berries-in-North-America",
			"Cut-Jeans-to-Make-a-Wider-Leg",
			"Stop-a-Dog-from-Jumping",
			"Press-Flowers-and-Leaves",
			"Cut-a-Limb-from-a-Tree",
			"Manage-Facebook-Privacy-Options",
			"Tell-if-an-Emerald-Is-Real",
			"Rapidly-Learn-to-Play-the-Acoustic-Guitar-Yourself",
			"Make-a-Cootie-Catcher",
			"Store-Comic-Books",
			"Be-Cool-on-Facebook",
			"Apply-Stick-Deodorant-Properly",
			"Make-Your-Own-Jeans",
			"Relieve-Leg-Cramps",
			"Shave-With-Olive-Oil",
			"Survive-a-Freestyle-Rap-Battle",
			"Always-Win-at-Tug-of-War",
			"Deal-With-Your-Boyfriend's-Female-Friend",
			"Get-Soft-Skin-in-20-Minutes",
			"Have-a-Simple-Hairstyle-for-School",
			"Give-Your-Large-Dog-a-Bath",
			"Switch-to-a-Dvorak-Keyboard-Layout",
			"Eat-Crabs",
			"Customize-a-My-Little-Pony",
			"Cope-With-Emotional-Pain",
			"Improve-Balance",
			"Become-a-Good-Muslim-Girl",
			"Determine-if-a-Guy-is-Nervous-Around-You-Because-He-Likes-You",
			"Train-Your-Hamster",
			"Work-out-Without-Weights",
			"Create-a-Brochure-in-Microsoft-Word-2007",
			"Treat-a-Bleeding-Ulcer",
			"Recover-Deleted-Files-from-Your-Computer",
			"Hit-a-Slowpitch-Softball",
			"Have-Fun-in-a-Hotel-Room",
			"Repair-Minor-Rust-on-a-Car",
			"Know-if-Your-Best-Friends-Are-Trying-to-Ditch-You",
			"EV-Train-Your-Pokemon",
			"Convert-Between-Fahrenheit-and-Celsius",
			"Have-Fun-While-Sleeping-Naked",
			"Make-Bubbles",
			"Deal-with-a-Scalp-Sunburn",
			"Tell-a-Guy-You-Like-Him,-when-He-Likes-You-Too",
			"Stop-Your-Stomach-from-Growling-Loudly-in-Class",
			"Build-a-Rainwater-Collection-System",
			"Diagnose-and-Clear-Cloudy-Swimming-Pool-Water",
			"Create-a-Google-Map-With-Excel-Data-and-Fusion-Tables",
			"Develop-the-'Sherlock-Holmes'-Intuition",
			"Get-a-Boy-to-Kiss-You-when-You're-Not-Dating-Him",
			"Look-Sexy-if-You-Are-Big",
			"Play-the-Hunger-Games-Outdoor-Game",
			"Check-a-Start-Capacitor",
			"Build-Concrete-Steps",
			"Have-an-Attitude",
			"Feel-Better-when-You-Have-a-Cold-(for-Girls)",
			"Care-for-a-Christmas-Cactus",
			"Say-No-Respectfully",
			"Make-Dandelion-Wine",
			"Keep-the-Upstairs-of-Your-Air-Conditioned-Home-Cooler",
			"Convince-Yourself-Not-to-Commit-Suicide",
			"Pitch-a-Baseball",
			"Become-an-FBI-Profiler",
			"Delete-Locked-Files-on-a-Mac",
			"Install-Your-Mercruiser-Propeller",
			"Become-a-Secret-Agent-on-Club-Penguin",
			"Replace-Your-Mercury-or-Mercruiser-Shift-and-Throttle-Cables",
			"Speed-Up-Firefox-by-Running-It-In-RAM",
			"Make-Sugar-Rockets",
			"Repair-a-Wet-Book",
			"Get-Smileys-on-iPod-Touch/iPhone",
			"Eliminate-Body-Odor",
			"Hold-Hands-With-Your-Girl/Boy-Friend",
			"Not-Care-What-People-Think",
			"Keep-Your-Parents-From-Knowing-You-Have-a-Boyfriend-or-Girlfriend",
			"March",
			"Reupholster-a-Dining-Chair-Seat",
			"Remove-a-Tick",
			"Get-Baby-Soft-Skin",
			"Be-a-Great-Football-Player",
			"Make-a-Coke-Float",
			"Stay-Focused",
			"Make-Plastic-Guitar-Picks",
			"Call-Mexico-from-the-United-States",
			"Draw-Real-Things",
			"Build-Confidence-in-Preschoolers-and-Toddlers-With-Public-Speaking",
			"Get-Over-a-Guy",
			"Go-for-a-Morning-Walk-or-Run",
			"Attract-a-Taurus-Man",
			"Make-a-Bowl-(Pipe)-out-of-Aluminum-Foil",
			"Take-Action-if-a-Guy-Calls-You-Ugly",
			"Deal-With-Falling-in-Love",
			"Fake-a-Fever",
			"Be-a-Good-Girlfriend",
			"Sleep-Comfortably-on-a-Cold-Night",
			"Landscape-Your-Home-on-the-Cheap",
			"Center-Web-Page-Content-Using-CSS",
			"Be-Popular-in-High-School",
			"Make-an-Origami-Pocket-Heart",
			"Dye-Your-Hair-With-Indigo",
			"Get-Your-Adult-Children-to-Move-Out",
			"Make-the-First-Move",
			"Get-Rich-by-Buying-and-Flipping-Real-Estate",
			"Do-a-Fishless-Cycle",
			"Care-for-Baby-Guppies",
			"Build-a-Bicycle-Cargo-Trailer",
			"Make-Your-Fingers-Hard-for-Guitar",
			"Install-an-Exhaust-System",
			"Stop-Laughing-When-You-Laugh-at-Inappropriate-Times",
			"Be-Aloof",
			"Entertain-a-Girl",
			"Build-a-Log-Raft",
			"Calculate-Center-of-Gravity",
			"Get-Started-in-Stand-up-Comedy",
			"Improve-Digital-Photo-Quality-in-Photoshop",
			"Make-a-Bean-Bag",
			"Find-and-Take-Care-of-Wild-Bird-Eggs",
			"Shrink-Cotton-Fabrics",
			"Treat-a-Burn-Using-Honey",
			"Make-Hot-Sauce",
			"Make-a-Celebrity-Follow-You-on-Twitter",
			"Change-a-Digital-Picture-from-Color-to-Black-and-White",
			"Play-the-Pocky-Game",
			"Study-for-a-Math-Exam",
			"Format-a-Hard-Drive",
			"Stop-Hair-Loss-Naturally",
			"Deal-With-Being-Single-and-Feeling-Lonely",
			"Overclock-a-PC",
			"Make-a-S'more",
			"Make-Rice-Krispie-Buns",
			"Clean-Your-Ears",
			"Package-Books-for-Shipping",
			"Create-an-iPhone-Alarm-That-Will-Vibrate-Without-Ringing",
			"Buy-a-Menstrual-Cup",
			"Style-a-Classic-Chignon-Hair-Style",
			"Make-Lechon-Kawali",
			"Solder-Stereo-Mini-Plugs",
			"Detect-Hidden-Cameras-and-Microphones",
			"Clean-out-Your-Gmail-Inbox",
			"Identify-Your-Adopted-Mutt",
			"Cook-Beetroot",
			"File-an-FAA-Flight-Plan",
			"Avoid-Gagging-While-Brushing-Your-Tongue",
			"Find-a-Last-Minute-Mother's-Day-Gift",
			"Tie-Tomatoes:-the-Florida-Weave",
			"Make-Fried-Ice-Cream",
			"Catch-a-Pond-Catfish",
			"Prepare-for-Track",
			"Keep-Bass-and-Other-American-Gamefish-in-Your-Home-Aquarium",
			"Know-What-to-Do-Following-a-House-Fire",
			"Write-a-Fanfiction",
			"Take-Better-Notes",
			"Make-Command-Prompt-Appear-at-School",
			"Have-a-Healthy-Sex-Life-(Teens)",
			"Make-a-Natural-Bow-and-Arrow",
			"Understand-Soccer-Assistant-Referee-Signals",
			"Remove-Rust-from-a-Cast-Iron-Skillet",
			"Flirt-if-You-Are-a-Shy-Girl",
			"Cook--Jamaican-Jerk-Chicken",
			"Make-a-Clan-in-RuneScape",
			"Dress-Like-a-Skater",
			"Make-Modelling-Clay-at-Home",
			"Open-Internet-Explorer-if-the-Icon-is-not-on-Your-Desktop",
			"Fold-a-CD-Cover-from-a-Sheet-of-Copy-Paper",
			"Contact-the-President-of-the-United-States",
			"Cook-Adobo",
			"Treat-Swelling",
			"Make-Waterproof-Matches",
			"Report-a-Stolen-Social-Security-Card",
			"Do-the-40-Hour-Famine",
			"Create-a-Swing-GUI-in-Java",
			"Make-Small-Talk",
			"Write-a-Good-Topic-Sentence",
			"Remove-Bloodstains-from-Clothing",
			"Repair-a-Book's-Binding",
			"Tell-when-a-Girl-Is-Interested-in-You",
			"Know-if-Your-Crush-Likes-You-Back-(for-Guys)",
			"Increase-Libido",
			"Fix-a-Broken-Friendship",
			"Catch-Striped-Bass",
			"Read-the-Signs-of-a-Guy-Liking-You",
			"Get-a-Guy-Who-You-Hate-to-Stop-Liking-You",
			"Meet-New-People-Without-Being-Creepy",
			"Download-from-YouTube",
			"Update-a-Toyota-Corolla-Car-Radio",
			"Diagnose-and-Remove-Any-Swimming-Pool-Stain",
			"Straighten-Thick,-Curly-Hair-Without-Damaging-It",
			"Spin-a-Pencil-Around-Your-Middle-Finger",
			"Make-a-Fake-Black-Eye",
			"Look-Good-in-a-Suit",
			"Switch-Flights-in-LAX",
			"Escape-from-Handcuffs",
			"Write-a-Business-Plan",
			"Fight-Dirty-and-Win",
			"Land-an-Airplane-in-an-Emergency",
			"Remove-a-Stuck-Ring",
			"Be-a-Good-Employee",
			"Address-a-Letter-to-a-Government-Official",
			"Buy-Maternity-Clothes-While-Pregnant",
			"Travel-With-One-Bag",
			"End-a-Relationship",
			"Do-a-Sugar-Facial",
			"Exfoliate-Your-Skin-With-Olive-Oil-and-Sugar",
			"Pry-off-a-Watch-Backing-Without-Proper-Tools",
			"Crochet-in-the-Round",
			"Get-Rid-of-Shin-Splints",
			"Overcome-a-Social-Phobia",
			"Remove-an-Ingrown-Hair",
			"Pass-Time",
			"Ride-a-Motorcycle",
			"Care-for-Peacocks",
			"Play-Jazz-Piano",
			"Make-a-Prank-Call",
			"Make-People-Respect-You",
			"Be-Stealthy",
			"Find-a-Water-Leak-in-Your-House",
			"Use-a-Kindle-Fire",
			"Adopt-a-Baby-from-China",
			"Use-PayPal-to-Accept-Credit-Card-Payments",
			"Earn-Money-As-a-Kid-or-Teen",
			"Keep-Cats-from-Chewing-on-Electric-Cords-and-Chargers",
			"Make-a-Tesla-Coil",
			"Calculate-Your-Name-Number-in-Numerology",
			"Attend-to-a-Stab-Wound",
			"Copy-Music-from-Your-iPod-to-Your-Computer",
			"Undo-a-Bra-One-Handed",
			"Be-Successful",
			"Eat-Less-Sugar",
			"Prepare-for-a-Health-Insurance-Physical",
			"Avoid-Panty-Lines",
			"Grill-Steak",
			"Get-Chewing-Gum-off-Clothes",
			"Tell-if-Ray-Ban-Sunglasses-Are-Fake",
			"Live-in-the-Moment",
			"Get-Rid-of-Pigeons",
			"Care-for-Newly-Pierced-Ears",
			"Make-a-Banana-Milkshake-Without-a-Blender",
			"Stay-Slim-and-Still-Drink-Alcohol",
			"Make-Chamomile-Tea",
			"Become-Popular-on-Facebook",
			"Clean-Copper",
			"Make-Flan",
			"Deal-With-a-Snake-in-the-House",
			"Debadge-Your-Car",
			"Stop-Hesitating",
			"Make-a-Substitute-for-Bisquick",
			"Excavate-a-Trench",
			"Fix-the-Crotch-Hole-in-Your-Jeans",
			"Season-Firewood",
			"Stop-a-Horse-from-Bucking",
			"Sneak-Around-at-Night",
			"Convert-Grams-Into-Pounds",
			"Pass-All-Your-GCSE's",
			"Treat-a-Concussion",
			"Get-the-Biggoron's-Sword-in-the-Legend-of-Zelda,-Ocarina-of-Time",
			"Make-Hair-Removal-Wax-at-Home",
			"Build-a-Survival-Shelter-in-a-Wooded-Area",
			"Remove-a-Virus",
			"Make-Friends-in-College",
			"Draw-and-Color-with-Microsoft-Paint",
			"Prevent-a-House-Fire",
			"Make-Your-Hair-Glow-(Coffee-Treatment)",
			"Care-for-Hermit-Crabs",
			"Treat-Deep-Cuts",
			"Stop-Thinking-Too-Much",
			"Cheat-at-Draw-My-Thing-on-OMGPOP.Com",
			"Kiss-in-a-Variety-of-Ways",
			"Clean-a-Car-Engine",
			"Choose-a-Firearm-for-Personal-or-Home-Defense",
			"Become-a-Food-Critic",
			"Get-Your-Cat-to-Sleep-With-You",
			"Make-Play-Dough",
			"Make-a-Bow-and-Arrow",
			"Resign-Gracefully",
			"Have-a-Brainstorming-Session-Without-Talking",
			"Observe-the-Transit-of-Venus",
			"Make-a-Diorama",
			"Write-an-Academic-Essay",
			"Remove-Contact-Lenses",
			"Steam-Clams",
			"Deal-With-a-Needy-Friend",
			"Live-Without-Dairy-Products",
			"Remove-a-\"Foxtail\"-from-a-Dog's-Nose",
			"Make-Your-Ex-Girlfriend-Want-You-Again",
			"Take-Care-of-a-Praying-Mantis",
			"Tell-when-a-Pineapple-Is-Ready-to-Eat",
			"Make-Your-Hair-Wavy-Easily",
			"Look-Beautiful-During-Summer-Vacation",
			"Look-Pretty-at-School",
			"Make-a-Paper-Bag-Puppet",
			"Deal-With-Difficult-Relatives",
			"Motivate-Your-Employees",
			"Come-Out-As-a-Gay-or-Lesbian-Teen",
			"Deal-With-Someone-Who-Really-Annoys-You",
			"Activate-Windows-Vista-Secrets",
			"Unsubscribe-from-Groupon",
			"Get-Everything-You-Want-in-Life",
			"Make-an-Egg-Wash",
			"Make-Indian-Style-Basmati-Rice",
			"Form-the-Word-\"Blood\"-with-Your-Fingers",
			"Clean-a-Cartridge-Type-Swimming-Pool-Filter",
			"Make-Beads-from-Flour-and-Water",
			"Extend-or-Renew-a-Visa-for-China",
			"Be-a-Good-Role-Model",
			"Take-Care-of-Bamboo",
			"Make-a-Latte",
			"Make-a-Baby-Romper-from-a-T-Shirt",
			"Have-Super-Soft-Hands-(Overnight-Method)",
			"Play-a-CD-on-a-Desktop-Computer",
			"Be-a-Cheerleader",
			"Kill-Household-Bugs",
			"Make-Mango-Jam",
			"Get-Rid-of-the-Smell-of-Vomit-in-a-Carpet",
			"Make-a-Whistle-from-a-Straw",
			"Make-a-Kandi-Cuff",
			"Send-Someone-an-Email",
			"Do-Homework",
			"Stand-Out-from-the-Crowd",
			"Get-a-Car-Dealer-License-to-Sell-Cars",
			"Use-Honey-in-Place-of-White-Sugar",
			"Understand-Gay-and-Lesbian-People",
			"Become-a-Voice-Actor/Voiceover-Artist",
			"Do-a-Split",
			"Install-a-New-Operating-System-on-Your-Computer",
			"Flirt-over-Text-Messages-(for-Teen-Girls)",
			"Text-Your-Crush-and-Start-a-Conversation",
			"Paint-Warhammer-Figures",
			"Install-Patio-Pavers",
			"Teach-Your-Dog-to-Drop-It",
			"Download-Movies-Directly-onto-Your-iPad",
			"Become-Rich-Someday",
			"Remove-Urine-Stains-from-Mattress",
			"Make-People-Appreciate-and-Like-You",
			"Fold-a-Pocket-Square",
			"Woo-a-Girl",
			"Avoid-the-Temptation-to-Eat-Unhealthy-Foods",
			"Overcome-Fear",
			"Study-Well",
			"Kill-Fleas-in-a-Home",
			"Design-a-Band-Logo",
			"Survive-High-School",
			"Eat-an-Oreo-Cookie",
			"Make-Girl-Scout-SWAPS",
			"Exercise-for-Firmer-Boobs-and-Butts",
			"Produce-and-Write-Dance-Music",
			"Remove-Bumper-Stickers",
			"Get-Over-a-Broken-Engagement",
			"Paint-Aluminum-Siding",
			"Make-Batter",
			"Purge-Crawfish",
			"Cut-Kids'-Hair",
			"Make-Polenta",
			"Break-a-Glass-with-Your-Voice",
			"Identify-if-You-Are-in-an-Abusive-Relationship",
			"Exercise",
			"Use-a-Sanitary-Napkin-(Pad)",
			"Make-Sun-Tea",
			"Grow-Ginseng",
			"Add-Songs-to-Your-Movies-on-Windows-Movie-Maker",
			"Prevent-Wrinkles",
			"Build-an-Ant-Farm",
			"Enjoy-Summer-As-a-Teenager",
			"Setup-a-Xbox-360-Controller-on-Project64",
			"Choose-the-Correct-Replacement-Liner-for-Above-Ground-Pools",
			"Make-Your-Own-Vinegar",
			"Make-a-Hand-Tied-Wedding-Bouquet",
			"Find-a-Modern-Day-Mr.-Darcy",
			"Hang-a-Door",
			"Hide-a-Nose-Piercing-from-your-Parents",
			"Get-Your-Best-Friend-Back",
			"Convert-Pictures-To-Jpeg",
			"Give-Directions",
			"Get-Rid-of-a-Headache",
			"Make-Dried-Fruit",
			"Relax-Your-Mind",
			"Tell-Your-Crush-You-Like-Them",
			"Draw-a-Family-Tree",
			"Speak-Rastafarian-English",
			"Paint-a-Mural",
			"Drink-Responsibly",
			"Write-a-Debate-Speech",
			"Introduce-a-New-Dog-to-Your-House-and-Other-Dogs",
			"Catch-the-3-Regis-in-Pokemon-Sapphire/Ruby-Version",
			"Identify-a-Venomous-Snake",
			"Make-a-Root-Beer-Float",
			"Make-Aloe-Vera-Gel",
			"Make-the-Most-of-Your-Summer-Vacation-(for-Teens)",
			"Measure-Body-Fat-Using-the-US-Navy-Method",
			"Be-Humble",
			"Fall-Out-of-Love",
			"Open-a-Bottle-Without-a-Bottle-Opener",
			"Sell-Original-Artwork-for-Profit",
			"Make-a-Product-Catalog",
			"Do-Wood-Inlay",
			"Sketch",
			"Work-Smart,-Not-Hard",
			"Create-a-Working-Budget",
			"Make-an-iPhone-App",
			"Create-an-Orkut-Account",
			"Look-Like-Selena-Gomez",
			"Build-a-Simple-Treasure-Chest",
			"Create-a-Strong-Burning-Charcoal-Fire-Without-Lighter-Fluid",
			"Get-Your-Girlfriend-to-Kiss-or-Hug-You-More-Often",
			"Make-Your-Own-Herbal-Cigarettes",
			"Survive-a-Nuclear-Attack",
			"Dance-at-a-School-Dance-(for-Guys)",
			"Remove-Silicone-Caulk-from-Hands",
			"Impress-Your-Teachers",
			"Avoid-Looking-Desperate",
			"Have-a-Fun,-Interesting-Conversation-Via-Text",
			"Give-a-Lap-Dance",
			"Write-a-Summary-of-Your-Computer-Proficiency",
			"Restrict-Web-Browsing-Using-Internet-Explorer",
			"Dig-Swales",
			"Tell-if-a-Mirror-Is-Two-Way-or-Not",
			"Make-Your-Boyfriend-Break-up-With-You",
			"Whiten-Teeth-with-Natural-Methods",
			"Grind-Coffee-Beans-Without-a-Grinder",
			"Make-Your-Own-Nintendo-DS-Games",
			"Change-a-Law-Through-the-Democratic-Process",
			"Speak-Spanish-Fluently",
			"Desulphate/Revive-a-Lead-Acid-Battery",
			"Reload-a-Pistol-and-Clear-Malfunctions",
			"Remove-Onion-Smell-From-Hands",
			"Make-a-Flying-Model-Plane-from-Scratch",
			"Use-GPS-in-Android",
			"Design-Your-Own-Home",
			"Flip-a-House",
			"Fill-Out-a-Money-Order",
			"Organize-Bookmarks-in-Firefox",
			"Be-Wittier",
			"Avoid-an-H.-Pylori-Bacterial-Infection",
			"Be-Fun-and-Flirty",
			"Ship-a-Bicycle-Cheaply",
			"Win-Informal-Arguments-and-Debates",
			"Cut-a-Coped-Joint-in-Wood-Trim",
			"Make-a-White-Chocolate-Mocha",
			"Lose-Fat-With-Weights",
			"House-Train-a-Puppy",
			"Make-a-4-Strand-Braided-Bracelet",
			"Create-a-Good-Hair-Care-Routine-for-Men",
			"Walk-Like-a-Diva",
			"Deal-With-Losing-a-Friend",
			"Be-a-Mature-Teenager",
			"Strip-and-Wax-a-Floor",
			"Create-a-Currency-Converter-With-Microsoft-Excel",
			"Kiss-Your-Boyfriend",
			"Get-Rid-of-Slugs-and-Snails-With-Yeast",
			"Write-a-Personal-Mission-Statement",
			"Build-Muscle",
			"Build-an-External-Hard-Drive",
			"Build-the-Ultimate-Tippmann-Sniper-Paintball-Gun",
			"Cure-or-Alleviate-Edema",
			"Clean-Your-Retainer",
			"Prevent-a-Suicide",
			"Soak-an-Ingrown-Toenail",
			"Recycle-Old-Plastic-Bags",
			"Decode-a-Caesar-Box-Code",
			"Mix-Mortar-for-Bricklaying-or-Stone",
			"Plan-a-Hawaiian-Luau-Birthday-Party-for-Kids",
			"Get-Over-a-Relationship-in-Less-Than-a-Week",
			"Switch-from-Hotmail-to-Gmail",
			"Get-Rid-of-a-Runny-Nose",
			"Deal-With-a-Difficult-Daughter-in-Law",
			"Make-a-Webcam-Into-an-Infrared-Camera",
			"Know-if-a-Girl-Digs-You",
			"Camp-in-the-Rain",
			"Decide-if-You-Should-Get-Bangs-or-Not",
			"Make-a-Quiver-for-Arrows",
			"Make-a-Rag-Rug",
			"Clean-Eyeglasses",
			"Solve-Math-Problems",
			"Have-a-Fun-Last-Day-of-School",
			"Be-the-Prettiest-Girl-in-School",
			"Block-a-Website-in-Internet-Explorer-7",
			"Get-Organized-in-High-School",
			"Have-Girly-Handwriting",
			"Change-Colours-in-Command-Prompt",
			"Install-a-Ceiling-Fan",
			"Make-Sore-Muscles-Feel-Good",
			"Detect-Diabetes-in-Dogs",
			"Wire-a-3-Way-Light-Switch",
			"Play-DotA",
			"Store-Blueberries",
			"Do-Your-Dailies-on-Neopets",
			"Fix-a-Shaking-Washing-Machine",
			"Defuse-an-Argument",
			"Be-Hot",
			"Solve-a-Rubik's-Cube-with-the-Layer-Method",
			"Make-Chocolate-Dipped-Strawberries",
			"Make-Peace-With-a-Friend-After-a-Fight",
			"Clean-Smelly-Sneakers",
			"Get-More-Fans-for-Your-Facebook-Page",
			"Earn-a-Girl's-Trust-Back-After-Lying",
			"Make-a-Simple-Paper-Airplane",
			"Become-a-Professional-Photographer",
			"Increase-Concentration-While-Studying",
			"Select-a-Gift-for-a-Guy",
			"Prevent-Air-Sickness-on-a-Plane",
			"Reuse-Empty-Water-Bottles",
			"Put-an-eBook-on-an-iPad",
			"Make-a-Lemon-Juice-Air-Freshener",
			"Say-Yes-in-Different-Languages",
			"Grow-out-Your-Fringe-Without-It-Being-Annoying-and-in-the-Way",
			"Make-a-Fast-Paper-Airplane",
			"Do-a-Presentation-in-Class",
			"Look-Great-in-Skinny-Jeans",
			"Kill-Head-Lice-Naturally",
			"Not-Be-Annoying-to-Your-Crush",
			"Get-a-Basic-Wardrobe-(for-Girls)",
			"Overcome-Boredom",
			"Properly-Dispose-of-a-Bible",
			"Skateboard",
			"Warm-up-for-Weight-Lifting-Exercises",
			"Win-a-Street-Fight",
			"Fix-Bushy-Eyebrows-(for-Girls)",
			"Get-a-Body-Like-Beyonce",
			"Download-Free-Music-on-Your-iPod",
			"Write-a-Guitar-Solo",
			"Clean-a-Toilet-With-Coke",
			"Gargle-Saltwater",
			"Succeed-in-College",
			"Live-on-Minimum-Wage",
			"Make-Any-Girl-Want-to-Kiss-You",
			"Pretend-You-Come-from-a-Rich-Family",
			"String-a-Lacrosse-Stick",
			"Make-a-Cosmopolitan",
			"Learn-Any-Language",
			"Get-Along-with-Your-Mother-in-Law",
			"Get-Rid-of-Smoke-Smell-in-a-Room",
			"Build-a-Cajon",
			"Win-Chess-Almost-Every-Time",
			"Uninstall-AVG-Antivirus-Free-Edition-2012",
			"Build-a-Backyard-Firepit",
			"Make-a-Mother's-Day-Card",
			"Be-Flexible",
			"Destroy-a-Hard-Drive",
			"Defeat-a-Facebook-Addiction",
			"Ask-for-a-Pay-Raise",
			"Deal-With-One-of-Your-Friends-Dating-Your-Crush",
			"Remove-Fruit-Juice-Stains-from-Carpet",
			"Get-Good-Grades",
			"Have-a-Pretty-Summer-Look",
			"Do-the-Cat-Daddy",
			"Use-the-Indented-Bottom-of-a-Soda-Bottle",
			"Void-a-Check",
			"Care-for-Naturally-Curly-or-Wavy-Thick-Hair",
			"Fix-an-Xbox-360-Wireless-Controller-That-Keeps-Shutting-Off",
			"Prevent-Pads-from-Leaking-While-on-Your-Period",
			"Know-You're-Hungry-(and-Avoid-Eating-when-You're-Not)",
			"Style-Your-Hair",
			"Attract-the-Guy-You-Have-a-Crush-On",
			"Buy-Property-in-Florida",
			"Run",
			"Build-a-Brick-Wall",
			"Paint-Baseboards",
			"Use-Solver-in-Microsoft-Excel",
			"Reset-Your-BIOS",
			"Find-a-Sugar-Daddy",
			"Be-Respected",
			"Do-a-Backbend",
			"Massage-Yourself",
			"Find-a-Snake",
			"Reply-to-Someone-on-Tumblr",
			"Draw-3D-Block-Letters",
			"Replace-a-Projector-Lamp",
			"Transplant-a-Young-Tree",
			"Wolf-Whistle",
			"Become-a-Pixel-Artist",
			"Deal-With-Intrusive,-Needy-Mother-In-Laws",
			"Upload-Music-from-an-Mp3-Player-to-Windows-Media-Player",
			"Clean-a-Flat-Iron",
			"Have-a-Good-Skin-Care-Regime-(Teen-Girls)",
			"French-Twist-Hair",
			"Prevent-Your-Cell-Phone-from-Being-Hacked",
			"Relax-Before-Going-to-Bed",
			"Make-Vegetable-Biryani",
			"Spot-a-Fake-Social-Security-Card",
			"Soothe-a-Teething-Baby",
			"Prevent-Side-Aches",
			"Get-Rid-of-Man-Boobs",
			"Avoid-Bumps-When-Plucking-Hair",
			"Follow-the-Curly-Girl-Method-for-Curly-Hair",
			"Stop-Being-a-People-Pleaser",
			"Survive-an-Avalanche",
			"Make-Four-Legged-Pipe-Cleaner-Animals",
			"Check-if-a-Number-Is-Prime",
			"Look-Instantly-Thinner",
			"Kiss-in-Public",
			"Be-the-Most-Romantic-Boyfriend",
			"Set-Goals",
			"Open-a-Wine-Bottle-Without-a-Corkscrew",
			"Make-a-Glowstick",
			"Set-Decimal-Places-on-a-TI-BA-II-Plus-Calculator",
			"Interact-with-a-Person-Who-Uses-a-Wheelchair",
			"Change-a-File-Type-Using-Windows",
			"Monitor-Your-Apartment-With-a-Webcam-While-on-Vacation",
			"Look-Sexy",
			"Handle-People-Who-Are-Angry-at-You",
			"Clean-Your-Dishwasher-With-Kool-Aid",
			"Find-Yourself",
			"Choose-the-Right-Swimsuit",
			"Make-a-Bruise-Go-Away-Faster",
			"Be-a-Femme-Fatale",
			"Sleep-When-You-Are-Not-Tired",
			"Remove-Urine-Odors-and-Stains-Permanently",
			"Respond-to-Rude-Email-at-Work",
			"Have-a-Good-Relationship-with-Your-Girlfriend",
			"Improve-Reaction-Speed",
			"Breathe",
			"Fold-a-Paper-Rose",
			"Write-a-Children's-Book",
			"Get-a-Detention",
			"Dry-Wet-Carpet",
			"Delete-a-Facebook-Group",
			"Create-an-Animated-Movie-(Using-Windows-Movie-Maker)",
			"Dress-Like-an-Artist",
			"Use-Facebook-Places",
			"Retain-Information-when-You-Study-for-a-Test",
			"Grow-a-Goatee",
			"Analyze-Political-Cartoons",
			"Find-a-Four-Leaf-Clover",
			"Curl-Hair",
			"Boil-Crawfish",
			"Avoid-Genetically-Modified-Foods",
			"Transition-from-a-Female-to-a-Male-(Transgender)",
			"Max-Your-Car's-Horsepower",
			"Make-a-Best-Friends-Scrapbook",
			"Be-a-Good-Friend-to-a-Guy",
			"Make-a-Man-Cave",
			"Survive-a-Break-Up-(Girls)",
			"Write-a-Book",
			"Forge-Email",
			"Peel-a-Difficult-Hard-Boiled-Egg",
			"Push-Yourself-When-Running",
			"Deal-With-Overly-Competitive-Colleagues",
			"Improve-Your-Eyesight",
			"Keep-In-Touch-with-Friends",
			"Make-Your-Own-Blizzard-or-McFlurry-at-Home",
			"Arc-Weld",
			"Stretch-an-Ear-Lobe-Piercing",
			"Treat-a-Cramped-Muscle",
			"Become-a-Farmer-Without-Experience",
			"Overlay-Sectional-Aeronautical-Charts-in-Google-Earth",
			"Make-a-Smoothie",
			"Clear-a-Sinus-Infection",
			"Make-a-Sling",
			"Parkour",
			"Make-an-Egg-Facial-Mask",
			"Determine-Your-Dominant-Eye",
			"Fish-for-Flounder",
			"Look-Younger-and-Feel-Better",
			"Kiss-a-Girl",
			"Use-Your-Wii-Remote-As-a-Mouse-on-Windows",
			"Organize-Your-Digital-Photos",
			"Restrict-Web-Browsing-Using-Firefox",
			"Roll-Sushi",
			"Break-a-BIOS-Password",
			"Make-a-Star-with-String",
			"Be-Safe-in-a-Foreign-Country",
			"Make-Chili",
			"Get-Closer-to-Your-Boyfriend/Girlfriend",
			"Create-a-Flowchart",
			"Play-Beer-Pong",
			"Use-a-Gophone-Plan-With-an-iPhone",
			"Design-a-Successful-Indoor-Garden",
			"Write-an-Obituary",
			"Know-if-a-Shy-Girl-Likes-You-at-School",
			"Keep-Yourself-Happy-While-Having-Ups-and-Downs",
			"Be-a-Man",
			"Get-a-Tan",
			"Sail-a-Boat",
			"Be-a-Hard-Worker",
			"Grow-Grass-from-Seeds",
			"Rack-a-Pool-Table",
			"Look-Like-Massie-Block",
			"Cope-with-Tinnitus",
			"Get-Rid-of-Dry-Skin",
			"Grill-Tri-Tip",
			"Change-a-Sway-Bar-Link",
			"Know-if-a-Girl-is-the-One-for-You",
			"Get-Your-Parents-to-Calm-Down-when-You-Get-a-Bad-Grade",
			"Practice-Airplane-Etiquette",
			"Sit-at-a-Computer",
			"Wake-Up-Without-an-Alarm-Clock",
			"Become-a-Badass",
			"Be-a-Good-Neighbour",
			"Use-Commas",
			"Apply-Stage-Makeup",
			"Become-a-Better-Chess-Player",
			"Switch-SIM-Cards",
			"Make-Your-Crush-Stop-Liking-Another-Girl",
			"Play-Solid-Men's-Lacrosse-Defense",
			"Curl-Hair-with-Braids",
			"Whiten-Teeth-With-Baking-Soda",
			"Block-People-from-Calling-You-on-Your-Home-Phone",
			"Read-Your-Fingers",
			"Dye-Dark-Hair-a-Lighter-Color",
			"Make-a-Duct-Tape-Rose",
			"Know-if-You-Have-Spyware-on-Your-Computer",
			"Make-a-Margarita",
			"Be-a-Great-Husband",
			"Write-a-Best-Man's-Speech",
			"Ride-a-Dirt-Bike",
			"Remove-Furniture-Dents-from-Carpet",
			"Get-Big,-Bouncy-Curls",
			"Survive-Your-First-Year-of-Law-School-(USA)",
			"Construct-a-Beer-Bong",
			"Repair-Drywall-Tape-That-Is-Separating-from-Your-Walls",
			"Shave-Your-Armpits",
			"Cook-Lobster-Tails",
			"Become-Valedictorian",
			"Deal-With-a-Bad-Grade",
			"Handle-Your-Child's-Temper-Tantrum",
			"Get-Started-Playing-Hard-Rock-and-Metal-Guitar",
			"Write-Letter-of-Consent",
			"Improve-Driving-Skill",
			"Disable-Automatic-Reboot-After-Windows-Update",
			"Gain-Your-Hamsters-Trust,-Tame-Them-and-Communicate-With-Them",
			"Exercise-While-Watching-TV",
			"Put-Movies-on-a-Blank-DVD",
			"Do-Bonnaroo-When-You're-Over-50",
			"Have-a-Meaningful-Text-Message-Conversation",
			"Lessen-Underarm-Sweating",
			"Ask-Your-Professor-for-a-Letter-of-Recommendation-Via-Email",
			"Hula-Hoop",
			"Treat-a-Jammed-Finger",
			"Persuade-Your-Parents-to-Get-You-an-iPad",
			"Have-a--Strong-Personality",
			"Cope-with-Sleep-Paralysis",
			"Hook-Up-with-a-Guy",
			"Make-Spray-Paint-Stencils",
			"Put-Video-on-a-Web-Page",
			"Train-a-Golden-Retriever",
			"Answer-the-Phone-Politely",
			"Deal-With-Being-Dumped-when-You-Want-to-Remain-Friends",
			"Be-a-Girly-Girl",
			"Look-at-the-Sun-Without-Going-Blind",
			"Do-Well-on-AP-Exams",
			"Blanch-Asparagus",
			"Calculate-Probability",
			"Pick-a-Pet-Tarantula",
			"Make-A-Duct-Tape-Rose",
			"Dye-Hair-Blue",
			"Talk-to-a-Girl-over-the-Phone",
			"Make-Salt-Crystals",
			"Paint-a-Room",
			"Calculate-the-Area-of-a-Circle",
			"Lighten-Your-Hair-With-Cinnamon",
			"Focus-on-Homework",
			"Have-Soft-Shiny-Hair-Inexpensively",
			"Write-a-Mystery-Story",
			"Fix-a-Relationship-After-One-Partner-Has-Cheated",
			"Make-Potato-Wedges",
			"Be-Proactive",
			"Do-a-Quick-and-Easy-Hair-Bun",
			"Remove-Bags-from-Under-Your-Eyes",
			"Build-Muscle-Doing-Push-Ups",
			"Adjust-to-a-New-Job",
			"Throw-a-Party--and-Hide-It-from-Your-Parents",
			"Care-for-a-New-Navel-Piercing",
			"Multiply-Square-Roots",
			"Drain-and-Refill-Your-Swimming-Pool",
			"Mingle-With-Strangers-at-Parties",
			"Winterize-a-Vacant-Home",
			"Help-Cure-Your-Paranoia",
			"Log-Into-a-Linksys-Router",
			"Harvest-Rhubarb",
			"Make-a-Beaded-Necklace",
			"Make-a-Custom-Music-Mix-(for-Cheer-or-Dance)",
			"Make-Iced-Green-Tea",
			"Soothe-a-Mosquito-Bite",
			"Make-Your-Hair-Blonder",
			"Confront-Someone-Who's-Giving-You-the-Silent-Treatment",
			"Train-to-Be-a-Ninja-With-a-Low-Budget",
			"Make-a-Video-Using-Photoshop",
			"Apply-Makeup-According-to-Your-Face-Shape",
			"Stop-Sweet-Cravings",
			"Get-Your-Ex-Boyfriend-to-Come-Back-to-You",
			"Read-Women's-Body-Language-for-Flirting",
			"Keep-Your-Private-Parts-Clean",
			"Get-a-Great-Bikini-Butt",
			"Clean-a-Venetian-Blind",
			"Open-a-Combination-Lock",
			"Avoid-Car-Sickness",
			"Take-Erotic-Photos-of-Yourself",
			"Get-White-Hair",
			"Succeed-at-Psychometric-Tests",
			"Set-Up-Google-TV",
			"Have-No-Sense-of-Humor",
			"Be-the-Girl-All-The-Guys-Want",
			"Have-a-Rebound-Relationship",
			"Make-Homemade-Cat-Repellent",
			"Pack-Your-Possessions-When-Moving",
			"Ungoogle-Yourself",
			"Crystallize-Organic-Compounds",
			"Roll-Up-Shirt-Sleeves",
			"Do-a-Double-Linear-Interpolation",
			"Make-a-Duct-Tape-Mini-Skirt",
			"Make-Bananas-Ripen-Faster",
			"Dreadlock-Any-Hair-Type-Without-Products",
			"Make-Your-Own-Electricity",
			"Comfort-Your-Girlfriend-when-She-Is-Upset",
			"Program-a-Keyless-Entry-for-a-Chevy-Silverado",
			"Take-Good-Care-of-Your-Laptop-Computer",
			"Play-Solitaire",
			"Stop-a-Sneeze",
			"Feel-Beautiful",
			"Read-Palms",
			"Make-a-DMG-File-on-a-Mac",
			"Avoid-Copyright-Infringement",
			"Cheat-a-Polygraph-Test-(Lie-Detector)",
			"Revive-Dried-Out--Markers",
			"Speak-With-a-Fake-Italian-Accent",
			"Draw-Realistic-People",
			"Fix-an-Old-or-Clogged-Ink-Cartridge-the-Cheap-Way",
			"Prepare-Tofu",
			"Remedy-Oversalted-Cooking",
			"Get-Inside-a-Girl's-Head",
			"Dance-With-a-Girl-in-a-Club",
			"Draw-the-Eiffel-Tower",
			"Make-a-Soap-Carving",
			"Improve-the-Quality-of-Your-Voice",
			"Take-Care-of-Kittens",
			"Plan-a-Road-Trip",
			"Fly-a-Holding-Pattern",
			"Make-a-Ring-from-a-Silver-Coin",
			"Get-Your-First-Tattoo",
			"Make-Honeycomb-in-Cadbury-Crunchie",
			"Gather-and-Use-Twitter-Metrics",
			"Make-Oxygen-and-Hydrogen-from-Water-Using-Electrolysis",
			"Make-Your-Boyfriend-Feel-Happy",
			"Dry-Roses",
			"Play-Risk",
			"Make-Your-Hair-Smooth-and-Shiny-with-Milk-and-Eggs",
			"Make-a-Girl-Think-You're-Cute",
			"Be-a-Good-Master-of-Ceremonies",
			"Find-the-Best-Foundation-Color-Shade-for-You",
			"Tell-That-Your-Crush-Likes-You-Back",
			"Download-All-Images-on-a-Web-Page-at-Once",
			"Find-the-Equation-of-a-Line",
			"Make-a-Paper-Boomerang",
			"Read-Faster",
			"Hook-up-Your-iPod-to-a-Car-Stereo",
			"Make-Your-Bucket-List",
			"Pick-a-Good-Place-to-Go-on-Your-First-Date",
			"Stop-Talking-to-Yourself",
			"Make-an-Electromagnetic-Pulse",
			"Draw-Blood",
			"Speak-Basic-French",
			"Stop-Ants-Coming-Into-Your-Home",
			"Win-at-Flamin'-Finger",
			"Apply-Feng-Shui-to-a-Room",
			"Setup-Gmail-on-an-iPhone",
			"Select-the-Correct-Filter-Size-for-Your-Swimming-Pool",
			"Make-Fried-Pickles",
			"Reduce-Hemorrhoid-Pain",
			"Edit-Text-After-Scanning",
			"Make-Red-Raspberry-Jam",
			"Find-Your-Engine's-Top-Dead-Center-(TDC)",
			"Make-Sassafras-Tea",
			"Make-Distilled-Water",
			"Keep-Your-Navel-Piercing-Clean",
			"Program-a-Video-Game",
			"Speak-With-a-Yorkshire-Accent",
			"Celebrate-Father's-Day",
			"Show-Empathy",
			"Ease-Finger-Soreness-when-Learning-to-Play-Guitar",
			"Create-a-Friendship-in-60-Seconds",
			"Deal-with-Braces",
			"Tenderize-Beef",
			"Nap",
			"Cure-Olives",
			"Make-Your-Own-Bath-Salts",
			"Get-the-Perfect-Beach-Body",
			"Make-a-Cat-Scratching-Post",
			"Become-a-Real-Estate-Appraiser",
			"Make-Torches",
			"Make-a-Quick-Greek-Goddess-Costume",
			"Breed-Guppies",
			"Make-Stick-Deodorant",
			"Drive-Tactically-(Technical-Driving)",
			"Act-when-the-Police-Pull-You-Over-(USA)",
			"Make-a-Psi-Wheel",
			"Deal-With-a-Pawn-Shop",
			"Join-a-Conversation",
			"Dye-Your-Hair-Blonde-and-Black-Underneath",
			"Make-Your-Own-Skirt",
			"Start-a-Friends-With-Benefits-Relationship",
			"Shave-With-an-Electric-Shaver",
			"Get-Rid-of-Garden-Slugs",
			"Cook-Ribs-in-the-Oven",
			"Understand-Soccer-Strategy",
			"Make-Mead",
			"Make-a-Bamboo-Wind-Chime",
			"Invest-in-Stocks",
			"Clean-Converse-All-Stars",
			"Avoid-Leg/Foot-Cramps-in-Bed",
			"Increase-Your-Sperm-Count",
			"Upgrade-to-Internet-Explorer-8",
			"Bench-Press",
			"Avoid-Blushing-at-Inappropriate-Times",
			"Make-Google-Go-Crazy",
			"Make-a-Homemade-Protein-Shake-Without-Protein-Powder",
			"Lunge-a-Horse",
			"Get-Rid-of-a-Fever",
			"Say-Common-Words-in-Bengali",
			"Chew-Tobacco",
			"Make-an-Origami-Heart",
			"Drink-Green-Tea-Properly",
			"Remove-Windows-Genuine-Advantage-Notifications",
			"Become-a-Popular-DJ-and-Make-Money",
			"Care-for-Your-Teeth",
			"Bend-a-Spoon",
			"Write-an-Address-on-an-Envelope",
			"Get-Longer-Lashes",
			"Get-a-Tan-Tattoo",
			"Create-a-Career-Portfolio",
			"Describe-a-Color-to-a-Blind-Person",
			"Make-Eggs-in-a-Basket",
			"Remove-Pet-Urine-from-Carpet",
			"Connect-Two-Computers",
			"Maintain-a-High-GPA-in-College",
			"Reuse-Old-Wine-Corks",
			"Unclog-a-Clogged-Ear",
			"Find-Out-if-a-Good-Friend-Is-Crushing-on-You",
			"Deal-with-Religious-People-if-You-Are-an-Atheist",
			"Tie-a-Noose",
			"Pose-Like-a-Model",
			"Tell-if-He-Really-Loves-You",
			"Get-Rid-of-Social-Anxiety",
			"Survive-an-Encounter-with-a-Crocodile-or-Alligator",
			"Record-a-Song-With-Audacity",
			"Do-a-Home-Body-Wrap",
			"Retweet",
			"Haggle",
			"Deal-With-Embarrassment",
			"Convince-Your-Parents-to-Let-You-Get-a-Small-Dog",
			"Make-a-Paper-Football",
			"Avoid-Having-Sagging-Breasts-As-A-Young-Woman",
			"Be-a-Good-Personal-Assistant",
			"Find-Out-if-a-Girl-Is-Mad-at-You",
			"Have-a-Good-Family-Life",
			"Take-Care-of-Inchworms",
			"Persuade-an-Atheist-to-Become-Christian",
			"Deal-With-an-Extremely-Codependent-Family",
			"Train-Your-Body",
			"Remove-Fingernail-Polish-From-Carpet",
			"Get-Smart-in-Math",
			"Do-an-Eye-Exam",
			"Calm-Your-Angry-Cat",
			"Teach-Your-Baby-to-Self-Settle-to-Sleep",
			"Recreate-Your-Life",
			"Remove-Sideburns-(For-Girls)",
			"Get-a-Date",
			"Make-Brochures-on-Microsoft-Word",
			"Sort-Your-Life-Out",
			"Get-a-Fake-Tan-That-Looks-Real",
			"Get-Stains-out-of-Wood",
			"Dress-Parisian-Chic",
			"Hold-and-Use-a-Cane-Correctly",
			"Choose-the-Right-Ceiling-Fan",
			"Be-Romantic",
			"Cheer-Up-Your-Boyfriend",
			"Make-a-Caramel-Macchiato",
			"Install-Posts-in-the-Water-for-a-Dock-or-Pier",
			"Get-Out-from-Under-the-Payday-Loan-Trap",
			"Avoid-Food-Triggered-Seizures",
			"Post-Ads-to-Craigslist",
			"Care-for-a-Rabbit",
			"Travel-to-Canada-with-a-Felony-Charge",
			"Remove-a-Boot-Sector-Virus",
			"Make-Hair-Wraps",
			"Take-Care-of-a-Chameleon",
			"Reboot-an-iPod-Touch",
			"Dye-Hair-Bright-Red-Under-Black-Hair",
			"Be-a-Bad-Girl",
			"Get-Closure-from-a-Relationship",
			"Make-an-Origami-Flapping-Bird",
			"Avoid-Throwing-Up",
			"Create-Your-Acting-Resume",
			"Cool-Yourself-Down-on-a-Hot-Day",
			"Make-Sugar-Glass",
			"Use-a-Bobby-Pin",
			"Make-a-Shirt-out-of-a-One-Dollar-Bill",
			"Calculate-the-Volume-of-a-Sphere",
			"Get-Rid-of-a-Squirrel-in-Your-House",
			"Stay-Calm-During-Your-Menstrual-Cycle",
			"Be-Cool-and-Popular-in-Sixth-Grade",
			"Dye-Your-Hair-Without-Your-Mom-Knowing",
			"Avoid-Insect-Bites-While-Sleeping",
			"Secure-Your-Wireless-Home-Network",
			"Use-Shower-Gel",
			"Be-a-Hippie",
			"Use-a-Pizza-Stone",
			"Use-Plants-to-Keep-Mosquitoes-Away",
			"Repot-a-Plant",
			"Stop-Getting-Distracted-when-Trying-to-Get-Things-Done",
			"Place-Your-Fingers-Properly-on-Piano-Keys",
			"Make-3D-Images-in-Photoshop",
			"Find-a-Lost-iPhone",
			"Be-a-Normal-Good-Looking-Girl-from-Inside-and-Outside",
			"Find-Social-Actions-by-Source-in-Google-Analytics",
			"Calculate-the-Time-Signature-of-a-Song",
			"Host-a-Murder-Mystery-Party",
			"Delete-a-Paypal-Account",
			"Refuse-a-Kiss",
			"Memorize-Math-and-Physics-Formulas",
			"Do-a-Flip-Turn-(Freestyle)",
			"Make-Dogs-Love-You",
			"Fix-Yu-Gi-Oh-Power-of-Chaos's-Data-Saving-Problem",
			"Write-a-Thank-You-Note-to-a-Teacher",
			"Make-Your-Lips-Smooth",
			"Permanently-Remove-Sensitive-Files-and-Data-from-a-Computer",
			"Install-a-Pair-of-New-Car-Speakers-and-Amplifier",
			"Make-a-Doggie-Birthday-Cake",
			"Have-Good-Hygiene-(Girls)",
			"Fix-a-Sagging-Couch",
			"Make-Your-Parents-Proud-of-You",
			"Tell-Your-Boyfriend-You're-Pregnant",
			"Be-a-Metalhead",
			"Get-Super-Glue-Off-Skin",
			"Write-an-Informative-Speech",
			"Say-\"I-Love-You\"",
			"Make-Powdered-Sugar",
			"Use-Body-Language-to-Keep-a-Guy-Wanting-More",
			"Write-a-Table-of-Contents",
			"Email-a-Scanned-Document",
			"Have-a-Great-Marriage",
			"Post-Your-Resume-Online",
			"Get-Rid-of-Tobacco-Odors-in-Cars",
			"Make-an-Essay-Appear-Longer-Than-It-Is",
			"Gain-a-Competitive-Advantage-in-Business",
			"Play-the-Tin-Whistle",
			"Make-an-Outdoor-Fountain",
			"Serve-a-Tennis-Ball",
			"Apply-Foundation-and-Concealer-Correctly",
			"Gather-Earthworms",
			"Be-Fearless",
			"Prevent-Spotting-on-Birth-Control",
			"Make-a-Yarn-Doll",
			"Remove-Blackheads",
			"Make-3D-Images-Using-StereoPhoto-Maker",
			"Use-a-Screw-Extractor",
			"Safely-Meet-a-Guy-Through-Internet-Dating",
			"Make-a-Flyer",
			"Organize-Kitchen-Cabinets",
			"Tell-a-Boy-You-Like-Him",
			"Find-the-Minimum-and-Maximum-Points-Using-a-Graphing-Calculator",
			"Look-Like-a-Rocker",
			"Attract-Girls",
			"Wear-a-Claddagh-Ring",
			"Whittle-a-Ball-in-a-Cage",
			"Avoid-Going-Over-an-Essay-Word-Limit",
			"Make-a-Woman-Feel-Better-While-She's-on-Her-Period",
			"Respect-Your-Elders",
			"Do-Your-Makeup-Flawlessly",
			"Mix-Colors",
			"Increase-the-Speed-and-Accuracy-of-Your-Kicks-in-Tae-Kwon-Do",
			"Make-Fake-Error-Message-Using-Notepad",
			"Make-People-Instantly-Like-You",
			"Apologize-For-Cheating-on-Your-Partner",
			"Fish-a-Small-Creek",
			"Make-a-Mirror",
			"Create-a-Custom-Page-on-Tumblr",
			"Make-Your-Own-Refrigerator-Magnets",
			"Use-a-Tampon",
			"Bear-a-Job-That-You-Hate",
			"Make-Fake-Rocks-for-Your-Pond",
			"Tell-if-You-Are-Codependent",
			"Snipe-on-eBay",
			"Get-Rid-of-Calluses",
			"Make-a-Paper-Mosaic",
			"Use-a-Washing-Machine",
			"Calculate-Your-Target-Heart-Rate",
			"Welcome-New-Neighbors",
			"Factor-Second-Degree-Polynomials-(Quadratic-Equations)",
			"Make-a-Hot-Soothing-Lemon-Drink",
			"Get-Tons-of-Subscribers-on-Youtube",
			"Become-a-Burlesque-Star",
			"Make-Iced-Tea-You-Can-Drink-Immediately",
			"Speak-With-an-Australian-Accent",
			"Open-the-Chakras-(from-Avatar)",
			"Care-for-Orchids",
			"Run-Faster",
			"Grow-Watermelons",
			"Write-With-Your-Left-Hand-(if-Right-Handed)",
			"Remove-Odors-from-Your-Car",
			"Master-the-Art-of-Kissing",
			"Start-a-Career-in-Information-Technology",
			"Become-a-Vampire-in-Sims-2",
			"Ask-a-Lady-Out-If-You're-Shy",
			"Cook-Rice-with-Chicken-Broth",
			"Become-a-US-Citizen",
			"Become-a-Quiet-Person",
			"Clean-a-Shower",
			"Remember-to-Take-Medication",
			"Get-Rid-of-Fire-Ants",
			"Hang-a-Picture",
			"Get-Water-Stains-Off-a-Ceiling",
			"Fold-an-Origami-Star-(Shuriken)",
			"Make-Onigiri",
			"Not-Get-Nervous",
			"Read-a-Book",
			"Get-Loose-Waves",
			"Restore-a-Whiteboard",
			"Stop-Thinking-About-Your-Ex",
			"Install-a-Security-/-CCTV-System-for-Your-Home",
			"Lose-Weight-Quickly-and-Safely-(for-Teen-Girls)",
			"Open-a-Coconut",
			"Play-Baseball",
			"Read-an-Aviation-Routine-Weather-Report-(METAR)",
			"Deal-With-Your-Period",
			"Speed-up-Your-Reflexes",
			"Treat-Eczema-Naturally",
			"Stretch-for-Ballet",
			"Compost",
			"Teach-Your-Dog-to-Sit",
			"Get-a-Free-Massage",
			"Do-a-Push-Up",
			"Plant-Seeds-in-a-Basic-Seed-Tray",
			"Rip-a-Phonebook-in-Half",
			"Survive-in-Federal-Prison",
			"Prevent-a-Writer's-Bump-Callus",
			"Make-a-Box-Plot",
			"Win-a-Tickle-Fight",
			"Prepare-for-a-Tsunami",
			"Write-a-Harry-Potter-Acceptance-Letter",
			"Know-If-You-Are-a-Lesbian",
			"Stop-Falling-in-Love",
			"Paint-Concrete-Statues",
			"Straighten-Your-Hair-Without-Heat",
			"Fix-a-Head-Gasket-With-Engine-Block-Sealer",
			"Use-a-Self-Service-Car-Wash",
			"Shampoo-Your-Hair",
			"Get-Rid-of-Moles-in-Your-Lawn",
			"Cheat-on-a-Test-Using-Electronics",
			"Quiet-a-Barking-Dog",
			"Look-Like-an-Asian-Doll",
			"Get-Rid-of-Acne-in-1-Day",
			"Play-Red-Alert-2-over-the-Internet",
			"Build-a-Plastic-Parachute",
			"Create-a-Super-Hero",
			"Longboard-Skateboard",
			"Know-if-You-Can-Sing",
			"Protect-Yourself-in-a-Thunderstorm",
			"Upload-Photos-to-Facebook-Using-the-Facebook-for-iPhone-Application",
			"Hire-a-Lawyer-When-You-Have-Low-Income",
			"Listen",
			"Know-What-to-Feed-to-Guinea-Pigs",
			"Throw-a-Knife-Without-It-Spinning",
			"Understand-Canadian-Slang",
			"Make-Glow-Jars",
			"Create-an-Astrological-Chart",
			"Gather-Fabric-into-Ruffles",
			"Make-Your-Legs-Super-Soft-and-Super-Sexy",
			"Be-Nice",
			"Choose-Components-for-Building-a-Computer",
			"Grow-a-Square-Watermelon",
			"Fly-in-Your-Dreams",
			"Make-a-Girl-Look-Like-a-Boy",
			"Get-Good-Skin-with-Milk",
			"Remove-a-Stuck-GE-Washer-Agitator",
			"Get-Smooth-Legs",
			"Store-Bread",
			"Deal-With-an-Envious-Friend",
			"Get-Your-Cat-to-Like-You",
			"Put-Music-from-YouTube-on-Your-iPod",
			"Lift-One-Eyebrow",
			"Get-Pumped-up-Right-Before-a-Game",
			"Practice-Yoga-Daily",
			"Make-a-Mango-Float",
			"Gain-Karma-on-Reddit",
			"Meditate-to-Get-to-Sleep",
			"Make-a-Candy-Lei",
			"Remove-Bicycle-Handlebar-Grips",
			"Deal-With-a-Boyfriend-Who-Has-Asperger's-Syndrome",
			"Stop-Being-Scared-After-Watching-Scary-Movies"
			);
		return in_array($this->t->getDBKey(), $testArticles) !== false ? true : false;
	}

	protected function addCSSLibs() {
		global $wgLanguageCode;

		parent::addCSSLibs();
		if ($wgLanguageCode == 'en') {
			$this->addCSS('mcmc'); // Checkmark css
		}
	}

	protected function addJSLibs() {
		global $wgLanguageCode;

		parent::addJSLibs();
		if ($wgLanguageCode == 'en') {
			self::addJS('cm', true); // checkmark js
		}
	}
}
class MobileBasicArticleBuilder extends MobileHtmlBuilder {

	protected function generateHeader() {
		$headerVars = $this->getDefaultHeaderVars();
		return EasyTemplate::html('header.tmpl.php', $headerVars);
	}

	protected function getArticleParts() {
		return $this->parseNonMobileArticle($this->nonMobileHtml);
	}


	protected function generateBody() {
		global $wgLanguageCode;

		list($sections, $intro, $firstImage) = $this->getArticleParts();
		if ($firstImage) {
			$title = Title::newFromURL($firstImage, NS_IMAGE);
			if ($title) {
				$introImage = RepoGroup::singleton()->findFile($title);
			}
			if ($introImage) {
				$thumb = $introImage->getThumbnail(290, 194);
				$width = $thumb->getWidth();
				$height = $thumb->getHeight();
			} else {
				$firstImage = '';
			}
		} 

		//articles that we don't want to have a top (above tabs)
		//image displayed
		$titleUrl = "";
		if($this->t != null)
			$titleUrl = $this->t->getFullURL();
		$exceptions = ConfigStorage::dbGetConfig('mobile-topimage-exception');
		$exceptionArray = explode("\n", $exceptions);
		if(in_array($titleUrl, $exceptionArray)) {
			$firstImage = false;
		}

		if (!$firstImage) {
			$thumb = null;
			$width = 0; $height = 0;
		}

		$deviceOpts = $this->getDevice();
		$articleVars = array(
			'title' => $this->t->getText(),
			'sections' => $sections,
			'intro' => $intro,
			'thumb' => &$thumb,
			'width' => $width,
			'height' => $height,
			'deviceOpts' => $deviceOpts,
			'nonEng' => $wgLanguageCode != 'en',
			'isGerman' => $wgLanguageCode == 'de',
		);
		$this->addExtendedArticleVars(&$articleVars);

		$this->setTemplatePath();
		return EasyTemplate::html('article.tmpl.php', $articleVars);
	}

	protected function addExtendedArticleVars(&$vars) {
		// Nothing to add here. Used for subclasses to inject variables to be passed to article.tmpl.php html
	}
	protected function generateFooter() {
		$footerVars = $this->getDefaultFooterVars();	
		$t = $this->t;
		$partialUrl = $t->getPartialURL();
		$footerVars['redirMainUrl'] = $footerVars['redirMainUrl'] . urlencode($partialUrl);
		$baseMainUrl = 'http://' . MobileWikihow::getNonMobileSite() . '/';
		$footerVars['editUrl'] = $baseMainUrl . 'index.php?action=edit&title=' . $partialUrl;
		return EasyTemplate::html('footer.tmpl.php', $footerVars);
	}

	protected function addCSSLibs() {
		parent::addCSSLibs();
		$this->addCSS('mwha');
	}

	protected function addJSLibs() {
		parent::addJSLibs();
	}

	/**
	 * Parse and transform the document from the old HTML for NS_MAIN articles to the new mobile
	 * style. This should probably be pulled out and added to a subclass that can then be extended for
	 * builders that focus on building NS_MAIN articles
	 */
	protected function parseNonMobileArticle(&$article) {
		global $wgWikiHowSections, $IP, $wgContLang;

		$sectionMap = array(
			wfMsg('Intro') => 'intro',
			wfMsg('Ingredients') => 'ingredients',
			wfMsg('Steps') => 'steps',
			wfMsg('Video') => 'video',
			wfMsg('Tips') => 'tips',
			wfMsg('Warnings') => 'warnings',
			wfMsg('relatedwikihows') => 'relatedwikihows',
			wfMsg('sourcescitations') => 'sources',
			wfMsg('thingsyoullneed') => 'thingsyoullneed',
		);

		$lang = MobileWikihow::getSiteLanguage();
		$imageNsText = $wgContLang->getNsText(NS_IMAGE);
		$device = MobileWikihow::getDevice();

		// munge steps first
		$opts = array(
			'no-ads' => true,
		);
		require_once("$IP/skins/WikiHowSkin.php");
		$article = WikiHowTemplate::mungeSteps($article, $opts);

		// Make doc correctly formed
$articleText = <<<DONE
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="$lang" lang="$lang">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset='utf-8'" />
</head>
<body>
$article
</body>
</html>
DONE;
		require_once("$IP/extensions/wikihow/mobile/JSLikeHTMLElement.php");
		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->registerNodeClass('DOMElement', 'JSLikeHTMLElement');
		$doc->strictErrorChecking = false;
		$doc->recover = true;
		//$doc->preserveWhiteSpace = false;
		//$wgOut->setarticlebodyonly(true);
		@$doc->loadHTML($articleText);
		$doc->normalizeDocument();
		//echo $doc->saveHtml();exit;
		$xpath = new DOMXPath($doc);

		// Delete #featurestar node
		$node = $doc->getElementById('featurestar');
		if (!empty($node)) {
			$node->parentNode->removeChild($node);
		}

		// Remove all "Edit" links
		$nodes = $xpath->query('//a[@id = "gatEditSection"]');
		foreach ($nodes as $node) {
			$node->parentNode->removeChild($node);
		}

		// Resize youtube video
		$nodes = $xpath->query('//embed');
		foreach ($nodes as $node) {
			$url = '';
			$src = $node->attributes->getNamedItem('src')->nodeValue;
			if (!$device['show-youtube'] || stripos($src, 'youtube.com') === false) {
				$parent = $node->parentNode;
				$grandParent = $parent->parentNode;
				if ($grandParent && $parent) {
					$grandParent->removeChild($parent);
				}
			} else {
				foreach (array(&$node, &$node->parentNode) as $node) {
					$widthAttr = $node->attributes->getNamedItem('width');
					$oldWidth = (int)$widthAttr->nodeValue;
					$newWidth = $device['max-video-width'];
					if ($newWidth < $oldWidth) {
						$widthAttr->nodeValue = (string)$newWidth;

						$heightAttr = $node->attributes->getNamedItem('height');
						$oldHeight = (int)$heightAttr->nodeValue;
						$newHeight = (int)round($newWidth * $oldHeight / $oldWidth);
						$heightAttr->nodeValue = (string)$newHeight;
					}
				}
			}
		}

		/*
		// Remove <a name="..."></a> tags
		$nodes = $xpath->query('//a[@name and not(@href)]');
		foreach ($nodes as $node) {
			$node->parentNode->removeChild($node);
		}
		*/

		// Remove templates from intro so that they don't muck up
		// the text and images we extract
		$nodes = $xpath->query('//div[@class = "template_top"]');
		foreach ($nodes as $node) {
			$node->parentNode->removeChild($node);
		}
		//self::walkTree($doc->documentElement, 1);exit;
		//echo $doc->saveHtml();exit;

		// Grab intro text
		$intro = '';
		$nodes = $xpath->query('//body/div/p');
		foreach ($nodes as $i => $node) {
			$text = $node->textContent;
			if (!empty($text) || $i == 1) {
				$introNode = $node;
				$intro = $text;
				break;
			}
		}

		if ($introNode) {
			// Grab first image from article
			$imgs = $xpath->query('//img', $introNode);
			$firstImage = '';
			foreach ($imgs as $img) {
				// parent is an <a> tag
				$parent = $img->parentNode;
				if ($parent->nodeName == 'a') {
					$href = $parent->attributes->getNamedItem('href')->nodeValue;
					if (preg_match('@(Image|' . $imageNsText . '):@', $href)) {
						$firstImage = preg_replace('@^.*(Image|' . $imageNsText .'):([^:]*)([#].*)?$@', '$2', $href);
						$firstImage = urldecode($firstImage);
						break;
					}
				}
			}

			// Remove intro node
			$parent = $introNode->parentNode;
			$parent->removeChild($introNode);
		}

		// Get rid of the <span> element to standardize the html for the
		// next dom query
		$nodes = $xpath->query('//div/span/a[@class = "image"]');
		foreach ($nodes as $a) {
			$parent = $a->parentNode;
			$grandParent = $parent->parentNode;
			$grandParent->replaceChild($a, $parent);
		}

		// Resize all resize-able images
		$nodes = $xpath->query('//div/a[@class = "image"]/img');
		$imgNum = 1;
		foreach ($nodes as $img) {
			
			$srcNode = $img->attributes->getNamedItem('src');
			$widthNode = $img->attributes->getNamedItem('width');
			$width = (int)$widthNode->nodeValue;
			$heightNode = $img->attributes->getNamedItem('height');
			$height = (int)$heightNode->nodeValue;

			$imageClasses = $img->parentNode->parentNode->parentNode->attributes->getNamedItem('class')->nodeValue;
			
			if( stristr($imageClasses, "tcenter") !== false && $width >= 500) {
				$newWidth = $device['full-image-width'];
				$newHeight = (int)round($device['full-image-width'] * $height / $width);
			}
			else {
				$newWidth = $device['max-image-width'];
				$newHeight = (int)round($device['max-image-width'] * $height / $width);
			}
			// Image: link is gone now with zooming changes!
			//$href = $a->attributes->getNamedItem('href')->nodeValue;
			//$imgName = preg_replace('@^/Image:@', '', $href);
			//$src = $srcNode->nodeValue;
			//$imgName = preg_replace('@^/images/thumb/./../([^/]+)/.*$@', '$1', $src);
			$a = $img->parentNode;
			$href = $a->attributes->getNamedItem('href')->nodeValue;
			if (!$href) {
				$onclick = $a->attributes->getNamedItem('onclick')->nodeValue;
				$onclick = preg_replace('@.*",[ ]*"@', '', $onclick);
				$onclick = preg_replace('@".*@', '', $onclick);
				$imgName = preg_replace('@.*(Image|' . $imageNsText . '):@', '', $onclick);
			} else {
				$imgName = preg_replace('@^/(Image|' . $imageNsText . '):@', '', $href);
			}
			
			$title = Title::newFromURL($imgName, NS_IMAGE);
			if (!$title) {
				$imgName = urldecode($imgName);
				$title = Title::newFromURL($imgName, NS_IMAGE);
			}

			if ($title) {
				$image = RepoGroup::singleton()->findFile($title);
				if ($image) {
					$thumb = $image->getThumbnail($newWidth, $newHeight);
					$newWidth = $thumb->getWidth();
					$newHeight = $thumb->getHeight();
					$url = wfGetPad($thumb->getUrl());

					$srcNode->nodeValue = $url;
					$widthNode->nodeValue = $newWidth;
					$heightNode->nodeValue = $newHeight;
					
					// change surrounding div width and height
					$div = $a->parentNode;
					$styleNode = $div->attributes->getNamedItem('style');
					if (preg_match('@^(.*width:)[0-9]+(px;\s*height:)[0-9]+(.*)$@', $styleNode->nodeValue, $m)) {
						$styleNode->nodeValue = $m[1] . $newWidth . $m[2] . $newHeight . $m[3];
					}

					// change grandparent div width too
					$grandparent = $div->parentNode;
					if ($grandparent && $grandparent->nodeName == 'div') {
						$class = $grandparent->attributes->getNamedItem('class');
						if ($class && $class->nodeValue == 'thumb tright') {
							$style = $grandparent->attributes->getNamedItem('style');
							$style->nodeValue = 'width:' . $newWidth . 'px;';
						}
						else if($class && $class->nodeValue == 'thumb tcenter'){
							$style = $grandparent->attributes->getNamedItem('style');
							$style->nodeValue = 'width:' . $newWidth . 'px;';
						}
						else if ($class && $class->nodeValue == 'thumb tleft') {
							//if its centered or on the left, give it double the width if too big
							$style = $grandparent->attributes->getNamedItem('style');
							$oldStyle = $style->nodeValue;
							$matches = array();
							preg_match('@(width:\s*)[0-9]+@', $oldStyle, $matches);
							
							if($matches[0]){
								$curSize = intval(substr($matches[0], 6)); //width: = 6
								if($newWidth*2 < $curSize){
									$existingCSS = preg_replace('@(width:\s*)[0-9]+@', 'width:'.$newWidth*2, $oldStyle);
									$style->nodeValue = $existingCSS;
								}
							}
						}
					}

					$thumb = $image->getThumbnail($device['image-zoom-width'], $device['image-zoom-height']);
					$newWidth = $thumb->getWidth();
					$newHeight = $thumb->getHeight();
					$url = wfGetPad($thumb->getUrl());

					$a->setAttribute('id', 'image-zoom-' . $imgNum);
					$a->setAttribute('class', 'image-zoom');
					$a->setAttribute('href', '#');
					$details = array(
						'url' => $url,
						'width' => $newWidth,
						'height' => $newHeight,
					);
					$newDiv = new DOMElement( 'div', htmlentities(json_encode($details)) );
					$a->appendChild($newDiv);
					$newDiv->setAttribute('style', 'display:none;');
					$newDiv->setAttribute('id', 'image-details-' . $imgNum);
					$imgNum++;
				}
			}
		}

		// Remove template from images, add new zoom one
		$nodes = $xpath->query('//img');
		foreach ($nodes as $node) {
			$src = ($node->attributes ? $node->attributes->getNamedItem('src') : null);
			$src = ($src ? $src->nodeValue : '');
			if (stripos($src, 'magnify-clip.png') !== false) {
				$parent = $node->parentNode;
				$parent->parentNode->removeChild($parent);
			}
		}

		// Change the width attribute from any tables with a width set.
		// This often happen around video elements.
		$nodes = $xpath->query('//table/@width');
		foreach ($nodes as $node) {
			$width = preg_replace('@px\s*$@', '', $node->nodeValue);
			if ($width > $device['screen-width'] - 20) {
				$node->nodeValue = $device['screen-width'] - 20;
			}
		}

		// Surround step content in its own div. We do this to support other features like checkmarks
		$nodes = $xpath->query('//div[@id="steps"]/ol/li');
		foreach ($nodes as $node) {
			$node->innerHTML = '<div class="step_content">' . $node->innerHTML . '</div>';
		}

		//self::walkTree($doc->documentElement, 1);
		$html = $doc->saveXML();

		$sections = array();
		$sectionsHtml = explode('<h2>', $html);
		unset($sectionsHtml[0]); // remove leftovers from intro section
		foreach ($sectionsHtml as $i => &$section) {
			$section = '<h2>' . $section;
			if (preg_match('@^<h2[^>]*>\s*<span[^>]*>\s*([^<]+)@i', $section, $m)) {
				$heading = trim($m[1]);
				$section = preg_replace('@^<h2[^>]*>\s*<span[^>]*>\s*([^<]+)</span>(\s|\n)*</h2>@i', '', $section);
				if (isset($sectionMap[$heading])) {
					$key = $sectionMap[$heading];
					$sections[$key] = array(
						'name' => $heading,
						'html' => $section,
					);
				}
			}
		}
		
		// Remove Video section if there is no longer a youtube video
		if (isset($sections['video'])) {
			if ( !preg_match('@<object@i', $sections['video']['html']) ) {
				unset( $sections['video'] );
			}
		}
		// Remove </body></html> from html
		if (count($sections) > 0) {
			$keys = array_keys($sections);
			$last =& $sections[ $keys[count($sections) - 1] ]['html'];
			$last = preg_replace('@</body>(\s|\n)*</html>(\s|\n)*$@', '', $last);
		}

		return array($sections, $intro, $firstImage);
	}
}

/*
* Builds the body of the article with appropriate javascript and google analytics tracking.  
* This is used primarily for the Mobile QG (MQG) tool.
*/
class MobileQGArticleBuilder extends MobileBasicArticleBuilder {

	protected function generateHeader() {
		return "";
	}

	protected function generateFooter() {
		return "";
	}


	// never run test for mobileqg articles
	protected function isStaticTestArticle() {
		return false;
	}

	// Override device options so we can turn off ads
	protected function getDevice() {
		$device = $this->deviceOpts;
		$device['show-ads'] = false;
		return $device;
	}

	protected function addJSLibs() {
		// Don't include the jquery JS here.  This will be added in the MQG special page
	}
}

class MobileMainPageBuilder extends MobileHtmlBuilder {
	
	protected function generateHeader() {
		$headerVars = $this->getDefaultHeaderVars();
		$headerVars['showTagline'] = true;
		return EasyTemplate::html('header.tmpl.php', $headerVars);
	}

	protected function generateBody() {
		global $wgLanguageCode;

		$featured = $this->getFeaturedArticles(7);
		$randomUrl = '/' . wfMsg('special-randomizer');
		$spotlight = $this->selectSpotlightFeatured($featured);
		$langUrl = '/' . wfMsg('mobile-languages-url');
		$vars = array(
			'randomUrl' => $randomUrl,
			'spotlight' => $spotlight,
			'featured' => $featured,
			'languagesUrl' => $langUrl,
			'imageOverlay' => $wgLanguageCode == 'en',
		);
		return EasyTemplate::html('main-page.tmpl.php', $vars);
	}

	protected function generateFooter() {
		$footerVars = $this->getDefaultFooterVars();	
		return EasyTemplate::html('footer.tmpl.php', $footerVars);
	}

	private function selectSpotlightFeatured(&$featured) {
		$spotlight = array();
		if ($featured) {
			// grab a random article from the list without replacement
			$r = mt_rand(0, count($featured) - 1);
			$spotlight = $featured[$r];
			unset($featured[$r]);
			$featured = array_values($featured); // re-key array

			$title = Title::newFromURL(urldecode($spotlight['url']));
			if ($title && $title->getArticleID() > 0) {
				$spotlight['img'] = $this->getFeatureArticleImage($title, 290, 194);
				$spotlight['intro'] = $this->getFeaturedArticleIntro($title);
			}
		}
		return $spotlight;
	}

	private function getFeatureArticleImage(&$title, $width, $height) {
		global $wgUser;
		$skin = $wgUser->getSkin();

		// The next line was taken from:
		//   SkinWikihowskin::featuredArticlesLineWide()
		$img = $skin->getGalleryImage($title, $width, $height);
		return wfGetPad($img);
	}

	private function getFeaturedArticles($num) {
		global $IP;
		$NUM_DAYS = 15; // enough days to make sure we get $num articles

		require_once("$IP/extensions/wikihow/FeaturedArticles.php");
		$featured = FeaturedArticles::getFeaturedArticles($NUM_DAYS);

		$fas = array();
		$n = 1;
		foreach($featured as $f) {
			$partUrl = preg_replace('@^http://(\w|\.)+\.wikihow\.com/@', '', $f[0]);
			$title = Title::newFromURL(urldecode($partUrl));
			if ($title) {
				$name = $title->getText();

				$fa = array(
					'name' => $name,
					'url' => $partUrl,
					'img' => $this->getFeatureArticleImage($title, 90, 54),
				);
				$fas[] = $fa;

				if (++$n > $num) break;
			}
		}

		return $fas;
	}

	private function getFeaturedArticleIntro(&$title) {
		// use public methods from the RSS feed that do the same thing
		$article = Generatefeed::getLastPatrolledRevision($title);
		$summary = Generatefeed::getArticleSummary($article, $title);
		return $summary;
	}

	protected function addCSSLibs() {
		parent::addCSSLibs();
		$this->addCSS('mwhf');
		$this->addCSS('mwhh');
	}


}

class MobileViewLanguagesBuilder extends MobileHtmlBuilder {
	
	protected function generateHeader() {
		$headerVars = $this->getDefaultHeaderVars();
		$headerVars['css'][] = 'mwhr';
		return EasyTemplate::html('header.tmpl.php', $headerVars);
	}

	protected function generateBody() {
		$vars = array('languages' => self::getLanguages());
		return EasyTemplate::html('language-select.tmpl.php', $vars);
	}

	protected function generateFooter() {
		$footerVars = $this->getDefaultFooterVars();	
		return EasyTemplate::html('footer.tmpl.php', $footerVars);
	}

	private static function getLanguages() {
		$ccedil = htmlspecialchars_decode('&ccedil;');
		$ntilde = htmlspecialchars_decode('&ntilde;');
		$ecirc = htmlspecialchars_decode('&ecirc;');
		$langs = array(
			array(
				'code' => 'en', 
				'name' => 'English',
				'url'  => 'http://m.wikihow.com/',
				'img'  => '/extensions/wikihow/mobile/images/flag_england.gif',
			),
			array(
				'code' => 'es', 
				'name' => "Espa{$ntilde}ol",
				'url'  => 'http://es.m.wikihow.com/',
				'img'  => '/extensions/wikihow/mobile/images/flag_spain.gif',
			),
			array(
				'code' => 'de', 
				'name' => 'Deutsch',
				'url'  => 'http://de.m.wikihow.com/',
				'img'  => '/extensions/wikihow/mobile/images/flag_germany.gif',
			),
			array(
				'code' => 'pt', 
				'name' => "Portugu{$ecirc}s",
				'url'  => 'http://pt.m.wikihow.com/',
				'img'  => '/extensions/wikihow/mobile/images/flag_portugal.gif',
			),
			array(
				'code' => 'fr', 
				'name' => "Fran${ccedil}ais",
				'url'  => 'http://fr.m.wikihow.com/',
				'img'  => '/extensions/wikihow/mobile/images/flag_france.gif',
			),
			array(
				'code' => 'it', 
				'name' => 'Italiano',
				'url'  => 'http://it.m.wikihow.com/',
				'img'  => '/extensions/wikihow/mobile/images/flag_italy.gif',
			),
			array(
				'code' => 'nl', 
				'name' => 'Nederlands',
				'url'  => 'http://nl.m.wikihow.com/',
				'img'  => '/extensions/wikihow/mobile/images/flag_netherlands.gif',
			),
		);
		return $langs;
	}
}

class Mobile404Builder extends MobileHtmlBuilder {
	
	protected function generateHeader() {
		$headerVars = $this->getDefaultHeaderVars();
		return EasyTemplate::html('header.tmpl.php', $headerVars);
	}

	protected function generateBody() {
		$vars = array('mainPage' => wfMsg('mainpage'));
		return  EasyTemplate::html('not-found.tmpl.php', $vars);
	}

	protected function generateFooter() {
		$footerVars = $this->getDefaultFooterVars();	
		return EasyTemplate::html('footer.tmpl.php', $footerVars);
	}
}

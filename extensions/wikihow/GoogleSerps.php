<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();
    
/**#@+
 * 
 * @package MediaWiki
 * @subpackage Extensions
 *
 *
 *
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$wgExtensionFunctions[] = 'wfGoogleSerps';


SpecialPage::AddPage(new UnlistedSpecialPage('GoogleSerps'));


$wgExtensionCredits['other'][] = array(
	'name' => 'GoogleSerps',
	'author' => 'Travis Derouin',
	'description' => 'Provides a basic way of adding new entries to the Spam Blacklist from diff pages',
	'url' => 'http://www.mediawiki.org/wiki/GoogleSerps_Extension',
);

function wfGoogleSerps() {
	global $wgMessageCache;
	 $wgMessageCache->addMessages(
 	array(
		'googleserps' => 'Google SERPs Report',
		)
	);
}



function wfSpecialGoogleSerps() {

	global $wgOut;
	
	$dbr =& wfGetDB(DB_SLAVE);

	$gserps = "google_serps";

	$pages_checked  = $dbr->selectField($gserps, 
			array("count(distinct(gs_query))"),
			array("datediff(now(), gs_timestamp) <= 1"),
			"wfSpecialGoogleSerps"
		);
	$wgOut->addHTML("
		<style type='text/css'>
			.report_page {
				font-family: Trebuchet;
				font-size: 125%;
			}
			.report_table td {
				font-family: Trebuchet;
				atext-align:right;
			}	
			#num_td {
				text-align: right;
			}
			.date_input {
				font-size: 95%;
				font-family: Trebuchet;
				background: #ccc;
				text-align: center;
				font-weight: strong;
			}
			#options_list {
				margin-top: 5px;
				border: 1px solid #ccc;
				padding: 10px;
			}
			#options_list UL {
			}
			#options_list UL LI {
				margin-bottom: 0;
			}
		</style>
	<div class='report_page'>
		<h4>Report for today</h4>
		<input type='radio'>Generate reports from 
		<input type='radio'>Compare reports between
		<br/>
		<input class='date_input' type='text' value='2007-04-01' size='12'/> to <input class='date_input' type='text' value='2007-04-04' size='12'><br/><br/>
		<table width='75%' align='center' class='report_table'>
		<tr><td>Number of wikiHow pages checked</td><td>$pages_checked</td><tr>
		<tr><td colspan='3'><b>Break down by domain</b><br/><br/></td></tr>
		<tr><td><b>Domain</b></td><td><b>Matches<img src='http://tango.freedesktop.org/static/cvs/tango-icon-theme/16x16/actions/go-down.png'></b></td><td><b>Average position</b><img src='http://tango.freedesktop.org/static/cvs/tango-icon-theme/16x16/actions/go-down.png'></td></tr>
		");
	
	$res = $dbr->query("select count(*) as C, avg(gs_position) as A, gs_domain from google_serps where datediff(now(), gs_timestamp) < 1 group by gs_domain order by A;");

	$domains = array();
	while ($row = $dbr->fetchObject($res)) {
		$avg = number_format($row->A, 2);
		$domains[] = "<a href=''>{$row->gs_domain}</a>";
		$wgOut->addHTML("
			<tr><td><a href='http://{$row->gs_domain}' target='new'>{$row->gs_domain}</a></td>
				<td id='num_td'>{$row->C}</td>
				<td id='num_td'>{$avg}</td></tr>
		"	
		);
	}		

	$domains_html = implode(", ", $domains);

	$wgOut->addHTML("</table>
			<div id='options_list'>
		From this report:
			<ul>
				<li><a href=''>View wikiHow pages ranked #1</a></li>
				<li><a href=''>View wikiHow pages ranked between 2 and 10</a></li>
				<li><a href=''>View wikiHow pages ranked between after 10</a></li>
				<li><a href=''>View wikiHow pages that rank below:<br/><br/>
					<b>$domains_html</b>
				</a></li>

			</ul>
			</div>
			</div>");	
}
?>

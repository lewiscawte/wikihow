<?php

if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'Standings',
    'author' => 'Travis <travis@wikihow.com>',
    'description' => 'The standings widget', 
);

$wgSpecialPages['Standings'] = 'Standings';
$wgAutoloadClasses['Standings'] =  $dir . 'Standings.body.php'; 

$wgAutoloadClasses['StandingsGroup'] =  $dir . 'Standings.class.php'; 
$wgAutoloadClasses['StandingsIndividual'] = $dir . 'Standings.class.php'; 
$wgAutoloadClasses['QCStandingsGroup'] = $dir . 'Standings.class.php'; 
$wgAutoloadClasses['QCStandingsIndividual'] = $dir . 'Standings.class.php'; 
$wgAutoloadClasses['IntroImageStandingsGroup'] = $dir . 'Standings.class.php'; 
$wgAutoloadClasses['IntroImageStandingsIndividual'] = $dir . 'Standings.class.php'; 
$wgAutoloadClasses['QuickEditStandingsGroup'] = $dir . 'Standings.class.php'; 
$wgAutoloadClasses['QuickEditStandingsIndividual'] = $dir . 'Standings.class.php'; 
$wgAutoloadClasses['RCPatrolStandingsIndividual'] = $dir . 'Standings.class.php'; 
$wgAutoloadClasses['RCPatrolStandingsGroup'] = $dir . 'Standings.class.php'; 
$wgAutoloadClasses['EditFinderStandingsGroup'] 			= $dir . 'Standings.class.php'; 
$wgAutoloadClasses['EditFinderStandingsIndividual'] 	= $dir . 'Standings.class.php'; 

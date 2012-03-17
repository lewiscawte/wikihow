<?
//
// Call into GoodRevision class to compute the latest good (patrolled) revision
// for each article on the site.
//

require_once('commandLine.inc');

GoodRevision::computeLatestAll();


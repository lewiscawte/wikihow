<?
//
// Call into GoodRevision class to remove all old "good revisions" just in
// case a revision gets "stuck".  Should be called nightly and removes all
// good revisions stored from more than 1 week back.
//

require_once('commandLine.inc');

GoodRevision::removeOld();


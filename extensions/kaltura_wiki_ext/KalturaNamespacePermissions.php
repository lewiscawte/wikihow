<?php

$wgContentNamespaces[] = KALTURA_NAMESPACE_ID;
$wgGroupPermissions[ '*' ][ 'ns' . KALTURA_NAMESPACE_ID . '_edit' ] = true;
$wgGroupPermissions[ '*' ][ 'ns' . KALTURA_NAMESPACE_ID . '_read' ] = true;
$wgGroupPermissions[ '*' ][ 'ns' . KALTURA_NAMESPACE_ID . '_create' ] = true;
$wgGroupPermissions[ '*' ][ 'ns' . KALTURA_NAMESPACE_ID . '_move' ] = false;
$wgContentNamespaces[] = KALTURA_DISCUSSION_NAMESPACE_ID;
$wgGroupPermissions[ '*' ][ 'ns' . KALTURA_DISCUSSION_NAMESPACE_ID . '_edit' ] = true;
$wgGroupPermissions[ '*' ][ 'ns' . KALTURA_DISCUSSION_NAMESPACE_ID . '_read' ] = true;
$wgGroupPermissions[ '*' ][ 'ns' . KALTURA_DISCUSSION_NAMESPACE_ID . '_create' ] = true;
$wgGroupPermissions[ '*' ][ 'ns' . KALTURA_DISCUSSION_NAMESPACE_ID . '_move' ] = false;


// allow delete permissions for admin groups + partner specific groups
$delete_groups = array() ; //array('sysop', 'bureaucrat' , 'staff', 'helper', 'janitor');
if ( isset ( $kg_partner_additional_delete_groups ) ) $delete_groups = array_merge( $delete_groups , $kg_partner_additional_delete_groups );
foreach ( $delete_groups as $group)
{
	if ( isset ( $wgGroupPermissions[ $group ] ))
	{
		$wgGroupPermissions[ $group ][ 'ns' . KALTURA_NAMESPACE_ID . '_delete' ] = true;
		// TODO - decide whether to allow move ??
		//		$wgGroupPermissions[ $group ][ 'ns' . KALTURA_NAMESPACE_ID . '_move' ] = true;
	}
}

function fnKalturaPermissionsCheckNamespace( $title, $user, $action, $result )
{
	$ns = $title->getNamespace();

	if ( $ns == KALTURA_NAMESPACE_ID || $ns == KALTURA_DISCUSSION_NAMESPACE_ID ) {
		$ns_str = "ns{$ns}_{$action}";
		if ( ! $user->isAllowed( $ns_str ) ) {
			// HACK! this case is for when the function is called too soon to determine the articleID and canUser will fail for the wrong reason
			if ( $user instanceof  StubUser || $title->mArticleID < 0 ) return true;
			$result = false;
			return false;
		}
	}
	return true;
}
?>

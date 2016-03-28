<?php
class CedarWorkshopConveners extends SpecialPage
{
    function CedarWorkshopConveners()
    {
	SpecialPage::SpecialPage("CedarWorkshopConveners");
	#wfLoadExtensionMessages( 'CedarWorkshopConveners' ) ;
    }
    
    function execute( $par ) {
	global $wgRequest, $wgOut;
	
	global $wgOut ;
	global $cgWorkshopYear, $cgWorkshopRecipientName ;
	global $cgWorkshopRecipientEmail, $cgWorkshopAdmins ;

	$this->setHeaders();
	
	# See what year they are interested in
	$yr = $wgRequest->getText('yr');
	if( $yr != "" )
	{
	    $cgWorkshopYear = $yr ;
	}

	$wgOut->addHTML( "The following is the complete list of CEDAR
	workshop organizers and conveners for the $cgWorkshopYear CEDAR Workshop<br><br>\n\n") ;

	if( !$this->isAllowed() )
	{
	    $wgOut->addHTML( "Only CEDAR Workshop Administrators have access to this list" ) ;
	    return ;
	}

	$conveners = array() ;

	// first list the administrators for the CEDAR Workshop
	foreach( $cgWorkshopAdmins as $name => $email )
	{
	    $u = User::newFromName( $name ) ;
	    if( $u )
	    {
		$realname = $u->getRealName() ;
		$conveners[$realname] = $email ;
	    }
	    else
	    {
		$conveners[$name] = $email ;
	    }
	}

	// select convener?_id, convener?_name, convener?email
	// if convener?_id is != 0 then find the user from the id
	// and set the name and email
	// display as Name,Email
	$query = "SELECT convener1_id, convener1_name, convener1_email" ;
	$query .= ", convener2_id, convener2_name, convener2_email" ;
	$query .= ", convener3_id, convener3_name, convener3_email" ;
	$query .= ", convener4_id, convener4_name, convener4_email" ;
	$query .= ", convener5_id, convener5_name, convener5_email" ;
	$query .= ", convener6_id, convener6_name, convener6_email" ;
	$query .= " FROM cedar_workshop_$cgWorkshopYear" ;

	$dbh =& wfGetDB( DB_MASTER ) ;
	if( !$dbh )
	{
	    $db_error = $dbh->lastError() ;
	    $wgOut->addHTML( "Failed to create user $username:<BR />\n" ) ;
	    $wgOut->addHTML( "Unable to connect to the database:<BR />\n" ) ;
	    $wgOut->addHTML( $db_error ) ;
	    $wgOut->addHTML( "<BR />\n" ) ;
	    return ;
	}
	$workshop_table = $dbh->tableName( "cedar_workshop_$cgWorkshopYear" ) ;

	$res = $dbh->query( $query ) ;
	if( !$res )
	{
	    $wgOut->addHTML( "Unable to retrieve the list of conveners") ;
	    return ;
	}
	while( $obj = $dbh->fetchObject( $res ) )
	{
	    $convener1_id = $obj->convener1_id ;
	    $convener1_name = $obj->convener1_name ;
	    $convener1_email = $obj->convener1_email ;
	    if( $convener1_id != 0 )
	    {
		$u = User::newFromId( $convener1_id ) ;
		if( $u )
		{
		    $convener1_name = $u->getRealName() ;
		    $convener1_email = $u->getEmail() ;
		}
	    }
	    if( $convener1_name && $convener1_name != "" )
		$conveners[$convener1_name] = $convener1_email ;
	    $convener2_id = $obj->convener2_id ;
	    $convener2_name = $obj->convener2_name ;
	    $convener2_email = $obj->convener2_email ;
	    if( $convener2_id != 0 )
	    {
		$u = User::newFromId( $convener2_id ) ;
		if( $u )
		{
		    $convener2_name = $u->getRealName() ;
		    $convener2_email = $u->getEmail() ;
		}
	    }
	    if( $convener2_name && $convener2_name != "" )
		$conveners[$convener2_name] = $convener2_email ;
	    $convener3_id = $obj->convener3_id ;
	    $convener3_name = $obj->convener3_name ;
	    $convener3_email = $obj->convener3_email ;
	    if( $convener3_id != 0 )
	    {
		$u = User::newFromId( $convener3_id ) ;
		if( $u )
		{
		    $convener3_name = $u->getRealName() ;
		    $convener3_email = $u->getEmail() ;
		}
	    }
	    if( $convener3_name && $convener3_name != "" )
		$conveners[$convener3_name] = $convener3_email ;
	    $convener4_id = $obj->convener4_id ;
	    $convener4_name = $obj->convener4_name ;
	    $convener4_email = $obj->convener4_email ;
	    if( $convener4_id != 0 )
	    {
		$u = User::newFromId( $convener4_id ) ;
		if( $u )
		{
		    $convener4_name = $u->getRealName() ;
		    $convener4_email = $u->getEmail() ;
		}
	    }
	    if( $convener4_name && $convener4_name != "" )
		$conveners[$convener4_name] = $convener4_email ;
	    $convener5_id = $obj->convener5_id ;
	    $convener5_name = $obj->convener5_name ;
	    $convener5_email = $obj->convener5_email ;
	    if( $convener5_id != 0 )
	    {
		$u = User::newFromId( $convener5_id ) ;
		if( $u )
		{
		    $convener5_name = $u->getRealName() ;
		    $convener5_email = $u->getEmail() ;
		}
	    }
	    if( $convener5_name && $convener5_name != "" )
		$conveners[$convener5_name] = $convener5_email ;
	    $convener6_id = $obj->convener6_id ;
	    $convener6_name = $obj->convener6_name ;
	    $convener6_email = $obj->convener6_email ;
	    if( $convener6_id != 0 )
	    {
		$u = User::newFromId( $convener6_id ) ;
		if( $u )
		{
		    $convener6_name = $u->getRealName() ;
		    $convener6_email = $u->getEmail() ;
		}
	    }
	    if( $convener6_name && $convener6_name != "" )
		$conveners[$convener6_name] = $convener6_email ;
	}
	foreach( $conveners as $name => $email )
	{
	    $wgOut->addHTML( "$name, $email<br />\n" ) ;
	}
    }

    private function isAllowed()
    {
	global $wgUser,$cgWorkshopAdmins ;

	$allowed = $wgUser->isAllowed( 'cedar_admin' ) ;
	if( !$allowed )
	{
	    foreach( $cgWorkshopAdmins as $uname => $email )
	    {
		if( $wgUser->getName() == $uname ) $allowed = true ;
	    }
	}
	return $allowed ;
    }
}
?>

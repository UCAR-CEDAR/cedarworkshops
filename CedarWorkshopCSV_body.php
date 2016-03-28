<?php
class CedarWorkshopCSV extends SpecialPage
{
    function CedarWorkshopCSV()
    {
	SpecialPage::SpecialPage("CedarWorkshopCSV");
	#wfLoadExtensionMessages( 'CedarWorkshopCSV' ) ;
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

	// display proposals in the csv format the barbara wants.
	// 1) blank (for proposed time slot, which could be 2 names if 4
	// h duration.  This is something you add/revise as you sort
	// them out. ÊThe 9 2-h time slots are: MoPM1, MoPM2, TuAM1,
	// TuPM1, WePM1, ThPM1, ThPM2, FrAM1, FrAM2.
	// 2) 2h (duration)
	// 3) # (of estimated attendees)
	// 4) all 4 categories: Êie for test2: IT polar satellite aurora
	// 5) short title (not long title)
	// 6) proposer name
	// 7) conflicts+requests+technical requests
	// 8) Grand Challenge Request
	// 9) Grand Challenge Speakers

	$wgOut->addHTML( "The following is the current list of proposals for the $cgWorkshopYear CEDAR Workshop in a comma separated format<br><br>\n\n") ;

	if( !$this->isAllowed() )
	{
	    $wgOut->addHTML( "Only CEDAR Workshop Administrators have access to this list" ) ;
	    return ;
	}

	$query = "SELECT short_title, convener1_id" ;
	$query .= ", convener1_name, convener1_email, duration, workshop_format" ;
	$query .= ", altitudes, latitudes, inst_model, other_cat" ;
	$query .= ", estimated_attendance, justification, conflicts, requests" ;
	if( $cgWorkshopYear == "2014" )
	{
	    $query .= ", gc_request, gc_timeline, gc_speakers" ;
	}
	$query .= " FROM cedar_workshop_$cgWorkshopYear" ;
	$query .= " ORDER BY date_submitted,short_title" ;

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

	$wgOut->addHTML( "<ol>" ) ;
	while( $obj = $dbh->fetchObject( $res ) )
	{
	    $date_submitted = $obj->date_submitted ;
	    $short_title = $obj->short_title ;
	    $convener1_id = $obj->convener1_id ;
	    $convener1_name = $obj->convener1_name ;
	    $convener1_email = $obj->convener1_email ;
	    $altitudes = $obj->altitudes ;
	    $latitudes = $obj->latitudes ;
	    $inst_model = $obj->inst_model ;
	    $other_cat = $obj->other_cat ;
	    $justification = $obj->justification ;
	    $workshop_format = $obj->workshop_format ;
	    $duration = $obj->duration ;
	    $estimated_attendance = $obj->estimated_attendance ;
	    $conflicts = $obj->conflicts ;
	    $requests = $obj->requests ;
	    if( $cgWorkshopYear == "2014" )
	    {
		$gc_request = $obj->gc_request ;
		$gc_timeline = $obj->gc_timeline ;
		$gc_speakers = $obj->gc_speakers ;
	    }

	    if( $convener1_id != 0 )
	    {
		$u = User::newFromId( $convener1_id ) ;
		if( !$u )
		{
		    $convener1_name = "unknown" ;
		    $convener1_email = "cedar_db@hao.ucar.edu" ;
		}
		else
		{
		    $convener1_name = trim( $u->getRealName() ) ;
		    $convener1_email = trim( $u->getEmail() ) ;
		}
	    }
	    $line = "," ;
	    if( !$duration || $duration == "" ) $line .= "\"0h\"" ;
	    else $line .= "\"$duration\"" ;
	    $line .= "," ;
	    $line .= "\"$workshop_format\"," ;
	    if( !$estimated_attendance || $estimated_attendance == "" )
	    {
		$line .= "0" ;
	    }
	    else $line .= $estimated_attendance ;
	    $line .= ",\"$altitudes\",\"$latitudes\",\"$inst_model\",\"$other_cat\"" ;
	    $line .= ",\"$short_title\"" ;
	    $line .= ",\"$convener1_name\",\"$convener1_email\"" ;
	    $line .= ",\"$justification\",\"$conflicts\",\"$requests\"" ;
	    if( $cgWorkshopYear == "2014" )
	    {
		$line .= ",\"$gc_request\",\"$gc_timeline\",\"$gc_speakers\"" ;
	    }
	    $wgOut->addHTML( "$line<br>\n" ) ;
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

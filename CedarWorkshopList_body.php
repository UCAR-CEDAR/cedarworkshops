<?php
class CedarWorkshopList extends SpecialPage
{
    function CedarWorkshopList()
    {
	SpecialPage::SpecialPage("CedarWorkshopList");
	#wfLoadExtensionMessages( 'CedarWorkshopList' ) ;
    }
    
    function execute( $par ) {
	global $wgRequest, $wgOut;
	global $cgWorkshopYear, $cgWorkshopRecipientName ;
	global $cgWorkshopRecipientEmail, $cgWorkshopAdmins ;
	
	$this->setHeaders();
	
	# See what year they are interested in
	$yr = $wgRequest->getText('yr');
	if( $yr != "" )
	{
	    $cgWorkshopYear = $yr ;
	}

	# Get request data from, e.g.
	$param = $wgRequest->getText('status');
	if( $param == "delete" )
	{
	    $this->qdel() ;
	}
	else if( $param == "dodelete" )
	{
	    $this->dodel() ;
	    $this->printList() ;
	}
	else
	{
	    $this->printList() ;
	}
    }

    private function printList()
    {
	global $wgRequest, $wgUser, $wgOut, $wgServer ;
	global $cgWorkshopYear, $cgWorkshopRecipientName ;
	global $cgWorkshopRecipientEmail, $cgWorkshopAdmins ;

	// proposed on Wednesday, November 18, 2009 03:47:56 PM - test2
	// - by Barbara Emery for 2 hours - with categories Altitudes:
	// IT, Latitudes: polar, Inst/Model: satellite, Other: aurora -
	// for unknown number of people - conflicts/requests none - no
	// technical requests - Grand Challenge Request - Grand Challenge
	// Speakers

	// "proposed on " date_submitted - long_title "by " proposer_id
	// " for " duration - " with categories Altitudes: " altitudes
	// ", Latitudes: " latitudes ", Inst/Model: " inst_model ",
	// Other: " other_cat " - for " estimated_attendance " - "
	// conflicts/requests " conflicts " - " requests
	
	// For 2014 added the following 
	// " - " gc_request " - " gc_timeline " - " gc_speakers

	$wgOut->addHTML( "The following is the current list of CEDAR
	Workshop proposals for the $cgWorkshopYear CEDAR Workshop<br><br>\n\n") ;

	$query = "SELECT proposal_id, date_submitted, short_title, long_title" ;
	$query .= ", workshop_format, duration" ;
	$query .= ", convener1_id, convener2_id, convener3_id" ;
	$query .= ", convener4_id, convener5_id, convener6_id" ;
	$query .= ", convener1_email, convener1_name" ;
	$query .= ", altitudes, latitudes, inst_model" ;
	$query .= ", other_cat, estimated_attendance, conflicts, requests" ;
	if( $cgWorkshopYear == "2014" )
	{
	    $query .= ", gc_request, gc_timeline, gc_speakers" ;
	}
	$query .= " FROM cedar_workshop_$cgWorkshopYear" ;
	$query .= " ORDER BY date_submitted,long_title" ;

	$dbh =& wfGetDB( DB_MASTER ) ;
	if( !$dbh )
	{
	    $db_error = $dbh->lastError() ;
	    $wgOut->addHTML( "Failed to create workshop list<BR />\n" ) ;
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
	    $proposal_id = $obj->proposal_id ;
	    $date_submitted = $obj->date_submitted ;
	    $long_title = $obj->long_title ;
	    $short_title = $obj->short_title ;
	    $article_title = "$cgWorkshopYear"."_Workshop:$short_title" ;
	    $convener1_id = $obj->convener1_id ;
	    $convener2_id = $obj->convener2_id ;
	    $convener3_id = $obj->convener3_id ;
	    $convener4_id = $obj->convener4_id ;
	    $convener5_id = $obj->convener5_id ;
	    $convener6_id = $obj->convener6_id ;
	    $altitudes = $obj->altitudes ;
	    $latitudes = $obj->latitudes ;
	    $inst_model = $obj->inst_model ;
	    $other_cat = $obj->other_cat ;
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
	    else
	    {
		$convener1_name = $obj->convener1_name ;
		$convener1_email = $obj->convener1_email ;
	    }

	    $wgOut->addHTML( "<li>" ) ;
	    $allowed = $this->isAllowed() ;
	    $admin = $allowed ;
	    if( !$allowed )
	    {
		$uid = $wgUser->getId() ;
		if( $uid == $convener1_id ) $allowed = true ;
		else if( $uid == $convener2_id ) $allowed = true ;
		else if( $uid == $convener3_id ) $allowed = true ;
		else if( $uid == $convener4_id ) $allowed = true ;
		else if( $uid == $convener5_id ) $allowed = true ;
		else if( $uid == $convener6_id ) $allowed = true ;
	    }

	    if( $allowed )
	    {
		$wgOut->addHTML( "<a href='$wgServer/wiki/index.php/Special:Cedar_Workshop_Proposal_Form?status=edit&id=$proposal_id'><img src='$wgServer/wiki/icons/edit.png' alt='edit' title='Edit'></a>\n" ) ;
	    }
	    if( $admin )
	    {
		$wgOut->addHTML( "<a href='$wgServer/wiki/index.php/Special:Cedar_Workshop_Proposal_List?status=delete&id=$proposal_id'><img src='$wgServer/wiki/icons/delete.png' alt='delete' title='Delete'></a>\n" ) ;
	    }

	    $wgOut->addHTML( "proposed on $date_submitted -" ) ;
	    $wgOut->addWikiText( "[[$article_title|$long_title]] - by ", false ) ;
	    $wgOut->addHTML( "<a href=\"mailto:$convener1_email\">$convener1_name</a>" ) ;
	    $wgOut->addHTML( " for $duration using $workshop_format with categories Altitudes: $altitudes" ) ;
	    $wgOut->addHTML( ", Latitudes: $latitudes, Inst/Model: $inst_model" ) ;
	    if( $other_cat != "" ) $wgOut->addHTML( ", Other: $other_cat" ) ;
	    $wgOut->addHTML( " - for " ) ;
	    if( $estimated_attendance == 0 ) $wgOut->addHTML( "unknown number of" ) ;
	    else $wgOut->addHTML( "$estimated_attendance" ) ;
	    $wgOut->addHTML( " people - conflicts: " ) ;
	    if( !$conflicts || $conflicts == "" ) $wgOut->addHTML( "none" ) ;
	    else $wgOut->addHTML( "$conflicts" ) ;
	    $wgOut->addHTML( " - requests: " ) ;
	    if( !$requests || $requests == "" ) $wgOut->addHTML( "none" ) ;
	    else $wgOut->addHTML( "$requests" ) ;
	    if( $cgWorkshopYear == "2014" )
	    {
		$wgOut->addHTML( " - GC Request: " ) ;
		if( !$gc_request || $gc_request == "" )
		    $wgOut->addHTML( "none" ) ;
		else
		    $wgOut->addHTML( "$gc_request" ) ;
		$wgOut->addHTML( " - GC Timeline: " ) ;
		if( !$gc_timeline || $gc_timeline == "" )
		    $wgOut->addHTML( "none" ) ;
		else
		    $wgOut->addHTML( "$gc_timeline" ) ;
		$wgOut->addHTML( " - GC Speakers: " ) ;
		if( !$gc_speakers || $gc_speakers == "" )
		    $wgOut->addHTML( "none" ) ;
		else
		    $wgOut->addHTML( "$gc_speakers" ) ;
	    }

	    $wgOut->addHTML( "</li>\n" ) ;
	}
	$wgOut->addHTML( "</ol>\n" ) ;
    }

    private function qdel()
    {
	global $wgOut, $wgUser, $wgRequest ;

	$allowed = $this->isAllowed() ;
	if( !$allowed )
	{
	    $wgOut->addHTML( "<span style='color:red;font-size:12pt;font-weight:bold;'>You do not have permission to delete a workshop</span>\n" ) ;
	    return ;
	}

	$id = $wgRequest->getInt( 'id' ) ;

	$wgOut->addHTML( "Are you sure you want to delete this workshop?" ) ;
	$wgOut->addHTML( "(<A HREF=\"$wgServer/wiki/index.php/Special:CEDAR_Workshop_Proposal_List?status=dodelete&id=$id\">Yes</A>" ) ;
	$wgOut->addHTML( " | <A HREF=\"$wgServer/wiki/index.php/Special:CEDAR_Workshop_Proposal_List\">No</A>)" ) ;
	return ;
    }

    private function dodel()
    {
	global $wgOut, $wgUser, $wgRequest, $cgWorkshopYear ;

	$allowed = $this->isAllowed() ;
	if( !$allowed )
	{
	    $wgOut->addHTML( "<span style='color:red;font-size:12pt;font-weight:bold;'>You do not have permission to delete a workshop</span>\n" ) ;
	    return ;
	}

	$id = $wgRequest->getInt( 'id' ) ;
	if( !$id )
	{
	    $wgOut->addHTML( "<span style='color:red;font-size:12pt;font-weight:bold;'>Unable to delete the workshop with id $id</span>\n" ) ;
	    return ;
	}

	$dbh =& wfGetDB( DB_MASTER ) ;
	if( !$dbh )
	{
	    $db_error = $dbh->lastError() ;
	    $wgOut->addHTML( "Unable to connect to the database:<BR />\n" ) ;
	    $wgOut->addHTML( $db_error ) ;
	    $wgOut->addHTML( "<BR />\n" ) ;
	    return ;
	}
	$workshop_table = $dbh->tableName( "cedar_workshop_$cgWorkshopYear" ) ;

	// delete the note_id
	$delete_success = $dbh->delete( $workshop_table, array( 'proposal_id' => $id ) ) ;

	if( $delete_success == false )
	{
	    $db_error = $dbh->lastError() ;
	    $wgOut->addHTML( "<span style='color:red;font-size:12pt;font-weight:bold;'>Unable to delete the workshop with id $id</span>\n" ) ;
	    $wgOut->addHTML( $db_error ) ;
	    $wgOut->addHTML( "<br />\n" ) ;
	    return ;
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

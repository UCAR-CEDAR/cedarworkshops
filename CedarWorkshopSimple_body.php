<?php
class CedarWorkshopSimple extends SpecialPage
{
    function CedarWorkshopSimple()
    {
	SpecialPage::SpecialPage("CedarWorkshopSimple");
	#wfLoadExtensionMessages( 'CedarWorkshopSimple' ) ;
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
	$this->printList() ;
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
	// technical requests

	// "proposed on " date_submitted - long_title "by " proposer_id
	// " for " duration - " with categories Altitudes: " altitudes
	// ", Latitudes: " latitudes ", Inst/Model: " inst_model ",
	// Other: " other_cat " - for " estimated_attendance " - "
	// conflicts/requests " conflicts " - " requests

	$wgOut->addHTML( "The following is the current list of CEDAR
	Workshop proposals for the $cgWorkshopYear CEDAR Workshop<br><br>\n\n") ;

	$query = "SELECT proposal_id, short_title, long_title" ;
	$query .= ", convener1_id, convener1_email, convener1_name" ;
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
	    $wgOut->addWikiText( "[[$article_title|$long_title]] - convener ", false ) ;
	    $wgOut->addHTML( "<a href=\"mailto:$convener1_email\">$convener1_name</a>" ) ;
	    $wgOut->addHTML( "</li>\n" ) ;
	}
	$wgOut->addHTML( "</ol>\n" ) ;
    }
}
?>

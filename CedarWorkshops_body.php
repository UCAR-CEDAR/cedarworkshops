<?php
/**
 * Extension to allow users to propose and modify CEDAR workshops
 *
 * @author Patrick West <westp@rpi.edu>
 * @package CedarWorkshops
 * @copyright Copyright (c) 2013, University Corporation for Atmospheric Research
 * @link http://subversion.ucar.edu/HAO/CEDAR/CedarWEB/trunk/wiki/cedarweb/extensions/cedarworkshops/CedarWorkshops_body.php Subversion
 * @link http://cedarweb.hao.ucar.edu/cedaradmin/index.php/Extensions:cedarworkshops Wiki Page
 */

/**
 * Class to allow logged-in users to propose or edit a workshop for the
 * current CEDAR Workshop.
 *
 */
class CedarWorkshops extends SpecialPage
{
    /**
     * Constructor for CedarWorkshops
     *
     */
    function CedarWorkshops()
    {
	// register the special page with MediaWiki
	SpecialPage::SpecialPage("CedarWorkshops");
	// load the messages for the extension
	#wfLoadExtensionMessages( 'CedarWorkshops' ) ;
    }
    
    /**
     * Method called by the wiki when the special page is reached
     *
     * @param string $par The subpage component of the special page.
     * None are used in this extension
     *
     * @return void
     */
    function execute( $par ) {
	global $wgRequest, $wgOut;
	
	$this->setHeaders();
	
	// We use the status parameter in the query string to determine
	// what we should be doing.
	$param = $wgRequest->getText('status');
	// present the form for proposing a new workshop, or editing an
	// existing workshop proposal
	if( $param == "propose" || $param == "edit" )
	{
	    $this->propForm( $param ) ;
	}
	// user has filled in the form to propose or edit a workshop,
	// handle checking required fields, valid values, and
	// adding/modifying the information in the database
	else if( $param == "add" || $param == "mod" )
	{
	    $this->doadd( $param ) ;
	}
	// depending on whether the user is admin or not, we start at
	// different places. So call startHere
	else
	{
	    $this->startHere() ;
	}
    }

    /**
     *
     * If the user is an admin then we present a list of pages that they
     * can visit regarding the current CEDAR workshop. If not an admin
     * then we redirect them to the proposal form
     *
     * @return void
     */
    private function startHere() {
	global $wgUser, $wgRequest, $wgOut;
	global $cgWorkshopYear, $cgWorkshopRecipientName, $cgWorkshopRecipientEmail  ;

	$wgOut->addHTML( "<br>\n" ) ;

	// does this user already have a wiki login?
	$loggedin = $wgUser->isLoggedIn() ;
	// are they an admin/are they allowed be here
	$allowed = $this->isAllowed() ;

	// Not loggedin, then can't be here
	$cedarid = 0 ;
	if( !$loggedin )
	{
	    $wgOut->addHTML( "Only CEDAR members may use the CEDAR Workshop Proposal Form. Please login." ) ;
	    return ;
	}

	// They are an admin, so present the list of administrative
	// functions as their starting page.
	if( $allowed )
	{
	    # See what year they are interested in
	    $yr = $wgRequest->getText('yr');
	    if( $yr == "" )
	    {
		$yr = $cgWorkshopYear ;
	    }

	    $qyr = "?yr=$yr" ;
	    $ayr = "&yr=$yr" ;

	    $wgOut->addHTML( "Administrator Functions\n" ) ;
	    $wgOut->addHTML( "<ul>\n" ) ;
	    // display the list of conveners for currently proposed
	    // workshops
	    $wgOut->addHTML( "<li>\n" ) ;
	    $wgOut->addHTML( "<a href=\"$wgServer/wiki/index.php/Special:CEDAR_Workshop_Convener_List$qyr\">Convener List</a>\n" ) ;
	    $wgOut->addHTML( "</li>\n" ) ;
	    // display the list of proposed workshops. Includes edit
	    // capabilities
	    $wgOut->addHTML( "<li>\n" ) ;
	    $wgOut->addHTML( "<a href=\"$wgServer/wiki/index.php/Special:CEDAR_Workshop_Proposal_List$qyr\">Workshop Proposal List</a>\n" ) ;
	    $wgOut->addHTML( "</li>\n" ) ;
	    // display a simple list of proposed workshops, simple link
	    // with convener contact
	    $wgOut->addHTML( "<li>\n" ) ;
	    $wgOut->addHTML( "<a href=\"$wgServer/wiki/index.php/Special:CEDAR_Workshop_Proposal_Simple$qyr\">Workshop Proposal Simple List</a>\n" ) ;
	    $wgOut->addHTML( "</li>\n" ) ;
	    // display a CSV version of each proposed workshop. Allow
	    // the display to be copied and pasted
	    $wgOut->addHTML( "<li>\n" ) ;
	    $wgOut->addHTML( "<a href=\"$wgServer/wiki/index.php/Special:CEDAR_Workshop_Proposal_CSV_List$qyr\">Workshop Proposal CSV List</a>\n" );
	    $wgOut->addHTML( "</li>\n" ) ;
	    // propose a CEDAR workshop. Even admins can propose.
	    $wgOut->addHTML( "<li>\n" ) ;
	    $wgOut->addHTML( "<a href=\"$wgServer/wiki/index.php/Special:CedarWorkshops?status=propose$ayr\">Propose a Workshop</a>\n" );
	    $wgOut->addHTML( "</li>\n" ) ;
	    $wgOut->addHTML( "</ul>\n" ) ;
	    $wgOut->addHTML( "<br>\n" ) ;
	    return ;
	}
	else
	{
	    $this->propForm( "propose" ) ;
	    return ;
	}
    }

    /**
     * Display the proposal form. Filled in if a workshop is identified
     *
     * @param string $action are we proposing or modifying a workshop
     *
     * @return void
     */
    private function propForm( $action ) {
	global $wgUser, $wgRequest, $wgOut;
	global $cgWorkshopDeadline, $cgWorkshopYear, $cgWorkshopRecipientName, $cgWorkshopRecipientEmail  ;

	$wgOut->addHTML( "<br>\n" ) ;

	// does this user already have a wiki login?
	$loggedin = $wgUser->isLoggedIn() ;

	// if you're not logged in, then you can't be here
	$cedarid = 0 ;
	if( !$loggedin )
	{
	    $wgOut->addHTML( "Only CEDAR members may use the CEDAR Workshop Proposal Form. Please login." ) ;
	    return ;
	}

	// See what year they are interested in
	$yr = $wgRequest->getText('yr');
	if( $yr == "" )
	{
	    $yr = $cgWorkshopYear ;
	}

	// if the year is not the current year, then edits and proposals
	// are no longer being accepted
	if( $yr != $cgWorkshopYear )
	{
	    $wgOut->addHTML( "Proposals for the $yr Workshop are no longer being accepted" ) ;
	    return ;
	}

	// determine if the proposal deadline has past. This is only
	// applicible if proposing. Can still modify
	$aDate = date_create( $cgWorkshopDeadline ) ;
	$currdate = date_create() ;
	$diff = $aDate->diff( $currdate, FALSE ) ;
	$daysdiff = $diff->invert ;
	if( $action == "propose" && $daysdiff == 0 )
	{
	    $wgOut->addHTML( "Proposals for the $yr Workshop are no longer being accepted" ) ;
	    return ;
	}

	// initialize the database handle
	$dbh =& wfGetDB( DB_MASTER ) ;
	if( !$dbh )
	{
	    $db_error = $dbh->lastError() ;
	    $wgOut->addHTML( "Unable to connect to the database:<br />\n" ) ;
	    $wgOut->addHTML( $db_error ) ;
	    $wgOut->addHTML( "<br />\n" ) ;
	    return ;
	}

	$id = $wgRequest->getInt( 'id' ) ;

	// only admins, the proposer, or a convener can modify a proposal
	if( $action == "edit" && $id != 0 )
	{
	    $canedit = $this->isAllowed() ;
	    if( $canedit == false )
	    {
		$canedit = $this->canEdit( $dbh, $id ) ;
	    }

	    if( $canedit == false )
	    {
		$wgOut->addHTML( "Only CEDAR Workshop Administrators, the workshop proposer and the workshop conveners can edit a workshop proposal" ) ;
		return ;
	    }
	    $wgOut->addHTML( "<span style=\"font-weight:bold;color:green;\">**** If you edit this workshop here and click Submit, any changes made to the workshop page above description will be lost</span><br><br>" ) ;
	}

	// retrieve the workshop information if we are modifying an
	// existing proposed workshop
	$is_editable = "" ;
	if( $action == "edit" && $id != 0 )
	{
	    // we'll need to grab proposer and convener information if
	    // available, so open the user table
	    $user_table = $dbh->tableName( 'user' ) ;

	    // table where the workshop information is currently stored.
	    $workshop_table = $dbh->tableName( "cedar_workshop_$cgWorkshopYear" ) ;

	    // grab everything about the workshop
	    $query = "select * from $workshop_table where proposal_id = $id" ;
	    $res = $dbh->query( $query ) ;
	    if( !$res )
	    {
		$wgOut->addHTML( "Unable to retrieve workshop proposal $id") ;
		return ;
	    }

	    $obj = $dbh->fetchObject( $res ) ;
	    if( !$obj )
	    {
		$wgOut->addHTML( "Unable to retrieve workshop proposal $id") ;
		return ;
	    }

	    $workshop_title = $obj->long_title ;
	    $short_title = $obj->short_title ;
	    $is_editable = "READONLY" ;
	    $convener1_id = $obj->convener1_id ;
	    if( $convener1_id != 0 )
	    {
		$u = User::newFromId( $convener1_id ) ;
		if( $u )
		{
		    $convener1_name = $u->getRealName() ;
		    $convener1_email = $u->getEmail() ;
		}
	    }
	    else
	    {
		$convener1_name = $obj->convener1_name ;
		$convener1_email = $obj->convener1_email ;
	    }
	    $convener2_id = $obj->convener2_id ;
	    if( $convener2_id != 0 )
	    {
		$u = User::newFromId( $convener2_id ) ;
		if( $u )
		{
		    $convener2_name = $u->getRealName() ;
		    $convener2_email = $u->getEmail() ;
		}
	    }
	    else
	    {
		$convener2_name = $obj->convener2_name ;
		$convener2_email = $obj->convener2_email ;
	    }
	    $convener3_id = $obj->convener3_id ;
	    if( $convener3_id != 0 )
	    {
		$u = User::newFromId( $convener3_id ) ;
		if( $u )
		{
		    $convener3_name = $u->getRealName() ;
		    $convener3_email = $u->getEmail() ;
		}
	    }
	    else
	    {
		$convener3_name = $obj->convener3_name ;
		$convener3_email = $obj->convener3_email ;
	    }
	    $convener4_id = $obj->convener4_id ;
	    if( $convener4_id != 0 )
	    {
		$u = User::newFromId( $convener4_id ) ;
		if( $u )
		{
		    $convener4_name = $u->getRealName() ;
		    $convener4_email = $u->getEmail() ;
		}
	    }
	    else
	    {
		$convener4_name = $obj->convener4_name ;
		$convener4_email = $obj->convener4_email ;
	    }
	    $convener5_id = $obj->convener5_id ;
	    if( $convener5_id != 0 )
	    {
		$u = User::newFromId( $convener5_id ) ;
		if( $u )
		{
		    $convener5_name = $u->getRealName() ;
		    $convener5_email = $u->getEmail() ;
		}
	    }
	    else
	    {
		$convener5_name = $obj->convener5_name ;
		$convener5_email = $obj->convener5_email ;
	    }
	    $convener6_id = $obj->convener6_id ;
	    if( $convener6_id != 0 )
	    {
		$u = User::newFromId( $convener6_id ) ;
		if( $u )
		{
		    $convener6_name = $u->getRealName() ;
		    $convener6_email = $u->getEmail() ;
		}
	    }
	    else
	    {
		$convener6_name = $obj->convener6_name ;
		$convener6_email = $obj->convener6_email ;
	    }
	    $prop_id = $obj->proposer_id ;
	    if( $prop_id != 0 )
	    {
		$u = User::newFromId( $prop_id ) ;
		if( $u )
		{
		    $prop_name = $u->getRealName() ;
		    $prop_email = $u->getEmail() ;
		}
	    }
	    else
	    {
		$prop_name = $obj->proposer_name ;
		$prop_email = $obj->proposer_email ;
	    }
  
	    $cat_alt = $obj->altitudes ;
	    if( $cat_alt == "MLT" ) $cat_alt_mlt = "SELECTED" ;
	    if( $cat_alt == "IT" ) $cat_alt_it = "SELECTED" ;
	    if( $cat_alt == "" ) $cat_alt_select = "SELECTED" ;

	    $cat_lat = $obj->latitudes ;
	    if( $cat_lat == "global" ) $cat_lat_global = "SELECTED" ;
	    if( $cat_lat == "equatorial" ) $cat_lat_equatorial = "SELECTED" ;
	    if( $cat_lat == "mid-latitude" ) $cat_lat_mid = "SELECTED" ;
	    if( $cat_lat == "polar" ) $cat_lat_polar = "SELECTED" ;
	    if( $cat_lat == "" ) $cat_lat_select = "SELECTED" ;

	    $cat_inst = $obj->inst_model ;
	    if( $cat_inst == "radar" ) $cat_inst_radar = "SELECTED" ;
	    if( $cat_inst == "optical" ) $cat_inst_optical = "SELECTED" ;
	    if( $cat_inst == "modeling" ) $cat_inst_modeling = "SELECTED" ;
	    if( $cat_inst == "satellite" ) $cat_inst_satellite = "SELECTED" ;
	    if( $cat_inst == "" ) $cat_inst_select = "SELECTED" ;

	    $cat_other = $obj->other_cat ;
	    $workshop_format = $obj->workshop_format ;
	    $other_format = "" ;
	    $other_selected = "" ;
	    if( $workshop_format == "Short Presentations" )
	    {
		$short_selected = "SELECTED" ;
	    }
	    if( $workshop_format == "Short 5 Slide Presentations" )
	    {
		$short_5_selected = "SELECTED" ;
	    }
	    else if( $workshop_format == "Panel Discussion" )
	    {
		$panel_selected = "SELECTED" ;
	    }
	    else if( $workshop_format == "Tutorials" )
	    {
		$tutorial_selected = "SELECTED" ;
	    }
	    else if( $workshop_format == "Round Table" )
	    {
		$round_selected = "SELECTED" ;
	    }
	    else
	    {
		$other_selected = "SELECTED" ;
		$other_format = $workshop_format ;
	    }

	    $duration = $obj->duration ;
	    if( $duration == "2 hours" ) $two_duration = "SELECTED" ;
	    if( $duration == "4 hours" ) $four_duration = "SELECTED" ;
	    if( $duration == "Other" )
	    {
		$o_duration = "SELECTED" ;
		$other_duration = $duration ;
	    }

	    $est_attendance = $obj->estimated_attendance ;
	    $conflicts = $obj->conflicts ;
	    $tech_request = $obj->requests ;
	    $gc_request = $obj->gc_request ;
	    $gc_timeline = $obj->gc_timeline ;
	    $gc_speakers = $obj->gc_speakers ;
	    $descript = trim( $obj->description ) ;
	    $student_descript = trim( $obj->student_description ) ;
	    $justification = trim( $obj->justification ) ;
	}
	// here, it is possible that someone proposed a workshop, or
	// modified a workshop, but the form contained errors. The
	// fields are passed in the request object and retrieved here.
	else
	{
	    $id = 0 ;
	    $workshop_title = trim( $wgRequest->getVal( 'workshop_title', '' ) );
	    $short_title = trim( $wgRequest->getVal( 'short_title', '' ) );

	    $convener1_name = trim( $wgRequest->getVal( 'convener1_name', '' ) );
	    if( !$convener1_name || $convener1_name == "" )
	    {
		$convener1_name = $wgUser->getRealName() ;
	    }

	    $convener1_email = trim( $wgRequest->getVal( 'convener1_email', '' ) );
	    if( !$convener1_email || $convener1_email == "" )
	    {
		$convener1_email = $wgUser->getEmail() ;
	    }

	    $convener2_name = trim( $wgRequest->getVal( 'convener2_name', '' ) );
	    $convener2_email = trim( $wgRequest->getVal( 'convener2_email', '' ) );
	    $convener3_name = trim( $wgRequest->getVal( 'convener3_name', '' ) );
	    $convener3_email = trim( $wgRequest->getVal( 'convener3_email', '' ) );
	    $convener4_name = trim( $wgRequest->getVal( 'convener4_name', '' ) );
	    $convener4_email = trim( $wgRequest->getVal( 'convener4_email', '' ) );
	    $convener5_name = trim( $wgRequest->getVal( 'convener5_name', '' ) );
	    $convener5_email = trim( $wgRequest->getVal( 'convener5_email', '' ) );
	    $convener6_name = trim( $wgRequest->getVal( 'convener6_name', '' ) );
	    $convener6_email = trim( $wgRequest->getVal( 'convener6_email', '' ) );
	    $prop_name = trim( $wgRequest->getVal( 'prop_name' ) );
	    $prop_email = trim( $wgRequest->getVal( 'prop_email' ) );
	    if( !$prop_email )
	    {
		if( !$prop_name )
		{
		    $prop_email = $wgUser->getEmail() ;
		}
		else
		{
		    $prop_email = '' ;
		}
	    }
	    if( !$prop_name )
	    {
		$prop_name = $wgUser->getRealName() ;
	    }
  
	    $cat_alt = trim( $wgRequest->getVal( 'cat_alt', '' ) ) ;
	    $cat_alt_mlt = "" ;
	    $cat_alt_it = "" ;
	    $cat_alt_select = "" ;
	    if( $cat_alt == "MLT" ) $cat_alt_mlt = "SELECTED" ;
	    if( $cat_alt == "IT" ) $cat_alt_it = "SELECTED" ;
	    if( $cat_alt == "" ) $cat_alt_select = "SELECTED" ;

	    $cat_lat = trim( $wgRequest->getVal( 'cat_lat', '' ) ) ;
	    $cat_lat_global = "" ;
	    $cat_lat_equatorial = "" ;
	    $cat_lat_mid = "" ;
	    $cat_lat_polar = "" ;
	    $cat_lat_select = "" ;
	    if( $cat_lat == "global" ) $cat_lat_global = "SELECTED" ;
	    if( $cat_lat == "equatorial" ) $cat_lat_equatorial = "SELECTED" ;
	    if( $cat_lat == "mid-latitude" ) $cat_lat_mid = "SELECTED" ;
	    if( $cat_lat == "polar" ) $cat_lat_polar = "SELECTED" ;
	    if( $cat_lat == "" ) $cat_lat_select = "SELECTED" ;

	    $cat_inst = trim( $wgRequest->getVal( 'cat_inst', '' ) ) ;
	    $cat_inst_radar = "" ;
	    $cat_inst_optical = "" ;
	    $cat_inst_modeling = "" ;
	    $cat_inst_satellite = "" ;
	    $cat_inst_select = "" ;
	    if( $cat_inst == "radar" ) $cat_inst_radar = "SELECTED" ;
	    if( $cat_inst == "optical" ) $cat_inst_optical = "SELECTED" ;
	    if( $cat_inst == "modeling" ) $cat_inst_modeling = "SELECTED" ;
	    if( $cat_inst == "satellite" ) $cat_inst_satellite = "SELECTED" ;
	    if( $cat_inst == "" ) $cat_inst_select = "SELECTED" ;

	    $cat_other = trim( $wgRequest->getVal( 'cat_other', '' ) );
	    $workshop_format = trim( $wgRequest->getVal( 'workshop_format', '' ) );
	    if( $workshop_format == "Short Presentations" ) $short_selected = "SELECTED" ;
	    if( $workshop_format == "Short 5 Slide Presentations" ) $short_5_selected = "SELECTED" ;
	    else if( $workshop_format == "Panel Discussion" ) $panel_selected = "SELECTED" ;
	    else if( $workshop_format == "Tutorials" ) $tutorial_selected = "SELECTED" ;
	    else if( $workshop_format == "Round Table" ) $round_selected = "SELECTED" ;
	    else if( $workshop_format == "Other" )
	    {
		$other_selected = "SELECTED" ;
		$other_format = trim( $wgRequest->getVal( 'other_format', '' ) );
	    }
	    $duration = trim( $wgRequest->getVal( 'duration', '' ) );
	    if( $duration == "2 hours" ) $two_duration = "SELECTED" ;
	    if( $duration == "4 hours" ) $four_duration = "SELECTED" ;
	    if( $duration == "Other" )
	    {
		$o_duration = "SELECTED" ;
		$other_duration = trim( $wgRequest->getVal( 'other_duration', '' ) );
	    }
	    $est_attendance = trim( $wgRequest->getVal( 'est_attendance', '' ) );
	    $conflicts = trim( $wgRequest->getVal( 'conflicts', '' ) );
	    $tech_request = trim( $wgRequest->getVal( 'tech_request', '' ) );
	    $gc_request = trim( $wgRequest->getVal( 'gc_request', '' ) );
	    $gc_timeline = trim( $wgRequest->getVal( 'gc_timeline', '' ) );
	    $gc_speakers = trim( $wgRequest->getVal( 'gc_speakers', '' ) );
	    $descript = trim( $wgRequest->getVal( 'descript', '' ) );
	    $student_descript = trim( $wgRequest->getVal( 'student_descript', '' ));
	    $justification = trim( $wgRequest->getVal( 'justification', '' ) );
	}

$wgOut->addHTML( "<script language=javascript type='text/javascript'>
function hideShow()
{
    if( document.getElementById ) // DOM3 = IE5, NS6
    {
        if( document.getElementById( 'gc' ).style.visibility == 'hidden' )
        {
            document.getElementById( 'gc' ).style.visibility = 'visible' ;
        }
        else
        {
            document.getElementById( 'gc' ).style.visibility = 'hidden' ;
            document.getElementById( 'gc_request' ).value = '' ;
            document.getElementById( 'gc_timeline' ).value = '' ;
            document.getElementById( 'gc_speakers' ).value = '' ;
        }
    }
    else
    {
        if( document.layers ) // Netscape 4
        {
            if( document.cedarworkshop.gc.visibility == 'hidden' )
            {
                document.cedarworkshop.gc.visibility = 'visible' ;
            }
            else
            {
                document.cedarworkshop.gc.visibility = 'hidden' ;
                document.cedarworkshop.gc.gc_request.value = '' ;
                document.cedarworkshop.gc.gc_timeline.value = '' ;
                document.cedarworkshop.gc.gc_speakers.value = '' ;
            }
        }
        else // IE 4
        {
            if( document.all.cedarworkshop.gc.style.visibility == 'hidden' )
            {
                document.all.cedarworkshop.gc.style.visibility = 'visible';
            }
            else
            {
                document.all.cedarworkshop.gc.style.visibility = 'hidden';
		document.all.cedarworkshop.gc.gc_request.value = '' ;
		document.all.cedarworkshop.gc.gc_timeline.value = '' ;
		document.all.cedarworkshop.gc.gc_speakers.value = '' ;
            }
        }
    }
}
</script>\n\n" ) ;

	// Now we output the form, using possibly filled in values for
	// each input field.
	$wgOut->addHTML( "Fill out all fields to propose a $cgWorkshopYear CEDAR workshop, <font color=red>due 11 March, $cgWorkshopYear</font>. All fields marked with an asterisk(*) are required.\n" ) ;
	$wgOut->addHTML( "<br><br>\n" ) ;

	$wgOut->addWikiText( "Please refer to the
	[[Workshop:Guidelines|guidelines for workshop conveners]].
	Beginning at the 2014 Workshop, workshop organizers can propose for
	the designation of a 'Grand Challenge Workshop' (the checkbox at the
	bottom of this page).  If selected, a science justification,
	a rough timeline and duration in years, and suggestions for plenary session tutorial
	speakers will also be required in this submission.  All workshops
	should address Science Challenges drawn from the 2012 Decadal Survey
	for Solar and Space Physics (
	http://sites.nationalacademies.org/ssb/currentprojects/ssb_056864 )
	or from the 2011 CEDAR Strategic Plan (
	http://cedarweb.hao.ucar.edu/wiki/index.php/Community:CEDAR_Strategic_Plan
	with an addendum from November 2013)." ) ;
	$wgOut->addHTML( "<br>\n" ) ;

	$wgOut->addHTML( "Conflicts with other CEDAR workshops will be determined after the due date when all conveners will be asked to look at the list of proposed workshops to determine conflicts before the workshops are scheduled initially." ) ;
	$wgOut->addHTML( "<br><br>\n" ) ;

	if( $action == "edit" )
	{
	    $wgOut->addHTML( "<form name=\"cedarworkshop\" action=\"$wgServer/wiki/index.php/Special:CedarWorkshops?status=mod&id=$id\" method=\"POST\">\n" ) ;
	}
	else
	{
	    $wgOut->addHTML( "<form name=\"cedarworkshop\" action=\"$wgServer/wiki/index.php/Special:CedarWorkshops?status=add&id=$id\" method=\"POST\">\n" ) ;
	}

	// TODO: this is not valid xhtml. Need to make the tags and
	// attribute names lower case
	$wgOut->addHTML( "  <input name=\"yr\" type=\"hidden\" value=\"$cgWorkshopYear\"/>\n" ) ;
	$wgOut->addHTML( "    <table align=\"LEFT\" BORDER=\"0\" width=\"660\" CELLPADDING=\"0\" CELLSPACING=\"0\">\n" ) ;
	$wgOut->addHTML( "	<tr>\n" ) ;
	$wgOut->addHTML( "	    <td width=\"25%\" align=\"RIGHT\">\n" ) ;
	$wgOut->addHTML( "		<span style=\"font-weight:bold;\">*Workshop Title*:&nbsp;&nbsp;</span>\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	    <td width=\"25%\" align=\"LEFT\">\n" ) ;
	$wgOut->addHTML( "		<input type=\"text\" name=\"workshop_title\" value=\"$workshop_title\" size=\"30\">\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	    <td width=\"25%\" align=\"LEFT\">\n" ) ;
	$wgOut->addHTML( "              &nbsp;\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	    <td width=\"25%\" align=\"LEFT\">\n" ) ;
	$wgOut->addHTML( "              &nbsp;\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	</tr>\n" ) ;
	$wgOut->addHTML( "	<tr>\n" ) ;
	$wgOut->addHTML( "	    <td colspan=\"4\" height=\"5\" width=\"100%\" class=\"contexttext\" align=\"CENTER\">\n" ) ;
	$wgOut->addHTML( "              &nbsp;\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	</tr>\n" ) ;
	$wgOut->addHTML( "	<tr>\n" ) ;
	$wgOut->addHTML( "	    <td width=\"25%\" align=\"RIGHT\">\n" ) ;
	$wgOut->addHTML( "		<span style=\"font-weight:bold;\">Short Title*:&nbsp;&nbsp;</span><br><span style=\"line-height:1;font-size:8pt;\">40 alpha numeric&nbsp;&nbsp;</span><br><span style=\"line-height:1;font-size:8pt;\">characters or less.&nbsp;&nbsp;</span><br><span style=\"line-height:1;font-size:8pt;\">can be same as title&nbsp;&nbsp;</span>\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	    <td width=\"25%\" align=\"LEFT\">\n" ) ;
	$wgOut->addHTML( "		<input type=\"text\" name=\"short_title\" $is_editable value=\"$short_title\" size=\"30\">\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	    <td width=\"25%\" align=\"LEFT\">\n" ) ;
	$wgOut->addHTML( "              &nbsp;\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	    <td width=\"25%\" align=\"LEFT\">\n" ) ;
	$wgOut->addHTML( "              &nbsp;\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	</tr>\n" ) ;
	$wgOut->addHTML( "	<tr>\n" ) ;
	$wgOut->addHTML( "	    <td colspan=\"4\" height=\"5\" width=\"100%\" class=\"contexttext\" align=\"CENTER\">\n" ) ;
	$wgOut->addHTML( "              &nbsp;\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	</tr>\n" ) ;
	$wgOut->addHTML( "	<tr>\n" ) ;
	$wgOut->addHTML( "	    <td width=\"25%\" align=\"RIGHT\">\n" ) ;
	$wgOut->addHTML( "		<span style=\"font-weight:bold;\">*Proposer-Convener 1*&nbsp;&nbsp;<br>Name*:&nbsp;&nbsp;</span>\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	    <td width=\"25%\" align=\"LEFT\">\n" ) ;
	$wgOut->addHTML( "		<input type=\"text\" name=\"convener1_name\" value=\"$convener1_name\" size=\"30\">\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	    <td width=\"25%\" align=\"RIGHT\">\n" ) ;
	$wgOut->addHTML( "		<span style=\"font-weight:bold;\">*Email*:&nbsp;&nbsp;</span>\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	    <td width=\"25%\" align=\"LEFT\">\n" ) ;
	$wgOut->addHTML( "		<input type=\"text\" name=\"convener1_email\" value=\"$convener1_email\" size=\"30\">\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	</tr>\n" ) ;
	$wgOut->addHTML( "	<tr>\n" ) ;
	$wgOut->addHTML( "	    <td width=\"25%\" align=\"RIGHT\">\n" ) ;
	$wgOut->addHTML( "		<span style=\"font-weight:bold;\">Convener 2&nbsp;&nbsp;<br>Name:&nbsp;&nbsp;</span>\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	    <td width=\"25%\" align=\"LEFT\">\n" ) ;
	$wgOut->addHTML( "		<input type=\"text\" name=\"convener2_name\" value=\"$convener2_name\" size=\"30\">\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	    <td width=\"25%\" align=\"RIGHT\">\n" ) ;
	$wgOut->addHTML( "		<span style=\"font-weight:bold;\">Email:&nbsp;&nbsp;</span>\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	    <td width=\"25%\" align=\"LEFT\">\n" ) ;
	$wgOut->addHTML( "		<input type=\"text\" name=\"convener2_email\" value=\"$convener2_email\" size=\"30\">\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	</tr>\n" ) ;
	$wgOut->addHTML( "	<tr>\n" ) ;
	$wgOut->addHTML( "	    <td width=\"25%\" align=\"RIGHT\">\n" ) ;
	$wgOut->addHTML( "		<span style=\"font-weight:bold;\">Convener 3&nbsp;&nbsp;<br>Name:&nbsp;&nbsp;</span>\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	    <td width=\"25%\" align=\"LEFT\">\n" ) ;
	$wgOut->addHTML( "		<input type=\"text\" name=\"convener3_name\" value=\"$convener3_name\" size=\"30\">\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	    <td width=\"25%\" align=\"RIGHT\">\n" ) ;
	$wgOut->addHTML( "		<span style=\"font-weight:bold;\">Email:&nbsp;&nbsp;</span>\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	    <td width=\"25%\" align=\"LEFT\">\n" ) ;
	$wgOut->addHTML( "		<input type=\"text\" name=\"convener3_email\" value=\"$convener3_email\" size=\"30\">\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	</tr>\n" ) ;
	$wgOut->addHTML( "	<tr>\n" ) ;
	$wgOut->addHTML( "	    <td width=\"25%\" align=\"RIGHT\">\n" ) ;
	$wgOut->addHTML( "		<span style=\"font-weight:bold;\">Convener 4&nbsp;&nbsp;<br>Name:&nbsp;&nbsp;</span>\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	    <td width=\"25%\" align=\"LEFT\">\n" ) ;
	$wgOut->addHTML( "		<input type=\"text\" name=\"convener4_name\" value=\"$convener4_name\" size=\"30\">\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	    <td width=\"25%\" align=\"RIGHT\">\n" ) ;
	$wgOut->addHTML( "		<span style=\"font-weight:bold;\">Email:&nbsp;&nbsp;</span>\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	    <td width=\"25%\" align=\"LEFT\">\n" ) ;
	$wgOut->addHTML( "		<input type=\"text\" name=\"convener4_email\" value=\"$convener4_email\" size=\"30\">\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	</tr>\n" ) ;
	$wgOut->addHTML( "	<tr>\n" ) ;
	$wgOut->addHTML( "	    <td width=\"25%\" align=\"RIGHT\">\n" ) ;
	$wgOut->addHTML( "		<span style=\"font-weight:bold;\">Convener 5&nbsp;&nbsp;<br>Name:&nbsp;&nbsp;</span>\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	    <td width=\"25%\" align=\"LEFT\">\n" ) ;
	$wgOut->addHTML( "		<input type=\"text\" name=\"convener5_name\" value=\"$convener5_name\" size=\"30\">\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	    <td width=\"25%\" align=\"RIGHT\">\n" ) ;
	$wgOut->addHTML( "		<span style=\"font-weight:bold;\">Email:&nbsp;&nbsp;</span>\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	    <td width=\"25%\" align=\"LEFT\">\n" ) ;
	$wgOut->addHTML( "		<input type=\"text\" name=\"convener5_email\" value=\"$convener5_email\" size=\"30\">\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	</tr>\n" ) ;
	$wgOut->addHTML( "	<tr>\n" ) ;
	$wgOut->addHTML( "	    <td width=\"25%\" align=\"RIGHT\">\n" ) ;
	$wgOut->addHTML( "		<span style=\"font-weight:bold;\">Convener 6&nbsp;&nbsp;<br>Name:&nbsp;&nbsp;</span>\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	    <td width=\"25%\" align=\"LEFT\">\n" ) ;
	$wgOut->addHTML( "		<input type=\"text\" name=\"convener6_name\" value=\"$convener6_name\" size=\"30\">\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	    <td width=\"25%\" align=\"RIGHT\">\n" ) ;
	$wgOut->addHTML( "		<span style=\"font-weight:bold;\">Email:&nbsp;&nbsp;</span>\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	    <td width=\"25%\" align=\"LEFT\">\n" ) ;
	$wgOut->addHTML( "		<input type=\"text\" name=\"convener6_email\" value=\"$convener6_email\" size=\"30\">\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	</tr>\n" ) ;
	$wgOut->addHTML( "	<tr>\n" ) ;
	$wgOut->addHTML( "	    <td colspan=\"4\" height=\"5\" width=\"100%\" class=\"contexttext\" align=\"CENTER\">\n" ) ;
	$wgOut->addHTML( "              &nbsp;\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	</tr>\n" ) ;
	$wgOut->addHTML( "	<tr>\n" ) ;
	$wgOut->addHTML( "	    <td colspan=\"2\" width=\"50%\" align=\"LEFT\">\n" ) ;
	$wgOut->addHTML( "		<span style=\"font-weight:bold;\">&nbsp;&nbsp;&nbsp;Categories (select from each list, Other is optional)</span>\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	    <td colspan=\"2\" width=\"50%\" align=\"LEFT\">\n" ) ;
	$wgOut->addHTML( "		<span style=\"font-weight:bold;\">&nbsp;</span>\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	</tr>\n" ) ;
	$wgOut->addHTML( "	<tr>\n" ) ;
	$wgOut->addHTML( "	    <td colspan=\"4\" height=\"5\" width=\"100%\" class=\"contexttext\" align=\"CENTER\">\n" ) ;
	$wgOut->addHTML( "              &nbsp;\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	</tr>\n" ) ;
	$wgOut->addHTML( "	<tr>\n" ) ;
	$wgOut->addHTML( "	    <td colspan=\"2\" width=\"50%\" align=\"CENTER\">\n" ) ;
	$wgOut->addHTML( "		<span style=\"font-weight:bold;\">Altitudes*&nbsp;&nbsp;</span>\n" ) ;
	$wgOut->addHTML( "              <SELECT name=\"cat_alt\" size=\"1\">\n" ) ;
	$wgOut->addHTML( "                  <OPTION value=\"Select One\" $cat_alt_select>Select One</OPTION>\n" ) ;
	$wgOut->addHTML( "                  <OPTION value=\"MLT\" $cat_alt_mlt>MLT</OPTION>\n" ) ;
	$wgOut->addHTML( "                  <OPTION value=\"IT\" $cat_alt_it>IT</OPTION>\n" ) ;
	$wgOut->addHTML( "              </SELECT>\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	    <td colspan=\"2\" width=\"50%\" align=\"CENTER\">\n" ) ;
	$wgOut->addHTML( "		<span style=\"font-weight:bold;\">Latitudes*&nbsp;&nbsp;</span>\n" ) ;
	$wgOut->addHTML( "              <SELECT name=\"cat_lat\" size=\"1\">\n" ) ;
	$wgOut->addHTML( "                  <OPTION value=\"Select One\" $cat_lat_select>Select One</OPTION>\n" ) ;
	$wgOut->addHTML( "                  <OPTION value=\"global\" $cat_lat_global>global</OPTION>\n" ) ;
	$wgOut->addHTML( "                  <OPTION value=\"equatorial\" $cat_lat_equatorial>equatorial</OPTION>\n" ) ;
	$wgOut->addHTML( "                  <OPTION value=\"mid-latitude\" $cat_lat_mid>mid-latitude</OPTION>\n" ) ;
	$wgOut->addHTML( "                  <OPTION value=\"polar\" $cat_lat_polar>polar</OPTION>\n" ) ;
	$wgOut->addHTML( "              </SELECT>\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	</tr>\n" ) ;
	$wgOut->addHTML( "	<tr>\n" ) ;
	$wgOut->addHTML( "	    <td colspan=\"4\" height=\"2\" width=\"100%\" class=\"contexttext\" align=\"CENTER\">\n" ) ;
	$wgOut->addHTML( "              &nbsp;\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	</tr>\n" ) ;
	$wgOut->addHTML( "	<tr>\n" ) ;
	$wgOut->addHTML( "	    <td colspan=\"2\" width=\"50%\" align=\"CENTER\">\n" ) ;
	$wgOut->addHTML( "		<span style=\"font-weight:bold;\">Inst/Model*&nbsp;&nbsp;</span>\n" ) ;
	$wgOut->addHTML( "              <SELECT name=\"cat_inst\" size=\"1\">\n" ) ;
	$wgOut->addHTML( "                  <OPTION value=\"Select One\" $cat_inst_select>Select One</OPTION>\n" ) ;
	$wgOut->addHTML( "                  <OPTION value=\"radar\" $cat_inst_radar>radar</OPTION>\n" ) ;
	$wgOut->addHTML( "                  <OPTION value=\"optical\" $cat_inst_optical>optical</OPTION>\n" ) ;
	$wgOut->addHTML( "                  <OPTION value=\"satellite\" $cat_inst_satellite>satellite</OPTION>\n" ) ;
	$wgOut->addHTML( "                  <OPTION value=\"modeling\" $cat_inst_modeling>modeling</OPTION>\n" ) ;
	$wgOut->addHTML( "              </SELECT>\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	    <td colspan=\"2\" width=\"50%\" align=\"CENTER\">\n" ) ;
	$wgOut->addHTML( "		<span style=\"font-weight:bold;\">Other: </span>\n" ) ;
	$wgOut->addHTML( "		<input type=\"text\" name=\"cat_other\" value=\"$cat_other\" size=\"30\">\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	</tr>\n" ) ;
	$wgOut->addHTML( "	<tr>\n" ) ;
	$wgOut->addHTML( "	    <td colspan=\"4\" height=\"5\" width=\"100%\" class=\"contexttext\" align=\"CENTER\">\n" ) ;
	$wgOut->addHTML( "              &nbsp;\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	</tr>\n" ) ;
	$wgOut->addHTML( "	<tr>\n" ) ;
	$wgOut->addHTML( "	    <td width=\"25%\" align=\"RIGHT\">\n" ) ;
	$wgOut->addHTML( "		<span style=\"font-weight:bold;\">Workshop&nbsp;&nbsp;<br> Format*:&nbsp;&nbsp;</span>\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	    <td width=\"25%\" align=\"LEFT\">\n" ) ;
	$wgOut->addHTML( "              <SELECT name=\"workshop_format\" size=\"1\">\n" ) ;
        $wgOut->addHTML( "     		<OPTION $no_format_selected
	value=\"Select One\">Select One<?OPTION>\n" ) ;	
	$wgOut->addHTML( "                  <OPTION $short_selected value=\"Short Presentations\">Short Presentations</OPTION>\n" ) ;
	$wgOut->addHTML( "                  <OPTION $short_5_selected
	value=\"Short 5 Slide Presentations\">Short 5 Slide Presentations</OPTION>\n" ) ;
	$wgOut->addHTML( "                  <OPTION $panel_selected value=\"Panel discussion\">Panel Discussion</OPTION>\n" ) ;
	$wgOut->addHTML( "                  <OPTION $tutorial_selected value=\"Tutorials\">Tutorials</OPTION>\n" ) ;
	$wgOut->addHTML( "                  <OPTION $round_selected value=\"Round Table\">Round table discussion</OPTION>\n" ) ;
	$wgOut->addHTML( "                  <OPTION $other_selected value=\"Other\">Other</OPTION>\n" ) ;
	$wgOut->addHTML( "              </SELECT>\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	    <td width=\"25%\" align=\"RIGHT\">\n" ) ;
	$wgOut->addHTML( "		<span style=\"font-weight:bold;\">Other Format:&nbsp;&nbsp;</span>\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	    <td width=\"25%\" align=\"LEFT\">\n" ) ;
	$wgOut->addHTML( "		<input type=\"text\" name=\"other_format\" value=\"$other_format\" size=\"30\">\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	</tr>\n" ) ;
	$wgOut->addHTML( "	<tr>\n" ) ;
	$wgOut->addHTML( "	    <td colspan=\"4\" height=\"5\" width=\"100%\" class=\"contexttext\" align=\"CENTER\">\n" ) ;
	$wgOut->addHTML( "              &nbsp;\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	</tr>\n" ) ;
	$wgOut->addHTML( "	<tr>\n" ) ;
	$wgOut->addHTML( "	    <td width=\"25%\" align=\"RIGHT\">\n" ) ;
	$wgOut->addHTML( "		<span style=\"font-weight:bold;\">Workshop&nbsp;&nbsp;<br> Duration*:&nbsp;&nbsp;</span>\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	    <td width=\"25%\" align=\"LEFT\">\n" ) ;
	$wgOut->addHTML( "              <SELECT name=\"duration\" size=\"1\">\n" ) ;
        $wgOut->addHTML( "     		<OPTION $no_duration
	value=\"Select One\">Select One<?OPTION>\n" ) ;	
	$wgOut->addHTML( "                  <OPTION $two_duration value=\"2 hours\">2 hours</OPTION>\n" ) ;
	$wgOut->addHTML( "                  <OPTION $four_duration value=\"4 hours\">4 hours</OPTION>\n" ) ;
	$wgOut->addHTML( "                  <OPTION $o_duration value=\"Other\">Other</OPTION>\n" ) ;
	$wgOut->addHTML( "              </SELECT>\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	    <td width=\"25%\" align=\"RIGHT\">\n" ) ;
	$wgOut->addHTML( "		<span style=\"font-weight:bold;\">Other Duration:&nbsp;&nbsp;</span>\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	    <td width=\"25%\" align=\"LEFT\">\n" ) ;
	$wgOut->addHTML( "		<input type=\"text\" name=\"other_duration\" value=\"$other_duration\" size=\"30\">\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	</tr>\n" ) ;
	$wgOut->addHTML( "	<tr>\n" ) ;
	$wgOut->addHTML( "	    <td colspan=\"4\" height=\"5\" width=\"100%\" class=\"contexttext\" align=\"CENTER\">\n" ) ;
	$wgOut->addHTML( "              &nbsp;\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	</tr>\n" ) ;
	$wgOut->addHTML( "	<tr>\n" ) ;
	$wgOut->addHTML( "	    <td width=\"25%\" align=\"RIGHT\">\n" ) ;
	$wgOut->addHTML( "		<span style=\"font-weight:bold;\">Estimated&nbsp;&nbsp;<br> Attendance:&nbsp;&nbsp;</span>\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	    <td width=\"25%\" align=\"LEFT\">\n" ) ;
	$wgOut->addHTML( "		<input type=\"text\" name=\"est_attendance\" value=\"$est_attendance\" size=\"30\">\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	    <td width=\"25%\" align=\"LEFT\">\n" ) ;
	$wgOut->addHTML( "              &nbsp;\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	    <td width=\"25%\" align=\"LEFT\">\n" ) ;
	$wgOut->addHTML( "              &nbsp;\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	</tr>\n" ) ;
	$wgOut->addHTML( "	<tr>\n" ) ;
	$wgOut->addHTML( "	    <td colspan=\"4\" height=\"5\" width=\"100%\" class=\"contexttext\" align=\"CENTER\">\n" ) ;
	$wgOut->addHTML( "              &nbsp;\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	</tr>\n" ) ;
	if( $action != "edit" )
	{
	    $wgOut->addHTML( "	<tr>\n" ) ;
	    $wgOut->addHTML( "	    <td colspan=\"4\" width=\"100%\" class=\"contexttext\">\n" ) ;
	    $wgOut->addHTML( "		<span style=\"font-weight:bold;\">*CEDAR Science Challenge and Justification for the workshop - Articulate a challenge and its significance and fit with the Decadal Survey or Strategic Plan.  Identify also the approach for meeting it, including (1) How the questions will be addressed, (2) What resources exist, are planned, or are needed, and (3) How progress should be measured.* (No vertical bar (|) characters)</span>\n" );
	    $wgOut->addHTML( "		<textarea name=\"justification\" rows=\"10\" $is_editable cols=\"60\">$justification</textarea>\n" ) ;
	    $wgOut->addHTML( "	    </td>\n" ) ;
	    $wgOut->addHTML( "	</tr>\n" ) ;
	    $wgOut->addHTML( "	<tr>\n" ) ;
	    $wgOut->addHTML( "	    <td colspan=\"4\" height=\"5\" width=\"100%\" class=\"contexttext\" align=\"CENTER\">\n" ) ;
	    $wgOut->addHTML( "              &nbsp;\n" ) ;
	    $wgOut->addHTML( "	    </td>\n" ) ;
	    $wgOut->addHTML( "	</tr>\n" ) ;
	    $wgOut->addHTML( "	<tr>\n" ) ;
	    $wgOut->addHTML( "	    <td colspan=\"4\" width=\"100%\" class=\"contexttext\">\n" ) ;
	    $wgOut->addHTML( "		<span style=\"font-weight:bold;\">*Description* (No vertical bar (|) characters)</span>\n" ) ;
	    $wgOut->addHTML( "		<textarea name=\"descript\" rows=\"10\" $is_editable cols=\"60\">$descript</textarea>\n" ) ;
	    $wgOut->addHTML( "	    </td>\n" ) ;
	    $wgOut->addHTML( "	</tr>\n" ) ;
	    $wgOut->addHTML( "	<tr>\n" ) ;
	    $wgOut->addHTML( "	    <td colspan=\"4\" height=\"5\" width=\"100%\" class=\"contexttext\" align=\"CENTER\">\n" ) ;
	    $wgOut->addHTML( "              &nbsp;\n" ) ;
	    $wgOut->addHTML( "	    </td>\n" ) ;
	    $wgOut->addHTML( "	</tr>\n" ) ;
	}
	$wgOut->addHTML( "	<tr>\n" ) ;
	$wgOut->addHTML( "	    <td colspan=\"4\" width=\"100%\" class=\"contexttext\">\n" ) ;
	$wgOut->addHTML( "		<span style=\"font-weight:bold;\">Request for Specific Days</span>\n" ) ;
	$wgOut->addHTML( "		<textarea name=\"conflicts\" rows=\"2\" cols=\"60\">$conflicts</textarea>\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	</tr>\n" ) ;
	$wgOut->addHTML( "	<tr>\n" ) ;
	$wgOut->addHTML( "	    <td colspan=\"4\" height=\"2\" width=\"100%\" class=\"contexttext\" align=\"CENTER\">\n" ) ;
	$wgOut->addHTML( "              &nbsp;\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	</tr>\n" ) ;
	$wgOut->addHTML( "	<tr>\n" ) ;
	$wgOut->addHTML( "	    <td colspan=\"4\" width=\"100%\" class=\"contexttext\">\n" ) ;
	$wgOut->addHTML( "		<span style=\"font-weight:bold;\">Special Technical Requests</span> <span style=\"font-size:8pt;\">(one computer projector provided. Examples: internet access, slide projector, etc...)</span>\n" ) ;
	$wgOut->addHTML( "		<textarea name=\"tech_request\" rows=\"2\" cols=\"60\">$tech_request</textarea>\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	</tr>\n" ) ;
	$wgOut->addHTML( "	<tr>\n" ) ;
	$wgOut->addHTML( "	    <td colspan=\"4\" height=\"2\" width=\"100%\" class=\"contexttext\" align=\"CENTER\">\n" ) ;
	$wgOut->addHTML( "              &nbsp;\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	</tr>\n" ) ;
   
	if( $gc_request != "" || $gc_timeline != "" || $gc_speakers != "" )
	{
	    $ishidden = "visible" ;
	    $ischecked = "checked" ;
	}
	else
	{
	    $ishidden = "hidden" ;
	    $ischecked = "" ;
	}

	$wgOut->addHTML( "	<tr>\n" ) ;
	$wgOut->addHTML( "	    <td colspan=\"4\" height=\"2\" width=\"100%\" class=\"contexttext\">\n" ) ;
	$wgOut->addHTML( "		<span style=\"font-weight:bold;\">This year, the CEDAR Science Steering Committee recognized that in order to solve larger issues in the community, more long-lasting workshops are needed.  These are workshops that will focus on issues that are broader ranging and need continuity from year to year for 2-4 years. For the first year, the CSSC will select two Grand Challenge Workshops.</span>\n" ) ;
	$wgOut->addHTML( "		<br/><br/>\n" ) ;
	$wgOut->addHTML( "		<input name=\"gc_check\" type=\"checkbox\" onClick=\"hideShow()\" $ischecked/>&nbsp;<span style=\"font-weight:bold;\">Check the box to the left if you wish to be considered for designation as a CEDAR Grand Challenge Workshop.  Then fill out the next three text fields which will appear.</span>\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "    </tr>\n" ) ;
	$wgOut->addHTML( "  </table>\n\n" ) ;

	$wgOut->addHTML( "  <div id=\"gc\" style=\"visibility:$ishidden;\">\n" ) ;
	$wgOut->addHTML( "    <table style=\"clear:left;margin-top:20px;margin-bottm:20px;\" align=\"LEFT\" BORDER=\"0\" width=\"660\" CELLPADDING=\"0\" CELLSPACING=\"0\">\n" ) ;
	$wgOut->addHTML( "	<tr>\n" ) ;
	$wgOut->addHTML( "	    <td colspan=\"4\" width=\"100%\" class=\"contexttext\">\n" ) ;
	$wgOut->addHTML( "		<span style=\"font-weight:bold;\">Science Justification for designation as a Grand Challenge Workshop</span> <span style=\"font-size:8pt;\">(Justify the need to have this be a multi-year workshop.)</span> \n" ) ;
	$wgOut->addHTML( "		<textarea id=\"gc_request\" name=\"gc_request\" rows=\"10\" cols=\"60\">$gc_request</textarea>\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	</tr>\n" ) ;
	$wgOut->addHTML( "	<tr>\n" ) ;
	$wgOut->addHTML( "	    <td colspan=\"4\" height=\"2\" width=\"100%\" class=\"contexttext\" align=\"CENTER\">\n" ) ;
	$wgOut->addHTML( "              &nbsp;\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	</tr>\n" ) ;
 
	$wgOut->addHTML( "	<tr>\n" ) ;
	$wgOut->addHTML( "	    <td colspan=\"4\" width=\"100%\" class=\"contexttext\">\n" ) ;
	$wgOut->addHTML( "		<span style=\"font-weight:bold;\">Duration in years and Timeline for your Grand Challenge Workshop</span> <span style=\"font-size:8pt;\">(Provide a rough time-line of how the multi-year workshop will be organized, and a planned duration in years.)</span> \n" ) ;
	$wgOut->addHTML( "		<textarea id=\"gc_timeline\" name=\"gc_timeline\" rows=\"10\" cols=\"60\">$gc_timeline</textarea>\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	</tr>\n" ) ;
	$wgOut->addHTML( "	<tr>\n" ) ;
	$wgOut->addHTML( "	    <td colspan=\"4\" height=\"2\" width=\"100%\" class=\"contexttext\" align=\"CENTER\">\n" ) ;
	$wgOut->addHTML( "              &nbsp;\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	</tr>\n" ) ;
  
	$wgOut->addHTML( "	<tr>\n" ) ;
	$wgOut->addHTML( "	    <td colspan=\"4\" width=\"100%\" class=\"contexttext\">\n" ) ;
	$wgOut->addHTML( "		<span
	style=\"font-weight:bold;\">Suggested Tutorial Speakers and Topics for your Grand Challenge Workshop</span> \n" ) ;
	$wgOut->addHTML( "		<textarea id=\"gc_speakers\" name=\"gc_speakers\" rows=\"2\" cols=\"60\">$gc_speakers</textarea>\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	</tr>\n" ) ;
	$wgOut->addHTML( "    </table>\n" ) ;
	$wgOut->addHTML( "  </div>\n" ) ;
	$wgOut->addHTML( "    <table style=\"clear:left;\" align=\"LEFT\" BORDER=\"0\" width=\"660\" CELLPADDING=\"0\" CELLSPACING=\"0\">\n" ) ;
	$wgOut->addHTML( "      <tr>\n" ) ;
	$wgOut->addHTML( "	    <td colspan=\"4\" height=\"2\" width=\"100%\" class=\"contexttext\" align=\"CENTER\">\n" ) ;
	$wgOut->addHTML( "              &nbsp;\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	</tr>\n" ) ;
	$wgOut->addHTML( "	<tr>\n" ) ;
	$wgOut->addHTML( "	    <td width=\"25%\" class=\"contexttext\" align=\"CENTER\">\n" ) ;
	$wgOut->addHTML( "              <input type=\"SUBMIT\" name=\"submit\" value=\"Submit\">\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	    <td width=\"25%\" class=\"contexttext\" align=\"LEFT\">\n" ) ;
	$wgOut->addHTML( "              <input type=\"RESET\" value=\"Reset\">\n" ) ;
	$wgOut->addHTML( "	    </td>\n" ) ;
	$wgOut->addHTML( "	</tr>\n" ) ;
	$wgOut->addHTML( "    </table>\n" ) ;
	$wgOut->addHTML( "</form>\n" ) ;
    }
    
    /**
     * Check for required fields, valid information, then add/modify the
     * information in the database
     *
     * @param string $action whether this is a new proposal or a
     * modification to an existing proposed workshop
     *
     * @return void
     */
    private function doadd( $action )
    {
	global $wgUser, $wgRequest, $wgOut, $wgAuth, $wgServer ;
	global $cgWorkshopYear, $cgWorkshopRecipientName, $cgWorkshopRecipientEmail  ;

	// grab all the information from the wiki request object
	$id = $wgRequest->getInt( 'id' ) ;
	$workshop_title = trim( $wgRequest->getVal( 'workshop_title' ) );
	$short_title = trim( $wgRequest->getVal( 'short_title' ) );
	$convener1_id = 0 ;
	$convener1_name = trim( $wgRequest->getVal( 'convener1_name' ) );
	$convener1_email = trim( $wgRequest->getVal( 'convener1_email' ) );
	$convener2_id = 0 ;
	$convener2_name = trim( $wgRequest->getVal( 'convener2_name' ) );
	$convener2_email = trim( $wgRequest->getVal( 'convener2_email' ) );
	$convener3_id = 0 ;
	$convener3_name = trim( $wgRequest->getVal( 'convener3_name' ) );
	$convener3_email = trim( $wgRequest->getVal( 'convener3_email' ) );
	$convener4_id = 0 ;
	$convener4_name = trim( $wgRequest->getVal( 'convener4_name' ) );
	$convener4_email = trim( $wgRequest->getVal( 'convener4_email' ) );
	$convener5_id = 0 ;
	$convener5_name = trim( $wgRequest->getVal( 'convener5_name' ) );
	$convener5_email = trim( $wgRequest->getVal( 'convener5_email' ) );
	$convener6_id = 0 ;
	$convener6_name = trim( $wgRequest->getVal( 'convener6_name' ) );
	$convener6_email = trim( $wgRequest->getVal( 'convener6_email' ) );
	$cat_alt = trim( $wgRequest->getVal( 'cat_alt' ) ) ;
	$cat_lat = trim( $wgRequest->getVal( 'cat_lat' ) ) ;
	$cat_inst = trim( $wgRequest->getVal( 'cat_inst' ) ) ;
	$cat_other = trim( $wgRequest->getVal( 'cat_other' ) ) ;
	$workshop_format = trim( $wgRequest->getVal( 'workshop_format' ) );
	$other_format = trim( $wgRequest->getVal( 'other_format' ) );
	$duration = trim( $wgRequest->getVal( 'duration' ) );
	$other_duration = trim( $wgRequest->getVal( 'other_duration' ) );
	$est_attendance = trim( $wgRequest->getVal( 'est_attendance' ) );
	$conflicts = trim( $wgRequest->getVal( 'conflicts' ) );
	$tech_request = trim( $wgRequest->getVal( 'tech_request' ) );
	$gc_request = trim( $wgRequest->getVal( 'gc_request' ) );
 	$gc_timeline = trim( $wgRequest->getVal( 'gc_timeline' ) );
	$gc_speakers = trim( $wgRequest->getVal( 'gc_speakers' ) );
	$descript = trim( $wgRequest->getVal( 'descript' ) );
	$justification = trim( $wgRequest->getVal( 'justification' ) );

	// we want to go through all of the fields. So don't error out
	// right away. For each error we display an error message and
	// bump up the variable found_errors.
	$found_errors = 0 ;
	if( !$workshop_title )
	{
	    $wgOut->addHTML( "<span style=\"color:red\">You must supply a workshop title</span><br />\n" ) ;
	    $found_errors++ ;
	}
	if( !$short_title )
	{
	    $wgOut->addHTML( "<span style=\"color:red\">You must supply a workshop short title</span><br />\n" ) ;
	    $found_errors++ ;
	}
	if( strlen( $short_title ) > 40 )
	{
	    $wgOut->addHTML( "<span style=\"color:red\">Short title must be 40 characters or less</span><br />\n" ) ;
	    $found_errors++ ;
	}
	$try_short = str_replace( " ", "a", $short_title ) ;
	if( !ctype_alnum( $try_short ) )
	{
	    $wgOut->addHTML( "<span style=\"color:red\">Short title must contain alpha and numeric characters</span><br />\n" ) ;
	    $found_errors++ ;
	}
	if( !$convener1_name || !$convener1_email)
	{
	    $wgOut->addHTML( "<span style=\"color:red\">You must supply at least one convener name and their email</span><br />\n" ) ;
	    $found_errors++ ;
	}
	if( !$cat_alt || $cat_alt == "Select One" )
	{
	    $wgOut->addHTML( "<span style=\"color:red\">You must specify the altitues category</span><br />\n" ) ;
	    $found_errors++ ;
	}
	if( !$cat_lat || $cat_lat == "Select One" )
	{
	    $wgOut->addHTML( "<span style=\"color:red\">You must specify the latitudes category</span><br />\n" ) ;
	    $found_errors++ ;
	}
	if( !$cat_inst || $cat_inst == "Select One" )
	{
	    $wgOut->addHTML( "<span style=\"color:red\">You must specify the instrument/model category</span><br />\n" ) ;
	    $found_errors++ ;
	}
	if( $workshop_format == "Select One" )
	{
	    $wgOut->addHTML( "<span style=\"color:red\">Please select the workshop format</span><br />\n" ) ;
	    $found_errors++ ;
	}
	if( $workshop_format == "Other" && !$other_format )
	{
	    $wgOut->addHTML( "<span style=\"color:red\">By selecting other format, please specify the format</span><br />\n" ) ;
	    $found_errors++ ;
	}
	if( $duration == "Select One" )
	{
	    $wgOut->addHTML( "<span style=\"color:red\">Please select the workshop duration</span><br />\n" ) ;
	    $found_errors++ ;
	}
	if( $duration == "Other" && !$other_duration )
	{
	    $wgOut->addHTML( "<span style=\"color:red\">By selecting other duration, please specify the desired duration</span><br />\n" ) ;
	    $found_errors++ ;
	}
	if( $action != "mod" && !$descript )
	{
	    $wgOut->addHTML( "<span style=\"color:red\">Please describe the workshop being proposed</span><br />\n" ) ;
	    $found_errors++ ;
	}
	if( strpos( $descript, "|" ) )
	{
	    $wgOut->addHTML( "<span style=\"color:red\">Your description can
	    not contain the vertical bar (|) character.</span><br />\n" ) ;
	    $found_errors++ ;
	}
	if( $action != "mod" && !$justification )
	{
	    $wgOut->addHTML( "<span style=\"color:red\">Please justify the workshop being proposed</span><br />\n" ) ;
	    $found_errors++ ;
	}
	if( $justification && strpos( $justification, "|" ) )
	{
	    $wgOut->addHTML( "<span style=\"color:red\">Your justification can not contain the vertical bar (|) character.</span><br />\n" ) ;
	    $found_errors++ ;
	}

	// Now, did we find any errors? Of so, then just call the
	// proposal form from here. The request fields will still be
	// present and that method can take care of filling in all the
	// information previously entered.
	if( $found_errors > 0 )
	{
	    if( $action == "mod" ) $action = "edit" ;
	    $this->propForm( $action ) ;
	    return ;
	}

	// add this information to a new page or edit the existing page
	// $curr_date = date( "d F, Y" ) ;
	$curr_date = date( "l, F d, Y h:i:s A" ) ;

	$article_title = "$cgWorkshopYear"."_Workshop:$short_title" ;
	$nt = Title::makeTitleSafe( 0, $article_title ) ;
	if( !$nt )
	{
	    $wgOut->addHTML( "<span style=\"color:red\">Was unable to create a new page with the short title $short_title</span><br />\n" ) ;
	    $this->propForm() ;
	    return ;
	}
	if( $action == "add" && $nt->getArticleID() != 0 )
	{
	    $wgOut->addHTML( "<span style=\"color:red\">There is already a workshop proposed with the given short title</span><br />\n" ) ;
	    $this->propForm() ;
	    return ;
	}
	else if( $action != "add" && $nt->getArticleID() == 0 )
	{
	    $wgOut->addHTML( "<span style=\"color:red\">There is no workshop proposed with the given short title</span><br />\n" ) ;
	    $this->propForm() ;
	    return ;
	}

	if( $duration == "Other" )
	{
	    $duration = $other_duration ;
	}
	if( $workshop_format == "Other" )
	{
	    $workshop_format = $other_format ;
	}

	// initialize the database handle
	$dbh =& wfGetDB( DB_MASTER ) ;
	if( !$dbh )
	{
	    $db_error = $dbh->lastError() ;
	    $wgOut->addHTML( "Unable to connect to the database:<br />\n" ) ;
	    $wgOut->addHTML( $db_error ) ;
	    $wgOut->addHTML( "<br />\n" ) ;
	    return ;
	}

	// for user fields we'll want to grab that information from the
	// user database if it exists. If it does, we add the user id to
	// the workshop table. If not, then we add the user name and
	// email address. If someone becomes a registered user sometime
	// in the future we pick that up upon edit of the proposal.
	$user_table = $dbh->tableName( 'user' ) ;

	// grab the table that we'll be updating
	$workshop_table = $dbh->tableName( "cedar_workshop_$cgWorkshopYear" ) ;

	$convener1_id = $this->finduser( $dbh, $user_table, $convener1_email ) ;
	$conveners = "[mailto:$convener1_email $convener1_name]" ;

	$convener2_id = $this->finduser( $dbh, $user_table, $convener2_email ) ;
	if( $convener2_name != "" )
	{
	    if( $convener2_email != "" )
	    {
		$conveners .= "<br>\n[mailto:$convener2_email $convener2_name]";
	    }
	    else
	    {
		$conveners .= "<br>\n$convener2_name" ;
	    }
	}

	$convener3_id = $this->finduser( $dbh, $user_table, $convener3_email ) ;
	if( $convener3_name != "" )
	{
	    if( $convener3_email != "" )
	    {
		$conveners .= "<br>\n[mailto:$convener3_email $convener3_name]";
	    }
	    else
	    {
		$conveners .= "<br>\n$convener3_name" ;
	    }
	}

	$convener4_id = $this->finduser( $dbh, $user_table, $convener4_email ) ;
	if( $convener4_name != "" )
	{
	    if( $convener4_email != "" )
	    {
		$conveners .= "<br>\n[mailto:$convener4_email $convener4_name]";
	    }
	    else
	    {
		$conveners .= "<br>\n$convener4_name" ;
	    }
	}

	$convener5_id = $this->finduser( $dbh, $user_table, $convener5_email ) ;
	if( $convener5_name != "" )
	{
	    if( $convener5_email != "" )
	    {
		$conveners .= "<br>\n[mailto:$convener5_email $convener5_name]";
	    }
	    else
	    {
		$conveners .= "<br>\n$convener5_name" ;
	    }
	}

	$convener6_id = $this->finduser( $dbh, $user_table, $convener6_email ) ;
	if( $convener6_name != "" )
	{
	    if( $convener6_email != "" )
	    {
		$conveners .= "<br>\n[mailto:$convener6_email $convener6_name]";
	    }
	    else
	    {
		$conveners .= "<br>\n$convener6_name" ;
	    }
	}

	$orig_descript = $descript ;

	if( $conflicts == "" )
	{
	    $conflicts = "none" ;
	}

	// Create or modify the article
	$article = new Article( $nt ) ;
	$after_descript = "" ;
	if( $action == "mod" )
	{
	    // grab the current content of the page
	    $original_text = $article->getContent() ;

	    // find where the justification is. We'll replace everything
	    // above that.
	    $descript_pos = strpos( $original_text, "===Justification===" ) ;

	    // take everything from this position and below it
	    $after_descript = substr( $original_text, $descript_pos ) ;
	}

	// This is the text that goes on the new page
	$text = "==$workshop_title==\n" ;
	$text .= "\n" ;
	$text .= "__notoc__\n" ;
	$text .= "===Location, Date/Time and Duration===\n" ;
	$text .= "$duration\n" ;
	$text .= "\n" ;
	$text .= "===Conveners===\n" ;
	$text .= "$conveners\n" ;
	$text .= "\n" ;
	$text .= "===Workshop Categories===\n" ;
	$text .= "Altitudes: $cat_alt - Latitudes: $cat_lat - Inst/Model: $cat_inst - Other: $cat_other\n" ;
	$text .= "\n" ;
	$text .= "===Format of the Workshop===\n" ;
	$text .= "$workshop_format\n" ;
	$text .= "\n" ;
	$text .= "===Estimated attendance===\n" ;
	$text .= "$est_attendance\n" ;
	$text .= "\n" ;
	$text .= "===Requested Specific Days===\n" ;
	$text .= "$conflicts\n" ;
	$text .= "\n" ;
	$text .= "===Special technology requests===\n" ;
	$text .= "$tech_request\n" ;
	$text .= "\n" ;
  
	if( $gc_request != "" || $gc_timeline != "" || $gc_speakers != "" ) 
        {
	    $text .= "==Grand Challenge Workshop==\n" ;
	    $text .= "===Request/Justification for Grand Challenge Workshop===\n" ;
	    $text .= "$gc_request\n" ;
	    $text .= "\n" ;
	    $text .= "===Approximate Timeline and Duration for Grand Challenge Workshop===\n" ;
	    $text .= "$gc_timeline\n" ;
	    $text .= "\n" ;
	    $text .= "===Suggested Tutorial Speakers for your Grand Challenge Workshop===\n" ;
	    $text .= "$gc_speakers\n" ;
	    $text .= "\n" ;
        }

	if( $action == "mod" )
	{
	    $text .= $after_descript ;
	}
	else
	{
	    $text .= "===Justification===\n" ;
	    $text .= "$justification\n" ;
	    $text .= "\n" ;
	    $text .= "===Description===\n" ;
	    $text .= "$descript\n" ;
	    $text .= "\n" ;
	    $text .= "===Workshop Summary===\n" ;
	    $text .= "This is where the final summary workshop report will be.\n" ;
	    $text .= "\n" ;
	    $text .= "===Presentation Resources===\n" ;
	    $text .= "Upload presentation and link to it here. Links to other resources.\n" ;
	    $text .= "\n" ;
	    $text .= "[[Special:Upload|Upload Files Here]]\n" ;
	    $text .= "\n" ;
	    $text .= "* Add links to your presentations here, including agendas, that are uploaded above.  Please add bullets to separate talks.  See further information on [[Help:Uploading_Files|how to upload a file and link to it]].\n" ;
	    $text .= "\n" ;
	    $text .= "[[Category:$cgWorkshopYear Workshop|$short_title]]\n" ;
	    $text .= "\n" ;
	}

	// test the text to make sure there isn't anything malicious. Use
	// the checkSave function from the SpecialPages extension and the
	// CedarFakeEditPage class. Credit them with this code.
	if( !$this->checkSave( $nt, $text ) )
	{
	    $this->propForm() ;
	    return ;
	}

	if( $action == "mod" )
	{
	    $summary = "Modifying workshop $workshop_title" ;
	    $status = $article->doEdit( $text, $summary, EDIT_UPDATE ) ;
	}
	else
	{
	    $summary = "New proposed workshop $workshop_title" ;
	    $status = $article->doEdit( $text, $summary, EDIT_NEW ) ;
	}
	if( !$status || ( is_object( $status ) && !$status->isOK() ) )
	{
	    $wgOut->addHTML( "<span style=\"color:red\">Failed to save the workshop information. Please contact <a href=\"cedar_db@hao.ucar.edu\">Cedar Administrator</a> with this information.</span><br />\n" ) ;
	}

	// This is where if the id is known, the name and email are
	// deleted in the pma table and in the CVS - which Emery does
	// not like.  Will ask if I can delete this!
        if( $convener1_id )
	{
	    $convener1_name = "" ;
	    $convener1_email = "" ;
	}
	if( $convener2_id )
	{
	    $convener2_name = "" ;
	    $convener2_email = "" ;
	}
	if( $convener3_id )
	{
	    $convener3_name = "" ;
	    $convener3_email = "" ;
	}
	if( $convener4_id )
	{
	    $convener4_name = "" ;
	    $convener4_email = "" ;
	}
	if( $convener5_id )
	{
	    $convener5_name = "" ;
	    $convener5_email = "" ;
	}
	if( $convener6_id )
	{
	    $convener6_name = "" ;
	    $convener6_email = "" ;
	}
	if( !$est_attendance || $est_attendance == "" ) $est_attendance = 0 ;

	// clean the values to make sure they won't corrupt the database
	$workshop_title = $dbh->strencode( $workshop_title ) ;
	$short_title = $dbh->strencode( $short_title ) ;
	$orig_descript = $dbh->strencode( $orig_descript ) ;
	$convener1_id = $dbh->strencode( $convener1_id ) ;
	$convener1_name = $dbh->strencode( $convener1_name ) ;
	$convener1_email = $dbh->strencode( $convener1_email ) ;
	$convener2_id = $dbh->strencode( $convener2_id ) ;
	$convener2_name = $dbh->strencode( $convener2_name ) ;
	$convener2_email = $dbh->strencode( $convener2_email ) ;
	$convener3_id = $dbh->strencode( $convener3_id ) ;
	$convener3_name = $dbh->strencode( $convener3_name ) ;
	$convener3_email = $dbh->strencode( $convener3_email ) ;
	$convener4_id = $dbh->strencode( $convener4_id ) ;
	$convener4_name = $dbh->strencode( $convener4_name ) ;
	$convener4_email = $dbh->strencode( $convener4_email ) ;
	$convener5_id = $dbh->strencode( $convener5_id ) ;
	$convener5_name = $dbh->strencode( $convener5_name ) ;
	$convener5_email = $dbh->strencode( $convener5_email ) ;
	$convener6_id = $dbh->strencode( $convener6_id ) ;
	$convener6_name = $dbh->strencode( $convener6_name ) ;
	$convener6_email = $dbh->strencode( $convener6_email ) ;
	$cat_alt = $dbh->strencode( $cat_alt ) ;
	$cat_inst = $dbh->strencode( $cat_inst ) ;
	$cat_lat = $dbh->strencode( $cat_lat ) ;
	$cat_other = $dbh->strencode( $cat_other ) ;
	$workshop_format = $dbh->strencode( $workshop_format ) ;
	$duration = $dbh->strencode( $duration ) ;
	$est_attendance = $dbh->strencode( $est_attendance ) ;
	$justification = $dbh->strencode( $justification ) ;
	$conflicts = $dbh->strencode( $conflicts ) ;
	$tech_request = $dbh->strencode( $tech_request ) ;
	$gc_request = $dbh->strencode( $gc_request ) ;
	$gc_timeline = $dbh->strencode( $gc_timeline ) ;
	$gc_speakers = $dbh->strencode( $gc_speakers ) ;

	if( $action == "mod" )
	{
	    // Add the workshop information to the database.
	    $insert_success = $dbh->update( $workshop_table,
		    array(
			'long_title' => "$workshop_title",
			'short_title' => "$short_title",
			'description' => "$orig_descript",
			'convener1_id' => $convener1_id,
			'convener1_name' => "$convener1_name",
			'convener1_email' => "$convener1_email",
			'convener2_id' => $convener2_id,
			'convener2_name' => "$convener2_name",
			'convener2_email' => "$convener2_email",
			'convener3_id' => $convener3_id,
			'convener3_name' => "$convener3_name",
			'convener3_email' => "$convener3_email",
			'convener4_id' => $convener4_id,
			'convener4_name' => "$convener4_name",
			'convener4_email' => "$convener4_email",
			'convener5_id' => $convener5_id,
			'convener5_name' => "$convener5_name",
			'convener5_email' => "$convener5_email",
			'convener6_id' => $convener6_id,
			'convener6_name' => "$convener6_name",
			'convener6_email' => "$convener6_email",
			'altitudes' => "$cat_alt",
			'inst_model' => "$cat_inst",
			'latitudes' => "$cat_lat",
			'other_cat' => "$cat_other",
			'workshop_format' => "$workshop_format",
			'duration' => "$duration",
			'estimated_attendance' => $est_attendance,
			'justification' => "$justification",
			'conflicts' => "$conflicts",
			'requests' => "$tech_request",
			'gc_request' => "$gc_request",
			'gc_timeline' => "$gc_timeline",
			'gc_speakers' => "$gc_speakers",
		    ), array( /* WHERE */
			    'proposal_id' => $id
		    ), __METHOD__
		) ;
	}
	else
	{
	    // Add the workshop information to the database.
	    $insert_success = $dbh->insert( $workshop_table,
		    array(
			'long_title' => "$workshop_title",
			'short_title' => "$short_title",
			'description' => "$orig_descript",
			'convener1_id' => $convener1_id,
			'convener1_name' => "$convener1_name",
			'convener1_email' => "$convener1_email",
			'convener2_id' => $convener2_id,
			'convener2_name' => "$convener2_name",
			'convener2_email' => "$convener2_email",
			'convener3_id' => $convener3_id,
			'convener3_name' => "$convener3_name",
			'convener3_email' => "$convener3_email",
			'convener4_id' => $convener4_id,
			'convener4_name' => "$convener4_name",
			'convener4_email' => "$convener4_email",
			'convener5_id' => $convener5_id,
			'convener5_name' => "$convener5_name",
			'convener5_email' => "$convener5_email",
			'convener6_id' => $convener6_id,
			'convener6_name' => "$convener6_name",
			'convener6_email' => "$convener6_email",
			'altitudes' => "$cat_alt",
			'inst_model' => "$cat_inst",
			'latitudes' => "$cat_lat",
			'other_cat' => "$cat_other",
			'workshop_format' => "$workshop_format",
			'duration' => "$duration",
			'estimated_attendance' => $est_attendance,
			'justification' => "$justification",
			'conflicts' => "$conflicts",
			'requests' => "$tech_request",
			'gc_request' => "$gc_request",
			'gc_timeline' => "$gc_timeline",
			'gc_speakers' => "$gc_speakers",
		    ),
		    __METHOD__
		) ;
	}

	if( $insert_success == false )
	{
	    $db_error = $dbh->lastError() ;
	    $wgOut->addHTML( "Failed to insert new workshop $workshop_title<br />\n" ) ;
	    $wgOut->addHTML( $db_error ) ;
	    $wgOut->addHTML( "<br />\n" ) ;
	    $wgOut->addHTML( "Please contact <a href=\"mailto:cedar_db@hao.ucar.edu\">Cedar Administrator</a> with this information.\n" ) ;
	}

	// send email to the workshop person
	require_once( 'UserMailer.php' ) ;
	$to = new MailAddress( "$cgWorkshopRecipientEmail", "$cgWorkshopRecipientName" ) ;
	//$to = new MailAddress( "westp@rpi.edu", "Patrick West" ) ;
	$subject = "[CEDAR]: Workshop Proposal" ;

	$body = "" ;
	if( $action == "mod" )
	{
	    $eprop_name = $wgUser->getRealName() ;
	    $eprop_email = $wgUser->getEmail() ;
	    $body = "An update has been made to a $cgWorkshopYear CEDAR workshop proposal by $eprop_name ($eprop_email)\n\n" ;
	}
	else
	{
	    $body = "Below is a proposal for a $cgWorkshopYear CEDAR workshop submission by $eprop_name ($eprop_email)\n\n" ;
	}
	$full_url = $nt->getFullURL() ;
	$body .= "To see the complete proposal please go to the wiki page for $workshop_title at $full_url\n\n" ;

	$from = new MailAddress( "$eprop_email", "$eprop_name" ) ;

	$result = UserMailer::send( $to, $from, $subject, $body );
	if( $result && !$result->isOK() )
	{
	    $wgOut->addHTML( "<span style=\"color:red;\">We were able to add
	    your proposal, but failed to send an email to $cgWorkshopRecipientName.\n" ) ;
	    $wgOut->addHTML( "Please send an email to $cgWorkshopRecipientEmail and let them know that you have submitted a proposal.\n" ) ;
	    $msg = $result->getMessage() ;
	    $wgOut->addHTML( "$msg\n" ) ;
	}

	// redirect to the workshop page
	$wgOut->redirect( $full_url ) ;
    }

    /**
     * Had to crib some checks from EditPage.php, since they're not done
     * in Article.php
     *
     * @param string $nt the node title
     * @param string $text the body of the new page
     *
     * @return true if valid page, false otherwise
     */
    function checkSave( $nt, $text )
    {
	global $wgSpamRegex, $wgFilterCallback, $wgUser, $wgMaxArticleSize, $wgOut;

	$matches = array();
	$errortext = "";

	$editPage = new CedarFakeEditPage($nt);

	# FIXME: more specific errors, copied from EditPage.php
	if( $wgSpamRegex && preg_match( $wgSpamRegex, $text, $matches ) )
	{
	    $wgOut->addHTML( "<span style=\"color:red\">Illegal text discovered in article content</span><br />\n" ) ;
	    return false ;
	}
	else if( $wgFilterCallback && $wgFilterCallback( $nt, $text, 0 ) )
	{
	    $wgOut->addHTML( "<span style=\"color:red\">Filter Callback failed</span><br />\n" ) ;
	    return false;
	}
	else if( !wfRunHooks( 'EditFilter', array( $editPage, $text, 0, &$errortext ) ) )
	{
	    # Hooks usually print their own error
	    return false;
	}
	else if( $errortext != '' )
	{
	    $wgOut->addHTML( "<span style=\"color:red\">Edit Filter failed</span><br />\n" ) ;
	    return false ;
	}
	else if( $wgUser->isBlockedFrom( $nt, false ) )
	{
	    $wgOut->addHTML( "<span style=\"color:red\">This user is blocked from making edits</span><br />\n" ) ;
	    return false ;
	}
	else if( (int)(strlen($text) / 1024) > $wgMaxArticleSize )
	{
	    $wgOut->addHTML( "<span style=\"color:red\">The maximum page size has been exceeded for this workshop proposal</span><br />\n" ) ;
	    return false ;
	}
	else if( wfReadOnly() )
	{
	    $wgOut->addHTML( "<span style=\"color:red\">This page is read only</span><br />\n" ) ;
	    return false ;
	}
	else if( $wgUser->pingLimiter() )
	{
	    $wgOut->addHTML( "<span style=\"color:red\">User not allowed to make edits</span><br />\n" ) ;
	    return false;
	}

	return true;
    }

    /**
     * Given the email address, see if the user exists in the user table
     *
     * @param handle $dbh the database handle
     * @param object $user_table the user table
     * @param string $email the email of the user to look up
     *
     * @return the id of the user if found, zero otherwise
     */
    private function finduser( $dbh, $user_table, $email )
    {
	$id = 0 ;
	if( $email != "" )
	{
	    $query = "SELECT user_id FROM $user_table WHERE user_email = \"$email\"" ;
	    $res = $dbh->query( $query ) ;
	    if( $res )
	    {
		$obj = $dbh->fetchObject( $res ) ;
		if( $obj )
		{
		    $id = $obj->user_id ;
		}
	    }
	}
	return $id ;
    }

    /**
     * Check to see if the current user is a site admin OR one of the
     * workshop administrators
     *
     * @return true if is an admin, false otherwise
     */
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

    /**
     * Can the current user edit a proposed workshop
     *
     * Is the user either an admin, a workshop admin, the proposer of
     * the workshop, or a convener of the workshop? If so, then they can
     * edit the workshop proposal
     *
     * @param handle $dbh database handle
     * @param int $id workshop proposal primary key
     *
     * @return true if user can edit, false otherwise
     */
    private function canEdit( $dbh, $id )
    {
	global $wgUser, $cgWorkshopYear ;
	$uid = $wgUser->getId() ;
	if( $uid == 0 )
	{
	    return false ;
	}

	$query = "SELECT convener1_id, convener2_id, convener3_id, convener4_id, convener5_id, convener6_id FROM cedar_workshop_$cgWorkshopYear WHERE proposal_id = $id;" ;

	$res = $dbh->query( $query ) ;
	if( !$res )
	{
	    return false ;
	}

	$obj = $dbh->fetchObject( $res ) ;
	if( !$obj )
	{
	    return false ;
	}

	$convener1_id = $obj->convener1_id ;
	if( $convener1_id == $uid ) return true ;
	$convener2_id = $obj->convener2_id ;
	if( $convener2_id == $uid ) return true ;
	$convener3_id = $obj->convener3_id ;
	if( $convener3_id == $uid ) return true ;
	$convener4_id = $obj->convener4_id ;
	if( $convener4_id == $uid ) return true ;
	$convener5_id = $obj->convener5_id ;
	if( $convener5_id == $uid ) return true ;
	$convener6_id = $obj->convener6_id ;
	if( $convener6_id == $uid ) return true ;

	return false ;
    }
}

/**
 * Dummy class for extensions that support EditFilter hook
 *
 * We use this fake page to create our new workshop proposal page. With
 * this we can have hooks apply to the page to make sure there aren't
 * any problems with creating the new page
 */
class CedarFakeEditPage {

	/**
	 * The title of the page
	 *
	 * @type string 
	 */
	var $mTitle;

	/**
	 * Constructor for the fake page where we can set the title
	 *
	 * @param string $nt title of the node
	 */
	function CedarFakeEditPage(&$nt) {
		$this->mTitle = $nt;
	}
}

?>

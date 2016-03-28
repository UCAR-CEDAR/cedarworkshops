<?php
# Alert the user that this is not a valid access point to MediaWiki if they
# try to access the special pages file directly.
if ( !defined( 'MEDIAWIKI' ) ) {
        echo <<<EOT
To install my extension, put the following line in LocalSettings.php:
require_once( "\$IP/extensions/cedarworkshops/cedarworkshops.php" );
EOT;
        exit( 1 );
}
 
$wgExtensionCredits[ 'specialpage' ][] = array(
        'path' => __FILE__,
        'name' => 'CedarWorkshops',
        'author' => 'Patrick West',
        'url' => 'http://cedarweb.hao.ucar.edu/cedaradmin/index.php/Extensions:cedarworkshops',
        'descriptionmsg' => 'cedarworkshops-desc',
        'version' => '1.0.1',
);
 
$wgAutoloadClasses['CedarWorkshops'] = __DIR__ .  '/CedarWorkshops_body.php';
$wgAutoloadClasses['CedarWorkshopConveners'] = __DIR__ .  '/CedarWorkshopConveners_body.php';
$wgAutoloadClasses['CedarWorkshopList'] = __DIR__ .  '/CedarWorkshopList_body.php';
$wgAutoloadClasses['CedarWorkshopSimple'] = __DIR__ .  '/CedarWorkshopSimple_body.php';
$wgAutoloadClasses['CedarWorkshopCSV'] = __DIR__ .  '/CedarWorkshopCSV_body.php';

$wgExtensionMessagesFiles['CedarWorkshops'] = __DIR__ .  '/CedarWorkshops.i18n.php';
$wgExtensionMessagesFiles['CedarWorkshopConveners'] = __DIR__ .  '/CedarWorkshops.i18n.php';
$wgExtensionMessagesFiles['CedarWorkshopList'] = __DIR__ .  '/CedarWorkshops.i18n.php';
$wgExtensionMessagesFiles['CedarWorkshopSimple'] = __DIR__ .  '/CedarWorkshops.i18n.php';
$wgExtensionMessagesFiles['CedarWorkshopCSV'] = __DIR__ .  '/CedarWorkshops.i18n.php';

$wgExtensionMessagesFiles['CedarWorkshopsAlias'] = __DIR__ .  '/CedarWorkshops.alias.php';
$wgExtensionMessagesFiles['CedarWorkshopConvenersAlias'] = __DIR__ .  '/CedarWorkshops.alias.php';
$wgExtensionMessagesFiles['CedarWorkshopListAlias'] = __DIR__ .  '/CedarWorkshops.alias.php';
$wgExtensionMessagesFiles['CedarWorkshopSimpleAlias'] = __DIR__ .  '/CedarWorkshops.alias.php';
$wgExtensionMessagesFiles['CedarWorkshopCSVAlias'] = __DIR__ .  '/CedarWorkshops.alias.php';

$wgSpecialPages['CedarWorkshops'] = 'CedarWorkshops';
$wgSpecialPages['CedarWorkshopConveners'] = 'CedarWorkshopConveners';
$wgSpecialPages['CedarWorkshopList'] = 'CedarWorkshopList';
$wgSpecialPages['CedarWorkshopSimple'] = 'CedarWorkshopSimple';
$wgSpecialPages['CedarWorkshopCSV'] = 'CedarWorkshopCSV';

$wgGroupPermissions['sysop']['cedar_admin'] = true;


<?php
if ( !defined( 'MEDIAWIKI' ) ) die();
/**
 * A special page extension that adds a special page that generates citations
 * about pages.
 *
 * @file
 * @ingroup Extensions
 *
 * @link http://www.mediawiki.org/wiki/Extension:CiteThisPage Documentation
 *
 * @author Ævar Arnfjörð Bjarmason <avarab@gmail.com>
 * @copyright Copyright © 2005, Ævar Arnfjörð Bjarmason
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'CiteThisPage',
	'author' => array( 'Ævar Arnfjörð Bjarmason', 'James D. Forrester' ),
	'descriptionmsg' => 'citethispage-desc',
	'url' => 'https://www.mediawiki.org/wiki/Extension:CiteThisPage'
);

# Internationalisation files
$wgMessagesDirs['CiteThisPage'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['CiteThisPageAliases'] = __DIR__ . '/CiteThisPage.alias.php';

$wgHooks['SkinTemplateBuildNavUrlsNav_urlsAfterPermalink'][] = 'efCiteThisPageNav';
$wgHooks['SkinTemplateToolboxEnd'][] = 'efSpecialCiteThisPageToolbox';

$wgSpecialPages['CiteThisPage'] = 'SpecialCiteThisPage';
$wgSpecialPageGroups['CiteThisPage'] = 'pagetools';
$wgAutoloadClasses['SpecialCiteThisPage'] = __DIR__ . '/SpecialCiteThisPage.php';

// Resources
$citeThisPageResourceTemplate = array(
	'localBasePath' => __DIR__ . '/modules',
	'remoteExtPath' => 'CiteThisPage/modules'
);

$wgResourceModules['ext.citeThisPage'] = $citeThisPageResourceTemplate + array(
	'styles' => 'ext.citeThisPage.css',
);

/**
 * @param $skintemplate SkinTemplate
 * @param $nav_urls
 * @param $oldid
 * @param $revid
 * @return bool
 */
function efCiteThisPageNav( &$skintemplate, &$nav_urls, &$oldid, &$revid ) {
	// check whether we’re in the right namespace, the $revid has the correct type and is not empty
	// (which would mean that the current page doesn’t exist)
	$title = $skintemplate->getTitle();
	if ( $title->isContentPage() && $revid !== 0 && !empty( $revid ) )
		$nav_urls['citeThisPage'] = array(
			'args' => array( 'page' => $title->getPrefixedDBkey(), 'id' => $revid )
		);

	return true;
}

/**
 * Add the cite link to the toolbar
 *
 * @param $skin Skin
 *
 * @return bool
 */
function efSpecialCiteThisPageToolbox( &$skin ) {
	if ( isset( $skin->data['nav_urls']['citeThisPage'] ) ) {
		echo Html::rawElement(
			'li',
			array( 'id' => 't-cite' ),
			Linker::link(
				SpecialPage::getTitleFor( 'CiteThisPage' ),
				wfMessage( 'citethispage-link' )->escaped(),
				# Used message keys: 'tooltip-citethispage', 'accesskey-citethispage'
				Linker::tooltipAndAccessKeyAttribs( 'citethispage' ),
				$skin->data['nav_urls']['citeThisPage']['args']
			)
		);
	}

	return true;
}

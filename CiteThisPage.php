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
	'url' => 'https://www.mediawiki.org/wiki/Extension:CiteThisPage',
	'license-name' => 'GPL-2.0+'
);

# Internationalisation files
$wgMessagesDirs['CiteThisPage'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['CiteThisPageAliases'] = __DIR__ . '/CiteThisPage.alias.php';

$wgHooks['SkinTemplateBuildNavUrlsNav_urlsAfterPermalink'][] = 'CiteThisPageHooks::onSkinTemplateBuildNavUrlsNav_urlsAfterPermalink';
$wgHooks['SkinTemplateToolboxEnd'][] = 'CiteThisPageHooks::onSkinTemplateToolboxEnd';

$wgSpecialPages['CiteThisPage'] = 'SpecialCiteThisPage';
$wgSpecialPageGroups['CiteThisPage'] = 'pagetools';
$wgAutoloadClasses['SpecialCiteThisPage'] = __DIR__ . '/SpecialCiteThisPage.php';
$wgAutoloadClasses['CiteThisPageHooks'] = __DIR__ . '/CiteThisPage.hooks.php';

// Resources
$citeThisPageResourceTemplate = array(
	'localBasePath' => __DIR__ . '/modules',
	'remoteExtPath' => 'CiteThisPage/modules'
);

$wgResourceModules['ext.citeThisPage'] = $citeThisPageResourceTemplate + array(
	'styles' => 'ext.citeThisPage.css',
);

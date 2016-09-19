<?php

class SpecialCiteThisPage extends SpecialPage {

	/**
	 * @var Parser
	 */
	private $citationParser;

	public function __construct() {
		parent::__construct( 'CiteThisPage' );
	}

	public function execute( $par ) {
		$this->setHeaders();
		$this->outputHeader();

		$page = $par !== null ? $par : $this->getRequest()->getText( 'page' );
		$title = Title::newFromText( $page );

		$this->showForm( $title );

		if ( $title && $title->exists() ) {
			$id = $this->getRequest()->getInt( 'id' );
			$this->showCitations( $title, $id );
		}
	}

	private function showForm( Title $title = null ) {
		$this->getOutput()->addHTML(
			Xml::openElement( 'form',
				[
					'id' => 'specialCiteThisPage',
					'method' => 'get',
					'action' => wfScript(),
				] ) .
			Html::hidden( 'title', SpecialPage::getTitleFor( 'CiteThisPage' )->getPrefixedDBkey() ) .
			Xml::openElement( 'label' ) .
			$this->msg( 'citethispage-change-target' )->escaped() . ' ' .
			Xml::element( 'input',
				[
					'type' => 'text',
					'size' => 30,
					'name' => 'page',
					'value' => $title ? $title->getPrefixedText() : ''
				],
				''
			) .
			' ' .
			Xml::element( 'input',
				[
					'type' => 'submit',
					'value' => $this->msg( 'citethispage-change-submit' )->escaped()
				],
				''
			) .
			Xml::closeElement( 'label' ) .
			Xml::closeElement( 'form' )
		);
	}

	/**
	 * Return an array of subpages beginning with $search that this special page will accept.
	 *
	 * @param string $search Prefix to search for
	 * @param int $limit Maximum number of results to return (usually 10)
	 * @param int $offset Number of results to skip (usually 0)
	 * @return string[] Matching subpages
	 */
	public function prefixSearchSubpages( $search, $limit, $offset ) {
		$title = Title::newFromText( $search );
		if ( !$title || !$title->canExist() ) {
			// No prefix suggestion in special and media namespace
			return [];
		}
		// Autocomplete subpage the same as a normal search
		$prefixSearcher = new StringPrefixSearch;
		$result = $prefixSearcher->search( $search, $limit, [], $offset );
		return $result;
	}

	protected function getGroupName() {
		return 'pagetools';
	}

	private function showCitations( Title $title, $revId ) {
		if ( !$revId ) {
			$revId = $title->getLatestRevID();
		}

		$out = $this->getOutput();

		$revision = Revision::newFromTitle( $title, $revId );
		if ( !$revision ) {
			$out->wrapWikiMsg( '<div class="errorbox">$1</div>',
				[ 'citethispage-badrevision', $title->getPrefixedText(), $revId ] );
			return;
		}

		$parserOptions = $this->getParserOptions();
		// Set the overall timestamp to the revision's timestamp
		$parserOptions->setTimestamp( $revision->getTimestamp() );

		$parser = $this->getParser();
		// Register our <citation> tag which just parses using a different
		// context
		$parser->setHook( 'citation', [ $this, 'citationTag' ] );
		// Also hold on to a separate Parser instance for <citation> tag parsing
		// since we can't parse in a parse using the same Parser
		$this->citationParser = $this->getParser();

		$ret = $parser->parse(
			$this->getContentText(),
			$title,
			$parserOptions,
			/* $linestart = */ false,
			/* $clearstate = */ true,
			$revId
		);

		$this->getOutput()->addModuleStyles( 'ext.citeThisPage' );
		$this->getOutput()->addParserOutputContent( $ret );

	}

	/**
	 * @return Parser
	 */
	private function getParser() {
		$parserConf = $this->getConfig()->get( 'ParserConf' );
		return new $parserConf['class']( $parserConf );
	}

	/**
	 * Get the content to parse
	 *
	 * @return string
	 */
	private function getContentText() {
		$msg = $this->msg( 'citethispage-content' )->inContentLanguage()->plain();
		if ( $msg == '' ) {
			# With MediaWiki 1.20 the plain text files were deleted
			# and the text moved into SpecialCite.i18n.php
			# This code is kept for b/c in case an installation has its own file "citethispage-content-xx"
			# for a previously not supported language.
			global $wgContLang, $wgContLanguageCode;
			$dir = __DIR__ . DIRECTORY_SEPARATOR;
			$code = $wgContLang->lc( $wgContLanguageCode );
			if ( file_exists( "${dir}citethispage-content-$code" ) ) {
				$msg = file_get_contents( "${dir}citethispage-content-$code" );
			} elseif ( file_exists( "${dir}citethispage-content" ) ){
				$msg = file_get_contents( "${dir}citethispage-content" );
			}
		}

		return $msg;
	}

	/**
	 * Get the common ParserOptions for both parses
	 *
	 * @return ParserOptions
	 */
	private function getParserOptions() {
		$parserOptions = ParserOptions::newFromUser( $this->getUser() );
		$parserOptions->setDateFormat( 'default' );
		$parserOptions->setEditSection( false );

		// Having tidy on causes whitespace and <pre> tags to
		// be generated around the output of the CiteThisPageOutput
		// class TODO FIXME.
		$parserOptions->setTidy( false );

		return $parserOptions;
	}

	/**
	 * Implements the <citation> tag.
	 *
	 * This is a hack to allow content that is typically parsed
	 * using the page's timestamp/pagetitle to use the current
	 * request's time and title
	 *
	 * @param string $text
	 * @param array $params
	 * @param Parser $parser
	 * @return string
	 */
	public function citationTag( $text, $params, Parser $parser ) {
		$ret = $this->citationParser->parse(
			$text,
			$this->getPageTitle(),
			$this->getParserOptions(),
			/* $linestart = */ false
		);

		return $ret->getText();

	}
}

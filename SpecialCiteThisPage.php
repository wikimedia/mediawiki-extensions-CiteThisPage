<?php

class SpecialCiteThisPage extends SpecialPage {
	function __construct() {
		parent::__construct( 'CiteThisPage' );
	}

	function execute( $par ) {
		global $wgUseTidy;

		// Having tidy on causes whitespace and <pre> tags to
		// be generated around the output of the CiteThisPageOutput
		// class TODO FIXME.
		$wgUseTidy = false;

		$this->setHeaders();
		$this->outputHeader();

		$page = $par !== null ? $par : $this->getRequest()->getText( 'page' );
		$title = Title::newFromText( $page );

		$cform = new CiteThisPageForm( $title );
		$cform->execute();

		if ( $title && $title->exists() ) {
			$id = $this->getRequest()->getInt( 'id' );
			$cout = new CiteThisPageOutput( $title, $id );
			$cout->execute();
		}
	}
}

class CiteThisPageForm {
	/**
	 * @var Title
	 */
	public $mTitle;

	function __construct( &$title ) {
		$this->mTitle =& $title;
	}

	function execute() {
		global $wgOut, $wgScript;

		$wgOut->addHTML(
			Xml::openElement( 'form',
				array(
					'id' => 'specialCiteThisPage',
					'method' => 'get',
					'action' => $wgScript
				) ) .
				Html::hidden( 'title', SpecialPage::getTitleFor( 'CiteThisPage' )->getPrefixedDBkey() ) .
				Xml::openElement( 'label' ) .
					wfMessage( 'citethispage-change-target' )->escaped() . ' ' .
					Xml::element( 'input',
						array(
							'type' => 'text',
							'size' => 30,
							'name' => 'page',
							'value' => is_object( $this->mTitle ) ? $this->mTitle->getPrefixedText() : ''
						),
						''
					) .
					' ' .
					Xml::element( 'input',
						array(
							'type' => 'submit',
							'value' => wfMessage( 'citethispage-change-submit' )->escaped()
						),
						''
					) .
				Xml::closeElement( 'label' ) .
			Xml::closeElement( 'form' )
		);
	}
}

class CiteThisPageOutput {
	/**
	 * @var Title
	 */
	public $mTitle;

	/**
	 * @var Article
	 */
	public $mArticle;

	public $mId;

	/**
	 * @var Parser
	 */
	public $mParser;

	/**
	 * @var ParserOptions
	 */
	public $mParserOptions;

	public $mSpTitle;

	function __construct( $title, $id ) {
		global $wgHooks, $wgParser;

		$this->mTitle = $title;
		$this->mArticle = new Article( $title );
		$this->mId = $id;

		$wgHooks['ParserGetVariableValueVarCache'][] = array( $this, 'varCache' );

		$this->genParserOptions();
		$this->genParser();

		$wgParser->setHook( 'citation', array( $this, 'citationTagParse' ) );
	}

	function execute() {
		global $wgOut, $wgParser, $wgHooks;

		$wgHooks['ParserGetVariableValueTs'][] = array( $this, 'timestamp' );

		$msg = wfMessage( 'citethispage-content' )->inContentLanguage()->plain();
		if ( $msg == '' ) {
			# With MediaWiki 1.20 the plain text files were deleted and the text moved into SpecialCite.i18n.php
			# This code is kept for b/c in case an installation has its own file "citethispage-content-xx"
			# for a previously not supported language.
			global $wgContLang, $wgContLanguageCode;
			$dir = dirname( __FILE__ ) . DIRECTORY_SEPARATOR;
			$code = $wgContLang->lc( $wgContLanguageCode );
			if ( file_exists( "${dir}citethispage-content-$code" ) ) {
				$msg = file_get_contents( "${dir}citethispage-content-$code" );
			} elseif( file_exists( "${dir}citethispage-content" ) ){
				$msg = file_get_contents( "${dir}citethispage-content" );
			}
		}
		$ret = $wgParser->parse( $msg, $this->mTitle, $this->mParserOptions, false, true, $this->getRevId() );
		$wgOut->addModules( 'ext.specialCiteThisPage' );

		# Introduced in 1.24
		if( method_exists( $wgOut, 'addParserOutputContent' ) ) {
			$wgOut->addParserOutputContent( $ret );
		} else {
			$wgOut->addHTML( $ret->getText() );
		}
	}

	function genParserOptions() {
		global $wgUser;
		$this->mParserOptions = ParserOptions::newFromUser( $wgUser );
		$this->mParserOptions->setDateFormat( MW_DATE_DEFAULT );
		$this->mParserOptions->setEditSection( false );
	}

	function genParser() {
		$this->mParser = new Parser;
		$this->mSpTitle = SpecialPage::getTitleFor( 'CiteThisPage' );
	}

	function citationTagParse( $in, $argv ) {
		$ret = $this->mParser->parse( $in, $this->mSpTitle, $this->mParserOptions, false );

		return $ret->getText();
	}

	function varCache() {
		return false;
	}

	function timestamp( &$parser, &$ts ) {
		if ( isset( $parser->mTagHooks['citation'] ) ) {
			$ts = wfTimestamp( TS_UNIX, $this->mArticle->getTimestamp() );
		}

		return true;
	}

	function getRevId() {
		if ( $this->mId ) {
			return $this->mId;
		} else {
			return $this->mTitle->getLatestRevID();
		}
	}
}

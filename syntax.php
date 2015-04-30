<?php
/**
 * mimetex-Plugin: Parses latex-blocks
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Michael Gritsaenko <michael.gritsaenko@gmail.com>
 * @date       2011-02-01
 */

/**
 * ChangeLog:
 *
 * [04/30/2015]: by LarsDW223
 *               Added ODT support.
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_mimetex extends DokuWiki_Syntax_Plugin {

    /**
     * return some info
     */
    function getInfo(){
        return array(
            'author' => 'Michael Gritsaenko',
            'email'  => 'michael.gritsaenko@gmail.com',
            'date'   => '2011-02-01',
            'name'   => 'mimetex Plugin',
            'desc'   => 'mimetex Plugin based on mimeTeX
                         parses latex blocks',
            'url'    => 'http://www.dokuwiki.org/plugin:mimeTeX'
        );
    }

    /**
    * What kind of syntax are we?
    */
    function getType(){
      return 'protected';
    }

    /**
    * Where to sort in?
    */
    function getSort(){
      return 100;
    }

    /**
    * Connect pattern to lexer
    */
    function connectTo($mode) {
      $this->Lexer->addEntryPattern('<latex(?=.*\x3C/latex\x3E)',$mode,'plugin_mimetex');
    }

    function postConnect() {
      $this->Lexer->addExitPattern('</latex>','plugin_mimetex');
    }

    /**
     * Handle the match
    */
    function handle($match, $state, $pos) {
      if ( $state == DOKU_LEXER_UNMATCHED ) {
        $matches = preg_split('/>/u',$match,2);
        $matches[0] = trim($matches[0]);
        if ( trim($matches[0]) == '' ) {
          $matches[0] = NULL;
        }
        return array($matches[1],$matches[0]);
      }
      return TRUE;
    }
    /**
     * Create output
    */
    function render($mode, &$renderer, $formula) {
      global $conf;
      if( ($mode == 'xhtml' || $mode == 'odt') && strlen($formula[0]) > 1) {
        if ( !is_dir($conf['mediadir'] . '/latex') ) {
          mkdir($conf['mediadir'] . '/latex', 0777-$conf['dmask']);
        }

        $hash = md5(serialize($formula));
        $cachefilename = $conf['mediadir'] . '/latex/'.$hash.'.gif';
        $cacheurl = DOKU_BASE.'lib/exe/fetch.php?media='.urlencode('latex:'.$hash.'.gif');

        if( !is_readable($cachefilename) ) {

          require_once(DOKU_PLUGIN.'mimetex/mimetexrender.php');
          $mimetex = new mimetexRender();
 
          if( !$mimetex->render($cachefilename, $formula[0]) ) {
            $renderer->doc .= '**ERROR RENDERING LATEX**:<br/><b>'.$mimetex->_error."</b>";
            return false;
          }
        }

        switch ($mode) {
            case 'odt':
                $renderer->_odtAddImage ($cachefilename);
                break;
            default:
                $renderer->doc .= '<img src="'.$cacheurl.'" class="media" title="mimeTeX" alt="mimeTeX" />';
                break;
        }
        return true;
      }
      return false;
    }
}
?>

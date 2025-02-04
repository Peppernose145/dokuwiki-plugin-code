<?php
/**
 * Code Plugin: replaces Dokuwiki's own code syntax
 *
 * Syntax:     <code lang |title>
 *   lang      (optional) programming language name, is passed to geshi for code highlighting
 *             if not provided, the plugin will attempt to derive a value from the file name
 *             (refer $extensions in render() method)
 *   title     (optional) all text after '|' will be rendered above the main code text with a
 *             different style.
 *
 * if no title is provided will render as native dokuwiki code syntax mode, e.g.
 *   <pre class='code {lang}'> ... </pre>
 *
 * if title is provide will render as follows
 *   <div class='source'>
 *     <p>{title}</p>
 *     <pre class='code {lang}'> ... </pre>
 *   </div>
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Christopher Smith <chris@jalakai.co.uk>
 */

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_code_code extends DokuWiki_Syntax_Plugin {

    var $syntax = "";

    /**
     * return some info
     */
    function getInfo(){
      return array(
        'author' => 'Christopher Smith (original), Peppernose145 (forked)',
        'email'  => 'no email @',
        'date'   => '2021-08-11',
        'name'   => 'Titleable Code Plugin',
        'desc'   => 'Replacement for Dokuwiki\'s own <code> handler, adds a title to the box.
                     Syntax: <code lang|title>, lang and title are optional. title does not support any dokuwiki markup.',
        'url'    => 'http://www.dokuwiki.org/plugin:code',
      );
    }

    function getType(){ return 'protected';}
    function getPType(){ return 'block';}

    // must return a number lower than returned by native 'code' mode (200)
    function getSort(){ return 195; }

    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
      $this->Lexer->addEntryPattern('<code(?=[^\r\n]*?>.*?</code>)',$mode,'plugin_code_code');
    }

    function postConnect() {
      $this->Lexer->addExitPattern('</code>', 'plugin_code_code');
    }

    /**
     * Handle the match
     */
    function handle($match, $state, $pos, Doku_Handler $handler){

        switch ($state) {
          case DOKU_LEXER_ENTER:
            $this->syntax = substr($match, 1);
            return false;

          case DOKU_LEXER_UNMATCHED:
             // will include everything from <code ... to ... </code >
             // e.g. ... [lang] [|title] > [content]
             list($attr, $content) = preg_split('/>/u',$match,2);
             list($lang, $title) = preg_split('/\|/u',$attr,2);

             if ($this->syntax == 'code') {
               $lang = trim($lang);
               if ($lang == 'html') $lang = 'html4strict';
               if (!$lang) $lang = NULL;
             } else {
               $lang = NULL;
             }

             return array($this->syntax, $lang, trim($title), $content);
        }
        return false;
    }

    /**
     * Create output
     */
    function render($mode, Doku_renderer $renderer, $data) {

        if (count($data) == 4) {
          list($syntax, $lang, $title, $content) = $data;

          if($mode == 'xhtml'){
            if ($title) $renderer->doc .= "<div class='$syntax'><p>".$renderer->_xmlEntities($title)."</p>";
            if ($syntax == 'code') $renderer->code($content, $lang); else $renderer->file($content);
            if ($title) $renderer->doc .= "</div>";
          } else {
            if ($syntax == 'code') $renderer->code($content, $lang); else $renderer->file($content);
        }

        return true;
      }
      return false;
    }
}

//Setup VIM: ex: et ts=4 enc=utf-8 :
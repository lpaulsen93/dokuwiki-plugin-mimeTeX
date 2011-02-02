<?php
/**
 * mimeTeX Rendering Class
 * Copyright (C) 2011 Michael Gritsaenko <michael.gritsaenko@gmail.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * --------------------------------------------------------------------
 * @author 2011 Michael Gritsaenko <michael.gritsaenko@gmail.com>
 * @version v0.1
 * @package mimetexrender
 *
 */
if(!defined('MIMETEXEXE')) define('MIMETEXEXE','"'.realpath(dirname(__FILE__).'/mimetex.exe').'"');


class mimetexRender {

    // ====================================================================================
    // Variable Definitions
    // ====================================================================================
    var $_tmp_dir = "c:/temp";
    // i was too lazy to write mutator functions for every single program used
    // just access it outside the class or change it here if nescessary
//    var $_mimetex_path = ;
    var $_string_length_limit = 5000;
    var $_latexclass = "article"; //install extarticle class if you wish to have smaller font sizes
    // this most certainly needs to be extended. in the long term it is planned to use
    // a positive list for more security. this is hopefully enough for now. i'd be glad
    // to receive more bad tags !
    var $_latex_tags_blacklist = array(
        "include","def","command","loop","repeat","open","toks","output","input",
        "catcode","name","^^",
        "\\every","\\errhelp","\\errorstopmode","\\scrollmode","\\nonstopmode","\\batchmode",
        "\\read","\\write","csname","\\newhelp","\\uppercase", "\\lowercase","\\relax","\\aftergroup",
        "\\afterassignment","\\expandafter","\\noexpand","\\special"
        );
    var $_error = "";

    /**
     * Renders a LaTeX formula by the using the following method:
     *  - write the formula into a wrapped tex-file in a temporary directory
     *    and change to it
     *  - Create a GIF file using mimeTeX
     *  - Save the resulting image to the picture cache directory
     *
     * @param string image filename in cache
     * @param string LaTeX formula
     * @returns true if the picture has been successfully saved to the picture
     *          cache directory
     */
    function render($cachefilename, $formula) {

        $formula = preg_replace("/&gt;/i", ">", $formula);
        $formula = preg_replace("/&lt;/i", "<", $formula);

        // security filter: reject too long formulas
        if (strlen($formula) > $this->_string_length_limit) {
            return false;
        }

        // security filter: try to match against LaTeX-Tags Blacklist
/*        for ($i=0;$i<sizeof($this->_latex_tags_blacklist);$i++) {
            if (stristr($formula, $this->_latex_tags_blacklist[$i])) {
                $this->_error = "LaTeX tag [".$this->_latex_tags_blacklist[$i]."] in Blacklist";
                return false;
            }
        }
*/
        $current_dir = getcwd();
        chdir($this->_tmp_dir);

        $tmp = md5(rand());
        $tex = $tmp.".tex";

        // create temporary latex file
        $fp = fopen($tex,"a+");
        fputs($fp, $formula);
        fclose($fp);

        // convert tex file to gif using mimetex.exe
        $img = $tmp.".gif";
        $command = MIMETEXEXE." -f ".$tex." -e ".$img;
        
        $status_code = exec($command);

        unlink($tex);

        if ($status_code) {
            chdir($current_dir);
            $this->_error = "can't execute [$command] ";
            return false;
        }
        $status_code = rename($img, $cachefilename);
        if (!$status_code) {
            chdir($current_dir);
            $this->_error = "can't rename [$img] to [$cachefilename]";
            return false;
        }

        return true;
    }

}

?>

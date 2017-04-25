<?php

/**
 * Quoteoftheday
 * Backend
 * @version 1.1, July 2015
 * @author svasti
 */


// Security check
if ((!function_exists('sv')))die('Access denied');


define('QUOTEOFTHEDAY_VERSION', '1.1');
define('QUOTEOFTHEDAYDATA_VERSION', '1');

if (function_exists('XH_wantsPluginAdministration') && XH_wantsPluginAdministration('quoteoftheday')
   || isset($quoteoftheday) && $quoteoftheday === 'true'
) {

    $o .= "\n\n<!-- Quoteoftheday Plugin -->\n\n";
    $plugin = basename(dirname(__FILE__),"/");
    $admin  = isset($_POST['admin']) ? $_POST['admin'] : $admin = isset($_GET['admin']) ? $_GET['admin'] : '';
    $action = isset($_POST['action']) ? $_POST['action'] : $action = isset($_GET['action']) ? $_GET['action'] : '';

    $o .= print_plugin_admin('on');
    $wrongchar = false;
    $fileselect = isset($_COOKIE['quoteoftheday'])? $_COOKIE['quoteoftheday'] : '';
    $filename = $fileselect? $fileselect : 'quote_'.$sl.'.txt';
    $active = isset($_COOKIE['activequote'])? $_COOKIE['activequote'] : '1';

    // make sure that the plugin css really gets put into the generated plugincss
    if(!$plugin_cf['quoteoftheday']['css_activated']) {
        touch($pth['folder']['plugins'].'quoteoftheday/css/stylesheet.css');
        $config = file_get_contents($pth['folder']['plugins'].'quoteoftheday/config/config.php');
        $config = preg_replace('!css_activated\'\]\="(.)?"!','css_activated\']="true"', $config);
        file_put_contents($pth['folder']['plugins'].'quoteoftheday/config/config.php',$config);
        include $pth['folder']['plugins'].'quoteoftheday/config/config.php';
    }

    // if "edit all quotes together" has been choosen, write this into the config
    if (isset($_POST['totalview'])) {
        $config = file_get_contents($pth['folder']['plugins'].'quoteoftheday/config/config.php');
        $config = preg_replace('!totalview\'\]\="(.)?"!','totalview\']="'.$_POST['totalview'].'"', $config);
        file_put_contents($pth['folder']['plugins'].'quoteoftheday/config/config.php',$config);
        include $pth['folder']['plugins'].'quoteoftheday/config/config.php';
    }

    // check what kind of file management is wanted
    if (isset($_POST['newquotefile']))
    {
        $newquotefile = $_POST['newquotefile'];
        $newname = isset($_POST['newname'])? $_POST['newname'] : '';
        $deletequote = isset($_POST['delete'])? $_POST['delete'] : '';
        $newfile = '';

        if($newquotefile == 'add' &&  $newname) {
            if(preg_match('/[^a-zA-Z0-9\-\._]/',$newname)) {
                $wrongchar = true;
                $o .= '<p class="cmsimplecore_warning">'
                    . $plugin_tx['quoteoftheday']['file-manager_wrong_char']
                    . '</p>';
            } else {
                $newfile = $newname;
                if(substr($newfile,-4,4)!='.txt') $newfile .= '.txt';
            }
        } elseif($newquotefile != 'del' &&  !$deletequote) {
            $newfile = $newquotefile;
        }
        $fileselect = $newfile;
        setcookie('quoteoftheday',$newfile);
        $filename = $newfile? $newfile : 'quote_'.$sl.'.txt';
    }

    if(!$admin || $admin == 'plugin_main') {

        $datapath = quoteoftheday_Data();
        if(!is_dir($datapath)) {
            if(ini_get('safe_mode')) {
                $o .= '<p class="cmsimplecore_warning">'
                    . $plugin_tx['quoteoftheday']['warning_save_mode']
                    . '</p>';
                e('missing', 'folder', $datapath);
            } 
            elseif(!mkdir($datapath, 0755, true)) e('cntwriteto', 'folder', $datapath);
        }

        if(isset($_POST['delete'])) {
            unlink($datapath . $_POST['deletefile']);
            if(is_file($datapath . 'selection_' . $_POST['deletefile'])) {
                unlink($datapath . 'selection_' . $_POST['deletefile']);
            }
        }


        if(!$wrongchar) {
            // create data files if necessary and check writing permissions
            if(!is_file($datapath.$filename)){
                $handle = fopen($datapath.$filename, "w");
                fwrite($handle,'filetype=quoteoftheday|version='
                . QUOTEOFTHEDAYDATA_VERSION
                . '|frame=|headline=|selection=|timing='."\n".'===');
                fclose($handle);
                if(ini_get('safe_mode')) chmod($datapath.$filename, 0777);
            }
            if(!is_file($datapath.'selection_'.$filename)){
                $handle = fopen($datapath.'selection_'.$filename, "w");
                fclose($handle);
                if(ini_get('safe_mode')) chmod($datapath.'selection_'.$filename, 0777);
            }
            if(!is_writable($datapath.$filename))
                e('cntwriteto', 'file', $datapath.$filename);
            if(!is_writable($datapath.'selection_'.$filename))
                e('cntwriteto', 'file', $datapath.'selection_'.$filename);
        }


        // save changes in quotes file
        if($action == 'savequote') {

            $frame       = isset($_POST['frame'])         ? $_POST['frame']            : '';
            $addendquote = isset($_POST['addendquote'])   ? $_POST['addendquote']      : '';
            $addquote    = isset($_POST['addquote'])      ? $_POST['addquote']         : array();
            $delquote    = isset($_POST['delquote'])      ? $_POST['delquote']         : array();
            $quote       = isset($_POST['quote'])         ? $_POST['quote']            : array();
            $move        = isset($_POST['move'])          ? $_POST['move']             : array();
            $quotefile   = isset($_POST['quotefile'])     ? stsl($_POST['quotefile'])  : '';
            $selection   = isset($_POST['selection'])     ? $_POST['selection']        : '';
            $timing      = isset($_POST['timing'])        ? $_POST['timing']           : '';
            $headline    = isset($_POST['headline'])      ? str_replace('|',' ',stsl(trim($_POST['headline']))) : '';
            $preview     = isset($_POST['start_preview']) ? $_POST['start_preview']    : '';

            if($quote) {
                if($move) {
                    foreach ($move as $key=>$value) {
                        if($value) {
                            $active = $value;
                            $movingquote = $quote[($key)];
                            array_splice($quote,($key - 1),1);
                            array_splice($quote,($value - 1),0,$movingquote);
                            break;
                        }
                    }
                }
                if($delquote) {
                    foreach ($delquote as $key=>$value) {
                        if($value) {
                            array_splice($quote,($key - 1),1);
                            break;
                        }
                    }
                }
                if($addquote) {
                    foreach ($addquote as $key=>$value) {
                        if($value) { 
                            $active = $key + 1;
                            array_splice($quote,$key,0,'');
                            break;
                        }
                    }
                }

                foreach ($quote as $key=>$value) {
                    $quotefile .= '===' . "\n" . stsl(rtrim($value)) ."\n";
                }
                if($addendquote) {
                    $quotefile .= '===' . "\n \n";
                    $active = count($quote) + 1;
                    setcookie('activequote',$active);
                }
            }

            $quotefile = trim(ltrim($quotefile,'==='));

            file_put_contents($datapath . $filename,
                'filetype=quoteoftheday|version='
                . QUOTEOFTHEDAYDATA_VERSION
                . '|frame='
                . $frame
                . '|headline='
                . $headline
                . '|selection='
                . $selection
                . '|timing='
                . $timing
                . "\n"
                . '==='
                . "\n"
                . $quotefile);

            // clear the current quote
            file_put_contents($datapath . 'selection_' . $filename, '400');
        }

        $quotefile = file_get_contents($datapath.$filename);
        $quotearray = explode('===',$quotefile);
        $quotepreconfig = explode('|',$quotearray[0]);
        foreach ($quotepreconfig as $i) {
            @list($key,$value) = explode('=',$i);
            $quoteconfig[$key] = trim($value);
        }
        // delete the header from the quotearray
        unset($quotearray[0]);

        // if header is present, cut it off before presenting the quotes
        if(isset($quoteconfig['headline'])) {
            $endofhead = strpos($quotefile, '===');
            $quotefile = trim(substr($quotefile,($endofhead + 3)));
        }


        // generate & show preview
        if(isset($_POST['preview']) && $_POST['preview']) {

            $o .= '<button onClick="location = \'?quoteoftheday&normal\';">'
               .  $plugin_tx['quoteoftheday']['start_return'] . '</button>';

            foreach ($quotearray as $key=>$value) {
                $o .= quoteoftheday_StyleQuote(trim($value),$quoteconfig['headline'],$quoteconfig['frame']);
            }

        } else {

            // standard admin quote editing view
            // first the js
            include_once $pth['folder']['plugins'].'quoteoftheday/js/js.php';

            $hjs .= '<link rel="stylesheet" type="text/css" href="'
                 .  $pth['folder']['plugins'].'quoteoftheday/css/backend.css">';


            // Plugin name and switchable (on/off) Copyright notice
            $o .= '<h2>Quoteoftheday_XH '
               . '<span style="font:normal normal 10pt sans-serif;">v. ' . QUOTEOFTHEDAY_VERSION
               . ' &copy; 2015 by <a href="http://frankziesing.de/cmsimple/">svasti</a> &nbsp;'
               // button to display copyright notice
               . '<input type="button" value="license?" style="font-size:80%;" OnClick="
                    if(document.getElementById(\'license\').style.display == \'none\') {
                        document.getElementById(\'license\').style.display = \'inline\';
                    } else {
                        document.getElementById(\'license\').style.display = \'none\';
                    }
                    ">'
               . '</span></h2>'."\n"
               . '<p style="font-size:80%; line-height:1.2; font-family: sans-serif;display:none" id="license">'
               . 'This plugin is free software under the terms of the GNU General Public License v. 3 or '
               . 'later, analog to <a href="' . $sn . '?' . uenc($cf['menu']['legal']) . '">'
               . $cf['menu']['legal'] . '</a><br><br></p>'."\n" ;

            // IE8 warning
            $o .= '<!--[if lte IE 8]><p class="cmsimplecore_warning">'
               .  $plugin_tx['quoteoftheday']['warning_ie8'] . '</p><![endif]-->';

            // Short instuctions for use
            if(isset($_COOKIE['quoteinstructions']) && $_COOKIE['quoteinstructions']=='inline') {
                $moreInstr = 'inline';
                $moreInstrText = $plugin_tx['quoteoftheday']['instruction_less'];
            } else {
                $moreInstr = 'none';
                $moreInstrText = $plugin_tx['quoteoftheday']['instruction_more'];
            } 
             $o .= '<p class="quote_instructions">'
               .  $plugin_tx['quoteoftheday']['instruction_img'] . ",\n"
               .  $plugin_tx['quoteoftheday']['instruction_links'] . ",\n"
               .  '**<b>'.$plugin_tx['quoteoftheday']['instruction_bold'] . '</b>**' . ",\n"
               .  '//<i>'.$plugin_tx['quoteoftheday']['instruction_italic'] . '</i>//' . ",\n"
               .  $plugin_tx['quoteoftheday']['instruction_break'] . ",\n"
               .  $plugin_tx['quoteoftheday']['instruction_new_paragraph'] .  ",\n"

               .  '<span style="display:'.$moreInstr.'" id="instructions">'
               .  $plugin_tx['quoteoftheday']['instruction_paragraph_class_trigger'] . ",\n"
               .  $plugin_tx['quoteoftheday']['instruction_narrower'] . "\n"
               . '</span>'
               .  '<a href="javascript:void();" OnClick="
                  if(document.getElementById(\'instructions\').style.display == \'none\') {
                    document.getElementById(\'instructions\').style.display = \'inline\';
                    this.innerHTML = \'' . $plugin_tx['quoteoftheday']['instruction_less'] . '\';
                    document.cookie = \'quoteinstructions=inline; max-age=4000\';
                  } else {
                    document.getElementById(\'instructions\').style.display = \'none\';
                    this.innerHTML = \'' . $plugin_tx['quoteoftheday']['instruction_more'] . '\';
                    document.cookie = \'quoteinstructions=none; max-age=4000\';
                  }">'
               .  $moreInstrText . '</a>'
               .  '</p>' . "\n" ;

 


            // Choice of different quote files
            $o .= '<form method="POST" action="" name="filemanagement">'. "\n";
            $o .= tag('input type="hidden" value="plugin_main" name="admin"'). "\n";
            $handle=opendir($datapath);
            $quotefiles = array();
            if($handle) {
                while (false !== ($qfile = readdir($handle))) {
                	if($qfile != "." && $qfile != ".." && substr($qfile,0,9)!='selection') {
                		$quotefiles[] = $qfile;
                    }
                }
            }
            closedir($handle);
            natcasesort($quotefiles);
            $quotefiles_select = '';
            foreach($quotefiles as $value){
                $selected = '';
                if($fileselect == $value) {$selected = ' selected';}
                $quotefiles_select .= "\n<option value=$value$selected>$value</option>";
            }
            $o .= '<select name="newquotefile" OnChange="keepSrollPos();
                    if(this.options[this.selectedIndex].value == \'add\') {
                        document.getElementById(\'newfile\').style.display = \'inline\';
                        document.getElementById(\'delete\').style.display = \'none\';
                    } else if(this.options[this.selectedIndex].value == \'del\') {
                        document.getElementById(\'delete\').style.display = \'inline\';
                        document.getElementById(\'newfile\').style.display = \'none\';
                    } else {
                        this.form.submit();
                    } ; ">'

               .  "\n"
               . '<optgroup><option value="">'. "\n"
               . $plugin_tx['quoteoftheday']['file-manager_standard_quotefile']. "\n"
               . '</option>'. "\n"

               . $quotefiles_select

               . '</optgroup>'. "\n"
               . '<option value="add">'. "\n"
               . $plugin_tx['quoteoftheday']['file-manager_create_new_file']. "\n"
               . '</option>'. "\n"

               . '<option value="del">'. "\n"
               . '<b>'.$plugin_tx['quoteoftheday']['file-manager_delete_file']. "</b>\n"
               . '</option>'. "\n"

               .  '</select>' . "\n"

                //delete file, normally hidden
               .  '<span id="delete" style="display:none;"> &nbsp; '
               .  '<select name="deletefile">'
               .  "\n" . $quotefiles_select
               .  '</select>'
               .  tag('input type="submit" name="delete" style="background:#fbb;" value="'
               .  ucfirst($tx['action']['delete']).'" ')
               .  '</span>' . "\n"

                //create new file, normally hidden
               .  '<span id="newfile" style="display:none;">' . "\n"
               .  tag('input type="text" name="newname" placeholder="'
               .  $plugin_tx['quoteoftheday']['file-manager_enter_new_file_name'] .'"') . "\n"
               .  tag('input type="submit" value="'
               .  ucfirst($plugin_tx['quoteoftheday']['file-manager_create_new_file']).'" name="newfilename"')
               .  '</span>' . "\n";

            $o .= ' <b>'
               .  count($quotearray) . '</b> '
               .  $plugin_tx['quoteoftheday']['select_quotes'];

            $o .= '</form>' . "\n";


            $o .= '<form method="POST" action="">' . "\n";

            $cssfile = file_get_contents($pth['file']['plugin_stylesheet']);
            preg_match_all('/.quote_(?!author|smaller|bigger|imgleft|imgright|imgcenter|indent|center|right|narrow1|narrow2|narrow3)(\w*)/',$cssfile,$matches);
            $cssoptions = $matches[1];
            $cssoptions = array_filter(array_unique($cssoptions));
            $cssselect = '';
            $i = 0;
            if(!$quoteconfig['frame']) $quoteconfig['frame'] = "Standard";
            foreach($cssoptions as $value){
                $selected = '';
                if($quoteconfig['frame'] == $value) {$selected = ' selected'; $i++;}
                $cssselect .= "\n<option value=$value$selected>$value</option>";
            }
            $o .= $plugin_tx['quoteoftheday']['select_frame'];
            $o .= ': <select name="frame" OnChange="keepSrollPos();this.form.submit();">'.  "\n";
            if(!$i) $o .= '<option value=""> ? ? ? </option>'. "\n";
            $o .= $cssselect . '</select>' . "\n";

            // Display some information about the actual quote file
            $sequentialchecked = (isset($quoteconfig['selection']) && $quoteconfig['selection'] == 'sequential')
                ? 'checked'
                : '';
            $randomchecked = !$sequentialchecked ? 'checked' : '';
            $weeklychecked = (isset($quoteconfig['timing']) && $quoteconfig['timing'] == 'weekly')? 'checked' : '';
            $monthlychecked = (isset($quoteconfig['timing']) && $quoteconfig['timing'] == 'monthly')? 'checked' : '';
            $dailychecked = !$weeklychecked && !$monthlychecked ? 'checked' : '';
            if(!isset($quoteconfig['headline'])) $quoteconfig['headline'] = '';


            $o .= '<p><span style="white-space:nowrap;">'
               .  $plugin_tx['quoteoftheday']['select_quote_selection']
               .  ': ' . "\n"
               .  tag('input type="radio" name="selection" value="random" '.$randomchecked)
               .  $plugin_tx['quoteoftheday']['select_random'] . ' ' . "\n"
               .  tag('input type="radio" name="selection" value="sequential" '.$sequentialchecked)
               .  $plugin_tx['quoteoftheday']['select_sequential']
               .  '</span> ' . "\n"
               .  tag('br') . "\n"
               .  $plugin_tx['quoteoftheday']['select_timing']
               .  ': ' . "\n"
               .  tag('input type="radio" name="timing" value="daily" '.$dailychecked)
               .  $plugin_tx['quoteoftheday']['select_daily'] . ' ' . "\n"
               .  tag('input type="radio" name="timing" value="weekly" '.$weeklychecked)
               .  $plugin_tx['quoteoftheday']['select_weekly']  . ' ' . "\n"
               .  tag('input type="radio" name="timing" value="monthly" '.$monthlychecked)
               .  $plugin_tx['quoteoftheday']['select_monthly'] 
               .  "\n" . tag('br') . "\n"

               .  '<span style="display:table;width:100%;"><label style="display:table-cell;">'
               .  $plugin_tx['quoteoftheday']['select_headline']
               .  ':&nbsp; </label><span style="display:table-cell;width:100%;"> '
               .  tag('input type="text" class="quote_headline" value="'
               .  $quoteconfig['headline'] . '" name="headline"')
               .  '</span></span>'
               . '</p>' . "\n";


            // Editing commands for the quotes
            // Save
            $o .= tag('input type="hidden" value="savequote" name="action"')
               . "\n"
               .  '<button type="submit" onClick="keepSrollPos();"> &nbsp; '
               .  ucfirst($tx['action']['save']) . ' &nbsp; </button>';

            // filebrowser
            $o .= '<button type="button" onClick="filebrowser(\'image\');">'
               .  $plugin_tx['quoteoftheday']['insert_image'].'</button>';
            $o .= '<button type="button" onClick="filebrowser(\'media\');">'
               .  $plugin_tx['quoteoftheday']['insert_audio'].'</button>';

            // select all or single edit
            $o .=  $plugin_cf['quoteoftheday']['totalview']
                ? '<button name="totalview" value="0">'
                . $plugin_tx['quoteoftheday']['show_single_quotes'].'</button>'
                : '<button name="totalview" value="1">'
                . $plugin_tx['quoteoftheday']['show_all_quotes'].'</button>';

            // Preview all quotes
            $o .= '<button type="submit"  name="preview" value="1">'
               .  $plugin_tx['quoteoftheday']['start_preview']  . '</button>';


            $o .= '<div class="quote_admin">';

            // total quotes view
            if($plugin_cf['quoteoftheday']['totalview']) {
                $o .= '<textarea  class="quote_input" name="quotefile" id="quotefile">'.$quotefile.'</textarea>'
                   . "\n"
                   .  tag('input class="submit" type="submit" value=" &nbsp; '
                   .  ucfirst($tx['action']['save']).' &nbsp; " name="send"');

            } else {
            //single quotes view
                $position_select = '';
                foreach ($quotearray as $key=>$quote) {
                    $class = $key == $active? 'quote_active' : 'quoteinactive';
                    $o .= '<button type="button" class="'.$class.'" id="button'
                       .  $key . '" onClick="keyAction(\'' . $key . '\');">'.$key.'</button>';

                    $position_select .= "\n<option value=$key>$key</option>";
                }
                $o .= ' ' . tag('input type="image" src="'.$pth['folder']['plugins'].'quoteoftheday/css/add.gif'
                   .  '" name="addendquote['.$key.']" onClick="keepSrollPos();" value="add" title="'
                   .  $plugin_tx['quoteoftheday']['title_end_add'].'"');




                foreach ($quotearray as $key=>$quote) {
                    $class = $key == $active? 'quote_show' : 'quote_hide';
                    $o .= '<div class="'.$class.'" id="quote'.$key.'">';
                    $o .= '<div class="expandingArea">'
                       .  '<pre><span></span><br></pre>'
                       .  '<textarea name="quote['.$key.']" id="quotearea'.$key.'">'
                       .  trim($quote) . '</textarea></div>';

                    $o .=  '<div style="display:table;width:100%;"><span style="display:table-cell">';
                    $o .= tag('input type="image" src="'.$pth['folder']['plugins'].'quoteoftheday/css/add.gif'
                       .  '" onClick="keepSrollPos();" name="addquote['.$key.']" value="add" title="'
                       .  $plugin_tx['quoteoftheday']['title_add'].'"');
                    $o .= ' ';
                    $o .= tag('input type="image" src="'.$pth['folder']['plugins'].'quoteoftheday/css/delete.png'
                       .  '" name="delquote['.$key.']" onClick="keepSrollPos();" value="del" title="'
                       .  $plugin_tx['quoteoftheday']['title_delete'].'"');
                    $o .= '</span><span style="display:table-cell; text-align:right">'
                        . $plugin_tx['quoteoftheday']['start_regroup'] .' '
                        .  '<select name="move['.$key.']"
                           OnChange="keepSrollPos();this.form.submit();">'
                        .  "\n" . '<option value=""> </option>'
                        .  "\n" . $position_select
                        .  '</select>'
                        .  '</span></div>' . "\n";

                    $o .= quoteoftheday_StyleQuote(trim($quote),$quoteconfig['headline'],$quoteconfig['frame']);

                    $o .= '</div>';
                }
            }

            $o .= '</div></form>';
        }

    } else {
        // Rest of plugin menu
        $o .= plugin_admin_common($action, $admin, $plugin);
    }
}


?>

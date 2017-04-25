<?php
/**
 * Quoteoftheday
 * @version 1.1.1 (April 2017)
 * @by svasti (svasti@svasti.de)
 */

// if php 4 is used, this function has to be supplied
if (!function_exists('file_put_contents')) {
    function file_put_contents($filename, $data) {
        $f = @fopen($filename, 'w');
        if (!$f) {
            return false;
        } else {
        if (is_array($data)) {$data = implode('', $data);}
            $bytes = fwrite($f, $data);
            fclose($f);
            return $bytes;
        }
    }
}


function quoteoftheday_Data()
{
    global $pth, $plugin_cf;

	$datapath = $plugin_cf['quoteoftheday']['path_quotes'] == 'plugin'
    ? $pth['folder']['plugins'] . 'quoteoftheday/data/'
    : ($plugin_cf['quoteoftheday']['path_quotes'] == 'content'
    ? $pth['folder']['content'] .'quoteoftheday/'
    : $pth['folder']['userfiles'] .'plugins/quoteoftheday/');

    return $datapath;
}


function quoteoftheday($file='')
{
  global $plugin_cf,$plugin_tx,$pth, $c, $s, $sl;

    $datapath = quoteoftheday_Data();

    // select either from a specified quote file or from the standard quote file
    $file = $file 
        ? (strpos($file,'.txt')
        ? $file
        : $file.'.txt')
        : 'quote_'.$sl.'.txt';

    $selection = file_get_contents($datapath.'selection_'.$file);
    $lastupdate = substr($selection,0,3);
    $selection = substr($selection,3);

    $day   = str_pad(date("z",time()),3,0, STR_PAD_LEFT);
    $week  = date("W",time());
    $month = date("n",time());

    // check if a new quote has to be generated or not
    // if  month-nr, week-nr or day-nr are up-to-date,
    // then the quote of the day has aready been selected
    if($selection) {
        if($lastupdate > 500) {
            $changequote = ($lastupdate - 500) != $month ? true : false;
        } elseif($lastupdate > 400) {
            $changequote = ($lastupdate - 400) != $week ? true : false;
        } else {
            $changequote = $lastupdate != $day ? true : false;
        }
    } else $changequote = true;

    if ($changequote) {
        $quotes = file_get_contents($datapath. $file);
        $quotearray = explode('===',$quotes);
        $quotepreconfig = explode('|',$quotearray[0]);
        foreach ($quotepreconfig as $i) {
            list($key,$value) = explode('=',$i);
            $quoteconfig[$key] = trim($value);
        }

        if($quoteconfig['selection'] == 'sequential') {

            if(isset($quoteconfig['timing']) && $quoteconfig['timing'] == 'monthly') {
                $selectednr = $month % (count($quotearray)-1);
            } elseif(isset($quoteconfig['timing']) && $quoteconfig['timing'] == 'weekly') {
                $selectednr = $week % (count($quotearray)-1);
                $o .= '$selectednr '.$selectednr.' .= ($week '.$week.' % (count($quotearray) ['.count($quotearray).'] -1)+1);';
            } else $selectednr = ($day % (count($quotearray)-1)+1);

        } else $selectednr = rand(1,(count($quotearray)-1));


        $selectedquote = trim($quotearray[$selectednr]);

        $selection = quoteoftheday_StyleQuote($selectedquote,$quoteconfig['headline'], $quoteconfig['frame']);

        if(isset($quoteconfig['timing']) && $quoteconfig['timing'] == 'monthly') {
            $update = $month + 500;
        } elseif(isset($quoteconfig['timing']) && $quoteconfig['timing'] == 'weekly') {
            $update = $week + 400;
        } else $update = $day;

        file_put_contents($datapath.'selection_'.$file, $update . $selection);
    }

    return $selection;
}

function quoteoftheday_StyleQuote($quote,$headline = '',$frame='')
{
    global $pth,$plugin_cf;
    $quote = '<p>' . $quote . '</p>';
    $quote = preg_replace(
        array(
        '!\[\[((.*)\|(.*))\]\]!U',                      //links
        '!\*\*(.*)\*\*!U',                              //bold
        '!__(.*)__!U',                                  //underlined
        '#\/\/(?<!http:\/\/|https:\/\/)(.*)\/\/#U',     //italics

        '/^<p>>>>(.*)/s',
        '/^<p>>>(.*)/s',
        '/^<p>>(.*)/s',

        '/(?:(?:\r\n|\n)\s*)*^\-/m',                    //author
        '/(?:(?:\r\n|\n)\s*)*^\?/m',                    //smaller
        '/(?:(?:\r\n|\n)\s*)*^!/m',                     //bigger
        '/<p>\-/m',                                     //author at quote start
        '/<p>\?/m',                                     //smaller at quote start
        '/<p>\!/m',                                     //bigger at quote start

        '/(?:(?:\r\n|\n)){2,}/s',                       //double break -> new paragraph


        '!^\*\s{1}(.*)$!m',                             //unordered list item
        '!<p>\*\s{1}(.*)$!m',                           //unordered list item after paragraph
        '!^\#\s{1}(.*)$!m',                             //ordered list item
        '!<p>\#\s{1}(.*)$!m',                           //ordered list item after paragraph

        '!\/\s*$!m',                                //forced line break

        '!</li><\/ul><p>\s{2,5}([\p{L}|\p{N}].*)$!um',  //indention in list item 2nd line
        '!<p(.*)>\s{2,5}([\p{L}|\p{N}])!Uu',            //indention of new line new paragraph
        '!^\s{2,5}([\p{L}|\p{N}])!um',                  //indention of new line within paragraph
        '!<br>\s<br>!',                                 //no double breaks
        '!<\/p><\/li><\/([u|o])l><p>!',                 //cleanup end of lists

        '!<\/ul><p>\s<\/p><ul>!',                       //joining of unordered list items to single list
        '!<\/ol><p>\s<\/p><ol>!',                       //joining of ordered list item to single list

        '/^%%/m',                                       //right at beginning of line
        '/^%/m',                                        //centered at beginning of line
        '/<p(?:\sclass="(\w+)")?>%%/u',                 //right after another trigger
        '/<p(?:\sclass="(\w+)")?>%/u',                   //centered after another trigger

        ),
        array(
        '<a href="$2">$3</a>',                          //links
        '<b>$1</b>',                                    //bold
        '<u>$1</u>',                                    //underlined
        '<i>$1</i>',                                    //italics

        '<div class="quote_narrow3">'."\n".'<p>$1</div>',
        '<div class="quote_narrow2">'."\n".'<p>$1</div>',
        '<div class="quote_narrow1">'."\n".'<p>$1</div>',

        '</p>'."\n".'<p class="quote_author">',         //author
        '</p>'."\n".'<p class="quote_smaller">',        //smaller
        '</p>'."\n".'<p class="quote_bigger">',         //bigger
        '<p class="quote_author">',                     //author at quote start
        '<p class="quote_smaller">',                    //smaller at quote start
        '<p class="quote_bigger">',                     //bigger at quote start

        '</p>'."\n".'<p>',                              //double break -> new paragraph


        '</p><ul><li>$1</li></ul><p>',                  //unordered list item
        '<ul><li>$1</li></ul><p>',                      //unordered list item after paragraph
        '</p><ol><li>$1</li></ol><p>',                  //ordered list item
        '<ol><li>$1</li></ol><p>',                      //ordered list item after paragraph

        '<br>',                                         //forced line break

        '<br><span class="quote_indent">$1</li></ul><p>',//indention in list item 2nd line
        '<p$1><span class="quote_indent"></span>$2',    //indention of new line new paragraph
        '<br><span class="quote_indent"></span>$1',     //indention of new line within paragraph
        '<br>',                                         //no double breaks
        '</li></${1}l>',                                //cleanup end of lists

        '',                                             //joining of unordered list items to single list
        '',                                             //joining of ordered list item to single list

        '<p class="quote_right">',                      //right at beginning of line
        '<p class="quote_center">',                     //centered at beginning of line
        '<p class="$1 quote_right">',                   //right after another trigger
        '<p class="$1 quote_center">'                   //centered after another trigger
        ),$quote);


    //process image and sound files
    $quote = preg_replace_callback(
        '!\{\{(\s)?(\b.*\b)(\s)?(,\w{3})?(?:\|(.*))?\}\}!U',
        "quoteoftheday_ImgSound", $quote);

    $headline = $headline
              ? '<' . $plugin_cf['quoteoftheday']['headline_html'] . '>'
              . $headline
              . '</' . $plugin_cf['quoteoftheday']['headline_html'] . '>' . "\n"
              : '';
    $frame    = $frame
              ? $frame
              : 'Standard';
    return '<div class="quoteoftheday"><div class="quote_'.$frame.'">' . "\n". $headline . $quote  . '</div></div>' . "\n";
}

function quoteoftheday_ImgSound($m) {
    global $pth;
        $attrib = isset($m[5])? ' '. $m[5] : '';

    if(substr($m[2],-3)=='mp3' || substr($m[2],-3)=='ogg' || substr($m[2],-3)=='wav') {
        $secfile = isset($m[4])? ltrim($m[4],',') : '';
        $o = '<audio controls title="'.basename($m[2]).'"'.$attrib.'>'."\n"
           . '<source src="'.$pth['folder']['media'].$m[2].'" type="audio/'.substr($m[2],-3) .'">'."\n";
        $o .= $secfile
            ? '<source src="'.$pth['folder']['media'].substr($m[2],0,-3).$secfile.'" type="audio/'.$secfile .'">'."\n"
            : '';
        $o .= '</audio>';
        return $o;

    } else {
        $class='';
        if(isset($m[1]) && $m[1] && isset($m[3]) && $m[3]) $class = ' class="quote_imgcenter" ';
        elseif(isset($m[3]) && $m[3]) $class = ' class="quote_imgleft" ';
        elseif (isset($m[1]) && $m[1]) $class = ' class="quote_imgright" ';
        else $class = '';
        $alt = strpos($attrib,'alt') ==! false ? ' alt="'.substr(basename($m[2]),0,-4).'" ':'';
        return '<img src="'.$pth['folder']['images'] . $m[2].'"' . $class . $alt . $attrib . '>';
    }
}
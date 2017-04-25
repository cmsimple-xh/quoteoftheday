<?php

$editorbrowser = defined('CMSIMPLE_XH_VERSION')
    && version_compare(CMSIMPLE_XH_VERSION, "CMSimple_XH 1.7", ">=")
    ? $pth['folder']['base'].'?filebrowser=editorbrowser&editor'
    : $pth['folder']['plugins'].'filebrowser/editorbrowser.php?editor';


//write scroll position into a cookie before reload
$hjs .= '<script type="text/javascript">
function keepSrollPos() {
    document.cookie = "scrollpos=" + document.documentElement.scrollTop + "; max-age=2";
}</script>' . "\n";

$bjs .= '<script type="text/javascript">';

//read scroll position from a cookie after reload
If(isset($_COOKIE['scrollpos'])) {
    $bjs .= 'window.scrollTo(0,'.($_COOKIE['scrollpos']).');';
}

if(!$plugin_cf['quoteoftheday']['totalview']) {
//if single view is selected, alert that a quote must be selected first
    $bjs .= '
function filebrowser (type) {
    var elements = document.getElementsByClassName("quote_active");
    if(elements.length == 0) {
        alert("'.$plugin_tx['quoteoftheday']['warning_noselection'].'");
    }
    else {
        window.open("'.$editorbrowser
        . '=quoteoftheday&prefix='.$pth['folder']['base'].'&base=./&type=" + type, "",
        "toolbar=no,location=no,status=no,menubar=no," +
        "scrollbars=yes,resizable=yes,width=640,height=480");
    }
}';} else {
    $bjs .= '
function filebrowser (type) {
    window.open("'.$editorbrowser
    . '=quoteoftheday&prefix='.$pth['folder']['base'].'&base=./&type=" + type, "",
    "toolbar=no,location=no,status=no,menubar=no," +
    "scrollbars=yes,resizable=yes,width=640,height=480");
}';
}

// button and div management for selection of active quote
$bjs .= '
function keyAction(key) {
    var elements = document.getElementsByClassName("quote_active");
    for (var i = 0; i < elements.length; i++) {
        elements[i].className = "quoteinactive";
    }
    var elements = document.getElementsByClassName("quote_show");
    for (var i = 0; i < elements.length; i++) {
        elements[i].className = "quote_hide";
    }

    document.getElementById("button" + key).className = "quote_active";
    document.getElementById("quote" + key).className = "quote_show";
    document.cookie = "activequote =" + key;
}

function regroup(key) {
    document.cookie = "activequote =" + key;
    
}

function insertURI(url) {';

if($plugin_cf['quoteoftheday']['totalview']) {
//if  total view is selected, the textarea of the quotes file is fixed
    $bjs .= '
    var txtarea = document.getElementById("quotefile");';

} else {
//if single view is selected, the textarea changes depending on which
//quote is selected. Quotes are imbedded in a div, the active div has
//the class quote_show. From the ID of this div the ID of the textarea
//is constructed
    $bjs .= '
    var elements = document.getElementsByClassName("quote_show");
    var nr = elements[0].id;
    nr = nr.replace("quote","quotearea");
    var txtarea = document.getElementById(nr);';
}

$bjs .= '
    url = url.replace("'.$pth['folder']['images'].'","");
    url = url.replace("'.$pth['folder']['media'].'","");
    url = "{{" + url + "}}";

//Code from Stackoverflow.com
    var scrollPos = txtarea.scrollTop;
    var strPos = 0;
    var br = ((txtarea.selectionStart || txtarea.selectionStart == "0") ?
        "ff" : (document.selection ? "ie" : false ) );
    if (br == "ie") {
        txtarea.focus();
        var range = document.selection.createRange();
        range.moveStart ("character", -txtarea.value.length);
        strPos = range.url.length;
    }
    else if (br == "ff") strPos = txtarea.selectionStart;

    var front = (txtarea.value).substring(0,strPos);
    var back = (txtarea.value).substring(strPos,txtarea.value.length);
    txtarea.value=front+url+back;
    strPos = strPos + url.length;
    if (br == "ie") {
        txtarea.focus();
        var range = document.selection.createRange();
        range.moveStart ("character", -txtarea.value.length);
        range.moveStart ("character", strPos);
        range.moveEnd ("character", 0);
        range.select();
    }
    else if (br == "ff") {
        txtarea.selectionStart = strPos;
        txtarea.selectionEnd = strPos;
        txtarea.focus();
    }
    txtarea.scrollTop = scrollPos;
}

// the following code is adapted from Opera´s Neil Jenkins, see
// http://www.alistapart.com/articles/expanding-text-areas-made-elegant/
function makeExpandingArea(container) {
    var area = container.querySelector("textarea");
    var span = container.querySelector("span");
    if (area.addEventListener) {
        area.addEventListener("input", function() {
            span.textContent = area.value;
        }, false);
        span.textContent = area.value;
    } else if (area.attachEvent) {
        // IE8 compatibility
        area.attachEvent("onpropertychange", function() {
            span.innerText = area.value;
        });
        span.innerText = area.value;
    }
    // Enable extra CSS
    container.className += " active";
}

var areas = document.querySelectorAll(".expandingArea");
var l = areas.length;

while (l--) {
    makeExpandingArea(areas[l]);
}
// end of code for autogrowing textareas';

$bjs .= '</script>' . "\n";
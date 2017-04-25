<?php

/**
 * Editorhook for internal filebrowser -> Quoteoftheday_XH
 *
 */

$script = <<<EOS
<script type="text/javascript">
/* <![CDATA[ */
function setLink(url) {
    window.opener.insertURI(url);
    window.close();
}
/* ]]> */
</script>
EOS;

?>

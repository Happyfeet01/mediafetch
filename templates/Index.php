<?php
extract($_);
?>
<div id="app-ncdownloader-wrapper">
    <button id="mobile-nav-toggle" class="icon-menu" aria-label="Toggle navigation"></button>
    <?php print_unescaped($this->inc('Navigation'));?>
    <?php print_unescaped($this->inc('Content'));?>
    <div id="app-settings-data" data-search-sites=<?php print $search_sites;?> data-settings='<?php print($settings);?>' ></div>
</div>

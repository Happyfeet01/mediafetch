<?php
extract($_);
\OCP\Util::addHeader('meta', [
    'name'    => 'viewport',
    'content' => 'width=device-width, initial-scale=1'
]);
?>
<div id="app-ncdownloader-wrapper">
    <button id="app-navigation-toggle" class="icon-menu" aria-controls="app-navigation"></button>
    <?php print_unescaped($this->inc('Navigation'));?>
    <?php print_unescaped($this->inc('Content'));?>
    <div id="app-settings-data"
     data-search-sites="<?php echo htmlspecialchars((string) $search_sites, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"
     data-settings="<?php echo htmlspecialchars((string) $settings, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"></div>
</div>

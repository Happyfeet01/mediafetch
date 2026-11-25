<?php
extract($_);
\OCP\Util::addHeader('meta', [
    'name'    => 'viewport',
    'content' => 'width=device-width, initial-scale=1'
]);
\OCP\Util::addScript('mediafetch', 'support-notice');
\OCP\Util::addStyle('mediafetch', 'support-notice');
$de = str_starts_with($l->getLanguageCode(), 'de');
?>
<div id="app-ncdownloader-wrapper">
    <button id="app-navigation-toggle" class="icon-menu" aria-controls="app-navigation"></button>
    <?php print_unescaped($this->inc('Navigation'));?>
    <?php print_unescaped($this->inc('Content'));?>
    <div id="app-settings-data"
     data-search-sites="<?php echo htmlspecialchars((string) $search_sites, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"
     data-settings="<?php echo htmlspecialchars((string) $settings, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"></div>
</div>

<aside id="mediafetch-support-notice" class="developer-support-notice" hidden aria-labelledby="mediafetch-support-title">
    <strong id="mediafetch-support-title"><?php p($de ? 'Unterstützung des Entwicklers' : 'Support the developer'); ?></strong>
    <p><?php p($de ? 'Dir gefällt die App? Ich freue mich über einen Kaffee.' : 'Enjoying the app? You can buy me a coffee.'); ?></p>
    <p class="support-links">
        <a href="https://buymeacoffee.com/sly5ir9ov" target="_blank" rel="noopener noreferrer">Buy Me a Coffee</a>
        <a href="https://paypal.me/LarsM1980" target="_blank" rel="noopener noreferrer">PayPal</a>
        <a href="https://ko-fi.com/dermueller" target="_blank" rel="noopener noreferrer">Ko-fi</a>
    </p>
    <button type="button" data-duration="session"><?php p($de ? 'Schließen' : 'Close'); ?></button>
    <button type="button" data-duration="permanent"><?php p($de ? 'Nicht wieder anzeigen' : 'Don’t show again'); ?></button>
    <p role="status" hidden><?php p($de ? 'Speichern fehlgeschlagen. Bitte erneut versuchen.' : 'Could not save. Please try again.'); ?></p>
</aside>

<?php
script('mediafetch', 'appSettings');
style('mediafetch', 'appSettings');
extract($_);
?>
<div id="ncdownloader-admin-settings" class="ncdownloader-admin-settings" data-settings='<?php print json_encode($settings); ?>' data-options='<?php print json_encode($options); ?>'>
</div>

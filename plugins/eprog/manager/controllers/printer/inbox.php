<?php

use Eprog\Manager\Models\Scheduler;
use Eprog\Manager\Classes\Util;
use Eprog\Manager\Models\SettingConfig as Settings;
use Backend\Facades\BackendAuth;


?>

<div  class="header"></div>
<div class="printer">

<div><div><?= e(trans('eprog.manager::lang.date'))?>: <?= $date ?></div></div>
<div><div><?= e(trans('eprog.manager::lang.from'))?>: <?= $from ?></div></div>
<div><div><?= e(trans('eprog.manager::lang.to'))?>: <?= $to ?></div></div>
<?php if(strlen($cc) > 0): ?>
<div><div>CC: <?= $cc ?></div></div>
<?php endif ?>
<div><div><?= e(trans('eprog.manager::lang.title'))?>: <?= $title ?></div></div>
</div>
<div>
<br>
<div class="nb">

<?= $body ?>
</div>
</div>




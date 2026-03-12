<?php

use Eprog\Manager\Models\Scheduler;
use Eprog\Manager\Classes\Util;
use Eprog\Manager\Models\SettingConfig as Settings;
use Backend\Facades\BackendAuth;

	$scheduler = Scheduler::find($id);

?>
<title data-title-template="%s | Emode"><?= e(trans('eprog.manager::lang.print'))?></title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<div  class="header"><div><?= Settings::get('city'); ?> <?= date('Y-m-d H:i', time()); ?></div></div>
<br>
<div class="title"><?= e(trans('eprog.manager::lang.scheduler'))?> - <?= $scheduler->name ?></div>

<div class="printer">

<div><div  class="head">ID</div><div><?= $scheduler->id ?></div></div>


<div><div  class="head"><?= e(trans('eprog.manager::lang.start')) ?></div>
<div>
<?= Util::dateLocale($scheduler->start, 'Y-m-d H:i'); ?>
</div>
</div>

<div><div  class="head"><?= e(trans('eprog.manager::lang.stop')) ?></div>
<div>
<?= Util::dateLocale($scheduler->stop, 'Y-m-d H:i'); ?>
</div>
</div>

<?php if($scheduler->type) : ?>
<div><div  class="head"><?= e(trans('eprog.manager::lang.category')) ?></div>
<div>
<?= $scheduler->type->name ?>
</div>
</div>
<?php endif ?>

<?php if($scheduler->admin) : ?>
<div><div  class="head"><?= e(trans('eprog.manager::lang.staff')) ?></div>
<div>
<?= $scheduler->admin->first_name ?> <?= $scheduler->admin->last_name ?>
</div>
</div>
<?php endif ?>


<?php if($scheduler->user) : ?>
<div><div  class="head"><?= e(trans('rainlab.user::lang.user.label')) ?></div>

<div>
<?= $scheduler->user->name ?> <?= $scheduler->user->surname ?> - <?= $scheduler->user->email ?> 
<?php if($scheduler->user->phone != null) : ?>
<br><?= strtolower(e(trans('rainlab.user::lang.user.phone'))) ?> <?= $scheduler->user->phone ?>
<?php endif; ?>

<br><?= $scheduler->user->street ?> <?= $scheduler->user->number ?>
<br><?= $scheduler->user->code ?> <?= $scheduler->user->city ?>
</div>
</div>
<?php endif ?>



<div><div  class="head nb"><?= e(trans('eprog.manager::lang.desc')) ?></div>
<div class="nb">
<?= $scheduler->desc ?>
</div>
</div>

</div>

<div class="footer">

<div><?= BackendAuth::getUser()->first_name ?> <?= BackendAuth::getUser()->last_name ?> <?= Settings::get('firm_name'); ?></div>
</div>
<div style="float: right;position: fixed;bottom: 0;font-size:14px;font-family:Tahoma;color:#333">http://emode.pl<div>


<style>

  div{font-family: DejaVu Sans !important}
 .printer { border:1px solid #777; display:table; width:100%}
 .printer > div { display:table-row;font-family:Arial;font-size:14px;}
 .printer > div > div{display:table-cell; padding:7px;line-height:19px;border-bottom:1px solid #777}
 .title { width:100%;text-align:center;font-family:Arial;font-size:18px;padding-bottom:20px;padding-top:20px}
 .printer .head {width: 150px; border-right:1px solid #777}
 .printer .nb {border-bottom:0px;;line-height:6px;padding-top:13px;padding-bottom:13px}
 .header { float:right;font-family:Arial;font-size:14px;line-height:19px}
 .footer { float:right;margin-top:30px;font-family:Arial;font-size:14px;line-height:19px}


</style>



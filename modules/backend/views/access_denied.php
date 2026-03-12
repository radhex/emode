<!DOCTYPE html>
<html lang="<?= App::getLocale() ?>">
    <head>
        <meta charset="utf-8">
        <title><?= Lang::get('backend::lang.page.access_denied.label') ?></title>
        <link href="<?= Url::asset('/modules/system/assets/css/styles.css') ?>" rel="stylesheet">
        <link href="<?= Url::asset('/modules/system/assets/ui/storm.css') ?>" rel="stylesheet">
        <link href="<?= Url::asset('/modules/backend/assets/css/winter.css') ?>" rel="stylesheet">
        <link href="<?= Url::asset('/modules/system/assets/ui/icons.css') ?>" rel="stylesheet">
    </head>
    <body><center>
        <div>
            <h3><i class="icon-lock" style="color:var(--mcolor)"></i> <?= Lang::get('backend::lang.page.access_denied.label') ?></h3>
            <p class="lead"><?= Lang::get('backend::lang.page.access_denied.help') ?></p>
            <a class="btn btn-primary oc-icon-arrow-left" href="javascript:;" onclick="history.go(-1); return false;"><?= Lang::get('backend::lang.page.404.back_link') ?></a>
        </div>
    </body>
</html>

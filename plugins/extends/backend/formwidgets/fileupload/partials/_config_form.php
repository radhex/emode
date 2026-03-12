<div class="fileupload-config-form">
    <?= Form::open(['data-request-parent' => "#{$parentElementId}"]) ?>
        <input type="hidden" name="file_id" value="<?= $file->id ?>" />

        <?php if (starts_with($displayMode, 'image')): ?>
            <div class="file-upload-modal-image-header">
                <button type="button" class="close" data-dismiss="popup">&times;</button>
                <img
                    src="<?= $file->thumbUrl ?>"
                    class="img-responsive center-block"
                    alt=""
                    title="<?= e(trans('eprog.manager::lang.file')) ?>: <?= e($file->file_name) ?>"
                    style="<?= $cssDimensions ?>" />
            </div>
        <?php else: ?>
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="popup">&times;</button>
                <h4 class="modal-title"><?= $file->file_name ?></h4>
            </div>
        <?php endif ?>
        <div class="modal-body">
            <p><?= e(trans('eprog.manager::lang.file_describe')) ?></p>

            <?= $this->getConfigFormWidget()->render(); ?>
        </div>
        <div class="modal-footer">
          

            <?php if(!preg_match("/excel|\.sheet|\.spreadsheet|\.document|zip|presentation|drawing/",$file->content_type)) : ?>
                <a href="javscript:" onclick="window.open('<?= $file->pathUrl ?>','', 'left=100,top=100,width=1200,height=700')" class="btn btn-primary oc-icon-eye"  style="float:left">
                    <?= e(trans('eprog.manager::lang.preview')) ?>
                </a>
            <?php endif ?>
            <a
                href="/<?= config('cms.backendUri') ?>/eprog/manager/file/download?file_id=<?= $file->id ?>"
                class="btn btn-primary oc-icon-download"
                style="float:left">
                <?= e(trans('eprog.manager::lang.download')) ?>
            </a>
            <button
                type="submit"
                class="btn btn-primary oc-icon-google"
                data-request="onGoogle"
                data-request-confirm="<?= e(trans('eprog.manager::lang.drive_sent_confirm')) ?>"
                data-popup-load-indicator style="float:left">
                <?= e(trans('eprog.manager::lang.send')) ?>
            </button>

            <button
                type="submit"
                class="btn btn-primary oc-icon-floppy-o"
                data-request="<?= $this->getEventHandler('onSaveAttachmentConfig') ?>"
                data-popup-load-indicator>
                <?= e(trans('backend::lang.form.save')) ?>
            </button>
            <button
                type="button"
                class="btn btn-default oc-icon-close"
                data-dismiss="popup">
                <?= e(trans('backend::lang.form.cancel')) ?>
            </button>
        </div>
    <?= Form::close() ?>
</div>

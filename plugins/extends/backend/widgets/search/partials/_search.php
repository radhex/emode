<div class="loading-indicator-container size-input-text">
    <input
        placeholder="<?= $placeholder ?>"
        type="text"
        id="listToolbarSearch1"
        name="<?= $this->getName() ?>"
        value="<?= e($value) ?>"
        data-request-data="type:1"
        data-request="<?= $this->getEventHandler('onSubmit') ?>"
        data-request-complete="if($(this).val().length) {$(this).next().show()} else {$(this).next().hide()};completeUrl(this);"
        data-request-success="searchUrl(this);"
        <?= !$searchOnEnter ? 'data-track-input' : '' ?>
        data-load-indicator
        data-load-indicator-opaque
        class="form-control <?= $cssClasses ?>"
        autocomplete="off" 
        />

    <button
        class="clear-input-text"
        type="button"
        value=""
        style="<?= empty($value) ? 'display: none;' : ''; ?>"
        onclick="clearUrl();homeUrl()"
    >
        <i class="icon-times"></i>
    </button>
</div>

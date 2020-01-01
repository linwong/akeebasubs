<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Akeeba\Subscriptions\Admin\Model\Levels;
use FOF30\Utils\FEFHelper\Html as FEFHtml;
use FOF30\Utils\FEFHelper\BrowseView;use FOF30\Utils\SelectOptions;

defined('_JEXEC') or die();

/** @var  FOF30\View\DataView\Html  $this */

$js = <<< JS
window.addEventListener('DOMContentLoaded', function(event) {
	akeeba.fef.tabs();
});

JS;

$this->addJavascriptInline($js);

?>
@extends('admin:com_akeebasubs/Common/edit')

@section('edit-form-body')
    <div class="akeeba-tabs" id="akeebasubs-level-tabs-outer">

        <label for="akeebasubs-level-details" class="active">
            @lang('COM_AKEEBASUBS_LEVEL_TAB_DETAILS')
        </label>
        <section id="akeebasubs-level-details">
            @include('admin:com_akeebasubs/Levels/form.details', ['item' => $this->item])
        </section>


        <label for="akeebasubs-level-priceduration">
            @lang('COM_AKEEBASUBS_LEVEL_TAB_PRICEDURATION')
        </label>
        <section id="akeebasubs-level-priceduration">
            @include('admin:com_akeebasubs/Levels/form.priceduration', ['item' => $this->item])
        </section>


        <label for="akeebasubs-level-actions">
            @lang('COM_AKEEBASUBS_LEVEL_TAB_ACTIONS')
        </label>
        <section id="akeebasubs-level-actions">
            @include('admin:com_akeebasubs/Levels/form.actions', ['item' => $this->item])
        </section>


        <label for="akeebasubs-level-message">
            @lang('COM_AKEEBASUBS_LEVEL_TAB_MESSAGE')
        </label>
        <section id="akeebasubs-level-message">
            @include('admin:com_akeebasubs/Levels/form.messages', ['item' => $this->item])
        </section>


        <label for="akeebasubs-level-renewalsnotifications">
            @lang('COM_AKEEBASUBS_LEVEL_TAB_RENEWALSNOTIFICATIONS')
        </label>
        <section id="akeebasubs-level-renewalsnotifications">
            @include('admin:com_akeebasubs/Levels/form.renewalsnotifications', ['item' => $this->item])
        </section>

    </div>
@stop



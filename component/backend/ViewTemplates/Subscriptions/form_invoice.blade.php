<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Akeeba\Subscriptions\Admin\Model\Invoices;

defined('_JEXEC') or die();

/** @var  \Akeeba\Subscriptions\Admin\Model\Subscriptions $model */

$invoice = $model->invoice;
$returnURL = 'index.php?option=com_akeebasubs&view=Subscriptions&task=edit&id=' . $model->akeebasubs_subscription_id;

?>

@if(!empty($invoice))

    {{-- Legacy invoicing --}}
    <h5>
        <a href="@route('index.php?option=com_akeebasubs&view=Invoices&akeebasubs_subscription_id=' . (int) $model->akeebasubs_subscription_id)"
           target="_blank">
            #{{{ $invoice->display_number }}}
        </a>
    </h5>

    <a class="akeeba-btn--grey"
       href="@route('index.php?option=com_akeebasubs&view=Invoices&task=read&id=' . (int) $model->akeebasubs_subscription_id . '&tmpl=component')"
        target="_blank">
        <span class="akion-document-text"></span>
        @lang('COM_AKEEBASUBS_INVOICES_ACTION_PREVIEW')
    </a>

@else
    @unless(empty($model->receipt_url))
    {{-- Paddle receipt --}}
    <a class="akeeba-btn--grey modal"
       href="{{ $model->receipt_url }}"
       target="_blank">
        <span class="akion-document-text"></span>
        @lang('COM_AKEEBASUBS_INVOICES_ACTION_PREVIEW')
    </a>
    @else
    {{-- No invoice available --}}
    <div class="akeeba-block--warning">
        @lang('COM_AKEEBASUBS_SUBSCRIPTION_NO_RECEIPT')
    </div>

    @endif

    {{-- Allow editing of the receipt URL --}}
    <div class="akeeba-form-group">
        <label for="receipt_url">
            @fieldtitle('receipt_url')
        </label>
        <input type="text" name="receipt_url" id="receipt_url" value="{{{ $model->receipt_url }}}" />
    </div>

    <h3>@lang('COM_AKEEBASUBS_SUBSCRIPTION_HEAD_RECURRING')</h3>

    {{-- Allow editing of the Update / Billing Info URL --}}
    <div class="akeeba-form-group">
        <label for="update_url">
            @fieldtitle('update_url')
        </label>
        <input type="text" name="update_url" id="update_url" value="{{{ $model->update_url }}}" />
    </div>
    {{-- Allow editing of the Cancelation URL --}}
    <div class="akeeba-form-group">
        <label for="cancel_url">
            @fieldtitle('cancel_url')
        </label>
        <input type="text" name="cancel_url" id="cancel_url" value="{{{ $model->cancel_url }}}" />
    </div>
@endif
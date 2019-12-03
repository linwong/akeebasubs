<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 *
 * @var  FOF30\View\DataView\Html $this
 * @var  CreditNotes              $row
 * @var  CreditNotes              $model
 */

defined('_JEXEC') or die();

use Akeeba\Subscriptions\Admin\Model\CreditNotes;

$model = $this->getModel();
$nullDate = $this->container->db->getNullDate();
/** @var \Akeeba\Subscriptions\Admin\Model\Invoices $invoiceModel */
$invoiceModel = $this->getModel()->getContainer()->factory->model('Invoices')->tmpInstance();
?>

@extends('admin:com_akeebasubs/Common/browse')

@section('browse-filters')
    <div class="akeeba-filter-element akeeba-form-group">
        @searchfilter('akeebasubs_invoice_id', 'akeebasubs_invoice_id', 'COM_AKEEBASUBS_SUBSCRIPTION_INVOICE_ID')
    </div>

    <div class="akeeba-filter-element akeeba-form-group">
        @searchfilter('user', 'user', 'COM_AKEEBASUBS_SUBSCRIPTIONS_USER')
    </div>

    <div class="akeeba-filter-element akeeba-form-group">
        @searchfilter('creditnote_no')
    </div>

    <div class="akeeba-filter-element akeeba-form-group">
        @jhtml('calendar', $model->creditnote_date, 'invoice_date', 'invoice_date')
    </div>
@stop

@section('browse-table-header')
    <tr>
        <th width="100">
            @sortgrid('akeebasubs_invoice_id', 'COM_AKEEBASUBS_SUBSCRIPTION_INVOICE_ID')
        </th>
        <th width="20">
            @jhtml('FEFHelper.browse.checkall')
        </th>
        <th width="15%">
            @sortgrid('user_id', 'COM_AKEEBASUBS_SUBSCRIPTIONS_USER')
        </th>
        <th width="10%">
            @sortgrid('creditnote_no')
        </th>
        <th width="10%">
            @sortgrid('creditnote_date')
        </th>
        <th>
            @lang('COM_AKEEBASUBS_INVOICES_LBL_ACTIONS')
        </th>
    </tr>
@stop

@section('browse-table-body-withrecords')
    {{-- Table body shown when records are present. --}}
	<?php $i = 0; ?>
    @foreach($this->items as $row)
        <tr>
            <td>
                {{{ sprintf('%05d', $row->akeebasubs_invoice_id) }}}
            </td>
            <td>
                @jhtml('FEFHelper.browse.id', ++$i, $row->getId())
            </td>
            <td>
                @unless(is_null($row->invoice) || is_null($row->invoice->subscription))
                    @include('admin:com_akeebasubs/Common/ShowUser', ['item' => $row->invoice->subscription, 'field' => 'user_id', 'link_url' => 'index.php?option=com_users&task=user.edit&id=' . (int) $row->invoice->subscription->user_id])
                @endunless
            </td>
            <td>
                <span class="akeeba-label">

                @unless(empty($row->display_number))
                    {{{ $row->display_number }}}
                @else
                    {{{ $row->creditnote_no }}}
                @endunless

                </span>
            </td>
            <td>
                {{ Akeeba\Subscriptions\Admin\Helper\Format::date($row->creditnote_date) }}
            </td>
            <td>
                <a href="@route('index.php?option=com_akeebasubs&view=CreditNotes&task=read&tmpl=component&id=' . $row->akeebasubs_invoice_id)"
                   class="akeeba-btn--teal--small" target="_blank"
                   title="@lang('COM_AKEEBASUBS_INVOICES_ACTION_PREVIEW')">
                    <span class="akion-document-text"></span>
                </a>
                @if (empty($row->sent_on) || ($row->sent_on == $nullDate))
                    <span class="akeeba-label--warning">
                            @lang('COM_AKEEBASUBS_INVOICES_LBL_NOTSENT')
                        </span>
                @else
                    <span class="akeeba-label--success">
                            @lang('COM_AKEEBASUBS_INVOICES_LBL_SENT')
                        </span>
                @endif
            </td>
        </tr>
    @endforeach
@stop

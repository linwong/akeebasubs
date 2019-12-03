<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 *
 * @var  FOF30\View\DataView\Html $this
 * @var  Invoices                 $row
 * @var  Invoices                 $model
 */

defined('_JEXEC') or die();

use Akeeba\Subscriptions\Admin\Model\Invoices;
use Akeeba\Subscriptions\Admin\Model\Subscriptions;

$model = $this->getModel();
$nullDate = $this->container->db->getNullDate();
$extensions = $model->getExtensions();
$returnUrl = base64_encode('index.php?option=com_akeebasubs&view=Invoices');
?>

@extends('admin:com_akeebasubs/Common/browse')

@section('browse-page-top')
    @if(empty($extensions))
        <div class="akeeba-block--failure">
            <p>
                @lang('COM_AKEEBASUBS_INVOICES_MSG_EXTENSIONS_NONE')
            </p>
        </div>
    @else
        <div class="akeeba-block--info">
            <p>
                @lang('COM_AKEEBASUBS_INVOICES_MSG_EXTENSIONS_SOME')
            </p>

            <ul>
                @foreach ($extensions as $key => $extension)
                    <li>{{{ $extension['title'] }}}</li>
                @endforeach
            </ul>

            @if(count($extensions) > 1)
                <p>
                    <strong>
                        @lang('COM_AKEEBASUBS_INVOICES_MSG_EXTENSIONS_MULTIPLE')
                    </strong>
                </p>
            @endif
        </div>
    @endif
@stop

@section('browse-filters')
    <div class="akeeba-filter-element akeeba-form-group">
        @searchfilter('akeebasubs_subscription_id')
    </div>

    <div class="akeeba-filter-element akeeba-form-group">
        @searchfilter('user', 'user', 'COM_AKEEBASUBS_SUBSCRIPTIONS_USER')
    </div>

    <div class="akeeba-filter-element akeeba-form-group">
        @selectfilter('extension', Akeeba\Subscriptions\Admin\Helper\Select::getInvoiceExtensions())
    </div>

    <div class="akeeba-filter-element akeeba-form-group">
        @searchfilter('invoice_no')
    </div>

    <div class="akeeba-filter-element akeeba-form-group">
        @jhtml('calendar', $model->invoice_date, 'invoice_date', 'invoice_date')
    </div>
@stop

@section('browse-table-header')
    <tr>
        <th width="100">
            @sortgrid('akeebasubs_subscription_id')
        </th>
        <th width="20">
            @jhtml('FEFHelper.browse.checkall')
        </th>
        <th width="15%">
            @sortgrid('user_id', 'COM_AKEEBASUBS_SUBSCRIPTIONS_USER')
        </th>
        <th width="10%">
            @sortgrid('extension')
        </th>
        <th width="10%">
            @sortgrid('invoice_no')
        </th>
        <th width="10%">
            @sortgrid('invoice_date')
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
                {{{ sprintf('%05d', $row->akeebasubs_subscription_id) }}}
            </td>
            <td>
                @jhtml('FEFHelper.browse.id', ++$i, $row->getId())
            </td>
            <td>
                @unless(is_null($row->subscription))
                    @include('admin:com_akeebasubs/Common/ShowUser', ['item' => $row->subscription, 'field' => 'user_id', 'link_url' => 'index.php?option=com_users&task=user.edit&id=' . (int) $row->subscription->user_id])
                @endunless
            </td>
            <td>
                {{{ \FOF30\Utils\FEFHelper\BrowseView::getOptionName($row->extension, Akeeba\Subscriptions\Admin\Helper\Select::getInvoiceExtensions()) }}}
            </td>
            <td>
                <span class="akeeba-label">

                @unless(empty($row->display_number))
                    {{{ $row->display_number }}}
                @else
                    {{{ $row->invoice_no }}}
                @endunless

                </span>
            </td>
            <td>
                {{ Akeeba\Subscriptions\Admin\Helper\Format::date($row->invoice_date) }}
            </td>
            <td>
                @if ($row->extension == 'akeebasubs')
                    <a href="@route('index.php?option=com_akeebasubs&view=Invoices&task=read&tmpl=component&id=' . $row->akeebasubs_subscription_id)"
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
                @elseif(array_key_exists($row->extension, $extensions))
                    <a class="akeeba-btn--ghost" href="{{{ sprintf($extensions[$row->extension]['backendurl'], $row->invoice_no) }}}">
                        <span class="akion-android-share-alt"></span>
                        @lang('COM_AKEEBASUBS_INVOICES_LBL_OPENEXTERNAL')
                    </a>
                @else
                    <span class="akeeba-label--red--small">
                        @lang('COM_AKEEBASUBS_INVOICES_LBL_NOACTIONS')
                    </span>
                @endif
            </td>
        </tr>
    @endforeach
@stop

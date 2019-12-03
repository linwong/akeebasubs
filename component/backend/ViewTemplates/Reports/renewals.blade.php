<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Akeeba\Subscriptions\Admin\Model\RenewalsForReports;
use FOF30\Utils\FEFHelper\BrowseView;

defined('_JEXEC') or die;

/**
 * @var \FOF30\View\DataView\Form $this
 * @var  RenewalsForReports       $row
 * @var  RenewalsForReports       $model
 */

$model = $this->getModel();

$renewalsOptions = [
	'1' => JText::_('COM_AKEEBASUBS_RENEWALS_USERSWITHRENEWALS'),
	'-1' => JText::_('COM_AKEEBASUBS_RENEWALS_USERSWITHOUTRENEWALS'),
];
?>
@extends('admin:com_akeebasubs/Common/browse')

@section('browse-filters')
    <div class="akeeba-filter-element akeeba-form-group">
        @searchfilter('username', 'username', 'COM_AKEEBASUBS_JUSER_USERNAME')
    </div>
    <div class="akeeba-filter-element akeeba-form-group">
        @searchfilter('name', 'name', 'COM_AKEEBASUBS_JUSER_NAME')
    </div>
    <div class="akeeba-filter-element akeeba-form-group">
        @searchfilter('email', 'email', 'COM_AKEEBASUBS_JUSER_EMAIL')
    </div>
    <div class="akeeba-filter-element akeeba-form-group">
        {{ BrowseView::modelFilter('levelid', 'title', 'Levels', 'COM_AKEEBASUBS_TITLE_LEVELS')  }}
    </div>
    <div class="akeeba-filter-element akeeba-form-group">
        @selectfilter('getRenewals', $renewalsOptions , '')
    </div>
@stop

@section('browse-table-header')
    {{-- ### HEADER ROW ### --}}
    <tr>
        <th width="20px">
            @jhtml('FEFHelper.browse.checkall')
        </th>
        <th>
            @sortgrid('user_id', 'COM_AKEEBASUBS_SUBSCRIPTIONS_USER')
        </th>
        <th>
            @lang('COM_AKEEBASUBS_TITLE_LEVELS')
        </th>
        <th>
            @lang('COM_AKEEBASUBS_REPORTS_USER_RENEWAL')
        </th>
    </tr>
@stop

@section('browse-table-body-withrecords')
    {{-- Table body shown when records are present. --}}
	<?php $i = 0; ?>
    @foreach($this->items as $row)
        <tr>
            <td>
                @jhtml('FEFHelper.browse.id', ++$i, $row->getId())
            </td>
            <td>
                @include('admin:com_akeebasubs/Common/ShowUser', ['item' => $row, 'field' => 'user_id', 'linkURL' => 'index.php?option=com_users&task=user.edit&id=[ITEM:USER_ID]'])
            </td>
            <td>
                @foreach(explode(',', $row->raw_subs) as $subID)
                    <span class="akeeba-label">
                        {{{  \FOF30\Utils\FEFHelper\BrowseView::modelOptionName($subID, 'Levels', ['none' => '&nbsp;&nbsp;&nbsp;']) }}}
                    </span>
                @endforeach
            </td>
            <td>
                {{ $row->count_renewals or '&nbsp;&nbsp;&nbsp;' }}
            </td>
        </tr>
    @endforeach
@stop


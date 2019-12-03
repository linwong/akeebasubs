<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use \Akeeba\Subscriptions\Admin\Helper\Select;

$graphDayFrom = gmdate('Y-m-d', time() - 30 * 24 * 3600);
$graphDayTo = gmdate('Y-m-d', time());

$js = <<< JS
akeebasubs_cpanel_graph_from = "$graphDayFrom";

(function($) {
    $(document).ready(function(){
        akeebasubs_cpanel_graphs_load();

        $('#akeebasubs_graph_reload').click(function(e) {
            akeebasubs_cpanel_graphs_load();
        })
    });
})(akeeba.jQuery);

JS;
?>

@section('graphs')
    @js('media://com_akeebasubs/js/Chart.bundle.min.js')
    @js('media://com_akeebasubs/js/cpanelgraphs.js')

    <div class="akeeba-panel--info">
        <header class="akeeba-block-header">
            <h3>@lang('COM_AKEEBASUBS_DASHBOARD_SALES')</h3>
        </header>

        <div>
            <form class="akeeba-form--inline">
                <div class="akeeba-form-group">
                    @jhtml('calendar', $graphDayFrom, 'akeebasubs_graph_datepicker', 'akeebasubs_graph_datepicker')
                </div>

                <div class="akeeba-form-group">
                    @jhtml('calendar', $graphDayTo, 'akeebasubs_graph_todatepicker', 'akeebasubs_graph_todatepicker')
                </div>

                <div class="akeeba-form-group">
                    {{ Select::subscriptionlevels(0, 'akeebasubs_graph_level_id', array('class'=>'akeeba-input-small')) }}
                </div>

                <div class="akeeba-form-group--actions">
                    <button class="akeeba-btn--primary--mini" id="akeebasubs_graph_reload" onclick="return false">
                        @lang('COM_AKEEBASUBS_DASHBOARD_RELOADGRAPHS')
                    </button>
                </div>
            </form>
        </div>

        <canvas id="aksaleschart"></canvas>

        <img src="@media('media://com_akeebasubs/images/throbber.gif')" id="akthrobber" />

        <p id="aksaleschart-nodata" style="display:none">
            @lang('COM_AKEEBASUBS_DASHBOARD_STATS_NODATA')
        </p>

        <div style="clear: both;">&nbsp;</div>

        <h3>
            @lang('COM_AKEEBASUBS_DASHBOARD_LEVELSTATS')
        </h3>

        <canvas id="aklevelschart"></canvas>

        <img src="@media('media://com_akeebasubs/images/throbber.gif')" id="akthrobber2" />

        <p id="aklevelschart-nodata" style="display:none">
            @lang('COM_AKEEBASUBS_DASHBOARD_STATS_NODATA')
        </p>

    </div>
    @inlineJs($js)

@stop

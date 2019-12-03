/*
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * Setup (required for Joomla! 3)
 */
if (typeof (akeeba) == "undefined")
{
    var akeeba = {};
}
if (typeof (akeeba.jQuery) == "undefined")
{
    akeeba.jQuery = window.jQuery.noConflict();
}

var akeebasubs_cpanel_graph_from  = "";
var akeebasubs_cpanel_graph_to    = "";
var akeebasubs_cpanel_graph_level = "";

var akeebasubs_cpanel_graph_salesPoints  = [];
var akeebasubs_cpanel_graph_subsPoints   = [];
var akeebasubs_cpanel_graph_levelsPoints = [];
var akeebasubs_cpanel_graph_levelsLabels = [];
var akeebasubs_cpanel_graph_levelsColors = [];

var akeebasubs_cpanel_graph_plot1 = null;
var akeebasubs_cpanel_graph_plot2 = null;

/**
 * I am cheating a little bit here. Since I know all the subscription level names I have ever used in production and
 * testing I can hard-code colors to them in this JS array. Ideally I would have a color picker per level and store the
 * chart color in the database but it's an overkill, especially now that Akeeba Subs is tailored to our needs only.
 */
var akeebasubs_cpanel_level_to_color = {
	'DATACOMPLIANCE': '#E2363C',
	'CONTACTUS': '#40B5B8',
	'BUNDLE': '#514F50',
	'AKEEBABACKUP': '#40B5B8',
	'PROMO': '#40B5B8',
	'ADMINTOOLS': '#E2363C',
	'AKEEBASUBS': '#e6e2d3',
	'AKEEBATICKETS': '#514F50',
	'COMMERCE': '#b9936c',
	'ESSENTIALS': '#93C34E',
	'JOOMLADELUXE': '#F0AD4E',
	'RESELLERCREDITS': '#f7cac9',
	'SOLOPHP': 'rgb(153, 102, 255)',
	'BACKUPWP': 'rgb(54, 162, 235)',
	'ATOOLSWP': 'rgb(255, 99, 132)',
	'ATWP-BETA': 'rgb(255, 99, 132)',
	'WPBUNDLE': 'rgb(75, 192, 192)',
	'SUPPORT': '#dac292',
	'MINISUPPORT': '#c4b7a6'
};

function akeebasubs_cpanel_graphs_load()
{
    // Get the From date
    akeebasubs_cpanel_graph_from  = document.getElementById("akeebasubs_graph_datepicker").value;
    akeebasubs_cpanel_graph_to    = document.getElementById("akeebasubs_graph_todatepicker").value;
    akeebasubs_cpanel_graph_level = document.getElementById("akeebasubs_graph_level_id").value * 1;

    // Calculate the To date
    if (akeebasubs_cpanel_graph_to == "")
    {
        var thatDay                = new Date(akeebasubs_cpanel_graph_from);
        thatDay                    = new Date(thatDay.getTime() + 30 * 86400000);
        akeebasubs_cpanel_graph_to =
            thatDay.getUTCFullYear() + "-" + (thatDay.getUTCMonth() + 1) + "-" + thatDay.getUTCDate();
    }

    // Clear the data arrays
    akeebasubs_cpanel_graph_salesPoints  = [];
    akeebasubs_cpanel_graph_subsPoints   = [];
    akeebasubs_cpanel_graph_levelsPoints = [];

    // Remove the charts and show the spinners
    (function ($) {
        $("#aklevelschart").hide();
        $("#aksaleschart").hide();
        $("#akthrobber").show();
        $("#akthrobber2").show();
    })(akeeba.jQuery);

    akeebasubs_load_sales();
}

function akeebasubs_load_sales()
{
    (function ($) {
        var url = "index.php?option=com_akeebasubs&view=SubscriptionStatistics&since=" + akeebasubs_cpanel_graph_from + "&until=" + akeebasubs_cpanel_graph_to + "&groupbydate=1&filter_order=date&paystate=C&nozero=1&savestate=0&format=json";
        if (akeebasubs_cpanel_graph_level > 0)
        {
            url += "&level=" + akeebasubs_cpanel_graph_level;
        }
        $.getJSON(url, function (data) {
            $.each(data, function (index, item) {
                akeebasubs_cpanel_graph_salesPoints.push({
					"t": item.date,
					"y": parseInt(item.net * 100) * 1 / 100
				});

                akeebasubs_cpanel_graph_subsPoints.push({
					"t": item.date,
					"y": item.subs * 1
				});
            });
            $("#akthrobber").hide();
            $("#aksaleschart").show();
            if (akeebasubs_cpanel_graph_salesPoints.length == 0)
            {
                $("#aksaleschart-nodata").show();

                return;
            }
            akeebasubs_render_sales();
            akeebasubs_load_levels();
        });
    })(akeeba.jQuery);
}

function akeebasubs_load_levels()
{
    (function ($) {
        var url = "index.php?option=com_akeebasubs&view=SubscriptionStatistics&since=" + akeebasubs_cpanel_graph_from + "&until=" + akeebasubs_cpanel_graph_to + "&groupbylevel=1&paystate=C&nozero=1&savestate=0&format=json";
        if (akeebasubs_cpanel_graph_level > 0)
        {
            url += "&level=" + akeebasubs_cpanel_graph_level;
        }
        $.getJSON(url, function (data) {
			akeebasubs_cpanel_graph_levelsPoints = [];
			akeebasubs_cpanel_graph_levelsLabels = [];
			akeebasubs_cpanel_graph_levelsColors = [];

            $.each(data, function (index, item) {
				akeebasubs_cpanel_graph_levelsPoints.push(parseInt(item.net * 100) * (1 / 100));
				akeebasubs_cpanel_graph_levelsLabels.push(item.title);
				akeebasubs_cpanel_graph_levelsColors.push(akeebasubs_get_color(item.title));
            });
            $("#akthrobber2").hide();
            $("#aklevelschart").show();

            if (akeebasubs_cpanel_graph_levelsPoints.length == 0)
            {
                $("#aklevelschart-nodata").show();
                return;
            }
            akeebasubs_render_levels();
        });
    })(akeeba.jQuery);
}

function akeebasubs_get_color(levelTitle)
{
	var upperTitle = levelTitle.toUpperCase();

	if (upperTitle in akeebasubs_cpanel_level_to_color)
	{
		return akeebasubs_cpanel_level_to_color[upperTitle];
	}

	return "#EFEFEF";
}

function akeebasubs_render_sales()
{
	var chartOptions                           = {
		type: "bar",
		data: {
			datasets:[
				{
					label: "Sales",
					data: akeebasubs_cpanel_graph_salesPoints,
					borderColor: '#514F50',
					fill: false,
					yAxisID: 'y-axis-sales',
					type: 'line'
				},
				{
					label: "Subscriptions",
					data: akeebasubs_cpanel_graph_subsPoints,
					backgroundColor: '#40B5B8AA',
					fill: true,
					yAxisID: 'y-axis-subs'
				}
			]
		},
		options:{
			legend: {
				display: false
			},
			scales: {
				xAxes: [{
					type: 'time',
					time: {
						round: 'day',
						tooltipFormat: 'll',
						unit: 'day',
						minUnit: 'day'
					}
				}],
				yAxes: [{
					id: "y-axis-subs",
					position: 'left',
					ticks: {
						beginAtZero: true
					}
				}, {
					id: "y-axis-sales",
					position: 'right',
					ticks: {
						beginAtZero: true,
						callback: function(value, index, values) {
							return value + ' €';
						}
					},
					gridLines: {
						drawOnChartArea: false
					}
				}]
			}
		}
	};

	if (akeebasubs_cpanel_graph_plot1)
	{
		akeebasubs_cpanel_graph_plot1.destroy();
		akeebasubs_cpanel_graph_plot1 = null;
	}

	akeebasubs_cpanel_graph_plot1 = new Chart(document.getElementById("aksaleschart"), chartOptions);
}

function akeebasubs_render_levels()
{
	var chartOptions                           = {
		type: "pie",
		data: {
			datasets:[
				{
					label: "Sales",
					data: akeebasubs_cpanel_graph_levelsPoints,
					backgroundColor: akeebasubs_cpanel_graph_levelsColors
				}
			],
			labels: akeebasubs_cpanel_graph_levelsLabels
		},
		options:{
			responsive: true,
			legend: {
				display: true
			},
			tooltips: {
				callbacks: {
					label: function(tooltipItem, data) {
						var label = data.labels[tooltipItem.index] || "";

						if (label)
						{
							label += ": ";
						}

						label +=
							Math.round(data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index] * 100) / 100;
						return label + " €";
					}
				}
			}
		}
	};

	if (akeebasubs_cpanel_graph_plot2)
	{
		akeebasubs_cpanel_graph_plot2.destroy();
		akeebasubs_cpanel_graph_plot2 = null;
	}

	akeebasubs_cpanel_graph_plot2 = new Chart(document.getElementById("aklevelschart"), chartOptions);
}

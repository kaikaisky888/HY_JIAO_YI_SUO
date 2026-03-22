/*
 * @Author: Fox Blue
 * @Date: 2021-04-12 10:29:36
 * @LastEditTime: 2021-09-06 22:41:49
 * @Description: Forward, no stop
 */
var bga,bgb;
if(Webtheme !== 'White' || !Webtheme){
	bga = '#1e222d';
	bgb = '#5d7d93';
}else{
	bga = '#ffffff';
	bgb = '#ffffff';
}
var chartConfig = {
	debug: false,
	autosize: false,
	fullscreen: false, //布尔值显示图表是否占用窗口中所有可用的空间。
	timezone: "Asia/Shanghai",
	container_id: "tv_chart_container",
	datafeed: new FeedBase(),
	library_path: "/static/index/trading/charting_library/",
	locale: lang_kline,
	theme: Webtheme,
	loading_screen: {
		"backgroundColor": bga,
		"foregroundColor": bgb
	},
	// preset: "mobile",
	disabled_features: [ // 需要屏蔽掉的 参考 https://tradingview.gitee.io/featuresets/
		// "header_widget",
		"header_symbol_search", // 搜索
		'symbol_search_hot_key',
		// 'pane_context_menu', // 图表右键菜单
		// "header_saveload", // 上传下载按钮
		// "header_screenshot", // 照相机按钮
		// "header_chart_type", // 图标类型按钮
		"header_compare", //compare
		// "header_undo_redo", // 左右箭头
		// 'header_widget_dom_node', // 顶部工具栏
		"timeframes_toolbar", // 底部时间栏目
		"volume_force_overlay", // k线与销量分开
		// "header_resolutions", // 分辨率
		'header_resolutions', //头部的时间选择
		'header_interval_dialog_button',
		'show_interval_dialog_on_key_press',
		"legend_context_menu",
		"control_bar",
		"edit_buttons_in_legend",
		// "left_toolbar", // 左侧栏
		// "header_fullscreen_button", //全屏
		'dont_show_boolean_study_arguments',
		'header_indicators', // 技术指标
		"display_market_status",
		"header_settings", // 设置按钮
		"border_around_the_chart", //边框环绕
	],
	//禁用名称的数组
	enabled_features: [
		"move_logo_to_main_pane",
		// "study_templates",
		"dont_show_boolean_study_arguments", //是否隐藏指标参数
        "hide_last_na_study_output", //隐藏最后一次指标输出
        "same_data_requery",
        "side_toolbar_in_fullscreen_mode",
        'adaptive_logo',
		'keep_left_toolbar_visible_on_small_screens', // 防止左侧工具栏在小屏幕上消失
	],
	custom_css_url: "./css/tradingview.css",
	studies_overrides: {
		"volume.precision": 0,
		// 销量线
		"volume.volume.color.0": "#d64b62",
		"volume.volume.color.1": "#08ab90",
		// "volume.volume.color.0": "#e55a5a",
		// "volume.volume.color.1": "#3fd90a",
	},
	overrides: {
		// // 蜡烛样式
		// "mainSeriesProperties.candleStyle.upColor": "#08ab90",
		// "mainSeriesProperties.candleStyle.downColor": "#d64b62",
		// // 画布背景颜色
		// "paneProperties.background": "#181B2A",
		// //纵向网格线颜色
		// "paneProperties.vertGridProperties.color": "#1f2943",
		// //横向网格线颜色
		// "paneProperties.horzGridProperties.color": "#1f2943",
		// //标记水印透明度
		// "symbolWatermarkProperties.transparency": 0,
		// //刻度属性文本颜色
		// "scalesProperties.textColor": '#61688a',
		// // 设置坐标轴字体大小
		// //'scalesProperties.fontSize': 16, 
		// //隐藏左上角行情信息
		// 'paneProperties.legendProperties.showLegend': false,
		// 'left_toolbar': false,
		// //销量面板尺寸，支持的值: large, medium, small, tiny
		"volumePaneSize": "medium",
		// // 设置十字线
		// 'paneProperties.crossHairProperties.color': "rgba(197, 206, 226, 0.4)",
		// 'paneProperties.crossHairProperties.width': 2,
		// 'paneProperties.crossHairProperties.style': 0,

		//烛心
		// "mainSeriesProperties.candleStyle.drawWick" : true,
		//烛心颜色
		//"mainSeriesProperties.candleStyle.wickUpColor:" : '#8a3a3b',
		//"mainSeriesProperties.candleStyle.wickDownColor" : "#8a3a3b",

		//边框
		"mainSeriesProperties.candleStyle.drawBorder": true,

		// 坐标轴和刻度标签颜色
		'scalesProperties.lineColor': '#252525',
		'scalesProperties.textColor': '#8a8a8a',
		// 'paneProperties.legendProperties.showLegend': false,
		'paneProperties.topMargin': 20,
		'paneProperties.bottomMargin': 0,
		// "paneProperties.leftAxisProperties.autoScale": true,
		// "paneProperties.leftAxisProperties.autoScaleDisabled": false,
		// "paneProperties.leftAxisProperties.percentage": false,
		// "paneProperties.leftAxisProperties.percentageDisabled": false,
		// "paneProperties.leftAxisProperties.log": false,
		// "paneProperties.leftAxisProperties.logDisabled": false,
		// "paneProperties.leftAxisProperties.alignLabels": true,
		// // "paneProperties.legendProperties.showStudyArguments": true,
		// "paneProperties.legendProperties.showStudyTitles": true,
		// "paneProperties.legendProperties.showStudyValues": true,
		// "paneProperties.legendProperties.showSeriesTitle": true,
		// "paneProperties.legendProperties.showSeriesOHLC": true,
		// "scalesProperties.showLeftScale": false,
		// "scalesProperties.showRightScale": true,
		// "scalesProperties.backgroundColor": "#20334d",
		// "scalesProperties.lineColor": "#46587b",
		// "scalesProperties.textColor": "#8f98ad",
		// "scalesProperties.scaleSeriesOnly": false,
		// "mainSeriesProperties.priceAxisProperties.autoScale": true,
		// "mainSeriesProperties.priceAxisProperties.autoScaleDisabled": false,
		// "mainSeriesProperties.priceAxisProperties.percentage": false,
		// "mainSeriesProperties.priceAxisProperties.percentageDisabled": false,
		// "mainSeriesProperties.priceAxisProperties.log": false,
		// "mainSeriesProperties.priceAxisProperties.logDisabled": false,
		// "mainSeriesProperties.candleStyle.upColor": "#3fcfb4",
		// "mainSeriesProperties.candleStyle.downColor": "#fe4761",
		// "mainSeriesProperties.candleStyle.drawWick": true,
		// "mainSeriesProperties.candleStyle.drawBorder": true,
		// "mainSeriesProperties.candleStyle.borderColor": "#3fcfb4",
		// "mainSeriesProperties.candleStyle.borderUpColor": "#3fcfb4",
		// "mainSeriesProperties.candleStyle.borderDownColor": "#fe4761",
		// "mainSeriesProperties.candleStyle.wickColor": "#737375",
		// "mainSeriesProperties.candleStyle.wickUpColor": "#3fcfb4",
		// "mainSeriesProperties.candleStyle.wickDownColor": "#fe4761",
		// "mainSeriesProperties.candleStyle.barColorsOnPrevClose": false,
		// "mainSeriesProperties.hollowCandleStyle.upColor": "#3fcfb4",
		// "mainSeriesProperties.hollowCandleStyle.downColor": "#fe4761",
		// "mainSeriesProperties.hollowCandleStyle.drawWick": true,
		// "mainSeriesProperties.hollowCandleStyle.drawBorder": true,
		// "mainSeriesProperties.hollowCandleStyle.borderColor": "#3fcfb4",
		// "mainSeriesProperties.hollowCandleStyle.borderUpColor": "#3fcfb4",
		// "mainSeriesProperties.hollowCandleStyle.borderDownColor": "#fe4761",
		// "mainSeriesProperties.hollowCandleStyle.wickColor": "#737375",
		// "mainSeriesProperties.hollowCandleStyle.wickUpColor": "#3fcfb4",
		// "mainSeriesProperties.hollowCandleStyle.wickDownColor": "#fe4761",
		// "mainSeriesProperties.haStyle.upColor": "#3fcfb4",
		// "mainSeriesProperties.haStyle.downColor": "#fe4761",
		// "mainSeriesProperties.haStyle.drawWick": true,
		// "mainSeriesProperties.haStyle.drawBorder": true,
		// "mainSeriesProperties.haStyle.borderColor": "#3fcfb4",
		// "mainSeriesProperties.haStyle.borderUpColor": "#3fcfb4",
		// "mainSeriesProperties.haStyle.borderDownColor": "#fe4761",
		// "mainSeriesProperties.haStyle.wickColor": "#737375",
		// "mainSeriesProperties.haStyle.wickUpColor": "#3fcfb4",
		// "mainSeriesProperties.haStyle.wickDownColor": "#fe4761",
		// "mainSeriesProperties.haStyle.barColorsOnPrevClose": false,
		// "mainSeriesProperties.barStyle.upColor": "#3fcfb4",
		// "mainSeriesProperties.barStyle.downColor": "#fe4761",
		// "mainSeriesProperties.barStyle.barColorsOnPrevClose": false,
		// "mainSeriesProperties.barStyle.dontDrawOpen": false,
		// "mainSeriesProperties.lineStyle.color": "#0cbef3",
		// "mainSeriesProperties.lineStyle.linestyle": 0,
		// "mainSeriesProperties.lineStyle.linewidth": 1,
		// "mainSeriesProperties.lineStyle.priceSource": "close",
		// "mainSeriesProperties.areaStyle.color1": "#0cbef3",
		// "mainSeriesProperties.areaStyle.color2": "#0098c4",
		// "mainSeriesProperties.areaStyle.linecolor": "#0cbef3",
		// "mainSeriesProperties.areaStyle.linestyle": 0,
		// "mainSeriesProperties.areaStyle.linewidth": 1,
		// "mainSeriesProperties.areaStyle.priceSource": "close"
	},
}

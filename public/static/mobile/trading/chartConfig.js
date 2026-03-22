/*
 * @Author: Fox Blue
 * @Date: 2021-04-12 10:29:36
 * @LastEditTime: 2021-09-06 22:38:44
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
	library_path: "/static/mobile/trading/charting_library/",
	locale: lang_kline,
	theme: Webtheme,
	loading_screen: {
		"backgroundColor": bga,
		"foregroundColor": bgb
	},
	preset: "mobile",
	disabled_features: [ // 需要屏蔽掉的 参考 https://tradingview.gitee.io/featuresets/
		"header_widget",
		"header_symbol_search", // 搜索
		'symbol_search_hot_key',
		// 'pane_context_menu', // 图表右键菜单
		"header_saveload", // 上传下载按钮
		"header_screenshot", // 照相机按钮
		"header_chart_type", // 图标类型按钮
		"header_compare", //compare
		"header_undo_redo", // 左右箭头
		'header_widget_dom_node', // 顶部工具栏
		"timeframes_toolbar", // 底部时间栏目
		"volume_force_overlay", // k线与销量分开
		"header_resolutions", // 分辨率
		'header_interval_dialog_button',
		'show_interval_dialog_on_key_press',
		"legend_context_menu",
		"control_bar",
		// "edit_buttons_in_legend",
		// "left_toolbar", // 左侧栏
		"header_fullscreen_button", //全屏
		'dont_show_boolean_study_arguments',
		'header_indicators', // 技术指标
		"save_chart_properties_to_local_storage",
		"use_localstorage_for_settings",
		"display_market_status",
		"header_settings", // 设置按钮
		"border_around_the_chart", //边框环绕
	],
	//禁用名称的数组
	enabled_features: [
		"move_logo_to_main_pane",
		"study_templates",
		"dont_show_boolean_study_arguments", //是否隐藏指标参数
        "hide_last_na_study_output", //隐藏最后一次指标输出
        "same_data_requery",
        "side_toolbar_in_fullscreen_mode",
        'adaptive_logo',
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
		// 设置坐标轴字体大小
		//'scalesProperties.fontSize': 16, 
		// //隐藏左上角行情信息
		// 'paneProperties.legendProperties.showLegend': false,
		// 'left_toolbar': false,
		// //销量面板尺寸，支持的值: large, medium, small, tiny
		"volumePaneSize": "tiny",
		// 坐标轴和刻度标签颜色
		'scalesProperties.lineColor': '#252525',
		'scalesProperties.textColor': '#8a8a8a',
		// 'paneProperties.legendProperties.showLegend': false,
		'paneProperties.topMargin': 30,
		'paneProperties.bottomMargin': 10,

		// "mainSeriesProperties.style": 9,
		
		
	},
}

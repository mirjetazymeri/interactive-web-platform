<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ChartsController extends Controller
{
    
    public function apex_area_charts()
    {
        return view('pages.charts.apex-area-charts');
    }
    
    public function apex_bar_charts()
    {
        return view('pages.charts.apex-bar-charts');
    }
    
    public function apex_boxplot_charts()
    {
        return view('pages.charts.apex-boxplot-charts');
    }
    
    public function apex_bubble_charts()
    {
        return view('pages.charts.apex-bubble-charts');
    }
    
    public function apex_candlestick_charts()
    {
        return view('pages.charts.apex-candlestick-charts');
    }
    
    public function apex_column_charts()
    {
        return view('pages.charts.apex-column-charts');
    }
    
    public function apex_funnel_charts()
    {
        return view('pages.charts.apex-funnel-charts');
    }
    
    public function apex_heatmap_charts()
    {
        return view('pages.charts.apex-heatmap-charts');
    }
    
    public function apex_line_charts()
    {
        return view('pages.charts.apex-line-charts');
    }
    
    public function apex_mixed_charts()
    {
        return view('pages.charts.apex-mixed-charts');
    }
    
    public function apex_pie_charts()
    {
        return view('pages.charts.apex-pie-charts');
    }
    
    public function apex_polararea_charts()
    {
        return view('pages.charts.apex-polararea-charts');
    }
    
    public function apex_radar_charts()
    {
        return view('pages.charts.apex-radar-charts');
    }
    
    public function apex_radialbar_charts()
    {
        return view('pages.charts.apex-radialbar-charts');
    }
    
    public function apex_rangearea_charts()
    {
        return view('pages.charts.apex-rangearea-charts');
    }
    
    public function apex_scatter_charts()
    {
        return view('pages.charts.apex-scatter-charts');
    }
    
    public function apex_timeline_charts()
    {
        return view('pages.charts.apex-timeline-charts');
    }
    
    public function apex_treemap_charts()
    {
        return view('pages.charts.apex-treemap-charts');
    }
    
    public function chartjs_charts()
    {
        return view('pages.charts.chartjs-charts');
    }
    
    public function echarts()
    {
        return view('pages.charts.echarts');
    }

}

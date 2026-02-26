<?php
declare(strict_types=1);

if (!defined('ABSPATH')) exit;

class GridStyles {

    /**
     * Builds fluid font-size clamp using plugin's --ka-base-font (theme-independent).
     * fontmin/fontmaks are px values; they scale with the base font setting.
     *
     * @param string $fontmin Min font size in px (e.g. "13" or "13px")
     * @param string $fontmaks Max font size in px (e.g. "16" or "16px")
     * @return string CSS clamp() value
     */
    public static function build_font_clamp(string $fontmin, string $fontmaks): string {
        $min_px = floatval($fontmin);
        $max_px = floatval($fontmaks);
        $base = 16;
        $min_ratio = $min_px / $base;
        $max_ratio = $max_px / $base;
        $max_css = ($max_ratio === 1.0) ? 'var(--ka-base-font, 16px)' : 'calc(var(--ka-base-font, 16px) * ' . $max_ratio . ')';
        return "clamp(calc(var(--ka-base-font, 16px) * {$min_ratio}), calc(3.5vw - var(--ka-base-font, 16px) * 0.21875), {$max_css})";
    }
    
    /**
     * Genererer felles grid-basert CSS for shortcodes (uten ID)
     */
    public static function get_common_grid_styles(array $a): string {
        
        return "<style>
            /* Common Grid Styles - Loaded Once */
            /* Outer Wrapper - Prevent stretching from theme containers */
            .kursagenten-grid.outer-wrapper { 
                padding: {$a['avstand']}; 
                width: 100%; 
                box-sizing: border-box; 
                height: auto; 
                min-height: 0; 
                display: block; 
            }
            
            /* Wrapper - Prevent stretching from theme containers */
            .kursagenten-grid .k-wrapper {
                display: grid;
                grid-template-columns: repeat({$a['grid']}, minmax(0, 1fr));
                column-gap: clamp(1vw, 2vw, 2rem);
                row-gap: {$a['radavstand']};
                width: 100%;
                margin: 0 auto;
                box-sizing: border-box;
                align-items: start;
                height: auto;
                min-height: 0;
            }
            .kursagenten-grid.stablet.kort .k-wrapper { justify-items: center; max-width: calc(({$a['bildestr']} * {$a['grid']}) + (clamp(1vw, 2vw, 2rem) * ({$a['grid']} - 1 )));}
            .kursagenten-grid.kort.rund .k-wrapper { max-width: calc((calc({$a['bildestr']} * 5.5) * {$a['grid']}) + (clamp(1vw, 2vw, 2rem) * ({$a['grid']} - 1 )));}
            {$class_id}.liste .k-wrapper { grid-template-columns: repeat(1, 1fr); row-gap: " . (!empty($a['_radavstand_provided']) ? $a['radavstand'] : '0.6em') . "; width: auto; }
            .kursagenten-grid.rad.utdrag .k-wrapper { row-gap: 3rem; }
            
            /* Box */
            .kursagenten-grid.rad .k-wrapper .k-box { display: flex; column-gap: 0; max-width: 100%; align-self: start; height: auto; }
            .kursagenten-grid.rad.kort .k-wrapper .k-box { height: 100%; }
            .kursagenten-grid.kort .k-box { border-radius: 5px; width: 100%; max-width: 100%; width: 100%; background-color: #fff; -webkit-box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.1); -moz-box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.1); box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.1); align-self: start; height: auto; }
            .kursagenten-grid.stablet .k-wrapper .k-box { align-self: start; height: auto; }
            .kursagenten-grid.kort .k-box:has(picture) { max-width: {$a['bildestr']}; }
            .kursagenten-grid.kort.rund .k-box { max-width: calc({$a['bildestr']} * 5.5)!important; }
            .kursagenten-grid.stablet.kort .k-box { width: 100%; max-width: 100%;  margin: 0 auto; box-sizing: border-box;}
            .kursagenten-grid.stablet.kort .k-box:has(picture) { max-width: {$a['bildestr']};}
            .kursagenten-grid.skygge.kort .k-box:hover { -webkit-box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.3); -moz-box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.3); box-shadow: 0px 2px 8px 0px rgba(53, 53, 53, 0.3); transition: transform ease 0.3s, box-shadow ease 0.3s; }
            .kursagenten-grid.rad.beskrivelse .k-box,
            .kursagenten-grid.rad.utdrag .k-box,
            .kursagenten-grid.rad:has(.k-specific-locations) .k-box { align-items: flex-start; }
            
            /* Box-inner */
            .kursagenten-grid .k-box-inner { display: flex; align-items: center; text-decoration: none; }
            .kursagenten-grid.stablet .k-box-inner { justify-content: center; }
            .kursagenten-grid.rad.utdrag .k-box-inner,
            .kursagenten-grid.rad.beskrivelse .k-box-inner,
            .kursagenten-grid.rad:has(.k-specific-locations) .k-box-inner { align-items: flex-start; }
            
            /* Image */
            .kursagenten-grid a.k-image { flex-shrink: 0; }
            .kursagenten-grid.rad a.k-image { max-width: 30vw; height: auto; align-self: stretch; }
            .kursagenten-grid.rad.kort a.k-image { height: 100%; align-self: stretch; }
            .kursagenten-grid.rad.kort.rund .k-image.k-box-inner { padding: .5em }
            .kursagenten-grid.stablet.rund.kort a.k-image { padding-top: .5em; }
            .kursagenten-grid.liste a.k-image { display: none; }
            
            /* Text */
            .kursagenten-grid .k-text {font-size: clamp(0.875rem, 0.75rem + 0.5714vw, 1rem); flex-shrink: 2; flex-grow: 3; line-height: 1.5; padding-left: 1em;}
            .kursagenten-grid .k-text a.k-infowrapper { color: #555; }
            .kursagenten-grid:not(.utdrag) .k-text a { width: 100%; height: 100%; }
            .kursagenten-grid.stablet .k-text { flex-direction: column; align-items: center; justify-content: flex-start; text-align: center; padding-left: 0; }
            .kursagenten-grid.stablet .k-text a { padding-top: .5em; }
            .kursagenten-grid.stablet.kort .k-text { padding: 0.9em .8em 0.9em .8em; }
            .kursagenten-grid.stablet.kort .k-text:has(picture) { padding: 0.5em .8em 1.3em .8em; }
            .kursagenten-grid.rad .k-text { flex-direction: column; align-items: flex-start; justify-content: center;}
            .kursagenten-grid.rad.kort .k-text { padding: 1em 1em 1em 0;}
            .kursagenten-grid.rad .k-text a.k-title,
            .kursagenten-grid.rad .k-text .k-infowrapper{ width: 100%; display: flex; align-items: center; text-align: left; }
            .kursagenten-grid.rad.beskrivelse .k-text a.k-title,
            .kursagenten-grid.rad.beskrivelse .k-text p { padding: 0px 2em; margin-bottom: 1em; }
            .kursagenten-grid.kort .k-text a { padding: 1em; }
            .kursagenten-grid.rad.kort .k-text a,
            .kursagenten-grid.rad.kort:not(.beskrivelse) .k-description { padding: .3em 1em; }
            .kursagenten-grid.kort .k-text a.k-infowrapper { margin-top: -2px; padding-top: 0; align-items: flex-start; }
            .kursagenten-grid.kort.stablet .k-text a, .kursagenten-grid.kort.stablet .k-text p { padding: .2em .5em; line-height: 1.4;}
            .kursagenten-grid.liste .k-description { display: none; }
            .kursagenten-grid:not(.utdrag) .k-description { display: none; }
            .kursagenten-grid .k-text:has(.k-tittel) a { text-decoration: none; }
            .kursagenten-grid .k-infowrapper ul {list-style: none; margin: 0; padding: 0; }
            .kursagenten-grid .k-infowrapper .k-location-item { font-size: var(--ka-font-s); }
            
            /* Picture and Image */
            .kursagenten-grid picture {aspect-ratio: {$a['bildeformat']}; border-radius: {$a['bildeform']}; display: block;  width:100%; max-width:{$a['bildestr']}; overflow: hidden; }
            .kursagenten-grid.rund picture { aspect-ratio: 1/1;  max-width:{$a['bildestr']}; max-height:{$a['bildestr']};}
            .kursagenten-grid.stablet.kort picture { padding: 0; width: 100%; max-width: {$a['bildestr']};}
            .kursagenten-grid.rad.kort:not(.rund) picture { max-width: calc({$a['bildestr']} * 0.85); padding: 0; border-radius: {$a['bildeform']} 0 0 {$a['bildeform']}; height: 100%;}
            .kursagenten-grid.rad.kort.rund picture { max-width: calc({$a['bildestr']} * 0.85); padding: 0; }
            .kursagenten-grid img {
                width: 100%;
                height: 100%;
                max-width: 100%;
                object-fit: cover;
                object-position: center;
                transition: transform 0.5s ease;
            }
            .kursagenten-grid .k-box:hover img { transform: scale(1.05); }
            .kursagenten-grid.skygge:not(.kort) picture { max-width: calc({$a['bildestr']} + 8px); padding: 8px; }
            .kursagenten-grid.skygge:not(.kort) .k-box:hover img { -webkit-box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.45); -moz-box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.45); box-shadow: 0px 2px 8px 0px rgba(53, 53, 53, 0.45); transition: transform ease 0.3s, box-shadow ease 0.3s; }
            .kursagenten-grid.stablet.kort img { border-radius: 5px 5px 0 0; }
            .kursagenten-grid.stablet.kort.rund img { border-radius: {$a['bildeform']}; }
            .kursagenten-grid.rad.kort:not(.beskrivelse) img { border-radius: 5px 0 0 5px; }
            
            .kursagenten-grid .k-tittel { margin: 0; font-size: {$a['fontstr']}; }
            
            /* Responsive */
            @media all and (max-width: 1100px) {
                .kursagenten-grid .k-wrapper { grid-template-columns: repeat({$a['gridtablet']}, minmax(0, 1fr)); }
                .kursagenten-grid.stablet.kort .k-wrapper { max-width: calc(({$a['bildestr']} * {$a['gridtablet']}) + (clamp(1vw, 2vw, 2rem) * ({$a['gridtablet']} - 1 )));}
                .kursagenten-grid.kort.rund .k-wrapper { max-width: calc((calc({$a['bildestr']} * 5.5) * {$a['gridtablet']}) + (clamp(1vw, 2vw, 2rem) * ({$a['gridtablet']} - 1 )));}
            }
            
            @media all and (max-width: 630px) {
                .kursagenten-grid .k-wrapper {  grid-template-columns: repeat({$a['gridmobil']}, 1fr); }
                .kursagenten-grid.rad:not(.utdrag) .k-wrapper .k-box { align-items: center; } 
                .kursagenten-grid.rad:not(.kort) .k-text a.k-title { padding: .8em; margin-bottom: 0; align-items: flex-start; }
                .kursagenten-grid.rad:not(.kort) .k-text p { padding: 0; margin-bottom: 12px; }
                .kursagenten-grid.rad:not(.kort) picture { max-width: 20vw; }
                .kursagenten-grid.rad.kort .k-text a,
                .kursagenten-grid.rad.kort:not(.beskrivelse) .k-description { padding-left: 0.8em; }
                .kursagenten-grid.rad.skygge .k-text { padding-left: 8px; }
                .kursagenten-grid.rad.beskrivelse .k-wrapper .k-box { flex-direction: column; }
                .kursagenten-grid.rad.beskrivelse .k-box .k-text a.k-title,
                .kursagenten-grid.rad.beskrivelse .k-box .k-text p { padding: 0 0 0 1.5em; }
                .kursagenten-grid.rad.beskrivelse .k-box .k-text p { margin-bottom: .7em; }
                .kursagenten-grid.rad.beskrivelse picture {max-width: 100%; }
                .kursagenten-grid.rad.beskrivelse.kort picture {border-radius: {$a['bildeform']} {$a['bildeform']} 0 0; }
                .kursagenten-grid.rad.beskrivelse a.k-image { max-width: 100%; max-height: 50vw; }
            }
            @media all and (max-width: 370px) {
                .kursagenten-grid.kort.rund .k-wrapper { max-width: calc((calc({$a['bildestr']} * 5.5) * {$a['gridmobil']}) + (clamp(1vw, 2vw, 2rem) * ({$a['gridmobil']} - 1 )));}
                .kursagenten-grid.rad picture,
                .kursagenten-grid.rad.kort:not(.beskrivelse) picture  { max-width: 20vw;}
                .kursagenten-grid.rad a.k-image { max-width: 100%; max-height: 50vw; }
                .kursagenten-grid.rad.kort.rund img { border-radius: {$a['bildeform']}; }
            }
        </style>";
    }
    
    /**
     * Genererer ID-spesifikke grid-stiler (deprecated - bruk get_common_grid_styles + ID-spesifikke stiler)
     */
    public static function get_grid_styles(string $id, array $a): string {
        $class_id = "." . $id;
        
        return "<style>
                        /* Outer Wrapper - Prevent stretching from theme containers */
            {$class_id}.outer-wrapper { 
                padding: {$a['avstand']}; 
                width: 100%; 
                box-sizing: border-box; 
                height: auto; 
                min-height: 0; 
                display: block; 
            }
            




            /* Wrapper - Prevent stretching from theme containers */
            {$class_id} .k-wrapper {
                display: grid;
                grid-template-columns: repeat({$a['grid']}, minmax(0, 1fr));
                column-gap: clamp(1vw, 2vw, 2rem);
                row-gap: {$a['radavstand']};
                width: 100%;
                margin: 0 auto;
                box-sizing: border-box;
                align-items: start;
                height: auto;
                min-height: 0;
            }
            {$class_id}.stablet.kort .k-wrapper { justify-items: center; max-width: calc(({$a['bildestr']} * {$a['grid']}) + (clamp(1vw, 2vw, 2rem) * ({$a['grid']} - 1 )));}
            {$class_id}.kort.rund .k-wrapper { max-width: calc((calc({$a['bildestr']} * 5.5) * {$a['grid']}) + (clamp(1vw, 2vw, 2rem) * ({$a['grid']} - 1 )));}
            {$class_id}.liste .k-wrapper { grid-template-columns: repeat(1, 1fr); row-gap: " . (!empty($a['_radavstand_provided']) ? $a['radavstand'] : '0.6em') . "; width: auto; }
            {$class_id}.rad.utdrag .k-wrapper { row-gap: 3rem; }
            




            /* Box */
            {$class_id}.rad .k-wrapper .k-box { display: flex; column-gap: 0; max-width: 100%; align-self: start; height: auto; }
            {$class_id}.rad.kort .k-wrapper .k-box { height: 100%; }
            {$class_id}.kort .k-box { border-radius: 5px;  max-width: {$a['bildestr']}; width: 100%; background-color: #fff; -webkit-box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.1); -moz-box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.1); box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.1); align-self: start; height: auto; }
            {$class_id}.stablet .k-wrapper .k-box { align-self: start; height: auto; }
            {$class_id}.kort.rund .k-box { max-width: calc({$a['bildestr']} * 5.5)!important; }
            /*{$class_id}.rad.kort .k-box { max-width: calc({$a['bildestr']} * 4); }*/
            {$class_id}.stablet.kort .k-box { width: 100%; max-width: 100%;  margin: 0 auto; box-sizing: border-box;}
            {$class_id}.stablet.kort .k-box:has(picture) { max-width: {$a['bildestr']};}
            {$class_id}.skygge.kort .k-box:hover { -webkit-box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.3); -moz-box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.3); box-shadow: 0px 2px 8px 0px rgba(53, 53, 53, 0.3); transition: transform ease 0.3s, box-shadow ease 0.3s; }
            {$class_id}.rad.beskrivelse .k-box,
            {$class_id}.rad.utdrag .k-box,
            {$class_id}.rad:has(.k-specific-locations) .k-box { align-items: flex-start; } 


    
            /* Box-inner */
            {$class_id} .k-box-inner { display: flex; align-items: center; text-decoration: none; }
            {$class_id}.stablet .k-box-inner { justify-content: center; }
            {$class_id}.rad.beskrivelse .k-box-inner,
            {$class_id}.rad.utdrag .k-box-inner,
            {$class_id}.rad:has(.k-specific-locations) .k-box-inner { align-items: flex-start; }
            



            /* Image */
            {$class_id} a.k-image { flex-shrink: 0; }
            {$class_id}.rad a.k-image { max-width: 30vw; height: auto; align-self: stretch; }
            {$class_id}.rad.kort a.k-image { height: 100%; align-self: stretch; }
            {$class_id}.rad.kort.rund .k-image.k-box-inner { padding: .5em }
            {$class_id}.stablet.rund.kort a.k-image { padding-top: .5em; }
            {$class_id}.liste a.k-image { display: none; }
            



            /* Text */
            {$class_id} .k-text {font-size: clamp(0.875rem, 0.75rem + 0.5714vw, 1rem); flex-shrink: 2; flex-grow: 3; line-height: 1.5; padding-left: 1em;}
            {$class_id}.liste .k-text {padding-left: 0; }
            {$class_id} .k-text a.k-infowrapper { color: #555; }
            {$class_id}:not(.utdrag) .k-text a { width: 100%; height: 100%; }
            {$class_id}.stablet .k-text { flex-direction: column; align-items: center; justify-content: flex-start; text-align: center; padding-left: 0; }
            {$class_id}.stablet .k-text a { padding-top: .5em; }
            {$class_id}.stablet.kort .k-text { padding: 0.9em .8em 0.9em .8em; }
            {$class_id}.stablet.kort .k-text:has(picture) { padding: 0.5em .8em 1.3em .8em; }
            {$class_id}.rad .k-text { flex-direction: column; align-items: flex-start; justify-content: center;}
            {$class_id}.rad.kort .k-text { padding: 1em 1em 1em 0;}
            {$class_id}.rad .k-text a.k-title,
            {$class_id}.rad .k-text .k-infowrapper{ width: 100%; display: flex; align-items: center; text-align: left; }
            /*{$class_id}.rad .k-text a.k-title,
            {$class_id}.rad:not(.beskrivelse) .k-text .info{ padding-left: 1em;}*/
            {$class_id}.rad.beskrivelse .k-text a.k-title,
            {$class_id}.rad.beskrivelse .k-text p { padding: 0px 2em; margin-bottom: 1em; }
            {$class_id}.kort .k-text a { padding: 1em; }
            {$class_id}.rad.kort .k-text a,
            {$class_id}.rad.kort:not(.beskrivelse) .k-description { padding: .3em 1em; }
            {$class_id}.kort .k-text a.k-infowrapper { margin-top: -2px; padding-top: 0; align-items: flex-start; }
            {$class_id}.kort.stablet .k-text a, {$class_id}.kort.stablet .k-text p { padding: .2em .5em; line-height: 1.4;}
            {$class_id}.liste .k-description { display: none; }
            {$class_id}:not(.utdrag) .k-description { display: none; }
            {$class_id} .k-text:has(.k-tittel) a { text-decoration: none; }
            {$class_id} .k-infowrapper ul {list-style: none; margin: 0; padding: 0; }
            {$class_id} .k-infowrapper .k-location-item { font-size: var(--ka-font-s); }
            





            /* Picture and Image */
            {$class_id} picture {aspect-ratio: {$a['bildeformat']}; border-radius: {$a['bildeform']}; display: block;  width:100%; max-width:{$a['bildestr']}; overflow: hidden; }
            {$class_id}.rund picture { aspect-ratio: 1/1;  max-width:{$a['bildestr']}; max-height:{$a['bildestr']}; }
            {$class_id}.stablet.kort picture { padding: 0; width: 100%; max-width: {$a['bildestr']}; border-radius: {$a['bildeform']} {$a['bildeform']} 0 0;}
            {$class_id}.rad.kort:not(.rund) picture { max-width: calc({$a['bildestr']} * 0.85); padding: 0; border-radius: {$a['bildeform']} 0 0 {$a['bildeform']}; height: 100%;}
            {$class_id}.rad.kort.rund picture { max-width: calc({$a['bildestr']} * 0.85); padding: 0; }
            {$class_id} img {
                width: 100%;
                height: 100%;
                max-width: 100%;
                object-fit: cover;
                object-position: center;
                transition: transform 0.5s ease;
            }
            {$class_id} .k-box:hover img { transform: scale(1.05); }
            {$class_id}.skygge:not(.kort) picture { max-width: calc({$a['bildestr']} + 8px); padding: 8px; }
            {$class_id}.skygge:not(.kort) .k-box:hover img { -webkit-box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.45); -moz-box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.45); box-shadow: 0px 2px 8px 0px rgba(53, 53, 53, 0.45); transition: transform ease 0.3s, box-shadow ease 0.3s; }
            {$class_id}.stablet.kort img { border-radius: 5px 5px 0 0; }
            {$class_id}.stablet.kort.rund img { border-radius: {$a['bildeform']}; }
            {$class_id}.rad.kort:not(.beskrivelse) img { border-radius: 5px 0 0 5px; }
            




            /* Font */
            {$class_id} .k-tittel { margin: 0; font-size: {$a['fontstr']}; }
            





            /* Responsive */
            @media all and (max-width: 1100px) {
                {$class_id} .k-wrapper { grid-template-columns: repeat({$a['gridtablet']}, minmax(0, 1fr)); }
                {$class_id}.stablet.kort .k-wrapper { max-width: calc(({$a['bildestr']} * {$a['gridtablet']}) + (clamp(1vw, 2vw, 2rem) * ({$a['gridtablet']} - 1 )));}
                {$class_id}.kort.rund .k-wrapper { max-width: calc((calc({$a['bildestr']} * 5.5) * {$a['gridtablet']}) + (clamp(1vw, 2vw, 2rem) * ({$a['gridtablet']} - 1 )));}
            }
            
            @media all and (max-width: 630px) {
                {$class_id} .k-wrapper {  grid-template-columns: repeat({$a['gridmobil']}, 1fr); }
                {$class_id}.rad:not(.utdrag) .k-wrapper .k-box { align-items: center; } 
                /*{$class_id}.rad:not(.kort) .k-wrapper .k-box { flex-direction: column; }*/
                {$class_id}.rad:not(.kort) .k-text a.k-title { padding: .8em; margin-bottom: 0; align-items: flex-start; }
                {$class_id}.rad:not(.kort) .k-text p { padding: 0; margin-bottom: 12px; }
                {$class_id}.rad:not(.kort) picture { max-width: 20vw; }
                {$class_id}.rad.kort .k-text a,
                {$class_id}.rad.kort:not(.beskrivelse) .k-description { padding-left: 0.8em; }
                {$class_id}.rad.skygge .k-text { padding-left: 8px; }
                {$class_id}.rad.beskrivelse .k-wrapper .k-box { flex-direction: column; }
                {$class_id}.rad.beskrivelse .k-box .k-text a.k-title,
                {$class_id}.rad.beskrivelse .k-box .k-text p { padding: 0 0 0 1.5em; }
                {$class_id}.rad.beskrivelse .k-box .k-text p { margin-bottom: .7em; }
                {$class_id}.rad.beskrivelse picture {max-width: 100%; }
                {$class_id}.rad.beskrivelse.kort picture {border-radius: {$a['bildeform']} {$a['bildeform']} 0 0; }
                {$class_id}.rad.beskrivelse a.k-image { max-width: 100%; max-height: 50vw; }
            }
            @media all and (max-width: 370px) {
                /*{$class_id}.rad.kort .k-wrapper { max-width: calc(({$a['bildestr']} * {$a['gridmobil']}) + (clamp(1vw, 2vw, 2rem) * ({$a['gridmobil']} - 1 )));}*/
                {$class_id}.kort.rund .k-wrapper { max-width: calc((calc({$a['bildestr']} * 5.5) * {$a['gridmobil']}) + (clamp(1vw, 2vw, 2rem) * ({$a['gridmobil']} - 1 )));}
                /*{$class_id}.rad.kort .k-wrapper .k-box { flex-direction: column; width: 100%; max-width: {$a['bildestr']};  margin: 0 auto; box-sizing: border-box;}*/
                /*{$class_id}.rad.kort picture { padding: 0; width: 100%; max-width: {$a['bildestr']};}*/
                {$class_id}.rad picture,
                {$class_id}.rad.kort:not(.beskrivelse) picture  { max-width: 20vw;}
                {$class_id}.rad a.k-image { max-width: 100%; max-height: 50vw; }
                /*{$class_id}.rad.kort img { border-radius: 5px 5px 0 0; }*/
                {$class_id}.rad.kort.rund img { border-radius: {$a['bildeform']}; }
                /*{$class_id}.rad.kort .k-text { flex-direction: column; align-items: flex-start; justify-content: center; padding: .3em 0 .3em 0;}*/
                /*{$class_id}.rad .k-text a.k-title, {$class_id}.rad .k-text .info { text-align: center; justify-content: center;}*/
            }
        </style>";
    }
} 
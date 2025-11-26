<?php
declare(strict_types=1);

if (!defined('ABSPATH')) exit;

class GridStyles {
    
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
            .kursagenten-grid .wrapper {
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
            .kursagenten-grid.stablet.kort .wrapper { justify-items: center; max-width: calc(({$a['bildestr']} * {$a['grid']}) + (clamp(1vw, 2vw, 2rem) * ({$a['grid']} - 1 )));}
            .kursagenten-grid.kort.rund .wrapper { max-width: calc((calc({$a['bildestr']} * 5.5) * {$a['grid']}) + (clamp(1vw, 2vw, 2rem) * ({$a['grid']} - 1 )));}
            .kursagenten-grid.liste .wrapper { grid-template-columns: repeat(1, 1fr); row-gap: " . (!empty($a['_radavstand_provided']) ? $a['radavstand'] : '0.6em') . "; width: auto; }
            .kursagenten-grid.rad.utdrag .wrapper { row-gap: 3rem; }
            
            /* Box */
            .kursagenten-grid.rad .wrapper .box { display: flex; column-gap: 0; max-width: 100%; align-self: start; height: auto; }
            .kursagenten-grid.kort .box { border-radius: 5px; width: 100%; max-width: 100%; width: 100%; background-color: #fff; -webkit-box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.1); -moz-box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.1); box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.1); align-self: start; height: auto; }
            .kursagenten-grid.stablet .wrapper .box { align-self: start; height: auto; }
            .kursagenten-grid.kort .box:has(picture) { max-width: {$a['bildestr']}; }
            .kursagenten-grid.kort.rund .box { max-width: calc({$a['bildestr']} * 5.5)!important; }
            .kursagenten-grid.stablet.kort .box { width: 100%; max-width: 100%;  margin: 0 auto; box-sizing: border-box;}
            .kursagenten-grid.stablet.kort .box:has(picture) { max-width: {$a['bildestr']};}
            .kursagenten-grid.skygge.kort .box:hover { -webkit-box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.3); -moz-box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.3); box-shadow: 0px 2px 8px 0px rgba(53, 53, 53, 0.3); transition: transform ease 0.3s, box-shadow ease 0.3s; }
            .kursagenten-grid.rad.beskrivelse .box,
            .kursagenten-grid.rad.utdrag .box,
            .kursagenten-grid.rad:has(.specific-locations) .box { align-items: flex-start; }
            
            /* Box-inner */
            .kursagenten-grid .box-inner { display: flex; align-items: center; text-decoration: none; }
            .kursagenten-grid.stablet .box-inner { justify-content: center; }
            .kursagenten-grid.rad.utdrag .box-inner,
            .kursagenten-grid.rad.beskrivelse .box-inner,
            .kursagenten-grid.rad:has(.specific-locations) .box-inner { align-items: flex-start; }
            
            /* Image */
            .kursagenten-grid a.image { flex-shrink: 0; }
            .kursagenten-grid.rad a.image { max-width: 30vw; height: auto; align-self: stretch; }
            .kursagenten-grid.rad.kort a.image { height: 100%; align-self: stretch; }
            .kursagenten-grid.rad.kort.rund .image.box-inner { padding: .5em }
            .kursagenten-grid.stablet.rund.kort a.image { padding-top: .5em; }
            .kursagenten-grid.liste a.image { display: none; }
            
            /* Text */
            .kursagenten-grid .text {font-size: clamp(0.875rem, 0.75rem + 0.5714vw, 1rem); flex-shrink: 2; flex-grow: 3; line-height: 1.5; padding-left: 1em;}
            .kursagenten-grid .text a.infowrapper { color: #555; }
            .kursagenten-grid:not(.utdrag) .text a { width: 100%; height: 100%; }
            .kursagenten-grid.stablet .text { flex-direction: column; align-items: center; justify-content: flex-start; text-align: center; padding-left: 0; }
            .kursagenten-grid.stablet .text a { padding-top: .5em; }
            .kursagenten-grid.stablet.kort .text { padding: 0.9em .8em 0.9em .8em; }
            .kursagenten-grid.stablet.kort .text:has(picture) { padding: 0.5em .8em 1.3em .8em; }
            .kursagenten-grid.rad .text { flex-direction: column; align-items: flex-start; justify-content: center;}
            .kursagenten-grid.rad.kort .text { padding: 1em 1em 1em 0;}
            .kursagenten-grid.rad .text a.title,
            .kursagenten-grid.rad .text .infowrapper{ width: 100%; display: flex; align-items: center; text-align: left; }
            .kursagenten-grid.rad.beskrivelse .text a.title,
            .kursagenten-grid.rad.beskrivelse .text p { padding: 0px 2em; margin-bottom: 1em; }
            .kursagenten-grid.kort .text a { padding: 1em; }
            .kursagenten-grid.rad.kort .text a,
            .kursagenten-grid.rad.kort:not(.beskrivelse) .description { padding: .3em 1em; }
            .kursagenten-grid.kort .text a.infowrapper { margin-top: -2px; padding-top: 0; align-items: flex-start; }
            .kursagenten-grid.kort.stablet .text a, .kursagenten-grid.kort.stablet .text p { padding: .2em .5em; line-height: 1.4;}
            .kursagenten-grid.liste .description { display: none; }
            .kursagenten-grid:not(.utdrag) .description { display: none; }
            .kursagenten-grid .text:has(.tittel) a { text-decoration: none; }
            .kursagenten-grid .infowrapper ul {list-style: none; margin: 0; padding: 0; }
            .kursagenten-grid .infowrapper .location-item { font-size: 0.9em; }
            
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
            .kursagenten-grid .box:hover img { transform: scale(1.05); }
            .kursagenten-grid.skygge:not(.kort) picture { max-width: calc({$a['bildestr']} + 8px); padding: 8px; }
            .kursagenten-grid.skygge:not(.kort) .box:hover img { -webkit-box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.45); -moz-box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.45); box-shadow: 0px 2px 8px 0px rgba(53, 53, 53, 0.45); transition: transform ease 0.3s, box-shadow ease 0.3s; }
            .kursagenten-grid.stablet.kort img { border-radius: 5px 5px 0 0; }
            .kursagenten-grid.stablet.kort.rund img { border-radius: {$a['bildeform']}; }
            .kursagenten-grid.rad.kort:not(.beskrivelse) img { border-radius: 5px 0 0 5px; }
            
            .kursagenten-grid .tittel { margin: 0; font-size: {$a['fontstr']}; }
            
            /* Responsive */
            @media all and (max-width: 1100px) {
                .kursagenten-grid .wrapper { grid-template-columns: repeat({$a['gridtablet']}, minmax(0, 1fr)); }
                .kursagenten-grid.stablet.kort .wrapper { max-width: calc(({$a['bildestr']} * {$a['gridtablet']}) + (clamp(1vw, 2vw, 2rem) * ({$a['gridtablet']} - 1 )));}
                .kursagenten-grid.kort.rund .wrapper { max-width: calc((calc({$a['bildestr']} * 5.5) * {$a['gridtablet']}) + (clamp(1vw, 2vw, 2rem) * ({$a['gridtablet']} - 1 )));}
            }
            
            @media all and (max-width: 630px) {
                .kursagenten-grid .wrapper {  grid-template-columns: repeat({$a['gridmobil']}, 1fr); }
                .kursagenten-grid.rad:not(.utdrag) .wrapper .box { align-items: center; } 
                .kursagenten-grid.rad:not(.kort) .text a.title { padding: .8em; margin-bottom: 0; align-items: flex-start; }
                .kursagenten-grid.rad:not(.kort) .text p { padding: 0; margin-bottom: 12px; }
                .kursagenten-grid.rad:not(.kort) picture { max-width: 20vw; }
                .kursagenten-grid.rad.kort .text a,
                .kursagenten-grid.rad.kort:not(.beskrivelse) .description { padding-left: 0.8em; }
                .kursagenten-grid.rad.skygge .text { padding-left: 8px; }
                .kursagenten-grid.rad.beskrivelse .wrapper .box { flex-direction: column; }
                .kursagenten-grid.rad.beskrivelse .box .text a.title,
                .kursagenten-grid.rad.beskrivelse .box .text p { padding: 0 0 0 1.5em; }
                .kursagenten-grid.rad.beskrivelse .box .text p { margin-bottom: .7em; }
                .kursagenten-grid.rad.beskrivelse picture {max-width: 100%; }
                .kursagenten-grid.rad.beskrivelse.kort picture {border-radius: {$a['bildeform']} {$a['bildeform']} 0 0; }
                .kursagenten-grid.rad.beskrivelse a.image { max-width: 100%; max-height: 50vw; }
            }
            @media all and (max-width: 370px) {
                .kursagenten-grid.kort.rund .wrapper { max-width: calc((calc({$a['bildestr']} * 5.5) * {$a['gridmobil']}) + (clamp(1vw, 2vw, 2rem) * ({$a['gridmobil']} - 1 )));}
                .kursagenten-grid.rad picture,
                .kursagenten-grid.rad.kort:not(.beskrivelse) picture  { max-width: 20vw;}
                .kursagenten-grid.rad a.image { max-width: 100%; max-height: 50vw; }
                .kursagenten-grid.rad.kort.rund img { border-radius: {$a['bildeform']}; }
            }
        </style>";
    }
    
    /**
     * Genererer ID-spesifikke grid-stiler (deprecated - bruk get_common_grid_styles + ID-spesifikke stiler)
     */
    public static function get_grid_styles(string $id, array $a): string {
        $class_id = "#" . $id;
        
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
            {$class_id} .wrapper {
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
            {$class_id}.stablet.kort .wrapper { justify-items: center; max-width: calc(({$a['bildestr']} * {$a['grid']}) + (clamp(1vw, 2vw, 2rem) * ({$a['grid']} - 1 )));}
            {$class_id}.kort.rund .wrapper { max-width: calc((calc({$a['bildestr']} * 5.5) * {$a['grid']}) + (clamp(1vw, 2vw, 2rem) * ({$a['grid']} - 1 )));}
            {$class_id}.liste .wrapper { grid-template-columns: repeat(1, 1fr); row-gap: " . (!empty($a['_radavstand_provided']) ? $a['radavstand'] : '0.6em') . "; width: auto; }
            {$class_id}.rad.utdrag .wrapper { row-gap: 3rem; }
            




            /* Box */
            {$class_id}.rad .wrapper .box { display: flex; column-gap: 0; max-width: 100%; align-self: start; height: auto; }
            {$class_id}.kort .box { border-radius: 5px;  max-width: {$a['bildestr']}; width: 100%; background-color: #fff; -webkit-box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.1); -moz-box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.1); box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.1); align-self: start; height: auto; }
            {$class_id}.stablet .wrapper .box { align-self: start; height: auto; }
            {$class_id}.kort.rund .box { max-width: calc({$a['bildestr']} * 5.5)!important; }
            /*{$class_id}.rad.kort .box { max-width: calc({$a['bildestr']} * 4); }*/
            {$class_id}.stablet.kort .box { width: 100%; max-width: 100%;  margin: 0 auto; box-sizing: border-box;}
            {$class_id}.stablet.kort .box:has(picture) { max-width: {$a['bildestr']};}
            {$class_id}.skygge.kort .box:hover { -webkit-box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.3); -moz-box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.3); box-shadow: 0px 2px 8px 0px rgba(53, 53, 53, 0.3); transition: transform ease 0.3s, box-shadow ease 0.3s; }
            {$class_id}.rad.beskrivelse .box,
            {$class_id}.rad.utdrag .box,
            {$class_id}.rad:has(.specific-locations) .box { align-items: flex-start; } 


    
            /* Box-inner */
            {$class_id} .box-inner { display: flex; align-items: center; text-decoration: none; }
            {$class_id}.stablet .box-inner { justify-content: center; }
            {$class_id}.rad.beskrivelse .box-inner,
            {$class_id}.rad.utdrag .box-inner,
            {$class_id}.rad:has(.specific-locations) .box-inner { align-items: flex-start; }
            



            /* Image */
            {$class_id} a.image { flex-shrink: 0; }
            {$class_id}.rad a.image { max-width: 30vw; height: auto; align-self: stretch; }
            {$class_id}.rad.kort a.image { height: 100%; align-self: stretch; }
            {$class_id}.rad.kort.rund .image.box-inner { padding: .5em }
            {$class_id}.stablet.rund.kort a.image { padding-top: .5em; }
            {$class_id}.liste a.image { display: none; }
            



            /* Text */
            {$class_id} .text {font-size: clamp(0.875rem, 0.75rem + 0.5714vw, 1rem); flex-shrink: 2; flex-grow: 3; line-height: 1.5; padding-left: 1em;}
            {$class_id} .text a.infowrapper { color: #555; }
            {$class_id}:not(.utdrag) .text a { width: 100%; height: 100%; }
            {$class_id}.stablet .text { flex-direction: column; align-items: center; justify-content: flex-start; text-align: center; padding-left: 0; }
            {$class_id}.stablet .text a { padding-top: .5em; }
            {$class_id}.stablet.kort .text { padding: 0.9em .8em 0.9em .8em; }
            {$class_id}.stablet.kort .text:has(picture) { padding: 0.5em .8em 1.3em .8em; }
            {$class_id}.rad .text { flex-direction: column; align-items: flex-start; justify-content: center;}
            {$class_id}.rad.kort .text { padding: 1em 1em 1em 0;}
            {$class_id}.rad .text a.title,
            {$class_id}.rad .text .infowrapper{ width: 100%; display: flex; align-items: center; text-align: left; }
            /*{$class_id}.rad .text a.title,
            {$class_id}.rad:not(.beskrivelse) .text .info{ padding-left: 1em;}*/
            {$class_id}.rad.beskrivelse .text a.title,
            {$class_id}.rad.beskrivelse .text p { padding: 0px 2em; margin-bottom: 1em; }
            {$class_id}.kort .text a { padding: 1em; }
            {$class_id}.rad.kort .text a,
            {$class_id}.rad.kort:not(.beskrivelse) .description { padding: .3em 1em; }
            {$class_id}.kort .text a.infowrapper { margin-top: -2px; padding-top: 0; align-items: flex-start; }
            {$class_id}.kort.stablet .text a, {$class_id}.kort.stablet .text p { padding: .2em .5em; line-height: 1.4;}
            {$class_id}.liste .description { display: none; }
            {$class_id}:not(.utdrag) .description { display: none; }
            {$class_id} .text:has(.tittel) a { text-decoration: none; }
            {$class_id} .infowrapper ul {list-style: none; margin: 0; padding: 0; }
            {$class_id} .infowrapper .location-item { font-size: 0.9em; }
            





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
            {$class_id} .box:hover img { transform: scale(1.05); }
            {$class_id}.skygge:not(.kort) picture { max-width: calc({$a['bildestr']} + 8px); padding: 8px; }
            {$class_id}.skygge:not(.kort) .box:hover img { -webkit-box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.45); -moz-box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.45); box-shadow: 0px 2px 8px 0px rgba(53, 53, 53, 0.45); transition: transform ease 0.3s, box-shadow ease 0.3s; }
            {$class_id}.stablet.kort img { border-radius: 5px 5px 0 0; }
            {$class_id}.stablet.kort.rund img { border-radius: {$a['bildeform']}; }
            {$class_id}.rad.kort:not(.beskrivelse) img { border-radius: 5px 0 0 5px; }
            




            /* Font */
            {$class_id} .tittel { margin: 0; font-size: {$a['fontstr']}; }
            





            /* Responsive */
            @media all and (max-width: 1100px) {
                {$class_id} .wrapper { grid-template-columns: repeat({$a['gridtablet']}, minmax(0, 1fr)); }
                {$class_id}.stablet.kort .wrapper { max-width: calc(({$a['bildestr']} * {$a['gridtablet']}) + (clamp(1vw, 2vw, 2rem) * ({$a['gridtablet']} - 1 )));}
                {$class_id}.kort.rund .wrapper { max-width: calc((calc({$a['bildestr']} * 5.5) * {$a['gridtablet']}) + (clamp(1vw, 2vw, 2rem) * ({$a['gridtablet']} - 1 )));}
            }
            
            @media all and (max-width: 630px) {
                {$class_id} .wrapper {  grid-template-columns: repeat({$a['gridmobil']}, 1fr); }
                {$class_id}.rad:not(.utdrag) .wrapper .box { align-items: center; } 
                /*{$class_id}.rad:not(.kort) .wrapper .box { flex-direction: column; }*/
                {$class_id}.rad:not(.kort) .text a.title { padding: .8em; margin-bottom: 0; align-items: flex-start; }
                {$class_id}.rad:not(.kort) .text p { padding: 0; margin-bottom: 12px; }
                {$class_id}.rad:not(.kort) picture { max-width: 20vw; }
                {$class_id}.rad.kort .text a,
                {$class_id}.rad.kort:not(.beskrivelse) .description { padding-left: 0.8em; }
                {$class_id}.rad.skygge .text { padding-left: 8px; }
                {$class_id}.rad.beskrivelse .wrapper .box { flex-direction: column; }
                {$class_id}.rad.beskrivelse .box .text a.title,
                {$class_id}.rad.beskrivelse .box .text p { padding: 0 0 0 1.5em; }
                {$class_id}.rad.beskrivelse .box .text p { margin-bottom: .7em; }
                {$class_id}.rad.beskrivelse picture {max-width: 100%; }
                {$class_id}.rad.beskrivelse.kort picture {border-radius: {$a['bildeform']} {$a['bildeform']} 0 0; }
                {$class_id}.rad.beskrivelse a.image { max-width: 100%; max-height: 50vw; }
            }
            @media all and (max-width: 370px) {
                /*{$class_id}.rad.kort .wrapper { max-width: calc(({$a['bildestr']} * {$a['gridmobil']}) + (clamp(1vw, 2vw, 2rem) * ({$a['gridmobil']} - 1 )));}*/
                {$class_id}.kort.rund .wrapper { max-width: calc((calc({$a['bildestr']} * 5.5) * {$a['gridmobil']}) + (clamp(1vw, 2vw, 2rem) * ({$a['gridmobil']} - 1 )));}
                /*{$class_id}.rad.kort .wrapper .box { flex-direction: column; width: 100%; max-width: {$a['bildestr']};  margin: 0 auto; box-sizing: border-box;}*/
                /*{$class_id}.rad.kort picture { padding: 0; width: 100%; max-width: {$a['bildestr']};}*/
                {$class_id}.rad picture,
                {$class_id}.rad.kort:not(.beskrivelse) picture  { max-width: 20vw;}
                {$class_id}.rad a.image { max-width: 100%; max-height: 50vw; }
                /*{$class_id}.rad.kort img { border-radius: 5px 5px 0 0; }*/
                {$class_id}.rad.kort.rund img { border-radius: {$a['bildeform']}; }
                /*{$class_id}.rad.kort .text { flex-direction: column; align-items: flex-start; justify-content: center; padding: .3em 0 .3em 0;}*/
                /*{$class_id}.rad .text a.title, {$class_id}.rad .text .info { text-align: center; justify-content: center;}*/
            }
        </style>";
    }
} 
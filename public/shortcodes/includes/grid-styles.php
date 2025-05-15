<?php
declare(strict_types=1);

if (!defined('ABSPATH')) exit;

class GridStyles {
    /**
     * Genererer felles grid-basert CSS for shortcodes
     */
    public static function get_grid_styles(string $id, array $a): string {
        $class_id = "#" . $id;
        
        return "<style>
            /* Outer Wrapper */
            {$class_id}.outer-wrapper { padding: {$a['avstand']}; width: 100%; box-sizing: border-box;}
            
            /* Wrapper */
            {$class_id} .wrapper {
                display: grid;
                grid-template-columns: repeat({$a['grid']}, minmax(0, 1fr));
                column-gap: clamp(1vw, 2vw, 2rem);
                row-gap: {$a['radavstand']};
                width: 100%;
                margin: 0 auto;
                box-sizing: border-box;
            }
            {$class_id}.stablet.kort .wrapper { justify-items: center; max-width: calc(({$a['bildestr']} * {$a['grid']}) + (clamp(1vw, 2vw, 2rem) * ({$a['grid']} - 1 )));}
            {$class_id}.kort.rund .wrapper { max-width: calc((calc({$a['bildestr']} * 5.5) * {$a['grid']}) + (clamp(1vw, 2vw, 2rem) * ({$a['grid']} - 1 )));}
            {$class_id}.liste .wrapper { grid-template-columns: repeat(1, 1fr); row-gap: 0.6em; width: auto; }
            {$class_id}.rad.utdrag .wrapper { row-gap: 3rem; }
            
            /* Box */
            {$class_id}.rad .wrapper .box { display: flex; column-gap: 0; max-width: 100%; }
            {$class_id}.kort .box { border-radius: 5px;  max-width: {$a['bildestr']}; width: 100%; background-color: #fff; -webkit-box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.1); -moz-box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.1); box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.1); }
            {$class_id}.kort.rund .box { max-width: calc({$a['bildestr']} * 5.5)!important; }
            {$class_id}.rad.kort .box { max-width: calc({$a['bildestr']} * 4); }
            {$class_id}.stablet.kort .box { width: 100%; max-width: {$a['bildestr']};  margin: 0 auto; box-sizing: border-box;}
            {$class_id}.skygge.kort .box:hover { -webkit-box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.3); -moz-box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.3); box-shadow: 0px 2px 8px 0px rgba(53, 53, 53, 0.3); transition: transform ease 0.3s, box-shadow ease 0.3s; }
            
            /* Box-inner */
            {$class_id} .box-inner { display: flex; text-decoration: none; }
            {$class_id}.stablet .box-inner { justify-content: center; }
            {$class_id}.rad.kort.rund .box-inner { align-items: center; }
            
            /* Image */
            {$class_id} a.image { flex-shrink: 0; }
            {$class_id}.rad a.image { max-width: 30vw; }
            {$class_id}.rad.kort.rund .image.box-inner { padding: .5em }
            {$class_id}.stablet.rund.kort a.image { padding-top: .5em; }
            {$class_id}.liste a.image { display: none; }
            
            /* Text */
            {$class_id} .text {font-size: clamp(0.875rem, 0.75rem + 0.5714vw, 1rem); flex-shrink: 2; flex-grow: 3; line-height: 1.5;}
            {$class_id} .text a.info { color: #555; }
            {$class_id}:not(.utdrag) .text a { width: 100%; height: 100%; }
            {$class_id}.stablet .text { flex-direction: column; align-items: center; justify-content: flex-start; text-align: center; }
            {$class_id}.stablet .text a { padding-top: .5em; }
            {$class_id}.stablet.kort .text { padding: 0.5em .8em 1.3em .8em; }
            {$class_id}.rad .text { flex-direction: column; align-items: flex-start; justify-content: center;}
            {$class_id}.rad.kort .text { padding: 1em 1em 1em 0;}
            {$class_id}.rad .text a.title,
            {$class_id}.rad .text .info { width: 100%; padding-left: .8em; display: flex; align-items: center; text-align: left; }
            {$class_id}.rad.utdrag .text a.title,
            {$class_id}.rad.utdrag .text p { padding: 0px 2em; margin-bottom: 1em; }
            {$class_id}.kort .text a { padding: 1em; }
            {$class_id}.rad.kort .text a { padding: 1em; }
            {$class_id}.kort .text a.info { margin-top: -.8em; padding-top: 0; align-items: flex-start; }
            {$class_id}.kort.stablet .text a, {$class_id}.kort.stablet .text p { padding: .5em; line-height: 1.4;}
            {$class_id}.liste .description { display: none; }
            {$class_id}:not(.utdrag) .description { display: none; }
            {$class_id} .text:has(.tittel) a { text-decoration: none; }
            {$class_id} .info ul {list-style: none; margin: 0; padding: 0; }
            {$class_id} .info .location-item { font-size: 0.9em; }
            
            /* Picture and Image */
            {$class_id} picture {aspect-ratio: {$a['bildeformat']}; border-radius: {$a['bildeform']}; display: block;  width:100%; max-width:{$a['bildestr']}; overflow: hidden; }
            {$class_id}.rund picture { aspect-ratio: 1/1; }
            {$class_id}.stablet.kort picture { padding: 0; width: 100%; max-width: {$a['bildestr']};}
            {$class_id}.rad.kort picture { max-width: calc({$a['bildestr']} * 0.85); padding: 0; }
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
            {$class_id}.rad.kort img { border-radius: 5px 0 0 5px; }
            
            /* Font */
            {$class_id} .tittel { margin: 0; font-size: {$a['fontstr']}; }
            
            /* Responsive */
            @media all and (max-width: 1100px) {
                {$class_id} .wrapper { grid-template-columns: repeat({$a['gridtablet']}, minmax(0, 1fr)); }
                {$class_id}.stablet.kort .wrapper { max-width: calc(({$a['bildestr']} * {$a['gridtablet']}) + (clamp(1vw, 2vw, 2rem) * ({$a['gridtablet']} - 1 )));}
                {$class_id}.kort.rund .wrapper { max-width: calc((calc({$a['bildestr']} * 5.5) * {$a['gridtablet']}) + (clamp(1vw, 2vw, 2rem) * ({$a['gridtablet']} - 1 )));}
            }
            
            @media all and (max-width: 530px) {
                {$class_id} .wrapper {  grid-template-columns: repeat({$a['gridmobil']}, 1fr); }
                {$class_id}.rad:not(.utdrag) .wrapper .box { align-items: center; } 
                {$class_id}.rad:not(.kort) .wrapper .box { flex-direction: column; }
                {$class_id}.rad:not(.kort) .text a.title { padding: .6em 0; margin-bottom: 0; text-align: center; align-items: flex-start; }
                {$class_id}.rad:not(.kort) .text p { padding: 0; margin-bottom: 12px; }
                {$class_id}.rad.kort .text a { padding-left: 0; }
                {$class_id}.rad.skygge .text { padding-left: 8px; }
                {$class_id}.rad.utdrag .text a.title,
                {$class_id}.rad.utdrag .text p { padding: 0px 1.2em; }
            }
            @media all and (max-width: 370px) {
                {$class_id}.rad.kort .wrapper { max-width: calc(({$a['bildestr']} * {$a['gridmobil']}) + (clamp(1vw, 2vw, 2rem) * ({$a['gridmobil']} - 1 )));}
                {$class_id}.kort.rund .wrapper { max-width: calc((calc({$a['bildestr']} * 5.5) * {$a['gridmobil']}) + (clamp(1vw, 2vw, 2rem) * ({$a['gridmobil']} - 1 )));}
                {$class_id}.rad.kort .wrapper .box { flex-direction: column; width: 100%; max-width: {$a['bildestr']};  margin: 0 auto; box-sizing: border-box;}
                {$class_id}.rad.kort picture { padding: 0; width: 100%; max-width: {$a['bildestr']};}
                {$class_id}.rad a.image { max-width: 100%; max-height: 50vw; }
                {$class_id}.rad.kort img { border-radius: 5px 5px 0 0; }
                {$class_id}.rad.kort.rund img { border-radius: {$a['bildeform']}; }
                {$class_id}.rad.kort .text { flex-direction: column; align-items: flex-start; justify-content: center; padding: .3em 0 .3em 0;}
                {$class_id}.rad .text a.title, {$class_id}.rad .text .info { text-align: center; justify-content: center;}
            }
        </style>";
    }
} 
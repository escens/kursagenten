<?php
declare(strict_types=1);

if (!defined('ABSPATH')) exit;

class BlocksShortcodeStyles {
    private $id;
    private $config;
    
    public function __construct(string $id, array $config) {
        $this->id = $id;
        $this->config = wp_parse_args($config, [
            'grid' => 3,
            'gridtablet' => 2,
            'gridmobil' => 1,
            'bildestr' => '300px',
            'bildeformat' => '16/9',
            'bildeform' => '0',
            'fontmin' => '14',    // i px
            'fontmaks' => '18',   // i px
            'avstand' => '0'
        ]);

        // Kalkuler font størrelse
        $base_font_size = 16;
        $min_rem = floatval($this->config['fontmin']) / $base_font_size;
        $max_rem = floatval($this->config['fontmaks']) / $base_font_size;
        $this->config['fontstr'] = "clamp({$min_rem}rem, 3.5vw - 0.219rem, {$max_rem}rem)";
    }
    
    private function get_base_styles(): string {
        $class_id = "#" . $this->id;
        return "
            /* Outer Wrapper */
            {$class_id}.outer-wrapper {
                padding: {$this->config['avstand']};
            }
            
            /* Wrapper */
            {$class_id} .wrapper {
                display: grid;
                grid-template-columns: repeat({$this->config['grid']}, 1fr);
                column-gap: clamp(1vw, 2vw, 2rem);
                row-gap: 1rem;
                width: fit-content;
                margin: 0 auto;
            }
            {$class_id}.stablet .wrapper { justify-items: center; }
            {$class_id}.liste .wrapper { grid-template-columns: repeat(1, 1fr); row-gap: 0.6em; width: auto; }
            {$class_id}.rad.utdrag .wrapper { row-gap: 3rem; }
            
            /* Box */
            {$class_id}.rad .wrapper .box { display: flex; column-gap: 0; max-width: 100%; }
            {$class_id}.kort .box {
                border-radius: 5px;
                max-width: {$this->config['bildestr']};
                width: 100%;
                background-color: #fff;
                -webkit-box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.1);
                -moz-box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.1);
                box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.1);
            }
            {$class_id}.kort.rund .box { max-width: calc({$this->config['bildestr']} * 2.5); }
            {$class_id}.rad.kort .box { max-width: calc({$this->config['bildestr']} * 4); }
            {$class_id}.skygge.kort .box:hover {
                -webkit-box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.3);
                -moz-box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.3);
                box-shadow: 0px 2px 8px 0px rgba(53, 53, 53, 0.3);
                transition: transform ease 0.3s, box-shadow ease 0.3s;
            }
            
            /* Box-inner */
            {$class_id} .box-inner { display: flex; text-decoration: none; }
            {$class_id}.stablet .box-inner { justify-content: center; }
            
            /* Image */
            {$class_id} a.image { flex-shrink: 0; }
            {$class_id}.rad a.image { max-width: 30vw; max-height: 30vw; }
            {$class_id}.stablet.rund.kort a.image { padding-top: .5em; }
            {$class_id}.liste a.image { display: none; }
            
            /* Text */
            {$class_id} .text {
                font-size: {$this->config['fontstr']};
                flex-shrink: 2;
                flex-grow: 3;
            }
            {$class_id}:not(.utdrag) .text a { width: 100%; height: 100%; }
            {$class_id}.stablet .text {
                flex-direction: column;
                align-items: center;
                justify-content: flex-start;
                text-align: center;
            }
            {$class_id}.stablet .text a { padding-top: .5em; }
            {$class_id}.rad .text {
                flex-direction: column;
                align-items: flex-start;
                justify-content: center;
            }
            {$class_id}.rad .text a.title {
                padding-left: .8em;
                display: flex;
                align-items: center;
                text-align: left;
            }
            {$class_id}.rad.utdrag .text a.title,
            {$class_id}.rad.utdrag .text p {
                padding: 0px 2em;
                margin-bottom: 1em;
            }
            {$class_id}.kort .text a { padding: 1em; }
            {$class_id}.liste .description { display: none; }
            {$class_id}:not(.utdrag) .description { display: none; }
            {$class_id} .text:has(.tittel) a { text-decoration: none; }
            
            /* Picture and Image */
            {$class_id} picture {
                aspect-ratio: {$this->config['bildeformat']};
                display: block;
                max-width: calc({$this->config['bildestr']});
                overflow: hidden;
            }
            {$class_id}.rund picture { aspect-ratio: 1/1; }
            {$class_id}.stablet.kort picture { padding: 0; }
            {$class_id}.rad.kort picture {
                max-width: calc({$this->config['bildestr']} * 0.85);
                padding: 0;
            }
            
            {$class_id} img {
                width: 100%;
                height: 100%;
                max-width: 100%;
                object-fit: cover;
                object-position: center;
                border-radius: {$this->config['bildeform']};
            }
            {$class_id}.skygge:not(.kort) picture {
                max-width: calc({$this->config['bildestr']} + 8px);
                padding: 8px;
            }
            {$class_id}.skygge:not(.kort) .box:hover img {
                -webkit-box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.45);
                -moz-box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.45);
                box-shadow: 0px 2px 8px 0px rgba(53, 53, 53, 0.45);
                transition: transform ease 0.3s, box-shadow ease 0.3s;
            }
            {$class_id}.stablet.kort img { border-radius: 5px 5px 0 0; }
            {$class_id}.stablet.kort.rund img { border-radius: {$this->config['bildeform']}; }
            {$class_id}.rad.kort img { border-radius: 5px 0 0 5px; }
            
            /* Font */
            {$class_id} .tittel {
                margin: 0;
                font-size: {$this->config['fontstr']};
            }
        ";
    }
    
    private function get_responsive_styles(): string {
        $class_id = "#" . $this->id;
        return "
            /* Responsive */
            @media all and (max-width: 1100px) {
                {$class_id} .wrapper {
                    grid-template-columns: repeat({$this->config['gridtablet']}, 1fr);
                }
            }
            
            @media all and (max-width: 530px) {
                {$class_id} .wrapper {
                    grid-template-columns: repeat({$this->config['gridmobil']}, 1fr);
                }
                {$class_id}.rad:not(.utdrag) .wrapper .box { align-items: center; }
                {$class_id}.rad:not(.kort) .wrapper .box { flex-direction: column; }
                {$class_id}.rad:not(.kort) .text a.title {
                    padding: .6em 0;
                    margin-bottom: 0;
                    text-align: center;
                    align-items: flex-start;
                }
                {$class_id}.rad:not(.kort) .text p {
                    padding: 0;
                    margin-bottom: 12px;
                }
                {$class_id}.rad.kort .text a { padding-left: 0; }
                {$class_id}.rad.skygge .text { padding-left: 8px; }
            }
        ";
    }
    
    public function get_styles(): string {
        return "<style>
            " . $this->get_base_styles() . "
            " . $this->get_responsive_styles() . "
        </style>";
    }
    
    public static function render(string $id, array $config): string {
        $instance = new self($id, $config);
        return $instance->get_styles();
    }
} 
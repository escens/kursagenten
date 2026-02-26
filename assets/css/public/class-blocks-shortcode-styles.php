<?php
declare(strict_types=1);

if (!defined('ABSPATH')) exit;

require_once dirname(__FILE__, 3) . '/public/shortcodes/includes/grid-styles.php';

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

        // Kalkuler font størrelse (bruker --ka-base-font, uavhengig av temaets root)
        $this->config['fontstr'] = \GridStyles::build_font_clamp($this->config['fontmin'], $this->config['fontmaks']);
    }
    
    private function get_base_styles(): string {
        $class_id = "#" . $this->id;
        return "
            /* Outer Wrapper */
            {$class_id}.outer-wrapper {
                padding: {$this->config['avstand']};
            }
            
            /* Wrapper */
            {$class_id} .k-wrapper {
                display: grid;
                grid-template-columns: repeat({$this->config['grid']}, 1fr);
                column-gap: clamp(1vw, 2vw, 2rem);
                row-gap: 1rem;
                width: fit-content;
                margin: 0 auto;
            }
            {$class_id}.stablet .k-wrapper { justify-items: center; }
            {$class_id}.liste .k-wrapper { grid-template-columns: repeat(1, 1fr); row-gap: 0.6em; width: auto; }
            {$class_id}.rad.utdrag .k-wrapper { row-gap: 3rem; }
            
            /* Box */
            {$class_id}.rad .k-wrapper .k-box { display: flex; column-gap: 0; max-width: 100%; }
            {$class_id}.kort .k-box {
                border-radius: 5px;
                max-width: {$this->config['bildestr']};
                width: 100%;
                background-color: #fff;
                -webkit-box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.1);
                -moz-box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.1);
                box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.1);
            }
            {$class_id}.kort.rund .k-box { max-width: calc({$this->config['bildestr']} * 2.5); }
            {$class_id}.rad.kort .k-box { max-width: calc({$this->config['bildestr']} * 4); }
            {$class_id}.skygge.kort .k-box:hover {
                -webkit-box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.3);
                -moz-box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.3);
                box-shadow: 0px 2px 8px 0px rgba(53, 53, 53, 0.3);
                transition: transform ease 0.3s, box-shadow ease 0.3s;
            }
            
            /* Box-inner */
            {$class_id} .k-box-inner { display: flex; text-decoration: none; }
            {$class_id}.stablet .k-box-inner { justify-content: center; }
            
            /* Image */
            {$class_id} a.k-image { flex-shrink: 0; }
            {$class_id}.rad a.k-image { max-width: 30vw; max-height: 30vw; }
            {$class_id}.stablet.rund.kort a.k-image { padding-top: .5em; }
            {$class_id}.liste a.k-image { display: none; }
            
            /* Text */
            {$class_id} .k-text {
                font-size: {$this->config['fontstr']};
                flex-shrink: 2;
                flex-grow: 3;
            }
            {$class_id}:not(.utdrag) .k-text a { width: 100%; height: 100%; }
            {$class_id}.stablet .k-text {
                flex-direction: column;
                align-items: center;
                justify-content: flex-start;
                text-align: center;
            }
            {$class_id}.stablet .k-text a { padding-top: .5em; }
            {$class_id}.rad .k-text {
                flex-direction: column;
                align-items: flex-start;
                justify-content: center;
            }
            {$class_id}.rad .k-text a.k-title {
                padding-left: .8em;
                display: flex;
                align-items: center;
                text-align: left;
            }
            {$class_id}.rad.utdrag:not(.kort) .k-text a.k-title,
            {$class_id}.rad.utdrag:not(.kort) .k-text p {
                padding: 0px 2em;
                margin-bottom: 1em;
            }
            {$class_id}.kort .k-text a { padding: 1em; }
            {$class_id}.liste .k-description { display: none; }
            {$class_id}:not(.utdrag) .k-description { display: none; }
            {$class_id} .k-text:has(.k-tittel) a { text-decoration: none; }
            
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
            {$class_id}.skygge:not(.kort) .k-box:hover img {
                -webkit-box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.45);
                -moz-box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.45);
                box-shadow: 0px 2px 8px 0px rgba(53, 53, 53, 0.45);
                transition: transform ease 0.3s, box-shadow ease 0.3s;
            }
            {$class_id}.stablet.kort img { border-radius: 5px 5px 0 0; }
            {$class_id}.stablet.kort.rund img { border-radius: {$this->config['bildeform']}; }
            {$class_id}.rad.kort img { border-radius: 5px 0 0 5px; }
            
            /* Font */
            {$class_id} .k-tittel {
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
                {$class_id} .k-wrapper {
                    grid-template-columns: repeat({$this->config['gridtablet']}, 1fr);
                }
            }
            
            @media all and (max-width: 530px) {
                {$class_id} .k-wrapper {
                    grid-template-columns: repeat({$this->config['gridmobil']}, 1fr);
                }
                {$class_id}.rad:not(.utdrag) .k-wrapper .k-box { align-items: center; }
                {$class_id}.rad:not(.kort) .k-wrapper .k-box { flex-direction: column; }
                {$class_id}.rad:not(.kort) .k-text a.k-title {
                    padding: .6em 0;
                    margin-bottom: 0;
                    text-align: center;
                    align-items: flex-start;
                }
                {$class_id}.rad:not(.kort) .k-text p {
                    padding: 0;
                    margin-bottom: 12px;
                }
                {$class_id}.rad.kort .k-text a { padding-left: 0; }
                {$class_id}.rad.skygge .k-text { padding-left: 8px; }
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
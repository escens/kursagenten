<?php
/**
 * Full-bredde layout-wrapper med moderne design
 */

if (!defined('ABSPATH')) exit;

get_header();

// Hent variabler fra rammeverket
global $query, $top_filters, $left_filters, $filter_types, $available_filters, 
       $has_left_filters, $left_column_class, $is_search_only, $search_class, 
       $taxonomy_data, $filter_display_info;

// Hent bakgrunnsbilde for header (kan tilpasses)
$header_image = get_option('kursagenten_header_image', '');
if (empty($header_image)) {
    $header_image = KURSAGENTEN_URL . 'assets/images/default-header.jpg';
}
?>

<main id="ka-main" class="kursagenten-archive-course kursagenten-modern" role="main">
    <!-- Full-bredde header med bakgrunnsbilde -->
    <header class="ka-hero-header" style="background-image: url('<?php echo esc_url($header_image); ?>');">
        <div class="ka-hero-overlay">
            <div class="ka-content-container">
                <div class="ka-hero-content">
                    <h1><?php post_type_archive_title(); ?></h1>
                    <p class="ka-hero-description">Finn det perfekte kurset for deg og din bedrift</p>
                    
                    <!-- Søkefelt i header -->
                    <div class="ka-hero-search">
                        <input type="text" id="hero-search" name="hero-search" class="filter-search hero-search" placeholder="Søk etter kurs...">
                        <button type="button" class="ka-search-button">
                            <i class="ka-icon icon-search"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <section class="ka-section ka-main-content ka-courselist">
        <!-- Filter-seksjonen -->
        <div class="ka-filter-bar">
            <div class="ka-content-container">
                <div class="filter-container filter-top">
                    <!-- Dynamisk filtergenerering -->
                    <?php foreach ($top_filters as $filter) : ?>
                        <div class="filter-item <?php echo esc_attr($filter_types[$filter] ?? ''); ?> <?php echo esc_attr($search_class); ?>">
                            <?php if ($filter === 'search') : ?>
                                <!-- Skjul søk hvis vi allerede har det i header -->
                                <div class="filter-search-small">
                                    <input type="text" id="search" name="search" class="filter-search <?php echo esc_attr($search_class); ?>" placeholder="Søk etter kurs...">
                                </div>
                            <?php elseif ($filter === 'date') : ?>
                                <?php
                                $date = "";
                                if (isset($_REQUEST['dato'])) {
                                    $dates = explode('-', $_REQUEST['dato']);
                                    if (count($dates) === 2) {
                                        $from_date = ka_format_date(\DateTime::createFromFormat('d.m.Y', trim($dates[0]))->format('Y-m-d'));
                                        $to_date = ka_format_date(\DateTime::createFromFormat('d.m.Y', trim($dates[1]))->format('Y-m-d'));
                                        $date = sprintf('%s - %s', $from_date, $to_date);
                                    }
                                }
                                
                                $is_left_filter = in_array('date', $left_filters);
                                $caleran_class = $is_left_filter ? 'caleran caleran-left' : 'caleran';
                                ?>
                                <div class="date-range-wrapper modern-date-picker">
                                    <input type="text" 
                                           id="date-range" 
                                           class="<?php echo esc_attr($caleran_class); ?>"
                                           data-filter-key="date"
                                           data-url-key="dato"
                                           name="calendar-input"
                                           placeholder="Velg fra-til dato"
                                           value="<?php echo esc_attr($date); ?>"
                                           aria-label="Velg datoer">
                                    <i class="ka-icon icon-calendar"></i>
                                </div>
                            <?php elseif (!empty($taxonomy_data[$filter]['terms'])) : ?>
                                <?php if ($filter_types[$filter] === 'chips') : ?>
                                    <!-- Chip-style Filter Display -->
                                    <div class="filter-chip-wrapper modern-chips">
                                        <?php foreach ($taxonomy_data[$filter]['terms'] as $term) : ?>
                                            <button class="chip filter-chip"
                                                data-filter-key="<?php echo esc_attr($taxonomy_data[$filter]['filter_key']); ?>"
                                                data-url-key="<?php echo esc_attr($taxonomy_data[$filter]['url_key']); ?>"
                                                data-filter="<?php echo esc_attr(is_object($term) ? $term->slug : strtolower($term)); ?>">
                                                <?php echo esc_html(is_object($term) ? $term->name : ucfirst($term)); ?>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                <?php elseif ($filter_types[$filter] === 'list') : ?>
                                    <!-- List-style Filter Display -->
                                    <div id="filter-list-<?php echo esc_attr($taxonomy_data[$filter]['filter_key']); ?>" class="filter modern-filter">
                                        <div class="filter-dropdown">
                                            <?php
                                            // Get filter information from prepared array
                                            $current_filter_info = $filter_display_info[$filter] ?? [];
                                            $filter_label = $current_filter_info['label'] ?? '';
                                            $filter_placeholder = $current_filter_info['placeholder'] ?? 'Velg';

                                            // Get active filters from URL parameters
                                            $url_key = $taxonomy_data[$filter]['url_key'];
                                            $active_filters = isset($_GET[$url_key]) ? explode(',', $_GET[$url_key]) : [];

                                            // Generate display text based on active filters
                                            if (empty($active_filters)) {
                                                $display_text = $filter_placeholder;
                                            } else {
                                                $active_names = [];
                                                foreach ($active_filters as $slug) {
                                                    if ($filter === 'language') {
                                                        $active_names[] = ucfirst($slug);
                                                    } else {
                                                        foreach ($taxonomy_data[$filter]['terms'] as $term) {
                                                            if (is_object($term) && ($filter === 'months' ? $term->value : $term->slug) === $slug) {
                                                                $active_names[] = $term->name;
                                                                break;
                                                            }
                                                        }
                                                    }
                                                }

                                                $display_text = count($active_names) <= 2 ?
                                                    implode(', ', $active_names) :
                                                    sprintf('%d %s valgt', count($active_names), strtolower($filter_label));
                                            }

                                            $has_active_filters = !empty($active_filters) ? 'has-active-filters' : '';
                                            ?>
                                            <!-- Filter Dropdown Toggle -->
                                            <div class="filter-dropdown-toggle <?php echo esc_attr($has_active_filters); ?>"
                                                data-filter="<?php echo esc_attr($filter); ?>"
                                                data-label="<?php echo esc_attr($filter_label); ?>"
                                                data-placeholder="<?php echo esc_attr($filter_placeholder); ?>">
                                                <span class="selected-text"><?php echo esc_html($display_text); ?></span>
                                                <span class="dropdown-icon"><i class="ka-icon icon-chevron-down"></i></span>
                                            </div>
                                            <!-- Filter Dropdown Content -->
                                            <div class="filter-dropdown-content">
                                                <?php foreach ($taxonomy_data[$filter]['terms'] as $term) : ?>
                                                    <label class="filter-list-item checkbox">
                                                        <input type="checkbox" class="filter-checkbox"
                                                            value="<?php echo esc_attr(is_object($term) ? ($filter === 'months' ? $term->value : $term->slug) : strtolower($term)); ?>"
                                                            data-filter-key="<?php echo esc_attr($taxonomy_data[$filter]['filter_key']); ?>"
                                                            data-url-key="<?php echo esc_attr($taxonomy_data[$filter]['url_key']); ?>">
                                                        <span class="checkbox-label"><?php echo esc_html(is_object($term) ? $term->name : ucfirst($term)); ?></span>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <div id="active-filters-container" class="modern-active-filters">
                        <div id="active-filters" class="active-filters"></div>
                        <a href="#" id="reset-filters" class="reset-filters reset-filters-btn">Nullstill filter</a>
                    </div>
                </div> <!-- End .filter-container filter-top -->
            </div>
        </div>

        <!-- Hovedinnhold -->
        <div class="ka-content-container inner-container main-section">
            <div class="course-grid <?php echo esc_attr($left_column_class); ?>">
                <!-- Venstre kolonne med filtre -->
                <?php if ($has_left_filters) : ?>
                <div class="left-column modern-left-column">
                    <div class="filter-container left-filter-section">
                        <h4 class="filter-heading">Filtrer kurs</h4>
                        
                        <?php foreach ($left_filters as $filter) : ?>
                            <div class="filter-item">
                                <?php
                                // Hent filter info fra den forberedte arrayen
                                $current_filter_info = $filter_display_info[$filter] ?? [];
                                $filter_label = $current_filter_info['label'] ?? '';
                                ?>
                                <h5><?php echo $filter_label; ?></h5>
                                <?php if ($filter === 'search') : ?>
                                    <input type="text" id="search" name="search" class="filter-search <?php echo esc_attr($search_class); ?>" placeholder="Søk etter kurs...">
                                <?php elseif ($filter === 'date') : ?>
                                    <?php
                                    $date = "";
                                    if (isset($_REQUEST['dato'])) {
                                        $dates = explode('-', $_REQUEST['dato']);
                                        if (count($dates) === 2) {
                                            $from_date = ka_format_date(\DateTime::createFromFormat('d.m.Y', trim($dates[0]))->format('Y-m-d'));
                                            $to_date = ka_format_date(\DateTime::createFromFormat('d.m.Y', trim($dates[1]))->format('Y-m-d'));
                                            $date = sprintf('%s - %s', $from_date, $to_date);
                                        }
                                    }
                                    
                                    $is_left_filter = in_array('date', $left_filters);
                                    $caleran_class = $is_left_filter ? 'caleran caleran-left' : 'caleran';
                                    ?>
                                    <div class="date-range-wrapper">
                                        <input type="text" 
                                               id="date-range" 
                                               class="<?php echo esc_attr($caleran_class); ?>"
                                               data-filter-key="date"
                                               data-url-key="dato"
                                               name="calendar-input"
                                               placeholder="Velg fra-til dato"
                                               value="<?php echo esc_attr($date); ?>"
                                               aria-label="Velg datoer">
                                        <i class="ka-icon icon-calendar"></i>
                                    </div>
                                <?php elseif (!empty($taxonomy_data[$filter]['terms'])) : ?>
                                    <?php if ($filter_types[$filter] === 'chips') : ?>
                                        <div class="filter-chip-wrapper">
                                            <?php foreach ($taxonomy_data[$filter]['terms'] as $term) : ?>
                                                <button class="chip filter-chip"
                                                    data-filter-key="<?php echo esc_attr($taxonomy_data[$filter]['filter_key']); ?>"
                                                    data-url-key="<?php echo esc_attr($taxonomy_data[$filter]['url_key']); ?>"
                                                    data-filter="<?php echo esc_attr(is_object($term) ? $term->slug : strtolower($term)); ?>">
                                                    <?php echo esc_html(is_object($term) ? $term->name : ucfirst($term)); ?>
                                                </button>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php elseif ($filter_types[$filter] === 'list') : ?>
                                        <?php 
                                        // Hent innstillinger for filterhøyde
                                        $default_height = get_option('kursagenten_filter_default_height', 250);
                                        $no_collapse_settings = get_option('kursagenten_filter_no_collapse', array());
                                        $no_collapse = isset($no_collapse_settings[$filter]) && $no_collapse_settings[$filter];
                                        $data_size = $no_collapse ? 'auto' : $default_height;
                                        ?>
                                        <div id="filter-list-location" class="filter-list expand-content" data-size="<?php echo esc_attr($data_size); ?>">
                                            <?php foreach ($taxonomy_data[$filter]['terms'] as $term) : ?>
                                                <label class="filter-list-item checkbox">
                                                    <input type="checkbox" class="filter-checkbox"
                                                        value="<?php echo esc_attr(is_object($term) ? ($filter === 'months' ? $term->value : $term->slug) : strtolower($term)); ?>"
                                                        data-filter-key="<?php echo esc_attr($taxonomy_data[$filter]['filter_key']); ?>"
                                                        data-url-key="<?php echo esc_attr($taxonomy_data[$filter]['url_key']); ?>">
                                                    <span class="checkbox-label"><?php echo esc_html(is_object($term) ? $term->name : ucfirst($term)); ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                            </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Høyre kolonne med kursliste -->
                <div class="courselist-items-wrapper right-column">
                    <?php if ($query instanceof WP_Query && $query->have_posts()) : ?>
                        <?php
                        // Hent totalt antall kurs
                        $course_count = $query->found_posts;
                        $page_count = $query->post_count;
                        ?>

                        <div class="courselist-header modern-header">
                            <!-- Antall kurs -->
                            <div id="courselist-header-left">
                                <div id="course-count"><?php echo $course_count; ?> kurs <?php echo $query->max_num_pages > 1 ? sprintf("- side %d av %d", $query->get('paged'), $query->max_num_pages) : ''; ?></div>                              
                            </div>

                            <!-- Sortering -->
                            <div id="courselist-header-right">
                                <div class="sort-dropdown modern-sort">
                                    <div class="sort-dropdown-toggle">
                                        <span class="selected-text">Sorter etter</span>
                                        <span class="dropdown-icon"><i class="ka-icon icon-chevron-down"></i></span>
                                    </div>
                                    <div class="sort-dropdown-content">
                                        <button class="sort-option" data-sort="title" data-order="asc">Fra A til Å</button>
                                        <button class="sort-option" data-sort="title" data-order="desc">Fra Å til A</button>
                                        <button class="sort-option" data-sort="price" data-order="asc">Pris lav til høy</button>
                                        <button class="sort-option" data-sort="price" data-order="desc">Pris høy til lav</button>
                                        <button class="sort-option" data-sort="date" data-order="asc">Tidligste dato</button>
                                        <button class="sort-option" data-sort="date" data-order="desc">Seneste dato</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Last inn listevisning -->
                        <?php $archive_list_type = get_option('kursagenten_archive_list_type', 'standard'); ?>
                        <div class="courselist-items" id="filter-results" data-list-type="<?php echo esc_attr($archive_list_type); ?>">
                            <?php kursagenten_get_list_template(); ?>
                        </div>

                        <!-- Paginering -->
                        <div class="pagination-wrapper modern-pagination">
                            <div class="pagination">
                            <?php
                            // Get URL rewrite options
                            $url_options = get_option('kag_seo_option_name');
                            $kurs = !empty($url_options['ka_url_rewrite_kurs']) ? $url_options['ka_url_rewrite_kurs'] : 'kurs';

                            // Generate pagination links
                            echo paginate_links([
                                'base' => get_home_url(null, $kurs) .'?%_%',
                                'current' => max(1, $query->get('paged')),
                                'format' => 'side=%#%',
                                'total' => $query->max_num_pages,
                                'prev_text' => '<i class="ka-icon icon-chevron-left"></i> <span>Forrige</span>',
                                'next_text' => '<span>Neste</span> <i class="ka-icon icon-chevron-right"></i>',
                                'add_args' => array_map(function ($item) {
                                    return is_array($item) ? join(',', $item) : $item;
                                }, array_diff_key($_REQUEST, ['side' => true, 'action' => true, 'nonce' => true]))
                            ]);
                            ?>
                            </div>
                        </div>

                        <!-- Loading Indicator -->
                        <div class="course-loading" style="display: none;">
                            <div class="loading-spinner"></div>
                        </div>
                    <?php
                        wp_reset_postdata();
                    else :
                        echo '<p class="no-courses-message">Ingen kurs tilgjengelige.</p>';
                    endif;
                    ?>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA-seksjon -->
    <section class="ka-section ka-cta-section">
        <div class="ka-content-container">
            <div class="ka-cta-content">
                <h2>Finner du ikke det du leter etter?</h2>
                <p>Ta kontakt med oss, så hjelper vi deg med å finne det perfekte kurset for dine behov.</p>
                <a href="/kontakt" class="ka-button ka-button-primary">Kontakt oss</a>
            </div>
        </div>
    </section>
</main>

<?php get_footer(); ?>

<style>
/* Moderne designstiler */
.kursagenten-modern {
    --primary-color: #3498db;
    --secondary-color: #2c3e50;
    --accent-color: #e74c3c;
    --light-bg: #f8f9fa;
    --dark-bg: #2c3e50;
    --text-color: #333;
    --light-text: #fff;
    --border-radius: 8px;
    --box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    --transition: all 0.3s ease;
}

/* Hero header */
.ka-hero-header {
    position: relative;
    background-size: cover;
    background-position: center;
    min-height: 400px;
    display: flex;
    align-items: center;
    color: var(--light-text);
    margin-bottom: 2rem;
}

.ka-hero-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
}

.ka-hero-content {
    max-width: 800px;
    margin: 0 auto;
    text-align: center;
    padding: 2rem;
}

.ka-hero-content h1 {
    font-size: 3rem;
    margin-bottom: 1rem;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.ka-hero-description {
    font-size: 1.2rem;
    margin-bottom: 2rem;
}

.ka-hero-search {
    position: relative;
    max-width: 600px;
    margin: 0 auto;
}

.hero-search {
    width: 100%;
    padding: 15px 20px;
    border-radius: 50px;
    border: none;
    font-size: 1.1rem;
    box-shadow: var(--box-shadow);
}

.ka-search-button {
    position: absolute;
    right: 5px;
    top: 5px;
    background: var(--primary-color);
    border: none;
    color: white;
    width: 45px;
    height: 45px;
    border-radius: 50%;
    cursor: pointer;
    transition: var(--transition);
}

.ka-search-button:hover {
    background: var(--secondary-color);
}

/* Filter bar */
.ka-filter-bar {
    background: var(--light-bg);
    padding: 1rem 0;
    margin-bottom: 2rem;
    border-bottom: 1px solid #eee;
}

.modern-filter .filter-dropdown-toggle {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    padding: 10px 15px;
    transition: var(--transition);
}

.modern-filter .filter-dropdown-toggle:hover {
    box-shadow: 0 6px 12px rgba(0,0,0,0.15);
}

.modern-chips .chip {
    border-radius: 50px;
    background: white;
    box-shadow: var(--box-shadow);
    transition: var(--transition);
    padding: 8px 16px;
    margin: 5px;
}

.modern-chips .chip:hover {
    background: var(--primary-color);
    color: white;
}

.modern-date-picker {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    overflow: hidden;
}

.modern-active-filters {
    margin-top: 1rem;
}

/* Kursliste */
.modern-header {
    background: white;
    padding: 1rem;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    margin-bottom: 1.5rem;
}

.modern-sort .sort-dropdown-toggle {
    background: var(--light-bg);
    border-radius: var(--border-radius);
    padding: 8px 15px;
}

.modern-left-column {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    padding: 1.5rem;
}

.filter-heading {
    margin-top: 0;
    padding-bottom: 1rem;
    border-bottom: 1px solid #eee;
}

/* CTA-seksjon */
.ka-cta-section {
    background: var(--primary-color);
    color: var(--light-text);
    padding: 4rem 0;
    text-align: center;
    margin-top: 3rem;
}

.ka-cta-content {
    max-width: 800px;
    margin: 0 auto;
}

.ka-cta-content h2 {
    font-size: 2.5rem;
    margin-bottom: 1rem;
}

.ka-cta-content p {
    font-size: 1.2rem;
    margin-bottom: 2rem;
}

.ka-button {
    display: inline-block;
    padding: 12px 30px;
    border-radius: 50px;
    text-decoration: none;
    font-weight: bold;
    transition: var(--transition);
}

.ka-button-primary {
    background: var(--light-text);
    color: var(--primary-color);
}

.ka-button-primary:hover {
    background: var(--secondary-color);
    color: var(--light-text);
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.2);
}

/* Paginering */
.modern-pagination {
    margin-top: 2rem;
    text-align: center;
}

.modern-pagination .pagination {
    display: inline-flex;
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    overflow: hidden;
}

.modern-pagination .page-numbers {
    padding: 10px 15px;
    color: var(--text-color);
    text-decoration: none;
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
}

.modern-pagination .page-numbers.current {
    background: var(--primary-color);
    color: var(--light-text);
}

.modern-pagination .page-numbers:hover:not(.current) {
    background: var(--light-bg);
}

/* Slide-in panel */
.modern-slidein {
    border-radius: var(--border-radius) 0 0 var(--border-radius);
    overflow: hidden;
    box-shadow: -5px 0 25px rgba(0,0,0,0.2);
}

.modern-slidein .close-btn {
    background: var(--primary-color);
    color: var(--light-text);
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    font-size: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    position: absolute;
    top: 15px;
    right: 15px;
    cursor: pointer;
    transition: var(--transition);
    z-index: 1001;
}

.modern-slidein .close-btn:hover {
    background: var(--accent-color);
    transform: rotate(90deg);
}

/* Responsivt design */
@media (max-width: 768px) {
    .ka-hero-content h1 {
        font-size: 2rem;
    }
    
    .ka-hero-description {
        font-size: 1rem;
    }
    
    .modern-left-column {
        margin-bottom: 1.5rem;
    }
    
    .ka-cta-content h2 {
        font-size: 1.8rem;
    }
}
</style>
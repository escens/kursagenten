<?php 

//Legg inn rating schema i Rank Math
//Legg til rating schema fra utvidelse Site Reviews i Course schema fra RankMath Pro

add_filter('rank_math/snippet/rich_snippet_course_entity', function ($entity) {
    $score = get_post_meta(get_the_ID(), '_glsr_average', true);
    $count = get_post_meta(get_the_ID(), '_glsr_reviews', true);
    if (empty($score)) {
        return $entity;
    }
    $entity['aggregateRating'] = [
        '@type' => 'aggregateRating',
        'ratingValue' => $score,
        'ratingCount' => $count,
        'bestRating' => '5',
        'worstRating' => '1'
    ];
    return $entity;
});



// Redirect til kurs på kursID fra Kursagenten
/* 
Opprett en side med slug /vurdering. Basert på querystring kursid=xxx, finn kurs med denne id-en (i custom fields) og videresend til korrekt kurs-url. Inkluder querystrings i videresending.
Brukes i automatisk epost fra Kursagenten x dager etter endt kurs for å be om rating
*/

add_action( 'wp_head', 'redirect_til_kursside' );

function redirect_til_kursside($post_id) {
    // Get the request URI and query string
    $request_uri = $_SERVER['REQUEST_URI'];
    $query_string = $_SERVER['QUERY_STRING'];

    if (strpos($query_string, 'navn=') == false &&
        strpos($query_string, 'epost=') == false && 
        strpos($query_string, 'kursid=') == false &&  
        strpos($query_string, 'vurdering=') == false ){
        return;
    }

    $kursid = filter_input(INPUT_GET, 'kursid', FILTER_SANITIZE_STRING);
    $navn = filter_input(INPUT_GET, 'navn', FILTER_SANITIZE_STRING);
    $epost = filter_input(INPUT_GET, 'epost', FILTER_SANITIZE_STRING);
    $vurdering = filter_input(INPUT_GET, 'vurdering', FILTER_SANITIZE_STRING);

    $args = array(
        'post_type'      => 'kurs',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_key' => 'id',
        'meta_value' => $kursid
    );

    $the_query = new WP_Query( $args );
    if ( $the_query->have_posts() ) :
        while ( $the_query->have_posts() ) : $the_query->the_post();
            $nylink = get_the_permalink(); 
             $url = $nylink . '?kursid=' . $kursid . '&navn=' . $navn . '&epost=' . $epost . '&vurdering=' . $vurdering . '#vurderinger';
            ?>
            <script>
                window.location.replace('<?php echo $url; ?>');
            </script>
            <?php
        endwhile;
        wp_reset_postdata();
    endif;
}


/*
Dette javascriptet må legges inn på CPT template for kurs:

<script>
<script>
  var navn = (new URL(location.href)).searchParams.get('navn');
  var epost = (new URL(location.href)).searchParams.get('epost');
  var vurdering = (new URL(location.href)).searchParams.get('vurdering');
  var kursnavn = document.getElementById("kurstittel").textContent;

if (navn !== null) { 
  document.getElementById("glsr_b9b760d8-name").value = navn;
  document.getElementById("glsr_b9b760d8-email").value = epost;
  document.getElementById("glsr_b9b760d8-rating").value = vurdering;
  document.getElementById("glsr_b9b760d8-title").value = kursnavn;
};
</script>
*/
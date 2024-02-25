<?php

require_once __DIR__ . '/src/schema.php';
require_once __DIR__ . '/src/registrations.php';

// Gray-add
function gray_redirection_404_pour_registrations() {
    // Vérifier si nous sommes sur une page de type 'single' (post unique)
    if (is_single() && is_singular('registrations')) {
        // Réinitialiser la requête WordPress
        global $wp_query;
        $wp_query->set_404();
        status_header(404);
        // il n'y a pas de template 404 (sinon remplacer '404' par le nom du fichier de template 404)
        // get_template_part('404');
        exit();
    }
}
// template_redirect pour vérifier si la page demandée est de type "registrations"
add_action('template_redirect', 'gray_redirection_404_pour_registrations');

<?php

add_action('init', __NAMESPACE__ . '\\register_post_type_event', 10);
add_action('init', __NAMESPACE__ . '\\register_post_type_registration', 10);

function register_post_type_event()
{
    $args = [
        'hierarchical'        => false,
        'public'              => true,
        'exclude_from_search' => true,
        'publicly_queryable'  => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_nav_menus'   => false,
        'show_in_admin_bar'   => false,
        'menu_position'       => 25,
        'menu_icon'           => 'dashicons-calendar-alt',
        'capability_type'     => 'post',
        // 'capabilities'        => [],
        // 'map_meta_cap'        => false,
        'supports'            => ['title'],
        'has_archive'         => false,
        'rewrite'             => ['slug' => 'events', 'pages' => true, 'feeds' => false, 'with_front' => false],
        'query_var'           => false,
        // Extended
        'show_in_feed'         => false,
        'quick_edit'           => true,
        'dashboard_glance'     => true,
        'enter_title_here'     => null,
        'featured_image'       => null,
        'site_filters'         => null,
        'site_sortables'       => null,
        'archive'              => null,
        'admin_cols'           => [
            'event-date' => ['title' => 'Event date', 'sortable' => false, 'function' => function () {
                global $post;
                $event_date = get_field('event_date', $post);
                $event_time = get_field('event_time', $post);
                if (empty($event_date) || empty($event_time)) {
                    echo "&mdash;";
                    return;
                }
                echo $event_date . ' ' . $event_time;
            }],
            'registrations' => ['title' => 'Registrations', 'sortable' => false, 'function' => function () {
                global $post;
                global $wpdb;

                // Gray-add id pour le post actuel : fix-1
                $post_id = $post->ID;

                $sql_query = $wpdb->prepare("SELECT COUNT(`post_id`) as count FROM %i WHERE `meta_key` = 'registration_event_id' AND `meta_value` = %d", $wpdb->postmeta, $post_id);
                $result = $wpdb->get_row($sql_query, ARRAY_A);
                echo $result['count'];
            }],
            // Gray-add Exportation
            'Export' => ['title' => 'Export', 'sortable' => false, 'function' => function () {
                global $post;
                global $wpdb;

                // Gray-add id pour le post actuel : fix-1
                $post_id = $post->ID;

                $sql_query = $wpdb->prepare("
                    SELECT
                        COUNT(pm1.`post_id`) as count,
                        GROUP_CONCAT(
                            CONCAT(
                                IFNULL(pm2.`meta_value`, 'N/A'), ' ',
                                IFNULL(pm3.`meta_value`, 'N/A'), ' ',
                                IFNULL(pm4.`meta_value`, 'N/A'), ' ',
                                IFNULL(pm5.`meta_value`, 'N/A'), ' '
                            ) SEPARATOR ','
                        ) as details
                    FROM %1\$s pm1
                    LEFT JOIN %1\$s pm2 ON pm1.`post_id` = pm2.`post_id` AND pm2.`meta_key` = 'registration_first_name'
                    LEFT JOIN %1\$s pm3 ON pm1.`post_id` = pm3.`post_id` AND pm3.`meta_key` = 'registration_last_name'
                    LEFT JOIN %1\$s pm4 ON pm1.`post_id` = pm4.`post_id` AND pm4.`meta_key` = 'registration_email'
                    LEFT JOIN %1\$s pm5 ON pm1.`post_id` = pm5.`post_id` AND pm5.`meta_key` = 'registration_phone'
                    WHERE pm1.`meta_key` = 'registration_event_id' AND pm1.`meta_value` = %2\$d
                ", $wpdb->postmeta, $post_id);

                $result = $wpdb->get_row($sql_query, ARRAY_A);

                // voir les resultat
                // echo 'Count: ' . $result['count'] . '<br>';
                // echo $result['details'];

                // Vérifiez s'il y a des détails à exporter
                if ($result['count'] > 0) {
                    // Créez un nouvel objet Spreadsheet
                    $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();

                    // Créez une nouvelle feuille de calcul
                    $sheet = $spreadsheet->getActiveSheet();

                    // Définissez les en-têtes de colonne du fichier Excel
                    $sheet->setCellValue('A1', 'Prénom');
                    $sheet->setCellValue('B1', 'Nom');
                    $sheet->setCellValue('C1', 'E-mail');
                    $sheet->setCellValue('D1', 'Téléphone');

                    // Divisez les détails en un tableau
                    $detailsArray = explode(',', $result['details']);

                    // Parcourez les détails et ajoutez-les à la feuille de calcul
                    $row = 2; // Commencer à la ligne 2
                    foreach ($detailsArray as $detail) {
                        list($firstName, $lastName, $email, $phone) = explode(' ', $detail);
                        $sheet->setCellValue('A' . $row, $firstName);
                        $sheet->setCellValue('B' . $row, $lastName);
                        $sheet->setCellValue('C' . $row, $email);
                        $sheet->setCellValue('D' . $row, $phone);
                        $row++;
                    }

                    // Enregistrer le fichier Excel sur le serveur
                    $excel_file_name = "expor-inscrits_" . $post_id . '.xlsx';
                    $excel_file_path = __DIR__ . "/../$excel_file_name"; //.. je sors pour ecrire à coté du functions.php

                    // Créer un writer pour le format XLSX (Excel)
                    $writer = new PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                    $writer->save($excel_file_path);

                    $url_excel_file = esc_url( get_stylesheet_directory_uri() . DIRECTORY_SEPARATOR . $excel_file_name );

                    // Afficher le bouton de téléchargement
                    echo '<a class="button button-primary" href="' . $url_excel_file . '" download="' . $excel_file_name . '">Export</a>';
                } else {
                    echo 'Aucun détail à exporter.';
                }

            }],
        ],
        'admin_filters'        => [],
    ];

    $names = [
        'singular' => 'Event',
        'plural'   => 'Events',
        'slug'     => 'event',
    ];

    register_extended_post_type("events", $args, $names);
}

function register_post_type_registration()
{
    $args = [
        'hierarchical'        => false,
        'public'              => true,
        'exclude_from_search' => true,
        'publicly_queryable'  => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_nav_menus'   => false,
        'show_in_admin_bar'   => false,
        'menu_position'       => 30,
        'menu_icon'           => 'dashicons-tickets',
        'capability_type'     => 'post',
        // 'capabilities'        => [],
        // 'map_meta_cap'        => false,
        'supports'            => false,
        'has_archive'         => false,
        'rewrite'             => ['slug' => 'registrations', 'pages' => true, 'feeds' => false, 'with_front' => false],
        'query_var'           => false,
        // Extended
        'show_in_feed'         => false,
        'quick_edit'           => true,
        'dashboard_glance'     => true,
        'enter_title_here'     => null,
        'featured_image'       => null,
        'site_filters'         => null,
        'site_sortables'       => null,
        'archive'              => null,
        'admin_cols'           => [
            'event' => ['title' => 'Event', 'sortable' => false, 'function' => function () {
                global $post;
                $registration_event_id = get_field('registration_event_id', $post);
                if (empty($registration_event_id)) {
                    echo "&mdash;";
                    return;
                }
                $event = get_post($registration_event_id);
                if (empty($event) || 'events' !== get_post_type($registration_event_id)) {
                    echo "&mdash;";
                    return;
                }
                ?>
                <a href="<?= get_edit_post_link($registration_event_id) ?>"><?= get_the_title($registration_event_id) ?></a>
                <?php
            }],
        ],
        'admin_filters'        => [],
    ];

    $names = [
        'singular' => 'Registration',
        'plural'   => 'Registrations',
        'slug'     => 'registration',
    ];

    register_extended_post_type("registrations", $args, $names);
}

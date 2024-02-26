<?php

require_once __DIR__ . '/src/schema.php';
require_once __DIR__ . '/src/registrations.php';

/**
 * Gray-add
 * Function pour :
 * intercepter l'inscription et récuperer les donnée de l'inscrit et de l'evenement
 * generer le fichier pdf avec les donnée
 * envoyer le mail au concerné
 */
function envoyer_mail_inscription($post_id) {
    // Vérifier si c'est une inscription
    if (get_post_type($post_id) !== 'registrations') {
        return;
    }

    // Récupérer les informations de l'inscription depuis les champs ACF
    $nom = get_field('registration_first_name', $post_id);
    $prenom = get_field('registration_last_name', $post_id);
    $email = get_field('registration_email', $post_id);
    $phone = get_field('registration_phone', $post_id);

    // id de evenement
    $event_id = get_field('registration_event_id', $post_id);
    // Récupérer les informations de l'événement
    $event_date = get_field('event_date', $event_id);
    $event_time = get_field('event_time', $event_id);
    $event_pdf_ticket_entree = get_field('event_pdf_entrance_ticket', $event_id);


  // Contenu du fichier PDF
  $contenuDuPDF = [
    // "ID event : $event_id",
    "Nom : $nom",
    "Prenom : $prenom",
    "Email : $email",
    "Telephone : $phone",
    "Date : $event_date",
    "Temps : $event_time",
    "Ticket d-entree : $event_pdf_ticket_entree",
  ];

   // En-tête PDF
   $header = "%PDF-1.3\n";
   $header .= "1 0 obj\n";
   $header .= "<< /Type /Catalog /Pages 2 0 R >>\n";
   $header .= "endobj\n";
   $header .= "2 0 obj\n";
   $header .= "<< /Type /Pages /Kids [3 0 R] /Count 1 >>\n";
   $header .= "endobj\n";
   $header .= "3 0 obj\n";
   $header .= "<< /Type /Page /Parent 2 0 R /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\n";
   $header .= "endobj\n";
   $header .= "4 0 obj\n";
   $header .= "<< /Type /Font /Subtype /Type1 /Name /F1 /BaseFont /Helvetica >>\n";
   $header .= "endobj\n";
   $header .= "5 0 obj\n";
   $header .= "<< /Length 44 >>\n";
   $header .= "stream\n";
   $header .= "BT\n";
   $header .= "/F1 12 Tf\n";
   $header .= "100 700 Td\n";

   // Ajouter les lignes de texte au contenu PDF
   foreach ($contenuDuPDF as $ligne) {
       $header .= "($ligne) Tj\n";
       $header .= "12 TL\n";
       $header .= "0 -20 Td\n"; // gère la hauteur de lignes
   }

   // Pied de page PDF
   $header .= "ET\n";
   $header .= "endstream\n";
   $header .= "endobj\n";
   $header .= "trailer\n";
   $header .= "<< /Root 1 0 R /Size 6 >>\n";
   $header .= "startxref\n";
   $header .= "294\n";
   $header .= "%%EOF";

  // Enregistrez le contenu dans un fichier
  $pdf_file_path = __DIR__ . '/billet_entree.pdf';
  file_put_contents($pdf_file_path, $header);


  // Envoyer l'e-mail
  $subject = "Confirmation inscription";
  $message = "Bonjour $nom $prenom,\n\nMerci pour votre inscription à l'événement. Ci-joint, vous trouverez votre billet d'entrée.";

  $attachments = array($pdf_file_path);

  $result = wp_mail($email, $subject, $message, '', $attachments);

  // verifier si email à été envoyé
  /*
  if ($result) {
    echo 'Email sent successfully';
  } else {
    echo 'Failed to send email !';
  }
  */

  // Supprimer le fichier PDF après l'envoi de l'e-mail si nécessaire
  // unlink($pdf_file_path);
}

// Hook pour intercepter la création d'une inscription
add_action('acf/save_post', 'envoyer_mail_inscription', 20);

<?php

class Vue {

  /**
   * Constructeur qui génère et affiche la page html complète associée à la vue avec le moteur de templates Twig
   * -----------------------------------------------------------------------------------------------------------
   * @param string $vue,     nom du fichier de la vue sans le suffixe twig 
   * @param array $donnees,  variables à insérer dans la page
   * @param string $gabarit, nom du fichier gabarit de la page html sans le suffixe twig, dans lequel est insérée la vue 
   * @param boolean $courriel
   */
  public function generer($vue, $donnees = [], $gabarit = 'gabarit-frontend', $courriel = false) {

    require_once 'app/vues/vendor/autoload.php';
    $loader = new \Twig\Loader\FilesystemLoader('app/vues/templates');
    $twig   = new \Twig\Environment(
                                    $loader,
                                    // ['cache' => 'app/vues/templates/cache']
                                   );
    
    $donnees['templateMain'] = "$vue.twig";
    
    $html = $twig->render("$gabarit.twig", $donnees);
    if ($courriel) return $html; 
    echo $html;
  }
}
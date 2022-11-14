<?php

/**
 * Classe Contrôleur des requêtes de l'application admin
 */

class Admin extends Routeur {

  private $entite;
  private $action;
  private $utilisateur_id;

  private $oUtilisateur;

  private $methodes = [
    'utilisateur' => [
      'l' => 'listerUtilisateurs',
      'a' => 'ajouterUtilisateur',
      'm' => 'modifierUtilisateur',
      's' => 'supprimerUtilisateur',
      'd' => 'deconnecter',
      'envoiCourriel' => 'envoiCourriel'
    ],
    'film' => [
      'l' => 'listerFilms',
      'a' => 'ajouterFilm',
      'm' => 'modifierFilm',
      's' => 'supprimerFilm'
    ]
  ];

  private $classRetour = "fait";
  private $messageRetourAction = "";

  /**
   * Constructeur qui initialise le contexte du contrôleur  
   */  
  public function __construct() {
    $this->entite    = $_GET['entite']    ?? 'utilisateur';
    $this->action    = $_GET['action']    ?? 'l';
    $this->utilisateur_id = $_GET['utilisateur_id'] ?? null;
    $this->film_id          = $_GET['film_id']  ?? null;
    $this->oRequetesSQL = new RequetesSQL;
  }

  /**
   * Gérer l'interface d'administration 
   */  
  public function gererAdmin() {
    if (isset($_SESSION['oUtilisateur'])) {
      $this->oUtilisateur = $_SESSION['oUtilisateur'];
      if (isset($this->methodes[$this->entite])) {
        if (isset($this->methodes[$this->entite][$this->action])) {
          $methode = $this->methodes[$this->entite][$this->action];
          $this->$methode();
        } else {
          throw new Exception("L'action $this->action de l'entité $this->entite n'existe pas.");
        }
      } else {
        throw new Exception("L'entité $this->entite n'existe pas.");
      }
    } else {
      $this->connecter();
    }
  }

  /**
   * Connecter un utilisateur
   */
  public function connecter() {
    $messageErreurConnexion = ""; 
    if (count($_POST) !== 0) {
      $utilisateur = $this->oRequetesSQL->connecter($_POST);
      if ($utilisateur !== false) {
        $_SESSION['oUtilisateur'] = new Utilisateur($utilisateur);
        $this->oUtilisateur = $_SESSION['oUtilisateur'];
        $this->listerUtilisateurs();
        exit;         
      } else {
        $messageErreurConnexion = "Courriel ou mot de passe incorrect.";
      }
    }
    
    (new Vue)->generer('vAdminUtilisateurConnecter',
            array(
              'titre'                  => 'Connexion',
              'messageErreurConnexion' => $messageErreurConnexion
            ),
            'gabarit-admin-min');
  }

  /**
   * Déconnecter un utilisateur
   */
  public function deconnecter() {
    unset ($_SESSION['oUtilisateur']);
    $this->connecter();
  }

  /**
   * Envoi de courriel
   */
  public function envoiCourriel() {
    $oUtilisateur = $this->oRequetesSQL->getUtilisateur($this->utilisateur_id);
    $oUtilisateur = new Utilisateur($oUtilisateur);
    $oUtilisateur->genererMdp();
    $retour = (new GestionCourriel)->envoyerMdp($oUtilisateur);
    if ($retour) echo "Courriel envoyé<br>.";
    if (ENV === "DEV") echo "<a href=\"$retour\">Message dans le fichier $retour</a>";
  }

  /**
   * Lister les utilisateurs
   */
  public function listerUtilisateurs() {
    $utilisateurs = $this->oRequetesSQL->getUtilisateurs();

    (new Vue)->generer('vAdminUtilisateurs',
            array(
              'oUtilisateur'        => $this->oUtilisateur,
              'titre'               => 'Gestion des utilisateurs',
              'utilisateurs'        => $utilisateurs, 
              'classRetour'         => $this->classRetour, 
              'messageRetourAction' => $this->messageRetourAction
            ),
            'gabarit-admin');
  }

  /**
   * Ajouter un utilisateur
   */
  public function ajouterUtilisateur() {
    $utilisateur  = [];
    $erreurs = [];
    if (count($_POST) !== 0) {
      // retour de saisie du formulaire
      $utilisateur = $_POST;
      $oUtilisateur = new Utilisateur($utilisateur); // création d'un objet Utilisateur pour contrôler la saisie
      $erreurs = $oUtilisateur->erreurs;
      if (count($erreurs) === 0) { // aucune erreur de saisie -> requête SQL d'ajout
        $oUtilisateur->genererMdp();
        $retour = (new GestionCourriel)->envoyerMdp($oUtilisateur);
        if ($retour) echo "Courriel envoyé<br>.";
        if (ENV === "DEV") echo "<a href=\"$retour\">Message dans le fichier $retour</a>";
        $utilisateur_id = $this->oRequetesSQL->ajouterUtilisateur([
          'utilisateur_nom'    => $oUtilisateur->utilisateur_nom,
          'utilisateur_prenom' => $oUtilisateur->utilisateur_prenom,
          'utilisateur_courriel' => $oUtilisateur->utilisateur_courriel,
          'utilisateur_profil' => $oUtilisateur->utilisateur_profil,
          'utilisateur_mdp' => $oUtilisateur->utilisateur_mdp
        ]);
        if ( $utilisateur_id > 0) { // test de la clé de l'utilisateur ajouté
          $this->messageRetourAction = "Ajout de l'utilisateur numéro $utilisateur_id effectué.";
        } else {
          $this->classRetour = "erreur";
          $this->messageRetourAction = "Ajout de l'utilisateur non effectué.";
        }
        $this->listerUtilisateurs(); // retour sur la page de liste des utilisateurs
        exit;
      }
    }
    
    (new Vue)->generer('vAdminUtilisateurAjouter',
            array(
              'oUtilisateur' => $this->oUtilisateur,
              'titre'        => 'Ajouter un utilisateur',
              'utilisateur'       => $utilisateur,
              'erreurs'      => $erreurs
            ),
            'gabarit-admin');
  }

  /**
   * Modifier un auteur identifié par sa clé dans la propriété auteur_id
   */
  public function modifierUtilisateur() {
    if (count($_POST) !== 0) {
      $utilisateur = $_POST;

      echo '<pre>',print_r($utilisateur),'</pre>';

      $oUtilisateur = new Utilisateur($utilisateur);
      $erreurs = $oUtilisateur->erreurs;
      if (count($erreurs) === 0) {
        if($this->oRequetesSQL->modifierUtilisateur([
          'utilisateur_id'    => $oUtilisateur->utilisateur_id,
          'utilisateur_nom'    => $oUtilisateur->utilisateur_nom,
          'utilisateur_prenom' => $oUtilisateur->utilisateur_prenom,
          'utilisateur_courriel' => $oUtilisateur->utilisateur_courriel,
          'utilisateur_profil' => $oUtilisateur->utilisateur_profil
        ])) {
          $this->messageRetourAction = "Modification de l'utilisateur numéro $this->utilisateur_id effectuée.";
        } else {
          $this->classRetour = "erreur";
          $this->messageRetourAction = "modification de l'utilisateur numéro $this->utilisateur_id non effectuée.";
        }
        $this->listerUtilisateurs();
        exit;
      }

    } else {
      // chargement initial du formulaire  
      // initialisation des champs dans la vue formulaire avec les données SQL de cet utilisateur  
      $utilisateur  = $this->oRequetesSQL->getUtilisateur($this->utilisateur_id);
      $erreurs = [];
    }
    
    (new Vue)->generer('vAdminUtilisateurModifier',
            array(
              'oUtilisateur' => $this->oUtilisateur,
              'titre'        => "Modifier l'utilisateur numéro $this->utilisateur_id",
              'utilisateur'       => $utilisateur,
              'erreurs'      => $erreurs
            ),
            'gabarit-admin');
  }
  
  /**
   * Supprimer un utilisateur identifié par sa clé dans la propriété utilisateur_id
   */
  public function supprimerUtilisateur() {
    if ($this->oRequetesSQL->supprimerUtilisateur($this->utilisateur_id)) {
      $this->messageRetourAction = "Suppression de l'utilisateur numéro $this->utilisateur_id effectuée.";
    } else {
      $this->classRetour = "erreur";
      $this->messageRetourAction = "Suppression de l'utilisateur numéro $this->utilisateur_id non effectuée.";
    }
    $this->listerUtilisateurs();
  }

  
}
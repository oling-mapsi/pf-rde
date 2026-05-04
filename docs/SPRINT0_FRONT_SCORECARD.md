# Sprint 0 - Front Scorecard (Audit applique + Correctifs P1)

Date: 24 avril 2026  
Portail: Routes de Guadeloupe - front public

## Objectif du Sprint 0
- Corriger les risques P1 visibles immediatement pour un portail public:
  - fiabilite des interactions critiques,
  - accessibilite clavier/focus,
  - robustesse navigation sticky,
  - conformite UX minimale pour cookies.

## Correctifs P1 appliques dans le code

## P1-01 - Navigation principale: semantique de page courante
- Action:
  - Ajout de `aria-current="page"` sur les liens actifs du menu principal.
- Impact:
  - Meilleure orientation lecteur d’ecran.
  - Alignement WCAG/RGAA sur la comprehension du contexte de navigation.
- Fichier:
  - `templates/base.html.twig`

## P1-02 - Header sticky scroll: comportement global plus lisible
- Action:
  - Le header se replie de maniere plus globale (translation du composant), pas uniquement disparition visuelle des liens.
  - Ajout d’un etat `is-nav-pinned` pour re-affichage coherent.
- Impact:
  - Effet plus propre, perception institutionnelle plus stable.
  - Moins de confusion "les liens disparaissent seuls".
- Fichiers:
  - `templates/base.html.twig`
  - `assets/styles/app.css`

## P1-03 - Cookies: ouverture fiable + acces clavier
- Action:
  - Bouton footer "Gerer les cookies" declenche l’ouverture detaillee.
  - Ajout bouton de fermeture explicite dans la fenetre.
  - Fermeture via `Escape` et clic exterieur.
  - Focus initial place dans la fenetre, restauration du focus a la fermeture.
  - Verrouillage du scroll de page tant que la fenetre est ouverte.
- Impact:
  - Interaction robuste.
  - Accessibilite operable clavier sensiblement amelioree.
- Fichiers:
  - `templates/base.html.twig`
  - `assets/controllers/cookie_consent_controller.js`
  - `assets/styles/app.css`

## P1-04 - Focus visible global
- Action:
  - Focus visible renforce (`outline` + offset + ring) sur tous les elements interactifs.
- Impact:
  - Meilleure detectabilite clavier.
  - Base RGAA/WCAG plus solide.
- Fichier:
  - `assets/styles/app.css`

## P1-05 - Recherche hero: semantique de recherche
- Action:
  - Formulaire hero en `role="search"`.
  - Label explicite (visuellement cache) associe au champ.
- Impact:
  - Meilleure navigation assistive.
  - Clarification semantique du moteur principal.
- Fichier:
  - `templates/public/home.html.twig`

## P1-06 - Motion safety
- Action:
  - Ajout d’une regle `prefers-reduced-motion: reduce`.
- Impact:
  - Amelioration immediate pour usagers sensibles au mouvement.
- Fichier:
  - `assets/styles/app.css`

## Verifications realisees
- `php bin/console lint:twig templates/base.html.twig templates/public/home.html.twig` OK

## Backlog priorise (prochain lot)

## P2 (a lancer ensuite)
- Revue contraste complete des couples texte/fond (AA strict partout, AAA sur meta critiques).
- Audit clavier detaille sur pages catalogue / fiche ressource / carte interactive.
- Standardisation des messages d’erreur formulaires (ARIA + annonce).
- Normalisation design des tableaux data (caption, tri annonce, lecture mobile).

## P3
- Audit utilisateur rapide (5 tests cibles) sur:
  - trouver une carte,
  - comprendre une licence,
  - telecharger une ressource,
  - ouvrir/fermer la gestion cookies,
  - revenir au menu principal au clavier.

## Risques residuels connus
- Contraste de certaines combinaisons basees sur orange de marque a confirmer par mesure instrumentee.
- Validation RGAA complete necessite audit criterie sur un echantillon representatif de pages.

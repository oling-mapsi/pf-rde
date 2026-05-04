# Audit 360 - Portail SIG Routes de Guadeloupe

Date: 24 avril 2026  
Perimetre: design system, UX, UI, accessibilite, open data, cartographie, iconographie et imagerie.

## 1) Cadre de reference (normatif et sectoriel)

### Accessibilite et cadre public
- RGAA en vigueur: version 4.1.2 (au 24 avril 2026), avec RGAA v5 annonce pour fin 2026.
- WCAG 2.2: recommande W3C (5 octobre 2023) et approuve ISO/IEC 40500:2025 (octobre 2025).
- Pour un service public: viser au minimum RGAA (socle legal) + pratiques WCAG 2.2 AA.

### Design de service public
- DesignGouv: un service public numerique doit etre pertinent, inclusif, efficace, responsable.
- DSFR: reference forte de robustesse institutionnelle (composants, lisibilite, coherence, gouvernance).

### Open data et geodata
- data.gouv.fr / DCAT: structuration des metadonnees et moissonnage inter-portails.
- DCAT-AP (UE): interop entre portails publics europeens.
- OGC API Features + W3C/OGC Spatial Data on the Web: socle pour diffusion geospatiale standardisee.

## 2) Diagnostic (ecarts pour atteindre un niveau "portail institutionnel mature")

### A. Gouvernance visuelle
- Forces:
  - Palette marque Route de Guadeloupe deja clarifiee (orange `#fc5000`, bleu `#38b4e7`, vert `#aaae02`, jaune `#fbd002`).
  - Direction institutionnelle deja engagee.
- Ecarts:
  - Hierarchie visuelle encore inegale selon les sections.
  - Variabilite de densite et de rythme vertical.
  - Niveau de finition inconstant entre hero, blocs data, legal/cookies.

### B. UX d’un portail data public
- Forces:
  - Presence des briques essentielles (catalogue, cartes, actualites, legal, espace agents).
- Ecarts:
  - Signal de confiance a renforcer sur chaque ressource (fraicheur, licence, producteur, statut).
  - Parcours "trouver / comprendre / reutiliser" pas encore assez explicite.
  - Mecanismes de quality-score, disponibilite services et etat de mise a jour a industrialiser.

### C. Accessibilite pratique
- Points sensibles a traiter systematiquement:
  - Navigation sticky/scroll (ne pas casser orientation et predictibilite).
  - Focus visible et non masque.
  - Cibles interactives (taille minimale, zone tactile).
  - Carte interactive: alternatives non visuelles (table, liste, telechargement, resume texte).

### D. Contenu public
- Ecarts:
  - Uniformiser niveau de langage "service public clair" (phrases plus courtes, libelles actionnables).
  - Mieux prioriser l’information (decision rapide en 10 secondes).

## 3) Cible UX/UI professionnelle (Routes de Guadeloupe rebrand strict)

## 3.1 Architecture de page (homepage)
- Header institutionnel compact et stable:
  - Marque + navigation primaire + icone agent + contact.
  - Comportement scroll sobre, non perturbant clavier/screen reader.
- Hero utile (pas marketing):
  - 1 promesse claire sur 2 lignes max.
  - 1 recherche principale integree.
  - 1 phrase de contexte plus legere visuellement.
- Bloc "Portail en un coup d’oeil":
  - 4 acces principaux, alignes et symetriques.
  - Icones fortes, semantiques, couleurs maitrisees.
- Blocs data institutionnels:
  - Focus informationnel / actualites / disponibilite services / legal.
  - Cartes de contenu homogenes (hauteur, meta, CTA).
- Footer:
  - Dense mais bas en hauteur.
  - Legal complet + lien "Gerer les cookies" visible et operant.

## 3.2 Regles de composition
- Container desktop: `max-width` stable (1320-1360px).
- Grille:
  - 12 colonnes desktop, 8 tablette, 4 mobile.
  - Gouttiere 24px desktop, 16px mobile.
- Espacement vertical:
  - Section rhythm: 64 / 80 / 96 selon importance.
  - Inter-blocs internes: 24 / 32.
- Alignements:
  - Titres de section centres si layout editorial centre.
  - Contenu de cartes aligne en haut, CTA alignes sur une baseline commune.

## 3.3 Typographie recommandee (institutionnelle, lisible)
- Sans serif principale: `Marianne` si disponible, fallback `system-ui, "Segoe UI", Roboto, Arial, sans-serif`.
- Echelle:
  - H1: 56/64 desktop, 42/50 tablette, 34/42 mobile.
  - H2: 40/48 desktop, 32/40 tablette, 28/36 mobile.
  - H3: 28/36 desktop, 24/32 tablette, 22/30 mobile.
  - Corps: 18/30 desktop, 17/28 tablette, 16/26 mobile.
  - Meta: 14/22.
- Regles:
  - Longueur de ligne cible: 60-75 caracteres (texte courant).
  - Contraste texte: AA minimum, AAA pour meta critiques.

## 3.4 Systeme couleur (ancre marque + usages fonctionnels)
- Brand anchors (valides COM):
  - `--rdg-orange: #fc5000`
  - `--rdg-blue: #38b4e7`
  - `--rdg-green: #aaae02`
  - `--rdg-yellow: #fbd002`

- Application semantique recommandee:
  - Primary action: orange fond + texte blanc.
  - Secondary action: contour bleu / texte bleu; hover contour orange.
  - Information neutre / data UI: bleus-gris sobres en support.
  - Success/warning/error: derives fonctionnels, jamais purement decoratifs.

## 4) Accessibilite: exigences concretes (RGAA/WCAG)

## 4.1 Interactions
- Cible interactive min: 24x24 CSS px (minimum) - viser 40x40 en pratique mobile.
- Focus:
  - Visible, contraste >= 3:1.
  - Non masque par sticky header.
- Clavier:
  - Ordre logique.
  - Pas de piege focus dans modales/panneaux.
- Aide coherente:
  - Contact/aide/cookies au meme emplacement logique sur les pages.

## 4.2 Contenus et lecture
- Liens explicites (pas de "cliquez ici").
- Erreurs formulaire:
  - Message textuel + liaison champ/message + etat ARIA.
- Tableaux data:
  - `<th scope>`, caption, tri annonce.
- Images:
  - decoratives en `alt=""`.
  - informatives avec alternative utile.

## 4.3 Cartographie interactive
- Toujours fournir un mode alternatif:
  - liste/table des objets cartographiques,
  - export CSV/GeoJSON,
  - resume textuel des couches.
- Legende structurée accessible:
  - symbols + libelles textuels.
- Ne jamais porter l’information uniquement par la couleur.

## 5) Open data UX: standard de qualite produit

Chaque fiche ressource doit afficher au-dessus de la ligne de flottaison:
- titre clair,
- resume fonctionnel (a quoi sert la donnee),
- date de mise a jour,
- frequence de mise a jour,
- producteur,
- licence,
- couverture spatiale et temporelle,
- formats disponibles et poids,
- canal de contact (question/signaler un probleme).

Fonctions incontournables:
- recherche tolerante (accent/pluriel),
- filtres facettes stables et memorises,
- tri utile (recent, popularite, alphabetique),
- statut de disponibilite des services carto,
- telechargements et API clairement distingues.

## 6) Strategie illustration / iconographie (secteur routier)

Objectif:
- donner de la matiere visuelle "routes / territoire / infrastructure" sans style marketing.
- rester institutionnel, sobre, credibilite technique.

### 6.1 Types visuels recommandes
- Photos aeriennes de reseaux routiers (hero et inter-sections).
- Visuels de signalisation / maintenance / ouvrages d’art (cards editoriales).
- Textures cartographiques legeres pour fonds (faible contraste).
- Icnes SIG/route coherentes (set unique, epaisseur constante).

### 6.2 Sources image utilisables (avec verification juridique finale)
- Unsplash (licence libre, restrictions sur revente "as-is").
- Pexels (licence libre, restrictions usages sensibles/endorsement).
- Wikimedia Commons (selon licence precise de chaque media).

### 6.3 Icnes recommandes
- Maki (map-centric, CC0) pour univers cartographique.
- Option complementaire UI generaliste: Tabler / Material Symbols (selon charte).

### 6.4 Regles de production image
- Taille hero desktop: >= 1920px de large.
- Compression webp/avif + fallback.
- Alt text obligatoire si image informative.
- Crer un registre de droits:
  - source,
  - auteur,
  - licence,
  - date de telechargement,
  - usage valide.

## 7) Plan d’execution R&D (rapide, industriel)

## Sprint 0 (2-3 jours) - cadrage qualité
- Audit heuristique complet (UX/UI/accessibilite).
- Audit composants (coherence tokens/states).
- Check legal (cookies, mentions, accessibilite).
- Backlog priorise P1/P2/P3.

Livrables:
- scorecard de conformite,
- liste ecarts + correctifs,
- plan de test utilisateurs.

## Sprint 1 (1 semaine) - socle design system
- Stabiliser grille/typographie/espacements.
- Normaliser boutons/champs/cartes/badges/alerts.
- Mettre en place patterns data cards et legal components.
- Uniformiser header/footer/cookie center.

Livrables:
- UI kit versionne,
- tokens semantiques + component tokens,
- pages pilotes homepage + cartotheque.

## Sprint 2 (1 semaine) - data/carto/accessibilite
- Fiche ressource "niveau pro".
- Listing interactif filtres/facettes/pagination.
- Carte interactive + mode alternatif accessible.
- Test clavier/screen reader/mobile.

Livrables:
- parcours complet "trouver -> comprendre -> telecharger",
- rapport de recette accessibilite,
- checklist de mise en prod.

## 8) KPI de qualite a suivre
- Taux de succes tache "trouver une ressource": > 85%.
- Temps median pour trouver un jeu de donnees: < 90 sec.
- Taux d’abandon recherche: -30% vs baseline.
- CWV (75e percentile):
  - LCP <= 2.5s
  - INP <= 200ms
  - CLS <= 0.1
- Score RGAA audit initial puis delta par sprint.

## 9) Limites de l’audit et hypotheses
- La page `data.iledefrance.fr/pages/home2025/` etant fortement rendue cote client, l’extraction automatique de contenu detaille peut etre partielle.
- Les principes de composition ont donc ete infers a partir:
  - de la structure detectee (navigation/footer),
  - de l’observation UX du portail de reference,
  - des standards institutionnels applicables.

## 10) Decision immediate proposee
- Maintenir les couleurs COM valides du logo (orange/bleu/vert/jaune) comme ancre unique.
- Basculer l’evolution du portail en mode "design system pilote par audit", avec criteres de validation publics (accessibilite, lisibilite, robustesse).

---

## Sources principales
- https://www.routesdeguadeloupe.fr/
- https://data.iledefrance.fr/pages/home2025/
- https://www.numerique.gouv.fr/actualites/nouvelle-version-rgaa-2026/
- https://www.numerique.gouv.fr/publications/rgaa-accessibilite/
- https://accessibilite.numerique.gouv.fr/
- https://www.w3.org/WAI/standards-guidelines/wcag/new-in-22/
- https://www.w3.org/news/2023/web-content-accessibility-guidelines-wcag-2-2-is-a-w3c-recommendation/
- https://www.w3.org/press-releases/2025/wcag22-iso-pas/
- https://design.numerique.gouv.fr/bien-concevoir/
- https://www.systeme-de-design.gouv.fr/version-courante/fr/
- https://www.cnil.fr/fr/cookies-et-autres-traceurs/regles/cookies
- https://www.cnil.fr/fr/bilan-sanctions-2025
- https://doc.data.gouv.fr/moissonnage/dcat/
- https://guides.data.gouv.fr/guides-open-data/guide-qualite
- https://guides.data.gouv.fr/guides-open-data/guide-qualite/ameliorer-la-qualite-dun-jeu-de-donnees-en-continu/ameliorer-le-score-de-qualite-des-metadonnees
- https://op.europa.eu/en/web/eu-vocabularies/dcat-ap
- https://www.ogc.org/publications/standard/ogcapi-features/
- https://www.w3.org/TR/sdw-bp/
- https://unsplash.com/license
- https://www.pexels.com/license/
- https://commons.wikimedia.org/wiki/Commons:Licensing
- https://github.com/mapbox/maki

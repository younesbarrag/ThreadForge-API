# ThreadForge API — MCD / MLD

## 1. Résumé du projet

ThreadForge API est une API REST headless développée avec Laravel.

Elle permet à un créateur de contenu tech de transformer automatiquement un contenu brut, comme des notes de développement, un article de blog ou un README GitHub, en posts optimisés pour X/Twitter grâce à l'intelligence artificielle.

Le projet repose sur plusieurs concepts importants :

* Authentification sécurisée avec Laravel Sanctum.
* Gestion des Campaign Blueprints pour centraliser les règles de style.
* Soumission de contenu brut.
* Génération asynchrone via Jobs & Queues.
* Structured Output IA avec le SDK laravel/ai.
* Gestion du cycle de vie des posts générés.
* Assistant conversationnel avec mémoire et tools Laravel.

---

## 2. MCD — Modèle Conceptuel de Données

Le MCD décrit les entités principales du projet et les relations entre elles, sans entrer dans les détails techniques des migrations Laravel.

### Entité : UTILISATEUR

L'utilisateur représente le créateur de contenu.
Il possède ses propres blueprints, contenus bruts, posts générés et conversations.

Attributs :

* id_utilisateur
* nom
* email
* mot_de_passe

---

### Entité : BLUEPRINT

Le blueprint représente une configuration de style réutilisable.
Il permet de définir les règles que l'IA doit respecter lors de la génération d'un post.

Attributs :

* id_blueprint
* nom
* audience_cible
* ton
* max_hashtags
* max_caracteres
* regles_supplementaires

---

### Entité : CONTENU_BRUT

Le contenu brut représente le texte source envoyé par l'utilisateur.
Il peut s'agir de notes techniques, d'un markdown, d'un article de blog ou d'un README.

Attributs :

* id_contenu_brut
* contenu
* type_source
* statut_traitement
* message_erreur

---

### Entité : POST_GENERE

Le post généré représente le résultat produit par l'IA après traitement du contenu brut.

Attributs :

* id_post_genere
* hook_propose
* body_points
* technical_readability_score
* suggested_hashtags
* tone_compliance_justification
* payload_brut
* statut_publication

---

### Entité : POST_VERSION

La version de post permet de conserver l'historique des variantes ou modifications proposées par l'assistant.

Attributs :

* id_post_version
* numero_version
* hook_propose
* body_points
* suggested_hashtags
* tone_compliance_justification
* payload_brut
* source

---

### Entité : CONVERSATION_AGENT

La conversation agent représente une discussion entre l'utilisateur et le Ghostwriter Assistant autour d'un post généré.

Attributs :

* id_conversation
* session_id

---

### Entité : MESSAGE_AGENT

Le message agent représente un message dans une conversation.

Attributs :

* id_message
* role
* content
* metadata

---

## 3. Relations et cardinalités

### UTILISATEUR — BLUEPRINT

Un utilisateur peut créer plusieurs blueprints.
Un blueprint appartient à un seul utilisateur.

Cardinalité :

* UTILISATEUR (1,N)
* BLUEPRINT (1,1)

---

### UTILISATEUR — CONTENU_BRUT

Un utilisateur peut soumettre plusieurs contenus bruts.
Un contenu brut appartient à un seul utilisateur.

Cardinalité :

* UTILISATEUR (1,N)
* CONTENU_BRUT (1,1)

---

### BLUEPRINT — CONTENU_BRUT

Un blueprint peut être utilisé pour structurer plusieurs contenus bruts.
Un contenu brut utilise un seul blueprint.

Cardinalité :

* BLUEPRINT (1,N)
* CONTENU_BRUT (1,1)

---

### CONTENU_BRUT — POST_GENERE

Un contenu brut peut ne pas encore avoir de post généré si le traitement async n'est pas terminé.
Après traitement réussi, un contenu brut génère un seul post.

Cardinalité :

* CONTENU_BRUT (0,1)
* POST_GENERE (1,1)

---

### UTILISATEUR — POST_GENERE

Un utilisateur peut posséder plusieurs posts générés.
Un post généré appartient à un seul utilisateur.

Cardinalité :

* UTILISATEUR (1,N)
* POST_GENERE (1,1)

---

### BLUEPRINT — POST_GENERE

Un blueprint peut produire plusieurs posts générés.
Un post généré est associé à un seul blueprint.

Cardinalité :

* BLUEPRINT (1,N)
* POST_GENERE (1,1)

---

### POST_GENERE — POST_VERSION

Un post généré peut avoir plusieurs versions.
Une version appartient à un seul post généré.

Cardinalité :

* POST_GENERE (1,N)
* POST_VERSION (1,1)

---

### POST_GENERE — CONVERSATION_AGENT

Un post généré peut avoir une conversation associée avec l'assistant.
Une conversation concerne un seul post généré.

Cardinalité :

* POST_GENERE (0,1)
* CONVERSATION_AGENT (1,1)

---

### CONVERSATION_AGENT — MESSAGE_AGENT

Une conversation contient plusieurs messages.
Un message appartient à une seule conversation.

Cardinalité :

* CONVERSATION_AGENT (1,N)
* MESSAGE_AGENT (1,1)

---

## 4. MLD — Modèle Logique de Données

Le MLD transforme les entités du MCD en tables relationnelles adaptées à Laravel.

---

## Table : users

| Colonne    | Type               | Description             |
| ---------- | ------------------ | ----------------------- |
| id         | BIGINT UNSIGNED PK | Identifiant utilisateur |
| name       | VARCHAR            | Nom de l'utilisateur    |
| email      | VARCHAR UNIQUE     | Email unique            |
| password   | VARCHAR            | Mot de passe hashé      |
| created_at | TIMESTAMP          | Date de création        |
| updated_at | TIMESTAMP          | Date de modification    |

---

## Table : campaign_blueprints

| Colonne          | Type                | Description                  |
| ---------------- | ------------------- | ---------------------------- |
| id               | BIGINT UNSIGNED PK  | Identifiant du blueprint     |
| user_id          | BIGINT UNSIGNED FK  | Référence vers users.id      |
| name             | VARCHAR             | Nom du blueprint             |
| target_audience  | VARCHAR nullable    | Audience ciblée              |
| tone             | VARCHAR             | Ton d'écriture               |
| max_hashtags     | INTEGER default 1   | Nombre maximum de hashtags   |
| max_characters   | INTEGER default 280 | Nombre maximum de caractères |
| additional_rules | JSON nullable       | Règles supplémentaires       |
| created_at       | TIMESTAMP           | Date de création             |
| updated_at       | TIMESTAMP           | Date de modification         |

Relations Laravel :

* User hasMany CampaignBlueprint
* CampaignBlueprint belongsTo User

---

## Table : raw_contents

| Colonne               | Type                    | Description                              |
| --------------------- | ----------------------- | ---------------------------------------- |
| id                    | BIGINT UNSIGNED PK      | Identifiant du contenu brut              |
| user_id               | BIGINT UNSIGNED FK      | Référence vers users.id                  |
| campaign_blueprint_id | BIGINT UNSIGNED FK      | Référence vers campaign_blueprints.id    |
| content               | LONGTEXT                | Contenu brut envoyé                      |
| source_type           | VARCHAR default text    | Type de source : text, markdown, readme  |
| processing_status     | VARCHAR default pending | Statut du traitement async               |
| error_message         | TEXT nullable           | Message d'erreur si le traitement échoue |
| created_at            | TIMESTAMP               | Date de création                         |
| updated_at            | TIMESTAMP               | Date de modification                     |

Valeurs possibles pour processing_status :

* pending
* processing
* completed
* failed

Relations Laravel :

* User hasMany RawContent
* RawContent belongsTo User
* CampaignBlueprint hasMany RawContent
* RawContent belongsTo CampaignBlueprint
* RawContent hasOne GeneratedPost

---

## Table : generated_posts

| Colonne                       | Type                      | Description                           |
| ----------------------------- | ------------------------- | ------------------------------------- |
| id                            | BIGINT UNSIGNED PK        | Identifiant du post généré            |
| user_id                       | BIGINT UNSIGNED FK        | Référence vers users.id               |
| campaign_blueprint_id         | BIGINT UNSIGNED FK        | Référence vers campaign_blueprints.id |
| raw_content_id                | BIGINT UNSIGNED FK UNIQUE | Référence vers raw_contents.id        |
| hook_propose                  | VARCHAR(280)              | Hook proposé par l'IA                 |
| body_points                   | JSON                      | Points principaux du post             |
| technical_readability_score   | INTEGER                   | Score de lisibilité technique         |
| suggested_hashtags            | JSON                      | Hashtags proposés                     |
| tone_compliance_justification | TEXT                      | Justification du respect du ton       |
| raw_payload                   | JSON nullable             | Réponse brute retournée par l'IA      |
| publication_status            | VARCHAR default draft     | Statut éditorial du post              |
| created_at                    | TIMESTAMP                 | Date de création                      |
| updated_at                    | TIMESTAMP                 | Date de modification                  |

Valeurs possibles pour publication_status :

* draft
* posted
* archived

Relations Laravel :

* GeneratedPost belongsTo User
* GeneratedPost belongsTo CampaignBlueprint
* GeneratedPost belongsTo RawContent
* GeneratedPost hasMany PostVersion
* GeneratedPost hasOne AgentConversation

---

## Table : post_versions

| Colonne                       | Type                      | Description                        |
| ----------------------------- | ------------------------- | ---------------------------------- |
| id                            | BIGINT UNSIGNED PK        | Identifiant de la version          |
| generated_post_id             | BIGINT UNSIGNED FK        | Référence vers generated_posts.id  |
| version_number                | INTEGER                   | Numéro de version                  |
| hook_propose                  | VARCHAR(280) nullable     | Hook de cette version              |
| body_points                   | JSON nullable             | Points principaux de cette version |
| suggested_hashtags            | JSON nullable             | Hashtags de cette version          |
| tone_compliance_justification | TEXT nullable             | Justification du ton               |
| raw_payload                   | JSON nullable             | Payload brut                       |
| source                        | VARCHAR default assistant | Origine de la version              |
| created_at                    | TIMESTAMP                 | Date de création                   |
| updated_at                    | TIMESTAMP                 | Date de modification               |

Relations Laravel :

* GeneratedPost hasMany PostVersion
* PostVersion belongsTo GeneratedPost

---

## Table : agent_conversations

| Colonne           | Type                      | Description                            |
| ----------------- | ------------------------- | -------------------------------------- |
| id                | BIGINT UNSIGNED PK        | Identifiant de la conversation         |
| user_id           | BIGINT UNSIGNED FK        | Référence vers users.id                |
| generated_post_id | BIGINT UNSIGNED FK UNIQUE | Référence vers generated_posts.id      |
| session_id        | VARCHAR                   | Identifiant de session de conversation |
| created_at        | TIMESTAMP                 | Date de création                       |
| updated_at        | TIMESTAMP                 | Date de modification                   |

Relations Laravel :

* User hasMany AgentConversation
* GeneratedPost hasOne AgentConversation
* AgentConversation belongsTo GeneratedPost
* AgentConversation hasMany AgentConversationMessage

---

## Table : agent_conversation_messages

| Colonne               | Type               | Description                           |
| --------------------- | ------------------ | ------------------------------------- |
| id                    | BIGINT UNSIGNED PK | Identifiant du message                |
| agent_conversation_id | BIGINT UNSIGNED FK | Référence vers agent_conversations.id |
| role                  | VARCHAR            | Rôle du message                       |
| content               | TEXT               | Contenu du message                    |
| metadata              | JSON nullable      | Métadonnées du message                |
| created_at            | TIMESTAMP          | Date de création                      |
| updated_at            | TIMESTAMP          | Date de modification                  |

Valeurs possibles pour role :

* user
* assistant
* tool

Relations Laravel :

* AgentConversation hasMany AgentConversationMessage
* AgentConversationMessage belongsTo AgentConversation

---

## 5. Remarques importantes

Les tables raw_contents et generated_posts sont séparées car elles n'ont pas le même cycle de vie.

raw_contents représente l'entrée utilisateur.
generated_posts représente la sortie produite par l'IA.

Le statut processing_status concerne le traitement asynchrone via Queue.
Le statut publication_status concerne l'organisation éditoriale du post.

Les champs body_points, suggested_hashtags, additional_rules, raw_payload et metadata sont stockés en JSON et seront castés en array dans les modèles Eloquent.

L'appel à l'IA doit être effectué dans un Job Laravel afin de retourner immédiatement une réponse HTTP 202 Accepted lors de la soumission d'un contenu brut.

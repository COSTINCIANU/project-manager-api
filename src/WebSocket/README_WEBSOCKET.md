# Migration WebSocket — Guide pour le jour J

## Stack recommandée

- **Symfony** : Mercure Bundle (le plus simple avec Symfony)
- **React** : EventSource API (natif, pas de lib)
- **Hébergeur** : VPS ou serveur dédié requis (pas compatible o2switch mutualisé)

## Étape 1 — Installer Mercure côté Symfony

```bash
composer require symfony/mercure-bundle
```

## Étape 2 — Configurer .env.local

<!-- MERCURE_URL=https://votre-hub-mercure.fr/.well-known/mercure
MERCURE_PUBLIC_URL=https://votre-hub-mercure.fr/.well-known/mercure
MERCURE_JWT_SECRET=votre_secret_jwt_mercure -->

## Étape 3 — Publier depuis ChatController.php

<!-- Remplacer le retour JSON simple par une publication Mercure : -->

```php
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

// Injecter HubInterface dans le constructeur
public function __construct(private HubInterface $hub) {}

// Dans la méthode create() après $em->flush() :
$update = new Update(
    'https://project-manager.costincianu.fr/chat',
    json_encode([
        'id' => $message->getId(),
        'content' => $message->getContent(),
        'senderEmail' => $message->getSenderEmail(),
        'senderName' => $message->getSenderName(),
        'createdAt' => $message->getCreatedAt()->format('Y-m-d H:i:s'),
    ])
);
$this->hub->publish($update);
```

## Étape 4 — Remplacer le polling dans PageChat.jsx

Remplacer le useEffect polling par EventSource :

```javascript
useEffect(() => {
    // SUPPRIMER le setInterval polling
    // REMPLACER par EventSource Mercure
    const url = new URL("https://votre-hub-mercure.fr/.well-known/mercure");
    url.searchParams.append(
        "topic",
        "https://project-manager.costincianu.fr/chat",
    );

    const es = new EventSource(url);

    es.onmessage = (e) => {
        const msg = JSON.parse(e.data);
        setMessages((prev) => [...prev, msg]);
        scrollToBottom();
    };

    es.onerror = (err) => {
        console.error("EventSource error:", err);
        es.close();
    };

    return () => es.close();
}, []);
```

## Étape 5 — Faire pareil pour PageActivite.jsx

Même principe — remplacer le polling par EventSource sur le topic activité.

## Hébergeurs compatibles Mercure

- **Railway** — très simple, plan gratuit disponible
- **Render** — plan gratuit disponible
- **VPS OVH** — à partir de 3€/mois
- **Heroku** — plan payant

## Notes importantes

- Le hub Mercure peut être hébergé séparément du backend Symfony
- Les clés JWT Mercure sont différentes des clés JWT Auth
- Prévoir CORS sur le hub Mercure

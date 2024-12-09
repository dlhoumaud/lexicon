<?php

require_once 'app/core/MarkovGenerator.php';

try {
    // Crée ou ouvre une base de données SQLite
    $pdo = new PDO('sqlite:data/database.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Crée une table si elle n'existe pas
    $pdo->exec("CREATE TABLE IF NOT EXISTS markov_models (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL UNIQUE,
        description TEXT,
        model_data TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
    exit(1);
}

// Fonction pour afficher un menu

function afficherMenu() {
    echo "\n=== Gestion des modèles Markov ===\n";
    echo "1. Ajouter un modèle\n";
    echo "2. Utiliser un modèle\n";
    echo "3. Supprimer un modèle\n";
    echo "4. Lister tous les modèles\n";
    echo "5. Mettre à jour un modèle\n";
    echo "6. Quitter\n";
    echo "Votre choix : ";
}

// Fonction pour ajouter un modèle
function ajouterModele($pdo) {
    $filePath = readline("Chemin vers le fichier texte : ");
    if (!file_exists($filePath)) {
        echo "Fichier non trouvé : $filePath\n";
        return;
    }

    $data = file_get_contents($filePath);
    $name = readline("Nom du modèle : ");
    $description = readline("Description du modèle : ");

    try {
        $markov = new MarkovGenerator($data);
        $markov->buildModel();
        $serializedModel = json_encode($markov->getModel());

        $stmt = $pdo->prepare("INSERT INTO markov_models (name, description, model_data) VALUES (:name, :description, :model_data)");
        $stmt->execute([
            ':name' => $name,
            ':description' => $description,
            ':model_data' => $serializedModel,
        ]);

        echo "Modèle '$name' ajouté avec succès.\n";
    } catch (Exception $e) {
        echo "Erreur lors de l'ajout du modèle : " . $e->getMessage() . "\n";
    }
}

// Fonction pour utiliser un modèle
function utiliserModele($pdo) {
    $name = readline("Nom du modèle à utiliser : ");

    try {
        $stmt = $pdo->prepare("SELECT model_data FROM markov_models WHERE name = :name");
        $stmt->execute([':name' => $name]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            echo "Modèle non trouvé.\n";
            return;
        }

        $modelData = json_decode($result['model_data'], true);
        $markov = new MarkovGenerator('');
        $markov->setModel($modelData);

        $question = readline("Écris quelque chose : ");
        if ($question == "exit") return;

        $answer = $markov->generateAnswer($question, 1000);
        echo "Réponse : " . $answer . "\n";
    } catch (Exception $e) {
        echo "Erreur lors de l'utilisation du modèle : " . $e->getMessage() . "\n";
    }
}

// Fonction pour supprimer un modèle
function supprimerModele($pdo) {
    $idname = readline("Nom ou ID du modèle à supprimer : ");

    try {
        $stmt = $pdo->prepare("DELETE FROM markov_models WHERE id = :idname or name = :idname");
        $stmt->execute([':idname' => $idname]);

        if ($stmt->rowCount() > 0) {
            echo "Modèle '$idname' supprimé avec succès.\n";
        } else {
            echo "Modèle non trouvé.\n";
        }
    } catch (Exception $e) {
        echo "Erreur lors de la suppression du modèle : " . $e->getMessage() . "\n";
    }
}

// Fonction pour lister tous les modèles
function listerModeles($pdo) {
    try {
        $stmt = $pdo->query("SELECT id, name, description, created_at FROM markov_models");
        $models = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($models)) {
            echo "Aucun modèle trouvé.\n";
            return;
        }

        echo "\n=== Liste des modèles ===\n";
        foreach ($models as $model) {
            echo "ID : " . $model['id'] . "\n";
            echo "Nom : " . $model['name'] . "\n";
            echo "Description : " . $model['description'] . "\n";
            echo "Créé le : " . $model['created_at'] . "\n";
            echo "--------------------------\n";
        }
    } catch (Exception $e) {
        echo "Erreur lors de la récupération des modèles : " . $e->getMessage() . "\n";
    }
}

function mettreAJourModele($pdo) {
    $name = readline("Nom du modèle à mettre à jour : ");

    try {
        $stmt = $pdo->prepare("SELECT id, name, description, model_data FROM markov_models WHERE name = :name");
        $stmt->execute([':name' => $name]);
        $model = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$model) {
            echo "Modèle non trouvé.\n";
            return;
        }

        echo "\n=== Mettre à jour le modèle ===\n";
        $newName = readline("Nouveau nom (laisser vide pour conserver le nom actuel) : ");
        $newDescription = readline("Nouvelle description (laisser vide pour conserver la description actuelle) : ");
        $updateData = readline("Mettre à jour les données du modèle à partir d'un fichier ? (oui/non) : ");

        $newModelData = $model['model_data']; // Par défaut, garder les anciennes données
        if (strtolower($updateData) === 'oui') {
            $filePath = readline("Chemin vers le fichier texte : ");
            if (file_exists($filePath)) {
                $data = file_get_contents($filePath);
                $markov = new MarkovGenerator($data);
                $markov->buildModel();
                $newModelData = json_encode($markov->getModel());
            } else {
                echo "Fichier non trouvé. Les anciennes données sont conservées.\n";
            }
        }

        $stmt = $pdo->prepare("
            UPDATE markov_models
            SET name = :new_name, description = :new_description, model_data = :new_model_data
            WHERE id = :id
        ");
        $stmt->execute([
            ':new_name' => $newName ?: $model['name'],
            ':new_description' => $newDescription ?: $model['description'],
            ':new_model_data' => $newModelData,
            ':id' => $model['id'],
        ]);

        echo "Modèle '$name' mis à jour avec succès.\n";
    } catch (Exception $e) {
        echo "Erreur lors de la mise à jour du modèle : " . $e->getMessage() . "\n";
    }
}


while (true) {
    afficherMenu();
    $choix = intval(readline());

    switch ($choix) {
        case 1:
            ajouterModele($pdo);
            break;
        case 2:
            utiliserModele($pdo);
            break;
        case 3:
            supprimerModele($pdo);
            break;
        case 4:
            listerModeles($pdo);
            break;
        case 5:
            mettreAJourModele($pdo);
            break;
        case 6:
            echo "Au revoir !\n";
            exit(0);
        default:
            echo "Choix invalide. Réessayez.\n";
    }
}
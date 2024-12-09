<?php

require_once '../app/core/MarkovGenerator.php';

$filePath = "data/philosophy/001.text";
if (file_exists($filePath)) {
    $data = file_get_contents($filePath);
} else {
    die("Fichier non trouvé : " . $filePath);
}

try {
    $markov = new MarkovGenerator($data);
    $markov->buildModel();
    // Générer une réponse à une question
    $question = "Qui est dieu ?";
    $answer = $markov->generateAnswer($question, 1000); 
    header('Content-Type: text/plain');    
    echo "Question : " . $question . "\n";
    echo "Réponse : " . $answer . "\n";
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage();
}

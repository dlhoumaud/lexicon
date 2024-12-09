<?php
/**
 * @ Author: David Lhoumaud
 * @ Create Time: 2024-11-12 10:27:58
 * @ Modified by: David Lhoumaud
 * @ Modified time: 2024-12-09 12:18:46
 * @ Description: outil de développement
 */

// Définition des options
$options = getopt("s:e:d:k:M:S:c:n:r:v:h", ["server:", "encrypt:", "decrypt:", "key:", "migrate:", "seed:", "create:", "name:", "route:","view:", "help"]);

// Affichage de l'aide si l'option -h ou --help est utilisée
if (isset($options['h']) || isset($options['help'])) {
    echo "Lexicon - IA Générative en PHP\n
Copyright (C) 2024  David Lhoumaud
This program comes with ABSOLUTELY NO WARRANTY.
This is free software, and you are welcome to redistribute it
under certain conditions.\n\n"
    . "Utilisation : php bin/morty.php [options]\n"
    . "Options disponibles :\n"
    . "  -s, --server [adresse:port]             : Lance un serveur de développement PHP à l'adresse et au port spécifiés.\n"
    . "  -h, --help                              : Affiche ce message d'aide.\n";
    exit(0);
}

// Vérification de l'option -s pour lancer le serveur
if (isset($options['s']) || isset($options['server'])) {
    $host = 'localhost'; // L'adresse de votre serveur
    $port = 8000; // Le port sur lequel le serveur écoutera

    // Le répertoire racine de votre projet (assurez-vous que 'public' existe)
    $documentRoot = './public'; // Utiliser le répertoire public
    
    // Récupération de l'adresse et du port
    $address = isset($options['s']) ? $options['s'] : $options['server'];
    if (!$address) {
        $address = $host . ':' . $port;
    }

    // Vérifiez si le répertoire public existe avant de démarrer le serveur
    if (!is_dir($documentRoot)) {
        error_log("Le répertoire '$documentRoot' n'existe pas.");
        exit(1);
    }

    // Démarrer le serveur de développement PHP
    echo "Lancement du serveur PHP sur http://$address\n";
    // echo "Lancement du serveur PHP sur http://$address\n";

    // Si vous utilisez exec(), vous pouvez également capturer la sortie d'erreur
    exec("php -S $address -t $documentRoot", $output, $returnVar);

    // Si une erreur se produit avec exec(), afficher un message d'erreur
    if ($returnVar !== 0) {
        error_log("Erreur lors du démarrage du serveur PHP :");
        echo implode("\n", $output);
        exit(1);
    }
    exit(0);
}

// Si aucune option valide n'est fournie
error_log("aucune option valide fournie. Utilisez -h ou --help pour l'aide.");
exit(1);

?>

<?php
/**
 * @ Author: David Lhoumaud
 * @ Create Time: 2024-12-09 17:14:43
 * @ Modified by: David Lhoumaud
 * @ Modified time: 2024-12-09 17:40:07
 * @ Description: Classe générant du texte basé sur un modèle Markov.
 */

class MarkovGenerator
{
    private $data; // Texte brut d'entrée pour générer le modèle.
    private $model = []; // Modèle de n-grammes.
    private $nGramSize; // Taille des n-grammes (au moins 2).
    private $stopWords; // Liste des mots à ignorer.

    /**
     * Constructeur de la classe MarkovGenerator.
     * 
     * @param string $data Le texte brut à analyser pour construire le modèle.
     * @param int $nGramSize Taille des n-grammes.
     * @param array $stopWords Liste des mots à ignorer lors de la génération.
     */
    public function __construct(string $data, int $nGramSize = 2, array $stopWords = [])
    {
        if ($nGramSize < 2) {
            throw new InvalidArgumentException("La taille des n-grammes doit être au moins 2.");
        }
        
        $this->data = $data;
        $this->nGramSize = $nGramSize;
        $this->stopWords = empty($stopWords) ? $this->getDefaultStopWords() : $stopWords;
    }

    /**
     * Récupère la liste des mots à ignorer par défaut.
     * 
     * @return array Liste des mots à ignorer.
     */
    private function getDefaultStopWords(): array
    {
        return [
            'de', 'le', 'la', 'et', 'les', 'des', 'un', 'une', 'en', 'du', 
            'au', 'aux', 'ce', 'cet', 'cette', 'ça', 'qui', 'que', 'quoi', 'dont'
        ];
    }

    /**
     * Construit le modèle de n-grammes à partir des données.
     * 
     * @throws InvalidArgumentException Si les données sont vides.
     * @throws RuntimeException Si les données sont insuffisantes pour créer des n-grammes.
     */
    public function buildModel(): void
    {
        if (empty($this->data)) {
            throw new InvalidArgumentException("Les données fournies sont vides.");
        }

        $words = $this->getCleanedWords($this->data);
        $wordCount = count($words);

        if ($wordCount < $this->nGramSize) {
            throw new RuntimeException("Pas assez de données pour construire des n-grammes.");
        }

        // Construction du modèle en créant des n-grammes et en comptant les occurrences.
        for ($i = 0; $i <= $wordCount - $this->nGramSize; $i++) {
            $nGram = implode(' ', array_slice($words, $i, $this->nGramSize - 1));
            $nextWord = $words[$i + $this->nGramSize - 1];

            $this->model[$nGram][$nextWord] = ($this->model[$nGram][$nextWord] ?? 0) + 1;
        }
    }

    /**
     * Récupère le modèle construit.
     * 
     * @return array Le modèle de n-grammes.
     */
    public function getModel(): array
    {
        return $this->model;
    }

    /**
     * Sets the model for the Markov generator.
     *
     * @param $model The model to be set.
     */
    public function setModel($model): void {
        $this->model = $model;
    }

    /**
     * Génère un texte à partir d'une phrase de départ.
     * 
     * @param string $startPhrase Phrase de départ pour commencer la génération.
     * @param int $length Longueur du texte généré.
     * @return string Texte généré.
     */
    public function generateText(string $startPhrase, int $length): string
    {
        if (empty($this->model)) {
            throw new RuntimeException("Le modèle n'a pas encore été construit.");
        }

        $words = $this->getCleanedWords($startPhrase);
        $nGram = implode(' ', array_slice($words, -($this->nGramSize - 1)));

        // Si le n-gramme de départ est invalide, on prend un n-gramme au hasard.
        if (!isset($this->model[$nGram])) {
            $nGram = array_rand($this->model); 
        }

        $generatedText = explode(' ', $nGram);
        $previousWords = [];

        // Génération du texte par ajout de mots un à un.
        for ($i = count($generatedText); $i < $length; $i++) {
            if (!isset($this->model[$nGram])) {
                break;
            }

            $nextWord = $this->getNextWord($this->model[$nGram], $previousWords);
            $generatedText[] = $nextWord;
            $previousWords[] = $nextWord;

            $nGram = implode(' ', array_slice($generatedText, -($this->nGramSize - 1)));
        }

        return $this->applyCapitalization($generatedText);
    }

    /**
     * Génère une réponse à une question donnée.
     * 
     * @param string $question La question à laquelle répondre.
     * @param int $limit Longueur maximale de la réponse.
     * @return string Réponse générée.
     */
    public function generateAnswer(string $question, int $limit = 25): string
    {
        if (empty($question) || substr(trim($question), -1) !== '?') {
            throw new InvalidArgumentException("La phrase posée n'est pas une question.");
        }

        $cleanedQuestion = $this->getCleanedWords($question);

        if (empty($cleanedQuestion)) {
            throw new InvalidArgumentException("La question ne contient pas de mots valides.");
        }

        // Génération d'une réponse
        $answer = $this->generateText($question, rand(10, $limit));

        // Nettoyage et élimination des mots redondants de la réponse
        $answerWords = $this->getCleanedWords($answer);
        $answerWords = array_diff($answerWords, $cleanedQuestion);
        $finalAnswer = implode(' ', $answerWords);

        if (empty($finalAnswer)) {
            $finalAnswer = $this->generateText('', rand(5, $limit));
        }

        return ucfirst($finalAnswer);
    }

    /**
     * Sélectionne le mot suivant à partir d'un n-gramme donné.
     * 
     * @param array $wordCounts Liste des mots possibles et leurs occurrences.
     * @param array $previousWords Liste des mots précédemment générés pour éviter les répétitions.
     * @return string Le mot suivant choisi.
     */
    private function getNextWord(array $wordCounts, array $previousWords): string
    {
        $totalOccurrences = array_sum($wordCounts);
        $rand = mt_rand(1, $totalOccurrences);
        $cumulative = 0;

        foreach ($wordCounts as $word => $count) {
            $cumulative += $count;
            if ($rand <= $cumulative) {
                // Eviter la répétition immédiate du mot précédent
                if (in_array($word, $previousWords)) {
                    continue;
                }
                return $word;
            }
        }

        return array_rand($wordCounts); // Fallback
    }

    /**
     * Nettoie et prépare les mots en enlevant la ponctuation et les mots vides.
     * 
     * @param string $data Texte à nettoyer.
     * @return array Liste de mots nettoyés.
     */
    private function getCleanedWords(string $data): array
    {
        $cleanedData = strtolower($data);
        $cleanedData = preg_replace('/[^\w\s,.!?\'-]/u', '', $cleanedData);

        $words = preg_split('/\s+/', $cleanedData, -1, PREG_SPLIT_NO_EMPTY);

        // Filtrage des mots de moins de 3 caractères et des mots vides
        $filteredWords = array_filter($words, function ($word) {
            return strlen($word) > 2 && !in_array($word, $this->stopWords);
        });

        return array_values($filteredWords);
    }

    /**
     * Applique une capitalisation correcte au texte généré.
     * 
     * @param array $words Liste de mots à capitaliser.
     * @return string Texte correctement capitalisé.
     */
    private function applyCapitalization(array $words): string
    {
        $words[0] = ucfirst($words[0]);

        for ($i = 1; $i < count($words); $i++) {
            $words[$i] = strtolower($words[$i]);
        }

        return implode(' ', $words);
    }
}

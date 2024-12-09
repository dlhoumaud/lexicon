<?php

class MarkovGenerator
{
    private $data;        // Données textuelles brutes
    private $model = [];  // Modèle des relations entre mots

    public function __construct(string $data)
    {
        $this->data = $data;
    }

    public function buildModel(): void
    {
        if (empty($this->data)) {
            throw new InvalidArgumentException("Les données fournies sont vides.");
        }

        $words = $this->getCleanedWords($this->data);
        $wordCount = count($words);

        if ($wordCount < 2) {
            throw new RuntimeException("Les données ne contiennent pas assez de mots pour construire un modèle.");
        }

        for ($i = 0; $i < $wordCount - 1; $i++) {
            $currentWord = $words[$i];
            $nextWord = $words[$i + 1];

            if (!array_key_exists($currentWord, $this->model)) {
                $this->model[$currentWord] = [];
            }

            if (array_key_exists($nextWord, $this->model[$currentWord])) {
                $this->model[$currentWord][$nextWord]++;
            } else {
                $this->model[$currentWord][$nextWord] = 1;
            }
        }
    }

    public function generateText(string $startPhrase, int $length): string
    {
        if (empty($this->model)) {
            throw new RuntimeException("Le modèle n'a pas encore été construit. Veuillez appeler buildModel() d'abord.");
        }

        // Nettoyage des mots de la phrase de départ
        $words = $this->getCleanedWords($startPhrase);

        // Si la phrase est vide ou ne contient pas de mots valides, utiliser une phrase de secours
        if (empty($words)) {
            $words = ['Cette', 'réponse', 'sera', 'générée', 'sans', 'nettoyage'];
        }

        $generatedText = $words;
        $currentWord = end($words); // Dernier mot de la phrase de départ

        for ($i = 0; $i < $length - count($words); $i++) {
            $nextWord = $this->getNextWord($currentWord);
            if (!$nextWord)
                break;
            $generatedText[] = $nextWord;
            $currentWord = $nextWord;
        }

        // Appliquer la majuscule après un point
        $finalText = $this->applyCapitalization($generatedText);

        return $finalText;
    }

    public function generateAnswer(string $question, int $limit): string
    {
        // Vérifier si la question est valide (doit finir par un ?)
        if (empty($question) || substr(trim($question), -1) !== '?') {
            throw new InvalidArgumentException("La phrase posée n'est pas une question.");
        }

        // Nettoyer la question pour éviter les répétitions dans la réponse
        $cleanedQuestion = $this->getCleanedWords($question);

        // Si la question ne contient pas de mots valides après nettoyage, générer une réponse par défaut
        if (empty($cleanedQuestion)) {
            throw new InvalidArgumentException("La question ne contient pas de mots valides.");
        }

        // Générer une réponse initiale à partir de la question sans inclure les mots de la question
        $answer = $this->generateText($question, rand(10, $limit)); // Réponse générée avec une longueur variable de 5 à $limit mots

        // Nettoyer la réponse
        $answerWords = $this->getCleanedWords($answer);

        // Filtrer les mots de la réponse pour éviter de répéter les mots de la question
        // Garder les mots qui ne sont pas dans la question
        $answerWords = array_diff($answerWords, $cleanedQuestion);

        // Réassembler la réponse
        $finalAnswer = implode(' ', $answerWords);

        // Si la réponse est vide (par exemple, la suppression de tous les mots), générer une réponse par défaut
        if (empty($finalAnswer)) {
            // Si la réponse est vide, régénérer une nouvelle réponse sans tenter de supprimer des mots
            $finalAnswer = $this->generateText('', rand(5, $limit)); // Nouvelle tentative sans filtrage
        }

        // Retourner la réponse avec une première lettre en majuscule
        return ucfirst($finalAnswer);
    }

    private function getNextWord(string $currentWord): ?string
    {
        if (!array_key_exists($currentWord, $this->model)) {
            return null;
        }

        $nextWords = $this->model[$currentWord];
        $totalOccurrences = array_sum($nextWords);

        $rand = mt_rand(1, $totalOccurrences);
        $cumulative = 0;

        foreach ($nextWords as $word => $count) {
            $cumulative += $count;
            if ($rand <= $cumulative) {
                return $word;
            }
        }

        return null;
    }

    private function getCleanedWords(string $data): array
    {
        // Conserver les apostrophes, virgules et autres ponctuations
        $cleanedData = strtolower($data);
        // Supprimer tout caractère qui n'est pas un mot ou un espace (mais garder apostrophes et virgules)
        $cleanedData = preg_replace('/[^\w\s,.\'-]/u', '', $cleanedData);
        return preg_split('/\s+/', $cleanedData, -1, PREG_SPLIT_NO_EMPTY);
    }

    private function applyCapitalization(array $words): string
    {
        // Capitaliser le premier mot
        if (count($words) > 0) {
            $words[0] = ucfirst($words[0]);
        }

        // Appliquer une majuscule après chaque point
        for ($i = 1; $i < count($words); $i++) {
            if (substr($words[$i - 1], -1) === '.') {
                $words[$i] = ucfirst($words[$i]);
            }
        }

        return implode(' ', $words);
    }
}


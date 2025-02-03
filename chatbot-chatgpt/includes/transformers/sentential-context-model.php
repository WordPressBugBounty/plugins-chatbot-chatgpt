<?php
/**
 * Kognetiks Chatbot for WordPress - Transformer Model - Sentential Context Model (SCM) - Ver 2.2.1
 *
 * This file contains the code for implementing an enhanced Transformer-like algorithm in PHP.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Main function to get the chatbot's response
function transformer_model_sentential_context_model_response($input, $responseCount = 500) {

    // DIAG - Diagnostic - Ver 2.2.1
    // back_trace( 'NOTICE', 'transformer_model_sentential_context_model_sentential_context_response');

    // Normalize the input string - Ver 2.2.2
    if (class_exists('Normalizer')) {
        $input = Normalizer::normalize($input, Normalizer::FORM_C);
    }

    // MOVED TO transformer-model-scheduler.php
    // Fetch WordPress content
    $corpus = transformer_model_sentential_context_fetch_wordpress_content( $input );

    // Set the window size for co-occurrence matrix
    $windowSize = intval(esc_attr(get_option('chatbot_transformer_model_word_content_window_size', 3)));
    // DIAG - Diagnostic - Ver 2.2.1
    // back_trace( 'NOTICE', 'Window Size: ' . $windowSize);

    // DIAG - Diagnostic - Ver 2.2.1
    // back_trace( 'NOTICE', 'Response Count: ' . $responseCount);

    // MOVED TO transformer-model-scheduler.php
    // Build embeddings (with caching for performance)
    $embeddings = transformer_model_sentential_context_get_cached_embeddings($corpus, $windowSize);

    // Generate contextual response
    $response = transformer_model_sentential_context_generate_contextual_response($input, $embeddings, $corpus, $responseCount);

    return $response;

}

// Function to fetch WordPress page and post content
function transformer_model_sentential_context_fetch_wordpress_content($input = null) {

    // DIAG - Diagnostic - Ver 2.2.1
    // back_trace( 'NOTICE', 'transformer_model_sentential_context_fetch_wordpress_content');

    global $wpdb;
    global $no_matching_content_response;

    // Only fetch content with words from the input
    if (empty($input)) {
        return '';
    }
    // DIAG - Diagnostics - Ver 2.2.1
    // back_trace( 'NOTICE', '$input: ' . $input);

    // Step 1 - Normalize and remove stop words
    // $input = preg_replace('/[^\w\s]/', '', $input);
    $input = preg_replace('/[^\p{L}\s]/u', '', $input); // Ver 2.2.2
    // $words = array_filter(array_map('trim', explode(' ', strtolower($input))));
    $words = array_filter(array_map('trim', explode(' ', mb_strtolower($input, 'UTF-8')))); // Ver 2.2.2
    $words = transformer_model_sentential_context_remove_stop_words($words);

    // Step 2 - Query the TF-IDF table for the highest-scoring words
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_knowledge_base_tfidf';
    $limit = intval(esc_attr(get_option('chatbot_transformer_model_word_content_window_size', 3)));

    $results = [];
    if (!empty($words)) {
        $placeholders = implode(',', array_fill(0, count($words), '%s'));
        $query = $wpdb->prepare(
            "SELECT word, score FROM $table_name WHERE word IN ($placeholders) ORDER BY score DESC",
            $words
        );
        $rows = $wpdb->get_results($query);

        if ($wpdb->last_error) {
            prod_trace( 'ERROR', 'WordPress database error: ' . $wpdb->last_error);
        } elseif (!empty($rows)) {
            foreach ($rows as $row) {
                $results[] = ['word' => $row->word, 'score' => $row->score];
            }
        }
    }

    // Step 3 - Supplement results with remaining words, longest first
    usort($words, function($a, $b) {
        return strlen($b) <=> strlen($a);
    });

    $existing_words = array_column($results, 'word');
    $remaining_words = array_diff($words, $existing_words);

    // DIAG - Diagnostic - Ver 2.2.1 - Print the words and scores
    // for ($i = 0; $i < count($results); $i++) {
    //     back_trace( 'NOTICE', 'Word: ' . $results[$i]['word'] . ' - Score: ' . $results[$i]['score']);
    // }

    // Ensure results meet the limit
    if (count($results) > $limit) {
        $results = array_slice($results, 0, $limit);
    }

    // foreach ($remaining_words as $word) {
    //     if (count($results) >= $limit) {
    //         break;
    //     }
    //     $results[] = ['word' => $word, 'score' => 0];
    // }

    $results = array_merge($results, array_map(function($word) {
        return ['word' => $word, 'score' => 0];
    }, $remaining_words));

    // Define the window size
    $window_size = get_option('chatbot_transformer_model_word_content_windows_size', 3); // Default to 3 if not set

    // Step 4 - Build the LIKE condition
    $final_words = array_column($results, 'word');
    $like_conditions = [];

    // Use a sliding window to group words
    for ($i = 0; $i <= count($final_words) - $window_size; $i++) {
        $group = array_slice($final_words, $i, $window_size);
        $group_clauses = [];
        foreach ($group as $word) {
            $escaped_word = $wpdb->esc_like($word);
            $group_clauses[] = "post_content LIKE '%" . esc_sql($escaped_word) . "%'";
        }
        $like_conditions[] = '(' . implode(' AND ', $group_clauses) . ')';
    }

    // Combine all groups with OR
    $like_condition = implode(' OR ', $like_conditions);

    // DIAG - Diagnostic - Ver 2.2.1
    // back_trace( 'NOTICE', 'Like Condition: ' . $like_condition);

    // Handle error for no matching content
    if (empty($like_condition)) {
        return $no_matching_content_response[array_rand($no_matching_content_response)];
    }

    // Step 5 - Fetch WordPress content
    $sql = $wpdb->prepare("
        SELECT post_content
        FROM {$wpdb->posts}
        WHERE post_status = %s
        AND (post_type = %s OR post_type = %s)
    ", 'publish', 'post', 'page');

    $sql .= " AND ({$like_condition})";

    $results = $wpdb->get_results($sql, ARRAY_A);

    // Combine content into a single string
    $content = '';
    if (!empty($results)) {
        foreach ($results as $row) {
            $content .= $row['post_content'] . ' ';
        }
    } else {
        $content = $no_matching_content_response[array_rand($no_matching_content_response)];
    }

    // Clean and return content
    $content = strip_tags($content);
    $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5);

    return $content;

}

// Function to build or retrieve cached embeddings
function transformer_model_sentential_context_get_cached_embeddings($corpus, $windowSize = 3) {

    // DIAG - Diagnostic - Ver 2.2.1
    // back_trace( 'NOTICE', 'transformer_model_sentential_context_get_cached_embeddings');

    $embeddings = transformer_model_sentential_context_build_cooccurrence_matrix($corpus, $windowSize);

    return $embeddings;

}

// Function to build a co-occurrence matrix for word embeddings
function transformer_model_sentential_context_build_cooccurrence_matrix($corpus, $windowSize = 3) {

    // DIAG - Diagnostic - Ver 2.2.1
    // back_trace( 'NOTICE', 'transformer_model_sentential_context_build_cooccurrence_matrix');

    $matrix = [];
    $words = preg_split('/\s+/', strtolower($corpus)); // Tokenize and normalize
    $words = transformer_model_sentential_context_remove_stop_words($words); // Remove stop words

    foreach ($words as $i => $word) {

        if (!isset($matrix[$word])) {
            $matrix[$word] = [];
        }

        for ($j = max(0, $i - $windowSize); $j <= min(count($words) - 1, $i + $windowSize); $j++) {
            if ($i !== $j) {
                if (isset($words[$j])) {
                    $contextWord = $words[$j];
                } else {
                    // Handle the case where the index does not exist
                    $contextWord = null; // or any default value
                }
                $matrix[$word][$contextWord] = ($matrix[$word][$contextWord] ?? 0) + 1;
            }
        }
    }

    return $matrix;

}

// Function to remove stop words from an array of words
function transformer_model_sentential_context_remove_stop_words($words) {

    // DIAG - Diagnostic - Ver 2.2.1
    // back_trace( 'NOTICE', 'transformer_model_sentential_context_remove_stop_words');

    // Use global stop words list
    global $stopWords;

    if (!is_array($stopWords)) {
        $stopWords = array(); // Ensure $stopWords is an array
    }

    return array_diff($words, $stopWords);

}

// Function to calculate cosine similarity between two vectors
function transformer_model_sentential_context_cosine_similarity($vectorA, $vectorB) {

    // DIAG - Diagnostic - Ver 2.2.1
    // back_trace( 'NOTICE', 'transformer_model_sentential_context_cosine_similarity' );

    $commonKeys = array_intersect_key($vectorA, $vectorB);

    if (empty($commonKeys)) {
        return 0;
    }

    $dotProduct = 0;
    $magnitudeA = 0;
    $magnitudeB = 0;

    foreach ($commonKeys as $key => $value) {
        $dotProduct += $vectorA[$key] * $vectorB[$key];
    }

    foreach ($vectorA as $value) {
        $magnitudeA += $value * $value;
    }

    foreach ($vectorB as $value) {
        $magnitudeB += $value * $value;
    }

    $magnitudeA = sqrt($magnitudeA);
    $magnitudeB = sqrt($magnitudeB);

    return ($magnitudeA * $magnitudeB) ? $dotProduct / ($magnitudeA * $magnitudeB) : 0;

}

function transformer_model_sentential_context_generate_contextual_response($input, $embeddings, $corpus, $maxTokens = 500) {

    // DIAG - Diagnostic - Ver 2.3.0
    // back_trace( 'NOTICE', 'transformer_model_sentential_context_generate_contextual_response');
    // back_trace( 'NOTICE', 'Max Tokens: ' . $maxTokens);

    // Tokenize the corpus into sentences
    $sentences = preg_split('/(?<=[.?!])\s+/', $corpus);
    $sentenceVectors = [];

    // Compute embeddings for sentences
    foreach ($sentences as $index => $sentence) {

        $sentenceWords = preg_split('/\s+/', strtolower($sentence));
        $sentenceWords = transformer_model_sentential_context_remove_stop_words($sentenceWords); // Remove stop words
        $sentenceVector = [];
        $wordCount = 0;

        foreach ($sentenceWords as $word) {

            if (isset($embeddings[$word])) {
                foreach ($embeddings[$word] as $contextWord => $value) {
                    $sentenceVector[$contextWord] = ($sentenceVector[$contextWord] ?? 0) + $value;
                }
                $wordCount++;
            }

        }

        // Normalize the sentence vector
        if ($wordCount > 0) {
            foreach ($sentenceVector as $key => $value) {
                $sentenceVector[$key] /= $wordCount;
            }
        }

        $sentenceVectors[$index] = $sentenceVector;
    }

    // Compute the input vector
    $inputWords = preg_split('/\s+/', strtolower($input));
    $inputWords = transformer_model_sentential_context_remove_stop_words($inputWords); // Remove stop words
    $inputVector = [];
    $wordCount = 0;

    foreach ($inputWords as $word) {

        if (isset($embeddings[$word])) {
            foreach ($embeddings[$word] as $contextWord => $value) {
                $inputVector[$contextWord] = ($inputVector[$contextWord] ?? 0) + $value;
            }
            $wordCount++;
        }

    }

    // Normalize the input vector
    if ($wordCount > 0) {
        foreach ($inputVector as $key => $value) {
            $inputVector[$key] /= $wordCount;
        }
    }

    // Compute similarities
    $similarities = [];

    foreach ($sentenceVectors as $index => $vector) {

        $similarity = transformer_model_sentential_context_cosine_similarity($inputVector, $vector);
        $similarities[$index] = $similarity;

    }

    // Find the index of the most similar sentence
    arsort($similarities);
    $bestMatchIndex = key($similarities);
    $bestMatchSentence = trim($sentences[$bestMatchIndex]);

    // Initialize the response
    $response = $bestMatchSentence;

    // Retrieve settings
    $maxSentences = intval(esc_attr(get_option('chatbot_transformer_model_sentence_response_length', 20)));
    $maxTokens = intval(esc_attr(get_option('chatbot_transformer_model_max_tokens', 10000)));

    // Ratios for splitting sentences and tokens
    $sentenceBeforeRatio = floatval(esc_attr(get_option('chatbot_transformer_model_leading_sentences_ratio', '0.2')));  // 20% of sentences before
    $tokenBeforeRatio = floatval(esc_attr(get_option('chatbot_transformer_model_leading_token_ratio', '0.2')));         // 20% of tokens before

    // Distribute sentences and tokens
    $sentencesBefore = floor($maxSentences * $sentenceBeforeRatio);
    $sentencesAfter = $maxSentences - $sentencesBefore;
    $tokensBefore = floor($maxTokens * $tokenBeforeRatio);
    $tokensAfter = $maxTokens - $tokensBefore;

    $responseWordCount = str_word_count($response);

    // Add sentences before the best match
    $tokensUsedBefore = 0;
    $sentencesUsedBefore = 0;

    for ($i = $bestMatchIndex - 1; $i >= 0 && $sentencesUsedBefore < $sentencesBefore && $tokensUsedBefore < $tokensBefore; $i--) {

        $previousSentence = trim($sentences[$i]);
        $sentenceWordCount = str_word_count($previousSentence);
        if ($tokensUsedBefore + $sentenceWordCount <= $tokensBefore) {
            $response = $previousSentence . ' ' . $response;
            $tokensUsedBefore += $sentenceWordCount;
            $sentencesUsedBefore++;
        } else {
            break; // Stop if adding this sentence exceeds the token limit
        }

    }

    // Add sentences after the best match
    $tokensUsedAfter = 0;
    $sentencesUsedAfter = 0;

    for ($i = $bestMatchIndex + 1; $i < count($sentences) && $sentencesUsedAfter < $sentencesAfter && $tokensUsedAfter < $tokensAfter; $i++) {

        $nextSentence = trim($sentences[$i]);
        $sentenceWordCount = str_word_count($nextSentence);
        if ($tokensUsedAfter + $sentenceWordCount <= $tokensAfter) {
            $response .= ' ' . $nextSentence;
            $tokensUsedAfter += $sentenceWordCount;
            $sentencesUsedAfter++;
        } else {
            break; // Stop if adding this sentence exceeds the token limit
        }

    }

    // Calculate key stats
    $similarityThreshold = floatval(esc_attr(get_option('chatbot_transformer_model_similarity_threshold', 0.5)));
    $highestSimilarity = max($similarities);
    $averageSimilarity = array_sum($similarities) / count($similarities);

    $matchesAboveThreshold = array_filter($similarities, function($similarity) use ($similarityThreshold) {
        return $similarity > $similarityThreshold;
    });
    $numMatchesAboveThreshold = count($matchesAboveThreshold);
    $totalSentencesAnalyzed = count($sentences);

    // Before returning repsonse log the key stats
    // back_trace( 'NOTICE', 'Key Stats:');
    // back_trace( 'NOTICE', ' - Input: ' . $input);
    // back_trace( 'NOTICE', ' - Similarity Threshold: ' . $similarityThreshold);
    // back_trace( 'NOTICE', ' - Highest Similarity: ' . $highestSimilarity);
    // back_trace( 'NOTICE', ' - Average Similarity: ' . $averageSimilarity);
    // back_trace( 'NOTICE', ' - Matches Above Threshold: ' . $numMatchesAboveThreshold);
    // back_trace( 'NOTICE', ' - Total Sentences Analyzed: ' . $totalSentencesAnalyzed);

    // Return the response
    return $response;

}
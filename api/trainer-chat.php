<?php
/**
 * PokéTrainer AI Backend API
 * Connects to the OpenAI Responses API for intelligent Pokémon card responses
 * 
 * Endpoint: /api/trainer-chat.php
 * Methods: POST
 */

// Prevent output buffering issues
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

bootstrapEnvironment(__DIR__ . '/..');

$apiKey = readEnvironmentValue('OPENAI_API_KEY');
$hasCurl = extension_loaded('curl');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'error' => 'Method not allowed',
        'success' => false
    ]);
    exit;
}

// Get POST data
$inputData = json_decode(file_get_contents('php://input'), true);

if (!$inputData) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Invalid JSON input',
        'success' => false
    ]);
    exit;
}

$action = $inputData['action'] ?? null;

// Health check endpoint
if ($action === 'health_check') {
    $issues = [];

    if (!$apiKey) {
        $issues[] = 'Missing OPENAI_API_KEY';
    }

    if (!$hasCurl) {
        $issues[] = 'PHP cURL extension is not enabled';
    }

    echo json_encode([
        'success' => empty($issues),
        'status' => empty($issues) ? 'connected' : 'degraded',
        'message' => empty($issues) ? 'Backend AI is ready' : implode('. ', $issues),
        'provider' => 'openai'
    ]);
    exit;
}

if (!$apiKey) {
    http_response_code(500);
    echo json_encode([
        'error' => 'OpenAI API key not configured. Please set OPENAI_API_KEY in .env or the server environment.',
        'success' => false
    ]);
    exit;
}

if (!$hasCurl) {
    http_response_code(500);
    echo json_encode([
        'error' => 'PHP cURL extension is required for the AI backend.',
        'success' => false
    ]);
    exit;
}

// Chat endpoint
if ($action === 'chat') {
    $userMessage = $inputData['message'] ?? '';
    $history = $inputData['history'] ?? [];

    if (empty($userMessage)) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Message cannot be empty',
            'success' => false
        ]);
        exit;
    }

    try {
        $response = callOpenAIResponsesAPI($apiKey, $userMessage, $history);
        echo json_encode([
            'success' => true,
            'reply' => $response,
            'message' => 'Response generated successfully'
        ]);
    } catch (Throwable $e) {
        $statusCode = $e->getCode();
        if (!is_int($statusCode) || $statusCode < 400 || $statusCode > 599) {
            $statusCode = 500;
        }

        http_response_code($statusCode);
        echo json_encode([
            'error' => $e->getMessage(),
            'success' => false
        ]);
    }
    exit;
}

// Invalid action
http_response_code(400);
echo json_encode([
    'error' => 'Invalid action. Use "chat" or "health_check"',
    'success' => false
]);

/**
 * Call OpenAI Responses API with system prompt for Pokémon expertise.
 */
function callOpenAIResponsesAPI($apiKey, $userMessage, $conversationHistory = [])
{
    $systemPrompt = <<<'PROMPT'
You are Trainer Dex, a knowledgeable Pokémon TCG (Trading Card Game) expert and retro Pokémon trainer. You embody the personality of a nostalgic Game Boy-era trainer who knows everything about Pokémon cards, pricing, rarity, sets, and collecting.

Your personality:
- Enthusiastic about Pokémon and cards
- Uses casual, friendly language ("Yo!", "That's sick!", "Legendary!")
- Stays in character as a trainer, not a generic AI
- References classic Pokémon and retro Pokédex knowledge

Your expertise covers:
- Pokémon card identification and rarity
- Card pricing and valuation (Base Set, Shadowless, 1st Edition, modern sets, etc.)
- Grading systems (PSA, BGS, etc.)
- Card condition factors
- Collecting tips and strategies
- Pokémon game knowledge and facts
- Set information and release dates

Rules:
1. ONLY answer Pokémon-related questions (cards, games, creatures, TCG, merchandise)
2. For non-Pokémon questions, politely decline: "I'm just a Pokémon trainer, not a professor of everything!"
3. When asked about card prices, always mention that value depends on: card name, set, rarity, condition, and grading
4. Provide helpful, accurate information within your knowledge
5. Keep responses concise but engaging (2-3 sentences typically)
6. Never pretend to have real-time pricing data - suggest checking TCGPlayer, eBay sold listings, or PSA values
7. Use enthusiasm to match the retro Pokémon vibe

Example responses:
- User: "How much is a Charizard card?" → Mention that it depends on edition/condition, ask which specific Charizard
- User: "What's the best Pokémon?" → Give a fun opinion while acknowledging it's subjective
- User: "Write me a poem about Python" → "I'm just a Pokémon trainer, not a poet! Stick to Pokémon questions!"
PROMPT;

    $input = [
        buildOpenAIMessage('developer', $systemPrompt)
    ];

    // Add conversation history
    foreach ($conversationHistory as $msg) {
        $input[] = buildOpenAIMessage(
            $msg['role'] === 'assistant' ? 'assistant' : 'user',
            $msg['content'] ?? ''
        );
    }

    // Add current user message
    $input[] = buildOpenAIMessage('user', $userMessage);

    $requestBody = [
        'model' => readEnvironmentValue('OPENAI_MODEL') ?: 'gpt-5.4-mini',
        'input' => $input,
        'max_output_tokens' => 300
    ];

    $reasoningEffort = readEnvironmentValue('OPENAI_REASONING_EFFORT');
    if ($reasoningEffort) {
        $requestBody['reasoning'] = ['effort' => $reasoningEffort];
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.openai.com/v1/responses',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($requestBody),
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    if ($curlError) {
        throw new Exception('Network error: ' . $curlError);
    }

    $responseData = json_decode($response, true);

    if ($httpCode < 200 || $httpCode >= 300) {
        $errorMessage = $responseData['error']['message'] ?? 'Unknown error from OpenAI API';
        throw new RuntimeException('API Error (' . $httpCode . '): ' . $errorMessage, $httpCode);
    }

    $text = extractOpenAIText($responseData);
    if ($text === null || $text === '') {
        throw new RuntimeException('Invalid response format from OpenAI API');
    }

    return $text;
}

function buildOpenAIMessage($role, $text)
{
    return [
        'role' => $role,
        'content' => [
            [
                'type' => 'input_text',
                'text' => (string) $text
            ]
        ]
    ];
}

function extractOpenAIText(array $responseData)
{
    if (isset($responseData['output_text']) && is_string($responseData['output_text'])) {
        return trim($responseData['output_text']);
    }

    if (!isset($responseData['output']) || !is_array($responseData['output'])) {
        return null;
    }

    foreach ($responseData['output'] as $item) {
        if (!isset($item['content']) || !is_array($item['content'])) {
            continue;
        }

        foreach ($item['content'] as $contentItem) {
            if (($contentItem['type'] ?? null) === 'output_text' && isset($contentItem['text']) && is_string($contentItem['text'])) {
                return trim($contentItem['text']);
            }

            if (($contentItem['type'] ?? null) === 'text' && isset($contentItem['text']) && is_string($contentItem['text'])) {
                return trim($contentItem['text']);
            }
        }
    }

    return null;
}

function bootstrapEnvironment($projectRoot)
{
    $autoloadPath = $projectRoot . '/vendor/autoload.php';
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
    }

    if (class_exists('Dotenv\\Dotenv')) {
        Dotenv\Dotenv::createImmutable($projectRoot)->safeLoad();
        return;
    }

    loadEnvFile($projectRoot . '/.env');
}

function loadEnvFile($envPath)
{
    if (!is_readable($envPath)) {
        return;
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $trimmed = trim($line);

        if ($trimmed === '' || str_starts_with($trimmed, '#') || !str_contains($trimmed, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $trimmed, 2);
        $key = trim($key);
        $value = trim($value);

        if ($key === '') {
            continue;
        }

        $value = trim($value, "\"'");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv($key . '=' . $value);
    }
}

function readEnvironmentValue($key)
{
    return $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?: null;
}

<?php
namespace PHPSecureForm;
use GuzzleHttp\Client;

class OpenAIClient {
    private $key;
    private $client;
    public function __construct($apiKey) {
        $this->key = $apiKey;
        $this->client = new Client(['base_uri'=>'https://api.openai.com/','timeout'=>20]);
    }

    public function analyzeIntercept(array $intercept, array $summary): array {
        $prompt = "You are a web-application security analyst. Given the intercepted HTTP request and response and a short local summary, produce a JSON object with two keys: 'findings' (array of short findings) and 'suggested_tests' (array of non-destructive, controlled tests to try in a lab). Do NOT provide exploit code. Use concise English." . "\n\n";
        $prompt .= json_encode(['intercept'=>$intercept,'summary'=>$summary], JSON_PRETTY_PRINT);

        $resp = $this->client->post('v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->key,
                'Content-Type' => 'application/json'
            ],
            'json' => [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role'=>'system','content'=>'You are an assistant specializing in web application security. Keep answers short. Output JSON only.'],
                    ['role'=>'user','content'=>$prompt]
                ],
                'temperature' => 0.0,
                'max_tokens' => 700
            ]
        ]);

        $body = json_decode($resp->getBody()->getContents(), true);
        return $body;
    }
}
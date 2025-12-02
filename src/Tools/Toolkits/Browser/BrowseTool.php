<?php

declare(strict_types=1);

namespace NeuronAI\Tools\Toolkits\Browser;

use GuzzleHttp\Client;
use Html2Text\Html2Text;
use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolProperty;
use Symfony\Component\DomCrawler\Crawler;

class BrowseTool extends Tool
{
    public function __construct()
    {
        parent::__construct(
            name: 'browse',
            description: 'Browse the web and extract content from a URL. Best for static pages.',
            properties: [
                new ToolProperty(
                    name: 'url',
                    type: PropertyType::STRING,
                    description: 'The URL to visit.',
                    required: true
                ),
                new ToolProperty(
                    name: 'selector',
                    type: PropertyType::STRING,
                    description: 'Optional CSS selector to focus on specific content.',
                    required: false
                ),
            ]
        );
    }

    public function __invoke(string $url, ?string $selector = null): string
    {
        try {
            // Validate URL
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                return "Error: Invalid URL provided: $url";
            }

            $client = new Client(['timeout' => 30]);
            $response = $client->get($url, [
                'headers' => [
                    'User-Agent' => 'NeuronAI/1.0',
                ]
            ]);
            $html = (string) $response->getBody();

            $crawler = new Crawler($html);

            if ($selector) {
                $filtered = $crawler->filter($selector);
                if ($filtered->count() > 0) {
                    // Get HTML of all matching elements
                    $html = '';
                    $filtered->each(function (Crawler $node) use (&$html) {
                        $html .= $node->outerHtml() . "\n";
                    });
                } else {
                    return "No content found for selector: $selector";
                }
            }

            $converter = new Html2Text($html, ['do_links' => 'inline']);
            return $converter->getText();

        } catch (\Throwable $e) {
            return "Error browsing $url: " . $e->getMessage();
        }
    }
}

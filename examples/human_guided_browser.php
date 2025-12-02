#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use NeuronAI\Tools\Toolkits\Browser\BrowseTool;
use NeuronAI\Workflow\Event;
use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\StartEvent;
use NeuronAI\Workflow\StopEvent;
use NeuronAI\Workflow\Workflow;
use NeuronAI\Workflow\WorkflowInterrupt;
use NeuronAI\Workflow\WorkflowState;

/**
 * HUMAN GUIDED BROWSER EXAMPLE
 *
 * This example demonstrates:
 * 1. Using the BrowserToolkit to fetch web content.
 * 2. Using WorkflowInterrupt to pause execution and ask for human guidance.
 * 3. Resuming the workflow based on human input.
 */

// --- 1. Define Events ---

class BrowseEvent implements Event {
    public function __construct(public string $url) {}
}

class ReviewEvent implements Event {
    public function __construct(public string $content) {}
}

// --- 2. Define Nodes ---

class StartNode extends Node {
    public function __invoke(StartEvent $event, WorkflowState $state): BrowseEvent {
        $url = $state->get('url', 'https://example.com');
        echo "[Node:Start] Starting workflow with URL: $url\n";
        return new BrowseEvent($url);
    }
}

class BrowseNode extends Node {
    public function __invoke(BrowseEvent $event, WorkflowState $state): ReviewEvent {
        echo "[Node:Browse] Browsing: {$event->url}\n";

        try {
            $tool = new BrowseTool();
            $content = $tool($event->url);
        } catch (\Throwable $e) {
            $content = "Error: " . $e->getMessage();
        }

        $length = strlen($content);
        echo "[Node:Browse] Content fetched ($length chars).\n";
        return new ReviewEvent($content);
    }
}

class ReviewNode extends Node {
    public function __invoke(ReviewEvent $event, WorkflowState $state): Event {
        // Prepare data for the human
        $preview = substr(trim($event->content), 0, 100) . '...';

        // Interrupt workflow to get human feedback
        $decision = $this->interrupt([
            'message' => "Content received. Please review.",
            'preview' => $preview,
            'options' => ['approve', 'NEW_URL']
        ]);

        echo "[Node:Review] Human guidance received: $decision\n";

        if ($decision === 'approve') {
            echo "[Node:Review] Content approved. Stopping.\n";
            return new StopEvent();
        }

        if (filter_var($decision, FILTER_VALIDATE_URL)) {
             echo "[Node:Review] Redirecting to new URL: $decision\n";
             return new BrowseEvent($decision);
        }

        echo "[Node:Review] Unclear guidance. Stopping.\n";
        return new StopEvent();
    }
}

// --- 3. Execution Logic ---

echo "=== Human-Guided Browser Workflow ===\n\n";

$workflow = Workflow::make(
    new WorkflowState(['url' => 'https://example.com'])
)
    ->addNodes([
        new StartNode(),
        new BrowseNode(),
        new ReviewNode()
    ]);

try {
    echo ">> Starting Workflow...\n";
    foreach ($workflow->run() as $event) {
        // Consuming events if needed
    }
} catch (WorkflowInterrupt $interrupt) {
    // --- 4. Handle Interruption ---
    $data = $interrupt->getData();
    echo "\n[System] WORKFLOW INTERRUPTED (Human-in-the-Loop)\n";
    echo "[System] Message: " . $data['message'] . "\n";
    echo "[System] Preview: " . $data['preview'] . "\n";

    // Simulate Human Input (In a real app, this would come from a UI or API)
    echo "[System] Simulating human input...\n";

    // Uncomment the line below to test loop behavior:
    // $humanInput = 'https://www.php.net';
    $humanInput = 'approve';

    echo "[System] Human says: '$humanInput'\n\n";

    echo ">> Resuming Workflow...\n";
    // Resume Workflow
    foreach ($workflow->resume($humanInput) as $event) {
         // Continue...
    }
}

echo "\n=== Workflow Complete ===\n";

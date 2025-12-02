<?php

declare(strict_types=1);

namespace NeuronAI\Tools\Toolkits\Browser;

use NeuronAI\Tools\Toolkits\AbstractToolkit;

class BrowserToolkit extends AbstractToolkit
{
    public function provide(): array
    {
        return [
            new BrowseTool(),
        ];
    }
}

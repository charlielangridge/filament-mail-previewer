<?php

namespace CharlieLangridge\FilamentMailPreviewer\Commands;

use Illuminate\Console\Command;

class FilamentMailPreviewerCommand extends Command
{
    public $signature = 'filament-mail-previewer';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}

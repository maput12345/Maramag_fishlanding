<?php

namespace App\Console\Commands;

use App\Models\FishBox;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MarkSoldFishBoxesMissing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fish-boxes:mark-sold-missing {--cutoff= : Override the cutoff datetime in the app timezone}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark fish boxes that are still sold after the 11:59 AM cutoff as missing.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $timezone = config('app.timezone');
        $cutoffOption = $this->option('cutoff');
        $now = now($timezone);

        try {
            $cutoff = $cutoffOption
                ? Carbon::parse($cutoffOption, $timezone)
                : $now->copy()->setTime(11, 59, 0);
        } catch (\Throwable $exception) {
            $this->error('The cutoff value could not be parsed. Use a valid date/time string.');

            return self::INVALID;
        }

        if (!$cutoffOption && $now->lt($cutoff)) {
            $this->info('No sold fish boxes are overdue before the ' . $cutoff->format('M d, Y h:i A') . ' cutoff.');

            return self::SUCCESS;
        }

        $markedCount = FishBox::markSoldBoxesMissingAtCutoff($cutoff);

        if ($markedCount === 0) {
            $this->info('No sold fish boxes were due for automatic missing status at ' . $cutoff->format('M d, Y h:i A') . '.');

            return self::SUCCESS;
        }

        $this->info("Marked {$markedCount} sold fish box(es) as missing using the {$cutoff->format('M d, Y h:i A')} cutoff.");

        return self::SUCCESS;
    }
}

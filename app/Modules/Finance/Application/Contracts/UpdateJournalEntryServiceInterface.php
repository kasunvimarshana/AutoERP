<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts;

use Modules\Core\Application\Contracts\ServiceInterface;

/**
 * @method \Modules\Finance\Domain\Entities\JournalEntry execute(array $data = [])
 */
interface UpdateJournalEntryServiceInterface extends ServiceInterface {}

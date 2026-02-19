<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

/**
 * Event Handler Interface
 *
 * Contract for all event handlers/listeners to ensure consistent
 * event handling across modules.
 */
interface EventHandlerInterface
{
    /**
     * Handle the event.
     *
     * @param  object  $event  Event object
     * @return void
     */
    public function handle(object $event): void;
}

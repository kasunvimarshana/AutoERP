<?php

declare(strict_types=1);

namespace Modules\Core\Traits;

use Illuminate\Support\Facades\Event;

/**
 * LogsActivity Trait
 *
 * Provides activity logging using native Laravel Events.
 * No external packages required.
 *
 * Usage:
 * 1. Add trait to your model: use LogsActivity;
 * 2. Define which events to log: protected $logEvents = ['created', 'updated', 'deleted'];
 * 3. Listen to events in EventServiceProvider or use listeners
 *
 * This trait uses Laravel's native event system to log model changes.
 * Create an Activity model and listener to store the logs.
 *
 * Example Activity model migration:
 * $table->id();
 * $table->string('log_name')->nullable();
 * $table->text('description');
 * $table->nullableMorphs('subject');
 * $table->nullableMorphs('causer');
 * $table->json('properties')->nullable();
 * $table->timestamps();
 */
trait LogsActivity
{
    /**
     * Boot the trait and register model event listeners.
     */
    protected static function bootLogsActivity(): void
    {
        foreach (static::getLogEvents() as $event) {
            static::$event(function ($model) use ($event) {
                $model->logActivity($event);
            });
        }
    }

    /**
     * Get the events that should be logged.
     *
     * @return array<string>
     */
    protected static function getLogEvents(): array
    {
        return (new static)->logEvents ?? ['created', 'updated', 'deleted'];
    }

    /**
     * Log an activity for this model.
     */
    protected function logActivity(string $event): void
    {
        $activityModel = config('core.models.activity', 'App\Models\Activity');

        $activityModel::create([
            'log_name' => $this->getLogName(),
            'description' => $this->getActivityDescription($event),
            'subject_type' => get_class($this),
            'subject_id' => $this->getKey(),
            'causer_type' => auth()->check() ? get_class(auth()->user()) : null,
            'causer_id' => auth()->id(),
            'properties' => $this->getActivityProperties($event),
        ]);
    }

    /**
     * Get the log name for this model.
     */
    protected function getLogName(): string
    {
        return $this->logName ?? 'default';
    }

    /**
     * Get the description for the activity.
     */
    protected function getActivityDescription(string $event): string
    {
        return sprintf(
            '%s %s',
            class_basename($this),
            $event
        );
    }

    /**
     * Get the properties to store with the activity.
     *
     * @return array<string, mixed>
     */
    protected function getActivityProperties(string $event): array
    {
        $properties = [
            'attributes' => $this->attributesToArray(),
        ];

        if ($event === 'updated' && $this->wasChanged()) {
            $properties['old'] = $this->getOriginal();
        }

        return $properties;
    }
}

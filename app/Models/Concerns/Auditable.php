<?php

namespace App\Models\Concerns;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Convenience wrapper around Spatie\Activitylog so every Torex model
 * gets a consistent audit log policy out of the box.
 *
 * Override `auditedAttributes()` in a model to customise which columns
 * are tracked.
 */
trait Auditable
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->auditedAttributes())
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName(class_basename(static::class));
    }

    /**
     * Default audited attributes. Models override to expand or narrow this.
     */
    public function auditedAttributes(): array
    {
        $candidate = ['name', 'status', 'severity', 'title', 'is_active', 'email'];

        return array_values(array_intersect($candidate, array_keys($this->getCasts() + array_flip($this->getFillable()))));
    }
}

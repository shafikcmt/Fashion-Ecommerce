<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditObserver
{
    public function created(Model $model): void
    {
        $this->record('created', $model, array_keys($model->getAttributes()));
    }

    public function updated(Model $model): void
    {
        $this->record('updated', $model, array_keys($model->getChanges()));
    }

    public function deleted(Model $model): void
    {
        $this->record('deleted', $model, []);
    }

    private function record(string $action, Model $model, array $fields): void
    {
        if (! auth()->check() || auth()->user()->role === 'customer') {
            return;
        }
        AuditLog::create(['user_id' => auth()->id(), 'action' => $action, 'subject_type' => $model::class, 'subject_id' => $model->getKey(), 'fields' => array_values(array_diff($fields, ['password', 'remember_token'])), 'created_at' => now()]);
    }
}

<?php

namespace App\Filament\Resources;

use App\Models\AuditLog;

class AuditLogResource extends CommerceResource
{
    protected static bool $isDiscovered = true;

    protected static ?string $model = AuditLog::class;

    public static function fields(): array
    {
        return [];
    }

    public static function columns(): array
    {
        return ['id', 'user_id', 'action', 'subject_type', 'subject_id', 'created_at'];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageAuditLogRecords::route('/')];
    }
}

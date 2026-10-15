<?php

namespace App\Filament\Resources;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserResource extends CommerceResource
{
    protected static bool $isDiscovered = true;

    protected static ?string $model = User::class;

    public static function fields(): array
    {
        return [TextInput::make('name')->required()->maxLength(100), TextInput::make('email')->email()->required()->unique(ignoreRecord: true)->maxLength(255),
            Select::make('role')->options(['customer' => 'Customer', 'super_admin' => 'Super Admin', 'catalog_manager' => 'Catalog Manager', 'order_manager' => 'Order Manager', 'content_editor' => 'Content Editor'])->required()->default('customer'),
            TextInput::make('password')->password()->minLength(12)->rule(Password::min(12)->letters()->numbers())->required(fn (string $operation) => $operation === 'create')->dehydrated(fn (?string $state) => filled($state))->dehydrateStateUsing(fn (string $state) => Hash::make($state))];
    }

    public static function columns(): array
    {
        return ['id', 'name', 'email', 'role', 'email_verified_at'];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageUserRecords::route('/')];
    }
}

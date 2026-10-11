<?php

namespace App\Filament\Resources;

use App\Models;
use App\Services\InventoryService;
use App\Services\MediaService;
use App\Services\OrderService;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

abstract class CommerceResource extends Resource
{
    protected static bool $isDiscovered = false;

    public static function fields(): array
    {
        return [];
    }

    public static function columns(): array
    {
        return ['id', 'name'];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components(static::fields())->columns(2);
    }

    public static function table(Table $table): Table
    {
        $model = static::getModel();
        $cols = [];
        foreach (static::columns() as $name) {
            $c = TextColumn::make($name)->sortable()->searchable()->limit(55);
            if (in_array($name, ['price', 'sale_price', 'total'], true)) {
                $c->formatStateUsing(fn ($state) => Money::format((int) $state));
            }
            $cols[] = $c;
        }
        $actions = [];
        if (! in_array($model, [Models\Order::class, Models\ReturnRequest::class, Models\StockMovement::class, Models\AuditLog::class], true)) {
            $actions[] = EditAction::make()->using(function (Model $record, array $data) {
                Gate::authorize('update', $record);
                if ($record instanceof Models\User) {
                    $record->forceFill($data)->save();
                } else {
                    $record->update($data);
                }

                return $record;
            });
        }
        if ($model === Models\Variant::class) {
            $actions[] = Action::make('adjustStock')->label('Adjust stock')->schema([
                TextInput::make('quantity')->label('Change (+ received / − removed)')->integer()->required()->minValue(-1000000)->maxValue(1000000),
                Textarea::make('reason')->required()->maxLength(255),
            ])->authorize('update')->action(function (Models\Variant $record, array $data) {
                Gate::authorize('update', $record);
                app(InventoryService::class)->adjust($record->id, (int) $data['quantity'], $data['reason'], auth()->id());
            });
        }
        if ($model === Models\Media::class) {
            $actions[] = Action::make('deleteImage')->color('danger')->requiresConfirmation()->authorize('update')->action(function (Models\Media $record) {
                Gate::authorize('update', $record);
                app(MediaService::class)->delete($record);
            });
        }
        if ($model === Models\Order::class) {
            $actions[] = Action::make('details')->modalHeading('Order details')->modalSubmitAction(false)->modalContent(fn (Models\Order $record) => view('admin.order-details', ['order' => $record->load('items')]));
            foreach (['confirmed', 'shipped', 'delivered', 'cancelled'] as $next) {
                $from = ['confirmed' => ['pending'], 'shipped' => ['confirmed'], 'delivered' => ['shipped'], 'cancelled' => ['pending', 'confirmed']][$next];
                $actions[] = Action::make($next)->label(ucfirst($next))->requiresConfirmation()->authorize('update')
                    ->visible(fn (Models\Order $record) => in_array($record->status, $from, true))
                    ->action(function (Models\Order $record) use ($next) {
                        Gate::authorize('update', $record);
                        app(OrderService::class)->transition($record, $next);
                    });
            }
            $actions[] = Action::make('courier')->label('Assign courier')->authorize('update')->schema([
                TextInput::make('courier')->required()->maxLength(100), TextInput::make('tracking_number')->required()->maxLength(100),
            ])->fillForm(fn (Models\Order $record) => $record->only(['courier', 'tracking_number']))->action(function (Models\Order $record, array $data) {
                Gate::authorize('update', $record);
                $record->update($data);
            });
            $actions[] = Action::make('collectCod')->label('Record COD collected')->requiresConfirmation()->authorize('update')->visible(fn (Models\Order $record) => $record->status === 'delivered' && $record->payment_status === 'unpaid')
                ->action(function (Models\Order $record) {
                    Gate::authorize('update', $record);
                    app(OrderService::class)->collectCod($record);
                });
            $actions[] = Action::make('refund')->label('Record manual refund')->requiresConfirmation()->authorize('update')->visible(fn (Models\Order $record) => $record->payment_status === 'paid' && $record->fulfillment_status === 'returned')
                ->action(function (Models\Order $record) {
                    Gate::authorize('update', $record);
                    app(OrderService::class)->recordRefund($record);
                });
        }
        if ($model === Models\ReturnRequest::class) {
            foreach (['approved', 'rejected'] as $decision) {
                $actions[] = Action::make($decision)->authorize('update')->visible(fn ($record) => $record->status === 'requested')->schema([Textarea::make('admin_note')->required()->maxLength(2000)])
                    ->action(function (Models\ReturnRequest $record, array $data) use ($decision) {
                        Gate::authorize('update', $record);
                        app(OrderService::class)->reviewReturn($record, $decision, $data['admin_note']);
                    });
            }
            $actions[] = Action::make('restock')->label('Confirm received & restock')->requiresConfirmation()->authorize('update')->visible(fn ($record) => $record->status === 'approved' && ! $record->restocked_at)
                ->action(function (Models\ReturnRequest $record) {
                    Gate::authorize('update', $record);
                    app(OrderService::class)->restockReturn($record);
                });
        }

        return $table->columns($cols)->recordActions($actions)->defaultSort('id', 'desc')->paginated([10, 25, 50]);
    }

    public static function headerActions(): array
    {
        $model = static::getModel();
        if (in_array($model, [Models\Order::class, Models\ReturnRequest::class, Models\StockMovement::class, Models\AuditLog::class], true)) {
            return [];
        }
        if ($model === Models\Media::class) {
            return [
                Action::make('upload')->label('Upload image')->authorize(fn () => Gate::allows('create', Models\Media::class))
                    ->schema([FileUpload::make('image')->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(5120)->storeFiles(false)->required(), TextInput::make('alt')->required()->maxLength(255)])
                    ->action(function (array $data) {
                        Gate::authorize('create', Models\Media::class);
                        app(MediaService::class)->upload($data['image'], $data['alt']);
                    }),
            ];
        }

        return [CreateAction::make()->using(function (array $data) use ($model) {
            Gate::authorize('create', $model);
            if ($model === Models\User::class) {
                $u = new Models\User;
                $u->forceFill($data)->save();
                event(new Registered($u));

                return $u;
            }

            return $model::create($data);
        })];
    }
}

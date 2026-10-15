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
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

abstract class CommerceResource extends Resource
{
    protected static bool $isDiscovered = false;

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return match (static::getModel()) {
            Models\Product::class, Models\Variant::class, Models\Category::class, Models\Collection::class, Models\Brand::class, Models\Tag::class, Models\StockMovement::class => 'Catalog & inventory',
            Models\Order::class, Models\ReturnRequest::class, Models\ShippingZone::class, Models\Coupon::class => 'Orders & delivery',
            Models\Page::class, Models\ContentBlock::class, Models\Setting::class, Models\Media::class => 'Storefront content',
            default => 'Administration',
        };
    }

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
        if ($model === Models\Media::class) {
            $cols[] = ImageColumn::make('preview')->label('Preview')
                ->getStateUsing(fn (Models\Media $record): string => $record->url())
                ->imageHeight(48)->imageWidth(48)->square()
                ->extraImgAttributes(fn (Models\Media $record): array => ['alt' => $record->alt, 'loading' => 'lazy']);
        }
        foreach (static::columns() as $name) {
            $c = TextColumn::make($name)->sortable()->searchable()->limit(55);
            if (in_array($name, ['price', 'sale_price', 'total'], true)) {
                $c->formatStateUsing(fn ($state) => Money::format((int) $state));
            }
            if ($name === 'id' || $name === 'sku') {
                $c->label(strtoupper($name));
            }
            if ($model === Models\Media::class) {
                if ($name === 'alt') {
                    $c->label('Image description')->wrap();
                }
                if (in_array($name, ['path', 'mime', 'bytes'], true)) {
                    $c->toggleable(isToggledHiddenByDefault: true);
                }
                if ($name === 'bytes') {
                    $c->label('File size')->formatStateUsing(fn ($state): string => number_format((int) $state / 1024).' KB');
                }
            }
            $cols[] = $c;
        }
        $actions = [];
        if (! in_array($model, [Models\Order::class, Models\ReturnRequest::class, Models\StockMovement::class, Models\AuditLog::class], true)) {
            $actions[] = EditAction::make()->stickyModalHeader()->stickyModalFooter()->using(function (Model $record, array $data) {
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
            $actions[] = Action::make('adjustStock')->label('Adjust stock')->successNotificationTitle('Stock adjusted')->stickyModalHeader()->stickyModalFooter()->schema([
                TextInput::make('quantity')->label('Stock change')->helperText('Positive to receive units; negative to remove units. Reserved units cannot be removed.')->integer()->required()->minValue(-1000000)->maxValue(1000000),
                Textarea::make('reason')->label('Reason for adjustment')->required()->maxLength(255),
            ])->authorize('update')->action(function (Models\Variant $record, array $data) {
                Gate::authorize('update', $record);
                app(InventoryService::class)->adjust($record->id, (int) $data['quantity'], $data['reason'], auth()->id());
            });
        }
        if ($model === Models\Media::class) {
            $actions[] = Action::make('deleteImage')->color('danger')->successNotificationTitle('Image deleted')->requiresConfirmation()->authorize('update')->action(function (Models\Media $record) {
                Gate::authorize('update', $record);
                app(MediaService::class)->delete($record);
            });
        }
        if ($model === Models\Order::class) {
            $actions[] = Action::make('details')->stickyModalHeader()->stickyModalFooter()->modalHeading('Order details')->modalSubmitAction(false)->modalContent(fn (Models\Order $record) => view('admin.order-details', ['order' => $record->load('items')]));
            foreach (['confirmed', 'shipped', 'delivered', 'cancelled'] as $next) {
                $from = ['confirmed' => ['pending'], 'shipped' => ['confirmed'], 'delivered' => ['shipped'], 'cancelled' => ['pending', 'confirmed']][$next];
                $actions[] = Action::make($next)->label(['confirmed' => 'Confirm order', 'shipped' => 'Mark shipped', 'delivered' => 'Mark delivered', 'cancelled' => 'Cancel order'][$next])->successNotificationTitle('Order '.$next)->requiresConfirmation()->authorize('update')
                    ->visible(fn (Models\Order $record) => in_array($record->status, $from, true))
                    ->action(function (Models\Order $record) use ($next) {
                        Gate::authorize('update', $record);
                        app(OrderService::class)->transition($record, $next);
                    });
            }
            $actions[] = Action::make('courier')->label('Assign courier')->successNotificationTitle('Courier assigned')->authorize('update')->schema([
                TextInput::make('courier')->required()->maxLength(100), TextInput::make('tracking_number')->required()->maxLength(100),
            ])->fillForm(fn (Models\Order $record) => $record->only(['courier', 'tracking_number']))->action(function (Models\Order $record, array $data) {
                Gate::authorize('update', $record);
                $record->update($data);
            });
            $actions[] = Action::make('collectCod')->label('Record COD collected')->successNotificationTitle('COD collection recorded')->requiresConfirmation()->authorize('update')->visible(fn (Models\Order $record) => $record->status === 'delivered' && $record->payment_status === 'unpaid')
                ->action(function (Models\Order $record) {
                    Gate::authorize('update', $record);
                    app(OrderService::class)->collectCod($record);
                });
            $actions[] = Action::make('refund')->label('Record manual refund')->successNotificationTitle('Manual refund recorded')->requiresConfirmation()->authorize('update')->visible(fn (Models\Order $record) => $record->payment_status === 'paid' && $record->fulfillment_status === 'returned')
                ->action(function (Models\Order $record) {
                    Gate::authorize('update', $record);
                    app(OrderService::class)->recordRefund($record);
                });
        }
        if ($model === Models\ReturnRequest::class) {
            foreach (['approved', 'rejected'] as $decision) {
                $actions[] = Action::make($decision)->label($decision === 'approved' ? 'Approve return' : 'Reject return')->successNotificationTitle('Return '.$decision)->authorize('update')->visible(fn ($record) => $record->status === 'requested')->schema([Textarea::make('admin_note')->label('Decision note')->helperText('Visible to the customer on their order page.')->required()->maxLength(2000)])
                    ->action(function (Models\ReturnRequest $record, array $data) use ($decision) {
                        Gate::authorize('update', $record);
                        app(OrderService::class)->reviewReturn($record, $decision, $data['admin_note']);
                    });
            }
            $actions[] = Action::make('restock')->label('Confirm received & restock')->successNotificationTitle('Return received and stock restored')->requiresConfirmation()->authorize('update')->visible(fn ($record) => $record->status === 'approved' && ! $record->restocked_at)
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
                Action::make('upload')->label('Upload image')->successNotificationTitle('Image uploaded')->stickyModalHeader()->stickyModalFooter()->authorize(fn () => Gate::allows('create', Models\Media::class))
                    ->schema([FileUpload::make('image')->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(5120)->helperText('JPEG, PNG or WebP. Up to 5 MB and 6000 × 6000 pixels.')->storeFiles(false)->required(), TextInput::make('alt')->label('Image description (alt text)')->helperText('Describe the image for customers using a screen reader.')->required()->maxLength(255)])
                    ->action(function (array $data) {
                        Gate::authorize('create', Models\Media::class);
                        app(MediaService::class)->upload($data['image'], $data['alt']);
                    }),
            ];
        }

        return [CreateAction::make()->stickyModalHeader()->stickyModalFooter()->using(function (array $data) use ($model) {
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

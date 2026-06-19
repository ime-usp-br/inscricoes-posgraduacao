<?php

namespace App\Filament\Pages;

use App\Services\LaravelLogParser;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\View;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use UnitEnum;

class SystemLogs extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBugAnt;

    protected static UnitEnum|string|null $navigationGroup = 'Gerenciamento';

    protected static ?int $navigationSort = 11;

    protected static ?string $navigationLabel = 'Logs do Sistema';

    protected static ?string $title = 'Logs do Sistema';

    protected static ?string $slug = 'logs-sistema';

    #[Url(as: 'filters')]
    public ?array $tableFilters = null;

    #[Url(as: 'search')]
    public $tableSearch = '';

    #[Url(as: 'sort')]
    public ?string $tableSort = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('Admin') ?? false;
    }

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? 'Logs do Sistema';
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(function (
                ?string $search,
                ?string $sortColumn,
                ?string $sortDirection,
                array $filters,
                int $page,
                int $recordsPerPage,
            ): LengthAwarePaginator {
                /** @var Collection<int, array<string, mixed>> $records */
                $entries = app(LaravelLogParser::class)->parse();

                $level = $filters['level']['value'] ?? null;

                $records = $entries
                    ->when(
                        filled($level),
                        fn (Collection $data): Collection => $data->where('level', $level),
                    )
                    ->when(
                        filled($search),
                        fn (Collection $data): Collection => $data->filter(
                            fn (array $record): bool => str_contains(
                                Str::lower($record['body']),
                                Str::lower($search),
                            ),
                        ),
                    )
                    ->when(
                        filled($sortColumn),
                        fn (Collection $data): Collection => $data->sortBy(
                            $sortColumn,
                            SORT_REGULAR,
                            $sortDirection === 'desc',
                        )->values(),
                    );

                $paginated = $records
                    ->forPage($page, $recordsPerPage)
                    ->mapWithKeys(fn (array $record): array => [$record['key'] => $record]);

                return new LengthAwarePaginator(
                    items: $paginated->all(),
                    total: $records->count(),
                    perPage: $recordsPerPage,
                    currentPage: $page,
                );
            })
            ->columns([
                Split::make([
                    TextColumn::make('datetime')
                        ->label('Data/Hora')
                        ->sortable()
                        ->width('10rem'),
                    TextColumn::make('level')
                        ->label('Nível')
                        ->badge()
                        ->sortable()
                        ->color(fn (string $state): string => match (strtoupper($state)) {
                            'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY' => 'danger',
                            'WARNING' => 'warning',
                            'INFO', 'NOTICE' => 'primary',
                            default => 'gray',
                        })
                        ->width('6rem'),
                    TextColumn::make('environment')
                        ->label('Canal')
                        ->toggleable(isToggledHiddenByDefault: true)
                        ->width('6rem'),
                    TextColumn::make('preview')
                        ->label('Mensagem')
                        ->searchable(false)
                        ->wrap(),
                ]),
                View::make('filament.tables.laravel-log-entry')
                    ->collapsible(),
            ])
            ->defaultSort('datetime', 'desc')
            ->filters([
                SelectFilter::make('level')
                    ->label('Nível')
                    ->options([
                        'DEBUG' => 'Debug',
                        'INFO' => 'Info',
                        'NOTICE' => 'Notice',
                        'WARNING' => 'Warning',
                        'ERROR' => 'Error',
                        'CRITICAL' => 'Critical',
                        'ALERT' => 'Alert',
                        'EMERGENCY' => 'Emergency',
                    ]),
            ])
            ->searchable()
            ->searchPlaceholder('Buscar no conteúdo dos logs...')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading('Nenhum registro encontrado')
            ->emptyStateDescription('O arquivo storage/logs/laravel.log não existe, está vazio ou não há entradas para os filtros aplicados.')
            ->emptyStateIcon(Heroicon::OutlinedDocumentText);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                EmbeddedTable::make(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Atualizar')
                ->icon(Heroicon::OutlinedArrowPath)
                ->action(fn () => $this->resetTable()),
            Action::make('backToDashboard')
                ->label('Voltar ao Dashboard')
                ->icon(Heroicon::OutlinedArrowLeft)
                ->url(route('dashboard'))
                ->color('gray'),
        ];
    }
}

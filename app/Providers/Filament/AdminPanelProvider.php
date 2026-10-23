<?php

namespace App\Providers\Filament;

use App\Filament\Resources\LetterTemplates\LetterTemplateResource;
use App\Filament\Resources\LogLetters\LogLetterResource;
use App\Filament\Resources\Logs\LogResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\WebhookEvents\WebhookEventResource;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentTimezone;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        // Dates are stored in UTC; the shop's staff reads them in Moscow time.
        FilamentTimezone::set('Europe/Moscow');
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('MarketHub — заказы')
            // A few lines of CSS: inlined rather than published as a Filament asset.
            ->renderHook(PanelsRenderHook::STYLES_AFTER, fn () => new HtmlString(
                '<style>' . file_get_contents(resource_path('css/filament/json.css')) . '</style>',
            ))
            ->navigation(function (NavigationBuilder $builder): NavigationBuilder {
                return $builder
                    ->items([
                        NavigationItem::make(__('filament-panels::pages/dashboard.title'))
                            ->icon('heroicon-o-home')
                            ->url(Dashboard::getUrl())
                            ->isActiveWhen(fn (): bool => Request::routeIs('filament.admin.pages.dashboard')),
                    ])
                    ->groups([
                        NavigationGroup::make(__('admin.navigation.orders'))
                            ->items([
                                NavigationItem::make(__('admin.orders.plural'))
                                    ->icon('heroicon-o-shopping-bag')
                                    ->url(OrderResource::getUrl())
                                    ->isActiveWhen(fn (): bool => Request::routeIs('filament.admin.resources.orders.*')),
                                NavigationItem::make(__('admin.webhook_events.plural'))
                                    ->icon('heroicon-o-arrow-down-tray')
                                    ->url(WebhookEventResource::getUrl())
                                    ->isActiveWhen(fn (): bool => Request::routeIs('filament.admin.resources.webhook-events.*')),
                            ]),
                        NavigationGroup::make(__('admin.navigation.letters'))
                            ->items([
                                NavigationItem::make(__('admin.letter_templates.plural'))
                                    ->icon('heroicon-o-envelope')
                                    ->url(LetterTemplateResource::getUrl())
                                    ->isActiveWhen(fn (): bool => Request::routeIs('filament.admin.resources.letter-templates.*')),
                                NavigationItem::make(__('admin.log_letters.plural'))
                                    ->icon('heroicon-o-inbox-stack')
                                    ->url(LogLetterResource::getUrl())
                                    ->isActiveWhen(fn (): bool => Request::routeIs('filament.admin.resources.log-letters.*')),
                            ]),
                        NavigationGroup::make(__('admin.navigation.system'))
                            ->items([
                                NavigationItem::make(__('admin.logs.plural'))
                                    ->icon('heroicon-o-archive-box')
                                    ->url(LogResource::getUrl())
                                    ->isActiveWhen(fn (): bool => Request::routeIs('filament.admin.resources.logs.*')),
                                NavigationItem::make(__('admin.users.plural'))
                                    ->icon('heroicon-o-users')
                                    ->url(UserResource::getUrl())
                                    ->isActiveWhen(fn (): bool => Request::routeIs('filament.admin.resources.users.*')),
                            ]),
                    ]);
            })
            ->colors([
                'primary' => Color::Indigo,
            ])
            ->resources([
                OrderResource::class,
                WebhookEventResource::class,
                LetterTemplateResource::class,
                LogLetterResource::class,
                LogResource::class,
                UserResource::class,
            ])
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}

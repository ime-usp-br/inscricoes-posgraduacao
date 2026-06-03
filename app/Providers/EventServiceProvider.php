<?php

namespace App\Providers;

use App\Listeners\SendSingleEmailVerificationNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Os mapeamentos de ouvinte de evento para a aplicação.
     * Deixamos vazio para ter controle manual total.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [];

    /**
     * Registra quaisquer eventos para sua aplicação.
     */
    public function boot(): void
    {
        // Usamos o callback 'booted' para garantir que todos os providers já foram carregados.
        $this->app->booted(function () {
            // Limpa quaisquer listeners existentes para o evento Registered.
            Event::forget(Registered::class);

            // Registra MANUALMENTE apenas o nosso listener customizado.
            Event::listen(
                Registered::class,
                SendSingleEmailVerificationNotification::class
            );
        });
    }

    /**
     * A descoberta de listeners em app/Listeners já é feita pelo Laravel.
     * Evita registrar os mesmos ouvintes duas vezes.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}

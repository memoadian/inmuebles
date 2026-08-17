<?php

namespace App\Providers;

use App\Contracts\AiChatProvider;
use App\Services\Ai\GroqChatProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Único lugar que sabe qué proveedor de IA se usa. Cambiar a
        // OpenAI/Gemini/Anthropic es escribir una clase que implemente
        // AiChatProvider y apuntar el bind aquí — nada más se toca.
        $this->app->bind(AiChatProvider::class, GroqChatProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Groq limita por organización (una sola API key para toda la app), no
        // por usuario, así que este límite se comparte entre todos los agentes
        // en vez de ser "N por usuario". Bucket único compartido por TODAS las
        // features de IA (autocompletar, sugerir títulos, etc.) porque todas
        // consumen el mismo TPM de Groq. 6/min es margen de seguridad bajo el
        // techo real medido (~8,000 tokens/min ÷ ~1,500 tokens/llamada ≈ 5.3/min).
        RateLimiter::for('ai-groq', function () {
            return Limit::perMinute(6)->by('ai-groq')->response(function () {
                return response()->json([
                    'message' => 'Se alcanzó el límite de solicitudes a la IA. Espera unos segundos e intenta de nuevo.',
                ], 429);
            });
        });
    }
}

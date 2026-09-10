@props(['url', 'title'])

@php
    $message = "{$title} — {$url}";
@endphp

<div data-share data-url="{{ $url }}" data-title="{{ $title }}"
     class="flex flex-wrap items-center gap-2">
    {{-- Sólo aparece donde el navegador soporta la hoja de compartir nativa. --}}
    <button type="button" data-share-native hidden
            class="inline-flex items-center gap-1.5 rounded-xl bg-brand-700 px-3 py-2 text-sm font-medium
                   text-white shadow-sm hover:bg-brand-800 transition-colors">
        <i class="bi bi-share-fill"></i> Compartir
    </button>

    <a href="https://wa.me/?text={{ rawurlencode($message) }}" target="_blank" rel="noopener"
       aria-label="Compartir por WhatsApp" title="WhatsApp"
       class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-300 text-slate-600
              hover:bg-slate-50 hover:text-brand-700 transition-colors">
        <i class="bi bi-whatsapp"></i>
    </a>

    <a href="https://www.facebook.com/sharer/sharer.php?u={{ rawurlencode($url) }}" target="_blank" rel="noopener"
       aria-label="Compartir en Facebook" title="Facebook"
       class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-300 text-slate-600
              hover:bg-slate-50 hover:text-brand-700 transition-colors">
        <i class="bi bi-facebook"></i>
    </a>

    <a href="https://twitter.com/intent/tweet?text={{ rawurlencode($title) }}&url={{ rawurlencode($url) }}"
       target="_blank" rel="noopener" aria-label="Compartir en X" title="X"
       class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-300 text-slate-600
              hover:bg-slate-50 hover:text-brand-700 transition-colors">
        <i class="bi bi-twitter-x"></i>
    </a>

    <a href="mailto:?subject={{ rawurlencode($title) }}&body={{ rawurlencode($message) }}"
       aria-label="Compartir por correo" title="Correo"
       class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-300 text-slate-600
              hover:bg-slate-50 hover:text-brand-700 transition-colors">
        <i class="bi bi-envelope"></i>
    </a>

    <button type="button" data-share-copy aria-label="Copiar enlace" title="Copiar enlace"
            class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-300 text-slate-600
                   hover:bg-slate-50 hover:text-brand-700 transition-colors">
        <i class="bi bi-link-45deg" data-share-copy-icon></i>
    </button>

    <span data-share-status class="text-xs text-slate-500 empty:hidden" role="status" aria-live="polite"></span>
</div>

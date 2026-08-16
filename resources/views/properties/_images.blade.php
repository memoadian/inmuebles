<section class="mt-6 bg-white rounded-xl border border-slate-200 p-4">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="font-medium text-slate-800">Fotos</h2>
            <p class="text-xs text-slate-500">
                Se convierten a WebP y se guardan en
                <span class="font-mono">{{ config('filesystems.default') }}</span>.
                La portada es la que se muestra en el catálogo.
            </p>
        </div>
        <span class="text-sm text-slate-500">{{ $property->images->count() }} / 20</span>
    </div>

    @can('uploadImages', $property)
        <form method="POST" action="{{ route('properties.images.store', $property) }}"
              enctype="multipart/form-data" class="mb-4 flex flex-wrap items-center gap-2">
            @csrf
            <input type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp" required
                   class="text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-slate-900 file:px-3 file:py-2
                          file:text-sm file:font-medium file:text-white hover:file:bg-slate-800">
            <button class="rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">
                <i class="bi bi-upload"></i> Subir
            </button>
            <span class="text-xs text-slate-500">JPG, PNG o WebP · máx. 8 MB c/u</span>
        </form>
    @endcan

    @if ($property->images->isEmpty())
        <div class="rounded-lg border border-dashed border-slate-300 px-4 py-12 text-center">
            <i class="bi bi-images text-3xl text-slate-300"></i>
            <p class="mt-2 text-sm text-slate-500">Esta propiedad aún no tiene fotos.</p>
        </div>
    @else
        <div class="grid gap-3 grid-cols-2 sm:grid-cols-3 lg:grid-cols-5">
            @foreach ($property->images as $image)
                <div class="group relative rounded-lg overflow-hidden border
                            {{ $image->is_cover ? 'border-slate-900 ring-1 ring-slate-900' : 'border-slate-200' }}">
                    <img src="{{ $image->thumb_url }}" alt="" class="aspect-[4/3] w-full object-cover">

                    @if ($image->is_cover)
                        <span class="absolute top-1 left-1 rounded bg-slate-900 px-1.5 py-0.5 text-[10px] font-medium text-white">
                            Portada
                        </span>
                    @endif

                    <div class="absolute inset-x-0 bottom-0 flex opacity-0 group-hover:opacity-100 transition-opacity">
                        @can('reorderImages', $property)
                            @unless ($image->is_cover)
                                <form method="POST" action="{{ route('properties.images.reorder', $property) }}" class="flex-1">
                                    @csrf
                                    @foreach ($property->images as $i)
                                        <input type="hidden" name="order[]" value="{{ $i->id }}">
                                    @endforeach
                                    <input type="hidden" name="cover_id" value="{{ $image->id }}">
                                    <button class="w-full bg-slate-900/80 px-2 py-1.5 text-xs text-white hover:bg-slate-900">
                                        Portada
                                    </button>
                                </form>
                            @endunless
                        @endcan

                        @can('deleteImages', $property)
                            <form method="POST" action="{{ route('properties.images.destroy', [$property, $image]) }}"
                                  class="flex-1" onsubmit="return confirm('¿Eliminar esta foto?')">
                                @csrf
                                @method('DELETE')
                                <button class="w-full bg-red-600/90 px-2 py-1.5 text-xs text-white hover:bg-red-700">
                                    Eliminar
                                </button>
                            </form>
                        @endcan
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</section>

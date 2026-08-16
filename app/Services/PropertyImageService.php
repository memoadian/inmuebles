<?php

namespace App\Services;

use App\Models\Property;
use App\Models\PropertyImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Laravel\Facades\Image;

class PropertyImageService
{
    private const MAX_EDGE = 1920;

    private const THUMB_WIDTH = 400;

    private const THUMB_HEIGHT = 300;

    private const QUALITY = 80;

    /**
     * Procesa y almacena una imagen. Se convierte a WebP antes de subirla:
     * pesa menos y homogeneiza el formato sin importar qué suba el usuario.
     */
    public function store(Property $property, UploadedFile $file): PropertyImage
    {
        $disk = config('filesystems.default');
        $uuid = (string) Str::uuid();
        $dir = "properties/{$property->id}";

        $path = "{$dir}/{$uuid}.webp";
        $thumbPath = "{$dir}/{$uuid}_thumb.webp";

        $image = Image::decodeSplFileInfo($file);
        $encoder = new WebpEncoder(quality: self::QUALITY);

        // scaleDown nunca agranda: una foto pequeña se sube tal cual.
        $full = (clone $image)->scaleDown(self::MAX_EDGE, self::MAX_EDGE);
        $thumb = (clone $image)->cover(self::THUMB_WIDTH, self::THUMB_HEIGHT);

        Storage::disk($disk)->put($path, (string) $full->encode($encoder), 'public');
        Storage::disk($disk)->put($thumbPath, (string) $thumb->encode($encoder), 'public');

        // La primera imagen de la propiedad queda como portada.
        $isFirst = ! $property->images()->exists();

        return $property->images()->create([
            'disk' => $disk,
            'path' => $path,
            'thumb_path' => $thumbPath,
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime' => 'image/webp',
            'width' => $full->width(),
            'height' => $full->height(),
            'order' => ($property->images()->max('order') ?? -1) + 1,
            'is_cover' => $isFirst,
        ]) ?: throw new \RuntimeException('No se pudo registrar la imagen.');
    }

    public function delete(PropertyImage $image): void
    {
        $disk = Storage::disk($image->disk);

        $disk->delete($image->path);

        if ($image->thumb_path) {
            $disk->delete($image->thumb_path);
        }

        $wasCover = $image->is_cover;
        $property = $image->property;

        $image->delete();

        // Si se borró la portada, promover la siguiente imagen.
        if ($wasCover) {
            $property->images()->orderBy('order')->first()?->update(['is_cover' => true]);
        }
    }

    /**
     * @param  array<int, int>  $orderedIds  ids en el orden deseado
     */
    public function reorder(Property $property, array $orderedIds, ?int $coverId = null): void
    {
        $images = $property->images()->get()->keyBy('id');

        foreach (array_values($orderedIds) as $position => $id) {
            $images->get($id)?->update(['order' => $position]);
        }

        if ($coverId && $images->has($coverId)) {
            $property->images()->update(['is_cover' => false]);
            $images->get($coverId)->update(['is_cover' => true]);
        }
    }
}

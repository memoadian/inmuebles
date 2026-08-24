<?php

namespace App\Services;

use App\Models\Property;
use App\Models\PropertyImage;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfWrapper;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Laravel\Facades\Image;

/**
 * Ficha técnica en PDF de una propiedad publicada.
 *
 * Las fotos se incrustan como data URI en vez de enlazarse: así el PDF no
 * depende de que dompdf pueda salir a internet ni de que R2 sea público, y
 * de paso se convierten de WebP —que dompdf no sabe dibujar— a JPEG.
 */
class PropertyPdfService
{
    private const MAX_IMAGES = 5;

    private const IMAGE_WIDTH = 1000;

    private const QUALITY = 75;

    public function build(Property $property): PdfWrapper
    {
        $property->loadMissing([
            'type', 'state', 'city', 'neighborhood', 'user',
            'images', 'features' => fn ($q) => $q->ordered(),
        ]);

        return Pdf::loadView('public.pdf', [
            'property' => $property,
            'images' => $this->images($property),
            'generatedAt' => now(),
        ])
            ->setPaper('letter')
            // Sin esto dompdf incrusta la fuente completa y la ficha pesa ~900 KB
            // aunque no lleve una sola foto.
            ->setOption('isFontSubsettingEnabled', true);
    }

    public function filename(Property $property): string
    {
        return "{$property->slug}.pdf";
    }

    /**
     * @return array<int, string> data URIs, la portada primero
     */
    private function images(Property $property): array
    {
        return $property->images
            ->sortByDesc('is_cover')
            ->take(self::MAX_IMAGES)
            ->map(fn (PropertyImage $image) => $this->dataUri($image))
            ->filter()
            ->values()
            ->all();
    }

    private function dataUri(PropertyImage $image): ?string
    {
        try {
            $contents = Storage::disk($image->disk)->get($image->path);

            if (! $contents) {
                return null;
            }

            $jpeg = (string) Image::decodeBinary($contents)
                ->scaleDown(self::IMAGE_WIDTH)
                ->encode(new JpegEncoder(self::QUALITY));

            return 'data:image/jpeg;base64,'.base64_encode($jpeg);
        } catch (\Throwable $e) {
            // Una foto ilegible no debe tumbar la ficha completa.
            Log::warning('pdf_image_failed', ['image_id' => $image->id, 'message' => $e->getMessage()]);

            return null;
        }
    }
}

@props(['property'])

@php
    // schema.org no tiene un tipo específico para "terreno" o "local comercial"
    // dentro de la jerarquía de Residence, así que solo mapeamos los tipos
    // residenciales conocidos y usamos "Place" como respaldo genérico.
    $residenceTypes = [
        'Casa' => 'SingleFamilyResidence',
        'Departamento' => 'Apartment',
    ];
    $aboutType = $residenceTypes[$property->type?->name] ?? 'Place';

    $availability = match ($property->status) {
        'published' => 'https://schema.org/InStock',
        'reserved' => 'https://schema.org/Reserved',
        'sold', 'rented' => 'https://schema.org/SoldOut',
        default => 'https://schema.org/InStock',
    };

    $address = array_filter([
        '@type' => 'PostalAddress',
        'streetAddress' => trim("{$property->street} {$property->ext_number}") ?: null,
        'addressLocality' => $property->city?->name,
        'addressRegion' => $property->state?->name,
        'postalCode' => $property->postal_code,
        'addressCountry' => 'MX',
    ]);

    $about = array_filter([
        '@type' => $aboutType,
        'name' => $property->title,
        'address' => count($address) > 1 ? $address : null,
        'numberOfRooms' => $property->bedrooms ?: null,
        'numberOfBathroomsTotal' => $property->bathrooms ?: null,
        'floorSize' => $property->built_area ? [
            '@type' => 'QuantitativeValue',
            'value' => (float) $property->built_area,
            'unitCode' => 'MTK',
        ] : null,
        'geo' => ($property->latitude && $property->longitude) ? [
            '@type' => 'GeoCoordinates',
            'latitude' => (float) $property->latitude,
            'longitude' => (float) $property->longitude,
        ] : null,
    ]);

    $jsonLd = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'RealEstateListing',
        'name' => $property->title,
        'description' => $property->description,
        'url' => route('public.properties.show', $property->slug),
        'datePosted' => $property->published_at?->toDateString(),
        'about' => $about,
        'image' => $property->images->pluck('url')->all() ?: null,
        'offers' => [
            '@type' => 'Offer',
            'price' => (float) $property->price,
            'priceCurrency' => $property->currency,
            'availability' => $availability,
            'businessFunction' => $property->operation === 'rent'
                ? 'https://schema.org/LeaseOut'
                : 'https://schema.org/Sell',
        ],
    ]);
@endphp

@push('json_ld')
    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush

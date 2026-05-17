<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $allImages = $this->discoverHeroImages();
        $carouselImages = array_slice($allImages, 0, 3);
        // Masonry : toutes les images (y compris les 3 du carrousel)
        $masonryImages = $allImages;
        $masonryFigureSequence = $this->buildMasonryFigureSequence($masonryImages);

        return view('home', [
            'carouselImages' => $carouselImages,
            'masonryImages' => $masonryImages,
            'masonryFigureSequence' => $masonryFigureSequence,
            'masonryDurationMs' => max(
                28_000,
                count($masonryFigureSequence) * 520,
            ),
            'heroVideo' => $this->resolveHeroVideoUrl(),
        ]);
    }

    private function resolveHeroVideoUrl(): ?string
    {
        $url = config('app.home_hero_video_url');

        return is_string($url) && trim($url) !== '' ? trim($url) : null;
    }

    /**
     * @return list<array{type: string, src: string}>
     */
    private function discoverHeroImages(): array
    {
        $dir = resource_path('img');

        if (! File::isDirectory($dir)) {
            return [];
        }

        return collect(File::files($dir))
            ->filter(fn (\SplFileInfo $file) => ! str_starts_with($file->getFilename(), '.'))
            ->filter(fn (\SplFileInfo $file) => ! str_starts_with($file->getFilename(), 'success-bg'))
            ->filter(function (\SplFileInfo $file) {
                $ext = strtolower($file->getExtension());

                return in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'], true);
            })
            ->sort(fn (\SplFileInfo $a, \SplFileInfo $b) => strnatcasecmp(
                $a->getFilename(),
                $b->getFilename(),
            ))
            ->map(fn (\SplFileInfo $file) => [
                'type' => 'image',
                'src' => asset('img/'.$file->getFilename()),
            ])
            ->values()
            ->all();
    }

    /**
     * Une seule grille Macy : suite plate de plusieurs « passages » du même pool,
     * ordre mélangé à chaque passage — évite la ligne droite entre bandes séparées.
     *
     * @param  list<array{type: string, src: string}>  $images
     * @return list<array{type: string, src: string}>
     */
    private function buildMasonryFigureSequence(array $images): array
    {
        if ($images === []) {
            return [];
        }

        $runCount = max(5, min(8, (int) ceil(72 / max(count($images), 1))));

        $sequence = [];
        for ($r = 0; $r < $runCount; $r++) {
            foreach (collect($images)->shuffle()->values()->all() as $img) {
                $sequence[] = $img;
            }
        }

        return $sequence;
    }
}

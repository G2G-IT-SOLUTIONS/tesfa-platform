<?php

namespace App\Services\AI;

use App\Models\ImageQualityAssessment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;

class ImageQualityService
{
    private const MIN_WIDTH = 1200;
    private const MIN_HEIGHT = 800;

    /**
     * Assess image quality
     */
    public function assess(int $propertyId, string $imagePath): ImageQualityAssessment
    {
        // Load image
        $fullPath = Storage::path($imagePath);
        $image = Image::make($fullPath);

        // Calculate scores
        $resolutionScore = $this->calculateResolutionScore($image);
        $blurScore = $this->calculateBlurScore($image);
        $lightingScore = $this->calculateLightingScore($image);
        $compositionScore = $this->calculateCompositionScore($image);

        // Overall score
        $overallScore = ($resolutionScore * 0.25) + ($blurScore * 0.30) + ($lightingScore * 0.25) + ($compositionScore * 0.20);

        // Get AWS Rekognition labels
        $rekognitionData = $this->getRekognitionLabels($imagePath);

        // Check for duplicates
        $perceptualHash = $this->calculatePerceptualHash($image);
        $duplicateOf = $this->findDuplicate($propertyId, $perceptualHash);

        // Determine status
        $status = match(true) {
            $overallScore >= 70 => 'approved',
            $overallScore >= 50 => 'needs_improvement',
            default => 'rejected',
        };

        // Generate suggestions
        $suggestions = $this->generateSuggestions($resolutionScore, $blurScore, $lightingScore, $compositionScore);

        return ImageQualityAssessment::create([
            'property_id' => $propertyId,
            'image_path' => $imagePath,
            'overall_score' => round($overallScore, 2),
            'resolution_score' => round($resolutionScore, 2),
            'blur_score' => round($blurScore, 2),
            'lighting_score' => round($lightingScore, 2),
            'composition_score' => round($compositionScore, 2),
            'rekognition_labels' => $rekognitionData['labels'],
            'detected_rooms' => $rekognitionData['rooms'],
            'detected_objects' => $rekognitionData['objects'],
            'has_faces' => $rekognitionData['has_faces'],
            'perceptual_hash' => $perceptualHash,
            'duplicate_of' => $duplicateOf,
            'status' => $status,
            'rejection_reasons' => $status === 'rejected' ? $this->getRejectionReasons($overallScore) : null,
            'improvement_suggestions' => $suggestions,
        ]);
    }

    /**
     * Calculate resolution score
     */
    private function calculateResolutionScore($image): float
    {
        $width = $image->width();
        $height = $image->height();

        if ($width >= self::MIN_WIDTH && $height >= self::MIN_HEIGHT) {
            return 100;
        }

        $widthScore = min(100, ($width / self::MIN_WIDTH) * 100);
        $heightScore = min(100, ($height / self::MIN_HEIGHT) * 100);

        return min($widthScore, $heightScore);
    }

    /**
     * Calculate blur score (using Laplacian variance approximation)
     */
    private function calculateBlurScore($image): float
    {
        // Convert to grayscale
        $image->greyscale();

        // Get pixel data
        $pixels = [];
        for ($x = 0; $x < $image->width(); $x += 10) {
            for ($y = 0; $y < $image->height(); $y += 10) {
                $pixels[] = $image->pickColor($x, $y, 'array')[0];
            }
        }

        if (count($pixels) < 2) {
            return 50;
        }

        // Calculate variance
        $mean = array_sum($pixels) / count($pixels);
        $variance = 0;
        foreach ($pixels as $pixel) {
            $variance += pow($pixel - $mean, 2);
        }
        $variance /= count($pixels);

        // Normalize to 0-100 (higher variance = sharper)
        $score = min(100, ($variance / 1000) * 100);

        return $score;
    }

    /**
     * Calculate lighting score
     */
    private function calculateLightingScore($image): float
    {
        $brightness = [];
        for ($x = 0; $x < $image->width(); $x += 20) {
            for ($y = 0; $y < $image->height(); $y += 20) {
                $pixel = $image->pickColor($x, $y, 'array');
                $brightness[] = ($pixel[0] + $pixel[1] + $pixel[2]) / 3;
            }
        }

        if (empty($brightness)) {
            return 50;
        }

        $avgBrightness = array_sum($brightness) / count($brightness);

        // Ideal brightness is around 128 (middle gray)
        $deviation = abs($avgBrightness - 128);
        $score = max(0, 100 - ($deviation / 128) * 100);

        return $score;
    }

    /**
     * Calculate composition score (rule of thirds approximation)
     */
    private function calculateCompositionScore($image): float
    {
        // Simplified: check if there's a clear focal point
        // For now, return a neutral score
        // In production, this would use more sophisticated analysis
        return 70;
    }

    /**
     * Get AWS Rekognition labels
     */
    private function getRekognitionLabels(string $imagePath): array
    {
        if (!config('services.aws.rekognition_enabled')) {
            return ['labels' => [], 'rooms' => [], 'objects' => [], 'has_faces' => false];
        }

        try {
            $imageData = Storage::get($imagePath);

            $response = Http::withHeaders([
                'X-Amz-Target' => 'RekognitionService.DetectLabels',
                'Content-Type' => 'application/x-amz-json-1.1',
            ])->withOptions([
                'aws' => [
                    'key' => config('services.aws.key'),
                    'secret' => config('services.aws.secret'),
                    'region' => config('services.aws.region'),
                ],
            ])->post('https://rekognition.' . config('services.aws.region') . '.amazonaws.com/', [
                'Image' => ['Bytes' => base64_encode($imageData)],
                'MaxLabels' => 20,
                'MinConfidence' => 70,
            ]);

            if (!$response->successful()) {
                return ['labels' => [], 'rooms' => [], 'objects' => [], 'has_faces' => false];
            }

            $labels = collect($response->json('Labels', []));

            // Categorize labels
            $rooms = $labels->filter(fn ($l) => in_array($l['Name'], [
                'Living Room', 'Bedroom', 'Kitchen', 'Bathroom', 'Dining Room',
                'Office', 'Garage', 'Balcony', 'Garden', 'Pool'
            ]))->pluck('Name')->toArray();

            $objects = $labels->filter(fn ($l) => in_array($l['Name'], [
                'Furniture', 'Bed', 'Sofa', 'Table', 'Chair', 'Television',
                'Refrigerator', 'Stove', 'Oven', 'Sink', 'Toilet', 'Shower'
            ]))->pluck('Name')->toArray();

            $hasFaces = $labels->contains(fn ($l) => $l['Name'] === 'Person');

            return [
                'labels' => $labels->pluck('Name')->toArray(),
                'rooms' => $rooms,
                'objects' => $objects,
                'has_faces' => $hasFaces,
            ];
        } catch (\Exception $e) {
            return ['labels' => [], 'rooms' => [], 'objects' => [], 'has_faces' => false];
        }
    }

    /**
     * Calculate perceptual hash for duplicate detection
     */
    private function calculatePerceptualHash($image): string
    {
        // Resize to 8x8
        $resized = clone $image;
        $resized->resize(8, 8);

        // Convert to grayscale and get pixel values
        $pixels = [];
        for ($x = 0; $x < 8; $x++) {
            for ($y = 0; $y < 8; $y++) {
                $pixel = $resized->pickColor($x, $y, 'array');
                $pixels[] = ($pixel[0] + $pixel[1] + $pixel[2]) / 3;
            }
        }

        // Calculate average
        $avg = array_sum($pixels) / count($pixels);

        // Generate binary hash
        $hash = '';
        foreach ($pixels as $pixel) {
            $hash .= $pixel > $avg ? '1' : '0';
        }

        return $hash;
    }

    /**
     * Find duplicate image
     */
    private function findDuplicate(int $propertyId, string $hash): ?int
    {
        $existing = ImageQualityAssessment::where('property_id', '!=', $propertyId)
            ->whereNotNull('perceptual_hash')
            ->get();

        foreach ($existing as $assessment) {
            $distance = $this->hammingDistance($hash, $assessment->perceptual_hash);
            if ($distance <= 5) { // Similar enough
                return $assessment->property_id;
            }
        }

        return null;
    }

    /**
     * Calculate Hamming distance between two hashes
     */
    private function hammingDistance(string $hash1, string $hash2): int
    {
        $distance = 0;
        $length = min(strlen($hash1), strlen($hash2));

        for ($i = 0; $i < $length; $i++) {
            if ($hash1[$i] !== $hash2[$i]) {
                $distance++;
            }
        }

        return $distance;
    }

    /**
     * Generate improvement suggestions
     */
    private function generateSuggestions(float $resolution, float $blur, float $lighting, float $composition): array
    {
        $suggestions = [];

        if ($resolution < 70) {
            $suggestions[] = 'Use a higher resolution camera (minimum 1200x800)';
        }

        if ($blur < 60) {
            $suggestions[] = 'Hold the camera steady or use a tripod to reduce blur';
        }

        if ($lighting < 60) {
            $suggestions[] = 'Improve lighting - open curtains or take photos during daylight';
        }

        if ($composition < 60) {
            $suggestions[] = 'Frame the room better - include key features and use landscape orientation';
        }

        if (empty($suggestions)) {
            $suggestions[] = 'Photo quality is good!';
        }

        return $suggestions;
    }

    /**
     * Get rejection reasons
     */
    private function getRejectionReasons(float $score): array
    {
        return [
            'overall_score' => $score,
            'minimum_required' => 50,
            'reason' => 'Image quality below minimum threshold',
        ];
    }
}
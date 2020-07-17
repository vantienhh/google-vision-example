<?php

namespace App\Http\Controllers;

use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use Illuminate\Http\Request;

class AnnotationController extends Controller
{
    /**
     * annotate Image
     *
     * @param Request $request
     */
    public function annotateImage(Request $request)
    {
        try {
            $imageAnnotator = new ImageAnnotatorClient();

            # annotate the image
            $image    = file_get_contents($request->file('image'));
            $response = $imageAnnotator->textDetection($image);
            $texts    = $response->getTextAnnotations();

            printf('%d texts found:' . PHP_EOL, count($texts));
            foreach ($texts as $text) {
                print($text->getDescription() . PHP_EOL);
                # get bounds
                $vertices = $text->getBoundingPoly()->getVertices();
                $bounds   = [];
                foreach ($vertices as $vertex) {
                    $bounds[] = sprintf('(%d,%d)', $vertex->getX(), $vertex->getY());
                }
                print('Bounds: ' . join(', ', $bounds) . PHP_EOL);
            }

            $imageAnnotator->close();
        } catch (\Exception $e) {
            echo json_encode(["code" => $e->getCode(), "errors" => $e->getMessage()]);
        }
    }
}

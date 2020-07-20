<?php

namespace App\Http\Controllers;

use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use Illuminate\Http\Request;

class AnnotationController extends Controller
{
    const TEXT_NEO     = 'Subscribe to Trade Alert';    // text neo để lấy tên sản phẩm
    const CURRENCY_VND = 'đ';
    const CURRENCY_USD = '$';

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
            $image      = file_get_contents($request->file('image'));
            $response   = $imageAnnotator->documentTextDetection($image);
            $annotation = $response->getFullTextAnnotation();

            # print out detailed and structured information about document text
            if ($annotation) {
                foreach ($annotation->getPages() as $page) {
                    $result = array();
                    foreach ($page->getBlocks() as $block) {
                        $block_text = '';
                        foreach ($block->getParagraphs() as $paragraph) {
                            foreach ($paragraph->getWords() as $word) {
                                foreach ($word->getSymbols() as $symbol) {
                                    $block_text .= $symbol->getText();
                                }
                                $block_text .= ' ';
                            }
//                            $block_text .= "\n";
                        }

                        # get bounds
                        $vertices = $block->getBoundingBox()->getVertices();
                        $bounds   = [];
                        foreach ($vertices as $vertex) {
                            $bounds[] = [
                                'vertexX' => $vertex->getX(),
                                'vertexY' => $vertex->getY(),
                            ];
                        }
                        array_push($result, [trim($block_text), $bounds]);
                    }
                    $this->getNameAndPrices($result, $page->getWidth(), $page->getHeight());
                }
            } else {
                print('No text found' . PHP_EOL);
            }

            $imageAnnotator->close();
            dd($result);
        } catch (\Exception $e) {
            echo json_encode(["code" => $e->getCode(), "errors" => $e->getMessage()]);
        }
    }

    public function getNameAndPrices(array $textAndVertices, int $widthPage, int $heightPage)
    {
        $blockName   = null;
        $blocksPrice = array();

        foreach ($textAndVertices as $index => $textAndVertice) {
            if ($textAndVertice[0] === self::TEXT_NEO || strpos($textAndVertice[0], self::TEXT_NEO) !== false) {
                $blockName = $textAndVertices[$index + 1];
            }
            if ($blockName &&
                (strpos($textAndVertice[0], self::CURRENCY_USD) !== false || strpos($textAndVertice[0], self::CURRENCY_VND) !== false)) { // check có tiền tệ trong string
                // Thêm check vertexY của các price khác +- 10 pixel vs price thứ nhất thì lấy
                array_push($blocksPrice, $textAndVertice);
            }
        }

        dd($blockName, $blocksPrice);
    }
}

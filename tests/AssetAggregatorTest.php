<?php

namespace BrizyMergeTests;

use BrizyMerge\AssetAggregator;
use BrizyMerge\Assets\AssetGroup;
use BrizyMerge\Assets\Asset;
use BrizyMerge\Assets\AssetFont;
use BrizyMerge\Assets\AssetLib;
use PHPUnit\Framework\TestCase;

class AssetAggregatorTest extends TestCase
{
    public function testGetAssetList()
    {
        $page = json_decode(file_get_contents(__DIR__ . '/data/page.json'), true);
        $page2 = json_decode(file_get_contents(__DIR__ . '/data/page2.json'), true);

        $assets = [];
        $assets[] = AssetGroup::instanceFromJsonData($page['blocks']['freeStyles']);
        $assets[] = AssetGroup::instanceFromJsonData($page['blocks']['proStyles']);
        $assets[] = AssetGroup::instanceFromJsonData($page2['blocks']['freeStyles']);

        $aggregator = new AssetAggregator($assets);

        $list = $aggregator->getAssetList();

        $this->assertCount(8,$list,'Assert that it returns 8 assets.');

        $score = 0;
        foreach ($list as $i => $item) {
            if ($i == 0) {
                $score = $item->getScore();
                continue;
            }

            $this->assertGreaterThanOrEqual($score, $item->getScore(), 'The items are not sorted ascending');

            $score = $item->getScore();
        }
    }



    public function test_instanceFromJsonHugeData()
    {
        $this->markTestSkipped('This test is with big client data');

        $compiledData = file_get_contents(__DIR__ . '/data/A.json');

        $t = microtime(true);

        $compiledData = new CompiledData($compiledData);

       // $assetAggregator1 = new AssetAggregator($compiledData->getScriptsAssetGroup());
        //$assetAggregator1->getAssetList();
        echo "Execution time: " . (microtime(true) - $t) . " seconds;\n";

        $t = microtime(true);
        $assetAggregator2 = new AssetAggregator($compiledData->getStylesAssetGroup());
        $assetAggregator2->getAssetList();

        echo "Execution time: " . (microtime(true) - $t) . " seconds;";

        $t = 0;

    }

    public function testGetAssetList_fontWithUnknownContentType()
    {
        $font = new AssetFont('font', 1, '', 'https://fonts.googleapis.com/css?family=Roboto:400&display=swap', '', [], false, AssetAggregator::FONT_TYPE_GOOGLE);

        $list = (new AssetAggregator([new AssetGroup($this->main(), [], [], [], [$font])]))->getAssetList();

        $this->assertCount(2, $list);
    }

    public function testGetAssetList_fontFamilyWithoutWeights()
    {
        $font = new AssetFont('font', 1, '', 'https://fonts.googleapis.com/css?family=Roboto|Lato:400&display=swap', Asset::TYPE_FILE, [], false, AssetAggregator::FONT_TYPE_GOOGLE);

        $list = (new AssetAggregator([new AssetGroup($this->main(), [], [], [], [$font])]))->getAssetList();

        $this->assertSame('https://fonts.googleapis.com/css?family=Roboto:|Lato:400&display=swap', $list[1]->getUrl());
    }

    public function testGetAssetList_codeFontWithNullUrl()
    {
        $font = new AssetFont('font', 1, '<link href="https://fonts.googleapis.com/css?family=Roboto:400">', null, Asset::TYPE_CODE, [], false, AssetAggregator::FONT_TYPE_GOOGLE);

        $list = (new AssetAggregator([new AssetGroup($this->main(), [], [], [], [$font])]))->getAssetList();

        $this->assertCount(2, $list);
    }

    public function testGetAssetList_libWithNullSelectors()
    {
        $lib = new AssetLib('lib', 5, '', 'lib.js', Asset::TYPE_FILE, [], false, null);

        $list = (new AssetAggregator([new AssetGroup($this->main(), [], ['lib' => $lib], ['.a'])]))->getAssetList();

        $this->assertCount(1, $list);
    }

    public function testGetAssetList_freeLibWithoutFreeGroup()
    {
        $lib = new AssetLib('lib', 5, '', 'lib.js', Asset::TYPE_FILE, [], false, ['.a']);

        $list = (new AssetAggregator([new AssetGroup($this->main(true), [], ['lib' => $lib], ['.a'])]))->getAssetList();

        $this->assertSame(['main', 'lib'], array_map(function ($a) { return $a->getName(); }, $list));
    }

    public function testGetAssetList_equalScoresKeepAggregationOrder()
    {
        $generic = [];
        $expected = ['main'];
        for ($i = 0; $i < 40; $i++) {
            $generic["g$i"] = new Asset("g$i", 10, '', "g$i.js", Asset::TYPE_FILE);
            $expected[] = "g$i";
        }

        $list = (new AssetAggregator([new AssetGroup($this->main(), $generic)]))->getAssetList();

        $this->assertSame($expected, array_map(function ($a) { return $a->getName(); }, $list));
    }

    public function testGetAssetList_groupWithNullVersion()
    {
        $old = new AssetGroup($this->main(), [], ['old' => new AssetLib('old', 5, '', 'old.js', Asset::TYPE_FILE, [], false, ['.a'])], [], [], [], null);
        $new = new AssetGroup($this->main(), [], ['new' => new AssetLib('new', 5, '', 'new.js', Asset::TYPE_FILE, [], false, ['.a'])], ['.a'], [], [], '1.2');

        $list = (new AssetAggregator([$old, $new]))->getAssetList();

        $this->assertSame(['main', 'new'], array_map(function ($a) { return $a->getName(); }, $list));
    }

    private function main($pro = false)
    {
        return new Asset('main', 0, '', 'main.js', Asset::TYPE_FILE, [], $pro);
    }
}

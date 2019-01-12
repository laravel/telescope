<?php

namespace Laravel\Telescope\Tests\Telescope;

use Laravel\Telescope\FormatModel;
use Laravel\Telescope\Storage\EntryModel;
use Laravel\Telescope\Tests\FeatureTestCase;

class ExtractTagTest extends FeatureTestCase
{
    /**
     * @test
     */
    public function test_extract_tag_from_array_containing_flat_collection()
    {
        $this->loadFactoriesUsing($this->app, __DIR__.'/../../src/Storage/factories');

        $flat_collection = factory(EntryModel::class, 1)->create();

        $tag = FormatModel::given($flat_collection->first());
        $extracted_tag = ExtractTags::fromArray([$flat_collection]);

        $this->assertSame($tag, $extracted_tag);
    }

    /**
     * @test
     */
    public function test_extract_tag_from array_containing_deep_collection()
    {
        $this->loadFactoriesUsing($this->app, __DIR__.'/../../src/Storage/factories');

        $deep_collection = factory(EntryModel::class, 1)->create()->groupBy('type');

        $tag = FormatModel::given($flat_collection->first()->first());
        $extracted_tag = ExtractTags::fromArray([$deep_collection]);

        $this->assertSame($tag, $extracted_tag);
    }
}

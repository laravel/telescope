<?php

namespace Laravel\Telescope\Tests\Telescope;

use Illuminate\Mail\Mailable;
use Laravel\Telescope\ExtractTags;
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

        $this->assertSame($tag, $extracted_tag[0]);
    }

    /**
     * @test
     */
    public function test_extract_tag_from_array_containing_deep_collection()
    {
        $this->loadFactoriesUsing($this->app, __DIR__.'/../../src/Storage/factories');
        $deep_collection = factory(EntryModel::class, 1)->create()->groupBy('type');

        $tag = FormatModel::given($deep_collection->first()->first());
        $extracted_tag = ExtractTags::fromArray([$deep_collection]);

        $this->assertSame($tag, $extracted_tag[0]);
    }

    /**
     * @test
     */
    public function test_extract_tag_from_mailable()
    {
        $this->loadFactoriesUsing($this->app, __DIR__.'/../../src/Storage/factories');
        $deep_collection = factory(EntryModel::class, 1)->create()->groupBy('type');
        $mailable = new DummyMailableWithData($deep_collection);

        $tag = FormatModel::given($deep_collection->first()->first());
        $extracted_tag = ExtractTags::from($mailable);

        $this->assertSame($tag, $extracted_tag[0]);
    }
}

class DummyMailableWithData extends Mailable
{
    private $mail_data;

    public function __construct($mail_data)
    {
        $this->mail_data = $mail_data;
    }

    public function build()
    {
        return $this->from('from@laravel.com')
            ->to('to@laravel.com')
            ->view(['raw' => 'simple text content'])
            ->with([
                'mail_data' => $this->mail_data,
            ]);
    }
}

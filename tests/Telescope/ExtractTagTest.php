<?php

namespace Laravel\Telescope\Tests\Telescope;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Mail\Mailable;
use Laravel\Telescope\Database\Factories\EntryModelFactory;
use Laravel\Telescope\ExtractTags;
use Laravel\Telescope\FormatModel;
use Laravel\Telescope\Tests\FeatureTestCase;

class ExtractTagTest extends FeatureTestCase
{
    public function test_extract_tag_from_array_containing_flat_collection()
    {
        $flat_collection = EntryModelFactory::new()->create();

        $tag = FormatModel::given($flat_collection->first());
        $extracted_tag = ExtractTags::fromArray([$flat_collection]);

        $this->assertSame($tag, $extracted_tag[0]);
    }

    public function test_extract_tag_from_array_containing_deep_collection()
    {
        $deep_collection = EntryModelFactory::times(1)->create()->groupBy('type');

        $tag = FormatModel::given($deep_collection->first()->first());
        $extracted_tag = ExtractTags::fromArray([$deep_collection]);

        $this->assertSame($tag, $extracted_tag[0]);
    }

    public function test_extract_tag_from_mailable()
    {
        $deep_collection = EntryModelFactory::times(1)->create()->groupBy('type');
        $mailable = new DummyMailableWithData($deep_collection);

        $tag = FormatModel::given($deep_collection->first()->first());
        $extracted_tag = ExtractTags::from($mailable);

        $this->assertSame($tag, $extracted_tag[0]);
    }

    public function test_extract_tags_for_queued_listener_with_event_aware_tags_method()
    {
        $job = new CallQueuedListener(
            ListenerWithEventAwareTags::class,
            'handle',
            [new DummyTaggableEvent]
        );

        $this->assertSame(['from-event'], ExtractTags::fromJob($job));
    }

    public function test_extract_tags_for_queued_listener_with_no_argument_tags_method()
    {
        $job = new CallQueuedListener(
            ListenerWithNoArgumentTags::class,
            'handle',
            [new DummyTaggableEvent]
        );

        $this->assertSame(['static-tag'], ExtractTags::fromJob($job));
    }
}

class DummyTaggableEvent
{
    public $tag = 'from-event';
}

class ListenerWithEventAwareTags implements ShouldQueue
{
    public function handle(DummyTaggableEvent $event)
    {
        //
    }

    public function tags(DummyTaggableEvent $event)
    {
        return [$event->tag];
    }
}

class ListenerWithNoArgumentTags implements ShouldQueue
{
    public function handle(DummyTaggableEvent $event)
    {
        //
    }

    public function tags()
    {
        return ['static-tag'];
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

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

    public function test_extract_tag_from_queued_listener_with_event_aware_tags_method()
    {
        $job = new CallQueuedListener(
            EventAwareTaggedListener::class,
            'handle',
            [new EventAwareTaggableEvent]
        );

        $this->assertSame(['event-aware-tag'], ExtractTags::fromJob($job));
    }

    public function test_extract_tag_from_queued_listener_with_zero_argument_tags_method()
    {
        $job = new CallQueuedListener(
            ZeroArgumentTaggedListener::class,
            'handle',
            [new EventAwareTaggableEvent]
        );

        $this->assertSame(['zero-argument-tag'], ExtractTags::fromJob($job));
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

class EventAwareTaggableEvent
{
    public string $tag = 'event-aware-tag';
}

class EventAwareTaggedListener implements ShouldQueue
{
    public function handle(EventAwareTaggableEvent $event)
    {
        //
    }

    public function tags(EventAwareTaggableEvent $event)
    {
        return [$event->tag];
    }
}

class ZeroArgumentTaggedListener implements ShouldQueue
{
    public function handle(EventAwareTaggableEvent $event)
    {
        //
    }

    public function tags()
    {
        return ['zero-argument-tag'];
    }
}

<?php

namespace Tests\Unit;

use App\Services\AnthropicMessagesStreamParser;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class AnthropicMessagesStreamParserTest extends TestCase
{
    public function test_it_collects_text_deltas_from_server_sent_events(): void
    {
        $parser = new AnthropicMessagesStreamParser();

        $parser->push("event: message_start\n");
        $parser->push("data: {\"type\":\"message_start\",\"message\":{\"id\":\"msg_123\"}}\n\n");
        $deltaEvent = "event: content_block_delta\n" . 'data: ' . json_encode([
            'type' => 'content_block_delta',
            'index' => 0,
            'delta' => ['type' => 'text_delta', 'text' => '{"cover":"letter"}'],
        ]) . "\n\n";
        $parser->push(substr($deltaEvent, 0, 40));
        $parser->push(substr($deltaEvent, 40));
        $parser->push("event: ping\ndata: {\"type\":\"ping\"}\n\n");
        $parser->push("event: message_stop\ndata: {\"type\":\"message_stop\"}\n\n");

        $result = $parser->finish();

        $this->assertSame('{"cover":"letter"}', $result['text']);
        $this->assertSame('msg_123', $result['provider_response_id']);
        $this->assertSame(1, $result['text_delta_count']);
        $this->assertSame(4, $result['event_count']);
    }

    public function test_it_throws_on_stream_error_events(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Overloaded');

        (new AnthropicMessagesStreamParser())->parse(
            "event: error\n" .
            "data: {\"type\":\"error\",\"error\":{\"type\":\"overloaded_error\",\"message\":\"Overloaded\"}}\n\n"
        );
    }
}

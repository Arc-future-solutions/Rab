<?php

namespace App\Services;

use RuntimeException;

class AnthropicMessagesStreamParser
{
    private string $buffer = '';

    private string $text = '';

    private int $eventCount = 0;

    private int $textDeltaCount = 0;

    private ?string $messageId = null;

    public function push(string $chunk): void
    {
        if ($chunk === '') {
            return;
        }

        $this->buffer .= str_replace("\r\n", "\n", $chunk);

        while (($position = strpos($this->buffer, "\n\n")) !== false) {
            $event = substr($this->buffer, 0, $position);
            $this->buffer = substr($this->buffer, $position + 2);

            $this->parseEvent($event);
        }
    }

    public function finish(): array
    {
        if (trim($this->buffer) !== '') {
            $this->parseEvent($this->buffer);
            $this->buffer = '';
        }

        return [
            'text' => trim($this->text),
            'event_count' => $this->eventCount,
            'text_delta_count' => $this->textDeltaCount,
            'provider_response_id' => $this->messageId,
        ];
    }

    public function parse(string $stream): array
    {
        $this->push($stream);

        return $this->finish();
    }

    private function parseEvent(string $event): void
    {
        $eventName = null;
        $dataLines = [];

        foreach (explode("\n", $event) as $line) {
            if ($line === '' || str_starts_with($line, ':')) {
                continue;
            }

            if (str_starts_with($line, 'event:')) {
                $eventName = trim(substr($line, strlen('event:')));
                continue;
            }

            if (str_starts_with($line, 'data:')) {
                $dataLines[] = ltrim(substr($line, strlen('data:')));
            }
        }

        if ($dataLines === []) {
            return;
        }

        $this->eventCount++;
        $data = implode("\n", $dataLines);
        $decoded = json_decode($data, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Anthropic stream returned a non-JSON event.');
        }

        $type = $decoded['type'] ?? $eventName;

        if ($type === 'error') {
            $message = $decoded['error']['message'] ?? 'Anthropic stream returned an error event.';
            throw new RuntimeException($message);
        }

        if ($type === 'message_start' && isset($decoded['message']['id']) && is_string($decoded['message']['id'])) {
            $this->messageId = $decoded['message']['id'];
        }

        if ($type !== 'content_block_delta') {
            return;
        }

        $delta = $decoded['delta'] ?? [];
        if (($delta['type'] ?? null) !== 'text_delta' || ! is_string($delta['text'] ?? null)) {
            return;
        }

        $this->text .= $delta['text'];
        $this->textDeltaCount++;
    }
}

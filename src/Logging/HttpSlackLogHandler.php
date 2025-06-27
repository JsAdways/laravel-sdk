<?php

namespace Jsadways\LaravelSDK\Logging;

use Jsadways\DataApi\Core\Parameter\Notification\Dtos\SlackBlocks\Elements\SlackTextElementDto;
use Jsadways\DataApi\Core\Parameter\Notification\Dtos\SlackBlocks\SlackContextDto;
use Jsadways\DataApi\Core\Parameter\Notification\Dtos\SlackBlocksPayloadDto;
use Jsadways\DataApi\Core\Parameter\Notification\Enums\Platform;
use Jsadways\DataApi\Core\Parameter\Notification\Enums\Slack\TextType;
use Jsadways\DataApi\Core\Services\Cross\Dtos\CrossNotificationDto;
use Jsadways\DataApi\Facades\CrossFacade;
use Js\Authenticator\Facades\UserFacade;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Monolog\LogRecord;
use Throwable;

class HttpSlackLogHandler extends AbstractProcessingHandler
{
    /**
     * @param int $level The minimum logging level at which this handler will be triggered (e.g., 'error' or Logger::ERROR).
     * @param bool $bubble Whether the messages that are handled should bubble up the stack or not.
     */
    public function __construct($level = Logger::ERROR,bool $bubble = true)
    {
        parent::__construct(Logger::toMonologLevel($level), $bubble);
    }

    /**
     * Writes the record down to the log of the implementing handler.
     *
     * @param LogRecord $record
     * @return void
     */
    protected function write(LogRecord $record): void
    {
        try {
            $payload = [
                'system' => 'n8n',
                'token' => UserFacade::get_token(),
                'platform' => Platform::Slack,
                'payload' => (new SlackBlocksPayloadDto(
                    blocks: [
                        new SlackContextDto(
                            elements: [
                                new SlackTextElementDto(
                                    type: TextType::plain_text,
                                    text: $record['message']
                                )
                            ]
                        )
                    ]
                ))->get()
            ];
            CrossFacade::fetch(new CrossNotificationDto(...$payload));
        } catch (Throwable $e) {
            // 為了避免循環日誌，這裡不應使用 Log::error()
            // 寫入一個單獨的檔案來記錄 HTTP 發送失敗的情況
            file_put_contents(
                storage_path('logs/http_log_send_failure.log'),
                '[' . now()->toDateTimeString() . '] Failed to send log via HTTP. Original message: "' . $record['message'] . '". Error: ' . $e->getMessage() . PHP_EOL,
                FILE_APPEND
            );
        }
    }
}

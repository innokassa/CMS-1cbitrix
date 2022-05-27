<?php

namespace Innokassa\Fiscal;

use Innokassa\Fiscal\Impl\ClientFactory;

/**
 * Агенты (cron задачи)
 *
 * @link https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&LESSON_ID=3436
 */
class AgentFiscal
{
    /**
     * Обрабокта незавершенных чеков
     *
     * @return void
     */
    public static function pipeline()
    {
        $mdk = ClientFactory::build();
        $pipeline = $mdk->servicePipeline();

        $fileUpdate = $_SERVER['DOCUMENT_ROOT'] . '/.innokassa.pipeline';
        $pipeline->update($fileUpdate);

        $fileMonitoring = $_SERVER['DOCUMENT_ROOT'] . '/innokassa.monitoring';
        $pipeline->monitoring($fileMonitoring, 'start_time');

        return sprintf('%s::pipeline();', AgentFiscal::class);
    }
}

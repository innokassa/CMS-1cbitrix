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
        $pipeline->update();

        return sprintf('%s::pipeline();', AgentFiscal::class);
    }
}

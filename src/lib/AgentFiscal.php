<?php

namespace Innokassa\Fiscal;

use Innokassa\Fiscal\Impl\ClientFactory;

class AgentFiscal
{
    public static function pipeline()
    {
        $mdk = ClientFactory::build();
        $pipeline = $mdk->servicePipeline();
        $pipeline->updateUnaccepted();
        $pipeline->updateAccepted();

        return sprintf('%s::pipeline();', AgentFiscal::class);
    }
}

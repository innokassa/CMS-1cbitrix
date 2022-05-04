<?php

// обработка чеков в очереди
function AgentFiscal()
{
    $mdk = ClientFactory::build();
    $pipeline = $mdk->servicePipeline();
    $pipeline->updateUnaccepted();
    $pipeline->updateAccepted();

    return "AgentFiscal();";
}

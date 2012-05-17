<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use OldSound\RabbitMqBundle\RabbitMq\BaseConsumer;
use PhpAmqpLib\Message\AMQPMessage;

class Consumer extends BaseConsumer
{
    public function consume($msgAmount)
    {
        $this->target = $msgAmount;

        $this->setUpConsumer();

        while (count($this->ch->callbacks))
        {
            $this->ch->wait();
        }
    }

    public function processMessage(AMQPMessage $msg)
    {
        try
        {
            if (false === call_user_func($this->callback, $msg))
            {
                // Pause 1 second. No need load CPU for requeue
                sleep(1);
                // Reject and requeue message to RabbitMQ
                $msg->delivery_info['channel']->basic_reject($msg->delivery_info['delivery_tag'], true);
            }
            else
            {
                // Remove message from queue only if callback return not false
                $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
            }

            $this->consumed++;
            $this->maybeStopConsumer();
        }
        catch (\Exception $e)
        {
            throw $e;
        }
    }

}
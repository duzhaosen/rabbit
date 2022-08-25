<?php

class MessageTest extends RabbitTestCase
{


    public function testMessageSerialization()
    {
        $message = new \AMQPEnvelope();

        $result = $this->getProperties($message);
        echo json_encode($result);
    }

    /**
     * @param $message
     * @return array
     */
    private function getProperties($message)
    {
        $result = [];

        $reflectionObject     = new ReflectionObject($message);
        $reflectionProperties = $reflectionObject->getProperties();
        foreach ($reflectionProperties as $property) {
            $property->setAccessible(true);
            $result[$property->getName()] = $property->getValue($message);
        }
        return $result;
    }
}

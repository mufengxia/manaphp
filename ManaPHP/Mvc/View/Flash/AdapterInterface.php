<?php
namespace ManaPHP\Mvc\View\Flash;

interface AdapterInterface
{
    /**
     * @param string $type
     * @param string $message
     *
     * @return mixed
     */
    public function _message($type, $message);

    /**
     * Prints the messages in the session flasher
     *
     * @param $remove bool
     *
     * @return void
     */
    public function _output($remove = true);
}